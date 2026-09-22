import re
from typing import List, Dict, Optional


STOPWORDS = {
    "yang", "dan", "atau", "untuk", "pada", "dengan", "bagaimana", "cara",
    "fungsi", "apa", "agar", "bisa", "saya", "ke", "di", "dari", "ini", "itu",
    "user", "manual", "website", "ulab", "umro", "modul", "menu", "halaman",
    "gimana", "dimana", "mana", "dong", "min", "kak", "tolong"
}


BAD_HEADINGS = {
    "pertanyaan yang harus bisa dijawab chatbot",
    "jawaban singkat untuk chatbot",
    "kata kunci penting untuk chatbot",
    "tujuan",
}


ACTION_WORDS = {
    "klik", "buka", "pilih", "isi", "upload", "simpan", "masuk",
    "cari", "verifikasi", "setujui", "tolak", "ajukan", "cetak",
    "lanjut", "lengkapi", "scan", "unduh", "download"
}


META_LINE_PREFIXES = (
    "pertanyaan yang dijawab",
    "kapan digunakan",
    "kata kunci",
    "kata kunci penting",
    "gunakan menu ini",
    "gunakan fitur ini",
    "manual ini menjelaskan",
)


ANSWER_PREFIXES = (
    "jawaban singkat untuk chatbot",
    "jawaban chatbot",
)


def normalize_text(text: str) -> str:
    return " ".join(text.lower().strip().split())


def clean_heading(heading: str) -> str:
    heading = " ".join(heading.strip().split())
    heading = re.sub(r"^\d+\.\s*", "", heading).strip()
    return heading


def detect_topic_from_filename(file_name: str) -> str:
    lowered = normalize_text(file_name)

    if "customer" in lowered:
        return "customer"
    if "pelaksana" in lowered:
        return "pelaksana"
    if "manager" in lowered:
        return "manager"
    if "registrasi" in lowered:
        return "registrasi"
    if "penyelia" in lowered:
        return "penyelia"
    if "pengajupbj" in lowered:
        return "pengajupbj"
    if "asman" in lowered:
        return "asman"

    return lowered


def filter_chunks_by_kelompok_user(chunks: List[Dict], kelompok_user: Optional[str]) -> List[Dict]:
    if not kelompok_user:
        return chunks

    target = normalize_text(kelompok_user)
    filtered = []

    for chunk in chunks:
        topic = detect_topic_from_filename(chunk.get("file_name", ""))
        if topic == target:
            filtered.append(chunk)

    return filtered


def extract_query_terms(query: str) -> List[str]:
    normalized = normalize_text(query)
    words = []

    for word in re.findall(r"[a-zA-Z0-9\-]+", normalized):
        if len(word) >= 3 and word not in STOPWORDS:
            words.append(word)

    return words


def split_lines(text: str) -> List[str]:
    """
    Memecah content menjadi baris.
    Diutamakan pecah berdasarkan newline agar langkah bernomor tidak rusak.
    """
    raw_lines = []

    for line in text.splitlines():
        cleaned = " ".join(line.strip().split())
        if cleaned:
            raw_lines.append(cleaned)

    if raw_lines:
        return raw_lines

    raw_parts = re.split(r"(?<=[\.\:\;])\s+", text.strip())
    lines = []

    for part in raw_parts:
        cleaned = " ".join(part.split())
        if cleaned:
            lines.append(cleaned)

    return lines


def strip_prefix_content(line: str) -> str:
    """
    Mengambil isi setelah tanda titik dua.
    Contoh:
    Jawaban singkat untuk chatbot: Buka menu Aktivitas.
    menjadi:
    Buka menu Aktivitas.
    """
    if ":" in line:
        return line.split(":", 1)[1].strip()
    return line.strip()


def is_meta_line(line: str) -> bool:
    normalized = normalize_text(line)

    for prefix in META_LINE_PREFIXES:
        if normalized.startswith(prefix):
            return True

    return False


def is_answer_line(line: str) -> bool:
    normalized = normalize_text(line)

    for prefix in ANSWER_PREFIXES:
        if normalized.startswith(prefix):
            return True

    return False


def is_step_line(line: str) -> bool:
    normalized = normalize_text(line)

    if re.match(r"^\d+\.\s+", line.strip()):
        return True

    if any(word in normalized for word in ACTION_WORDS):
        return True

    return False


def remove_old_number(line: str) -> str:
    return re.sub(r"^\d+\.\s*", "", line.strip()).strip()


def score_text_lexical(query: str, text: str) -> float:
    normalized_query = normalize_text(query)
    normalized_text = normalize_text(text)
    query_terms = extract_query_terms(query)

    score = 0.0

    for term in query_terms:
        if term in normalized_text:
            score += 2.5

    if normalized_query in normalized_text:
        score += 10.0

    for size in [3, 2]:
        if len(query_terms) >= size:
            for i in range(len(query_terms) - size + 1):
                phrase = " ".join(query_terms[i:i + size])
                if phrase in normalized_text:
                    score += 5.0 if size == 3 else 3.0

    return score


