<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CalibrationNotesTranslationService
{
    private const SOURCE_LANGUAGE = 'id';
    private const TARGET_LANGUAGE = 'en';
    private const MYMEMORY_MAX_BYTES = 450;

    /**
     * Prepare the two note fields used by the certificate view.
     *
     * Some legacy records stored Indonesian and English together in the source
     * column ("Indonesia./English"). Split those records without calling an API.
     */
    public function prepareWorksheetForPrint(string $detailNorec, object $worksheet): void
    {
        $originalText = isset($worksheet->noteslembarkerja)
            ? (string) $worksheet->noteslembarkerja
            : '';

        if (trim($originalText) === '') {
            return;
        }

        [$sourceText, $embeddedTranslation] = $this->splitLegacyBilingualNotes($originalText);
        $worksheet->noteslembarkerja = $sourceText;

        if ($embeddedTranslation !== null) {
            $worksheet->noteslembarkerja_en = $embeddedTranslation;
            $this->persistDetailTranslation(
                $detailNorec,
                $originalText,
                $sourceText,
                $embeddedTranslation
            );

            return;
        }

        $worksheet->noteslembarkerja_en = $this->translateForDetail($detailNorec, $sourceText);
    }

    /**
     * Translate calibration notes once and reuse the database result afterwards.
     *
     * If the provider is unavailable, return null so Indonesian text is never
     * accidentally printed as though it were the English translation.
     */
    public function translate(?string $sourceText): ?string
    {
        if ($sourceText === null || trim($sourceText) === '') {
            return null;
        }

        $sourceText = $this->normalizeSourceText($sourceText);
        $hash = $this->sourceHash($sourceText);

        $cached = $this->findCached($hash);
        if ($this->isUsableTranslation($cached, $sourceText)) {
            return $cached;
        }

        $provider = strtolower((string) config('services.translation.provider', 'libretranslate'));

        try {
            return Cache::lock('calibration-notes-translation:' . $hash, 30)
                ->block(12, function () use ($hash, $provider, $sourceText) {
                    $cached = $this->findCached($hash);
                    if ($this->isUsableTranslation($cached, $sourceText)) {
                        return $cached;
                    }

                    try {
                        $translated = $this->translateRemotely($sourceText, $provider);
                        if (!$this->isUsableTranslation($translated, $sourceText)) {
                            throw new RuntimeException('Translation provider returned the source text unchanged.');
                        }

                        DB::table('translation_cache_t')->insertOrIgnore([
                            'source_hash' => $hash,
                            'source_language' => self::SOURCE_LANGUAGE,
                            'target_language' => self::TARGET_LANGUAGE,
                            'source_text' => $sourceText,
                            'translated_text' => $translated,
                            'provider' => $provider,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        return $this->findCached($hash) ?? $translated;
                    } catch (Throwable $exception) {
                        Log::warning('Calibration notes translation failed; English will be retried on the next print.', [
                            'provider' => $provider,
                            'source_hash' => $hash,
                            'exception' => $exception->getMessage(),
                        ]);

                        return null;
                    }
                });
        } catch (Throwable $exception) {
            Log::warning('Calibration notes translation cache lock failed; English will be retried on the next print.', [
                'provider' => $provider,
                'source_hash' => $hash,
                'exception' => $exception->getMessage(),
            ]);

            $cached = $this->findCached($hash);

            return $this->isUsableTranslation($cached, $sourceText) ? $cached : null;
        }
    }

    /**
     * Resolve English notes for one registration detail and persist the result
     * on that row. Old records are therefore backfilled on their first print.
     */
    public function translateForDetail(string $detailNorec, ?string $sourceText): ?string
    {
        if ($sourceText === null || trim($sourceText) === '') {
            return null;
        }

        $sourceText = $this->normalizeSourceText($sourceText);
        $hash = $this->sourceHash($sourceText);
        $detail = DB::table('mitraregistrasidetail_t')
            ->where('norec', $detailNorec)
            ->select('noteslembarkerja', 'noteslembarkerja_en', 'noteslembarkerja_en_source_hash')
            ->first();

        if ($detail
            && hash_equals((string) $detail->noteslembarkerja_en_source_hash, $hash)
            && $this->isUsableTranslation($detail->noteslembarkerja_en, $sourceText)
        ) {
            return $detail->noteslembarkerja_en;
        }

        $translated = $this->translate($sourceText);
        if (!$this->isUsableTranslation($translated, $sourceText)) {
            return null;
        }

        if ($detail) {
            $this->persistDetailTranslation(
                $detailNorec,
                $detail->noteslembarkerja,
                $sourceText,
                $translated
            );
        }

        return $translated;
    }

    private function persistDetailTranslation(
        string $detailNorec,
        string $storedSourceText,
        string $sourceText,
        string $translated
    ): void {
        DB::table('mitraregistrasidetail_t')
            ->where('norec', $detailNorec)
            ->where('noteslembarkerja', $storedSourceText)
            ->update([
                'noteslembarkerja_en' => $translated,
                'noteslembarkerja_en_source_hash' => $this->sourceHash(
                    $this->normalizeSourceText($sourceText)
                ),
            ]);
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitLegacyBilingualNotes(string $text): array
    {
        $lines = preg_split('/\R/u', $this->normalizeSourceText($text)) ?: [];
        $pairs = [];
        $hasEmbeddedTranslation = false;

        foreach ($lines as $line) {
            $parts = preg_split('/\s*\/\s*(?=[A-Z][A-Za-z])/u', trim($line), 2);
            if (is_array($parts) && count($parts) === 2) {
                $pairs[] = [trim($parts[0]), trim($parts[1])];
                $hasEmbeddedTranslation = true;
            } else {
                $pairs[] = [trim($line), null];
            }
        }

        if (!$hasEmbeddedTranslation) {
            return [$this->normalizeSourceText($text), null];
        }

        $sourceLines = [];
        $translatedLines = [];

        foreach ($pairs as [$sourceLine, $translatedLine]) {
            if ($sourceLine === '') {
                continue;
            }

            $sourceLines[] = $sourceLine;

            if ($translatedLine === null || $translatedLine === '') {
                $translatedLine = $this->translate($sourceLine);
            }

            if (is_string($translatedLine) && trim($translatedLine) !== '') {
                $translatedLines[] = trim($translatedLine);
            }
        }

        return [
            implode("\n", $sourceLines),
            $translatedLines === [] ? null : implode("\n", $translatedLines),
        ];
    }

    private function findCached(string $hash): ?string
    {
        $translated = DB::table('translation_cache_t')
            ->where('source_hash', $hash)
            ->value('translated_text');

        return is_string($translated) && trim($translated) !== '' ? $translated : null;
    }

    private function sourceHash(string $sourceText): string
    {
        return hash(
            'sha256',
            self::SOURCE_LANGUAGE . "\0" . self::TARGET_LANGUAGE . "\0" . $sourceText
        );
    }

    private function normalizeSourceText(string $sourceText): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $sourceText));
    }

    private function isUsableTranslation($translated, string $sourceText): bool
    {
        if (!is_string($translated) || trim($translated) === '') {
            return false;
        }

        $normalizeForComparison = static function (string $text): string {
            return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($text)), 'UTF-8');
        };

        return $normalizeForComparison($translated) !== $normalizeForComparison($sourceText);
    }

    private function translateRemotely(string $sourceText, string $provider): string
    {
        if ($provider === 'libretranslate') {
            return $this->translateWithLibreTranslate($sourceText);
        }

        $translatedChunks = [];

        foreach ($this->splitByByteLength($sourceText, self::MYMEMORY_MAX_BYTES) as $chunk) {
            if ($provider === 'deepl') {
                $translatedChunks[] = $this->translateWithDeepL($chunk);
            } elseif ($provider === 'mymemory') {
                $translatedChunks[] = $this->translateWithMyMemory($chunk);
            } else {
                throw new RuntimeException("Unsupported translation provider: {$provider}");
            }
        }

        $translated = trim(implode(' ', $translatedChunks));
        if ($translated === '') {
            throw new RuntimeException('Translation provider returned an empty result.');
        }

        return $translated;
    }

    private function translateWithLibreTranslate(string $text): string
    {
        $response = Http::acceptJson()
            ->timeout((int) config('services.translation.timeout', 30))
            ->post((string) config('services.translation.libretranslate.url'), [
                'q' => $text,
                'source' => self::SOURCE_LANGUAGE,
                'target' => self::TARGET_LANGUAGE,
                'format' => 'text',
            ]);

        $response->throw();

        $translated = $response->json('translatedText');
        if (!is_string($translated) || trim($translated) === '') {
            throw new RuntimeException('LibreTranslate returned an empty translation.');
        }

        return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function translateWithMyMemory(string $text): string
    {
        $query = [
            'q' => $text,
            'langpair' => self::SOURCE_LANGUAGE . '|' . self::TARGET_LANGUAGE,
            'mt' => 1,
        ];

        $email = trim((string) config('services.translation.mymemory.email'));
        if ($email !== '') {
            $query['de'] = $email;
        }

        $response = Http::acceptJson()
            ->timeout((int) config('services.translation.timeout', 10))
            ->get((string) config('services.translation.mymemory.url'), $query);

        $response->throw();

        if ((int) $response->json('responseStatus') !== 200) {
            throw new RuntimeException(
                'MyMemory rejected the translation: ' . (string) $response->json('responseDetails')
            );
        }

        $translated = $response->json('responseData.translatedText');
        if (!is_string($translated) || trim($translated) === '') {
            throw new RuntimeException('MyMemory returned an empty translation.');
        }

        return html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function translateWithDeepL(string $text): string
    {
        $apiKey = trim((string) config('services.translation.deepl.key'));
        if ($apiKey === '') {
            throw new RuntimeException('DEEPL_API_KEY is not configured.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->withHeaders(['Authorization' => 'DeepL-Auth ' . $apiKey])
            ->timeout((int) config('services.translation.timeout', 10))
            ->post((string) config('services.translation.deepl.url'), [
                'text' => $text,
                'source_lang' => strtoupper(self::SOURCE_LANGUAGE),
                'target_lang' => strtoupper(self::TARGET_LANGUAGE),
            ]);

        $response->throw();

        $translated = $response->json('translations.0.text');
        if (!is_string($translated) || trim($translated) === '') {
            throw new RuntimeException('DeepL returned an empty translation.');
        }

        return $translated;
    }

    /**
     * MyMemory accepts at most 500 bytes for q. Keep some headroom and split on
     * whitespace so UTF-8 characters and words are not cut in the middle.
     *
     * @return array<int, string>
     */
    private function splitByByteLength(string $text, int $limit): array
    {
        if (strlen($text) <= $limit) {
            return [$text];
        }

        $tokens = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $current = '';

        foreach ($tokens ?: [$text] as $token) {
            if (strlen($current . $token) <= $limit) {
                $current .= $token;
                continue;
            }

            if (trim($current) !== '') {
                $chunks[] = trim($current);
                $current = '';
            }

            while (strlen($token) > $limit) {
                $chunks[] = trim(mb_strcut($token, 0, $limit, 'UTF-8'));
                $token = mb_strcut($token, $limit, null, 'UTF-8');
            }

            $current = ltrim($token);
        }

        if (trim($current) !== '') {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks, static function ($chunk) {
            return $chunk !== '';
        }));
    }
}