def heading_penalty(heading: str) -> float:
    normalized = normalize_text(heading)

    for bad in BAD_HEADINGS:
        if bad in normalized:
            return -20.0

    return 0.0


def content_penalty(content: str) -> float:
    normalized = normalize_text(content[:400])

    penalty = 0.0

    if "website resmi u-lab umro" in normalized:
        penalty -= 6.0
    if "melalui situs ini" in normalized:
        penalty -= 4.0
    if "sistem requirement" in normalized or "system requirement" in normalized:
        penalty -= 4.0

    return penalty


def score_chunk_lexical(query: str, chunk: Dict) -> float:
    heading = chunk.get("heading", "")
    content = chunk.get("content", "")

    score = 0.0
    score += score_text_lexical(query, heading) * 2.0
    score += score_text_lexical(query, content)
    score += heading_penalty(heading)
    score += content_penalty(content)

    return score


def build_numbered_steps(step_lines: List[str], max_steps: int = 8) -> str:
    cleaned_steps = []
    used = set()

    for line in step_lines:
        line = remove_old_number(line)

        if not line:
            continue

        key = normalize_text(line)
        if key in used:
            continue

        used.add(key)
        cleaned_steps.append(line)

        if len(cleaned_steps) >= max_steps:
            break

    if not cleaned_steps:
        return ""

    parts = []
    for index, line in enumerate(cleaned_steps, start=1):
        parts.append(f"{index}. {line}")

    return " ".join(parts)


def build_guided_answer_from_chunk(query: str, chunk: Dict, max_lines: int = 8) -> str:
    """
    Membuat jawaban final untuk mode manual.
    Meta seperti 'Pertanyaan yang dijawab', 'Kapan digunakan',
    dan 'Jawaban singkat untuk chatbot' tidak ditampilkan sebagai label.
    """

    heading = clean_heading(chunk.get("heading", ""))
    content = chunk.get("content", "")
    lines = split_lines(content)

    if not lines:
        return f"{heading}: {content}".strip()

    answer_line = ""
    step_lines = []
    result_lines = []
    fallback_lines = []

    for line in lines:
        normalized = normalize_text(line)

        if is_answer_line(line):
            extracted = strip_prefix_content(line)
            if extracted:
                answer_line = extracted
            continue

        if is_meta_line(line):
            continue

        if normalized.startswith("langkah"):
            continue

        if normalized.startswith("hasil"):
            result_text = strip_prefix_content(line)
            if result_text:
                result_lines.append(result_text)
            continue

        if is_step_line(line):
            step_lines.append(line)
            continue

        fallback_lines.append(line)

    # Prioritas 1: jika ada langkah, jawab dengan langkah praktis
    if step_lines:
        steps = build_numbered_steps(step_lines, max_steps=max_lines)
        if steps:
            result_text = ""

            if result_lines:
                result_text = " Hasil: " + " ".join(result_lines[:2])

            if heading:
                return f"{heading}: {steps}{result_text}".strip()

            return f"{steps}{result_text}".strip()

    # Prioritas 2: jika ada jawaban singkat, tampilkan isinya saja tanpa label
    if answer_line:
        if heading:
            return f"{heading}: {answer_line}".strip()
        return answer_line.strip()

    # Prioritas 3: pilih kalimat paling relevan, tapi tetap buang meta
    scored_lines = []

    for idx, line in enumerate(fallback_lines):
        normalized_line = normalize_text(line)
        score = score_text_lexical(query, line)

        if any(word in normalized_line for word in ACTION_WORDS):
            score += 2.0

        if score > 0:
            scored_lines.append({
                "index": idx,
                "line": line,
                "score": score,
            })

    if scored_lines:
        scored_lines.sort(key=lambda x: x["score"], reverse=True)
        chosen_indexes = sorted({item["index"] for item in scored_lines[:max_lines]})

        selected_lines = []
        for idx in chosen_indexes:
            selected_lines.append(fallback_lines[idx])

        answer = " ".join(selected_lines).strip()

        if len(answer) > 1000:
            answer = answer[:1000].rstrip() + "..."

        if heading:
            return f"{heading}: {answer}".strip()

        return answer

    # Prioritas 4: fallback akhir
    clean_lines = []
    for line in lines:
        if is_meta_line(line):
            continue
        if is_answer_line(line):
            extracted = strip_prefix_content(line)
            if extracted:
                clean_lines.append(extracted)
            continue
        clean_lines.append(line)

    short = " ".join(clean_lines).strip()

    if len(short) > 700:
        short = short[:700].rstrip() + "..."

    if heading:
        return f"{heading}: {short}".strip()

    return short