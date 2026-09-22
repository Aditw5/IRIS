<!DOCTYPE html>
<html>

<head>
    <title>Lihat Dokumen Mutu</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            background: #f3f4f6;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            display: flex;
            flex-direction: column;
            height: 100%;
            width: 100%;
        }

        .preview {
            flex: 1;
            border: none;
            width: 100%;
            background: #fff;
        }

        .image-preview-wrapper {
            flex: 1;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
            background: #f3f4f6;
            padding: 16px;
            box-sizing: border-box;
        }

        .image-preview-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .download-bar {
            padding: 10px;
            background: #f7f7f7;
            text-align: center;
            border-top: 1px solid #ddd;
        }

        .download-bar a {
            text-decoration: none;
            font-size: 14px;
            color: #007bff;
            margin: 0 8px;
        }

        .excel-download-bar {
            padding: 16px;
            background: #ecfdf5;
            border-top: 2px solid #10b981;
            text-align: center;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.08);
            z-index: 10;
        }

        .excel-download-title {
            font-size: 15px;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 10px;
        }

        .excel-download-info {
            font-size: 13px;
            color: #047857;
            margin-bottom: 12px;
        }

        .excel-download-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .excel-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 220px;
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 15px !important;
            font-weight: 700;
            text-decoration: none !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
            transition: all 0.2s ease;
        }

        .excel-btn-download {
            background: #16a34a;
            color: #ffffff !important;
        }

        .excel-btn-open {
            background: #ffffff;
            color: #047857 !important;
            border: 1px solid #10b981;
        }

        .excel-btn:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .file-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 14px !important;
            font-weight: 600;
            text-decoration: none !important;
            background: #ffffff;
            color: #2563eb !important;
            border: 1px solid #bfdbfe;
        }

        .unsupported {
            text-align: center;
            margin-top: 2em;
            color: #444;
            font-size: 15px;
        }

        @media (max-width: 600px) {
            .excel-download-bar {
                padding: 14px 10px;
            }

            .excel-download-actions {
                flex-direction: column;
                gap: 8px;
            }

            .excel-btn {
                width: 100%;
                min-width: unset;
                box-sizing: border-box;
                font-size: 14px !important;
                padding: 12px 14px;
            }
        }
    </style>
</head>

<body>
    @php
        $extension = strtolower($extension);

        $excel = in_array($extension, ['xls', 'xlsx']);
        $word = in_array($extension, ['doc', 'docx']);
        $pdf = $extension === 'pdf';
        $image = in_array($extension, ['png', 'jpg', 'jpeg']);
    @endphp

    <div class="container">
        @if ($pdf)
            <iframe class="preview" src="{{ $filepath }}"></iframe>

            <div class="download-bar">
                <a href="{{ $filepath }}" target="_blank" class="file-action-btn">
                    🔍 Buka file
                </a>
                <a href="{{ $filepath }}" download class="file-action-btn">
                    ⬇️ Download file PDF
                </a>
            </div>

        @elseif ($word)
            <iframe class="preview" src="https://docs.google.com/gview?url={{ urlencode($filepath) }}&embedded=true">
            </iframe>

            <div class="download-bar">
                <a href="{{ $filepath }}" target="_blank" class="file-action-btn">
                    🔍 Buka file
                </a>
                <a href="{{ $filepath }}" download class="file-action-btn">
                    ⬇️ Download file Word
                </a>
            </div>

        @elseif ($excel)
            <iframe class="preview" src="{{ route('mutu.preview.excel', ['id' => $data->id]) }}">
            </iframe>

            <div class="excel-download-bar">
                <div class="excel-download-title">
                    File Excel tersedia untuk diunduh
                </div>

                <div class="excel-download-info">
                    Jika tampilan preview Excel tidak lengkap atau kosong, silakan gunakan tombol download di bawah ini.
                </div>

                <div class="excel-download-actions">
                    <a href="{{ $filepath }}" target="_blank" class="excel-btn excel-btn-open">
                        🔍 Buka Excel
                    </a>

                    <a href="{{ $filepath }}" download class="excel-btn excel-btn-download">
                        ⬇️ Download File Excel
                    </a>
                </div>
            </div>

        @elseif ($image)
            <div class="image-preview-wrapper">
                <img src="{{ $filepath }}" alt="Preview Dokumen Gambar">
            </div>

            <div class="download-bar">
                <a href="{{ $filepath }}" target="_blank" class="file-action-btn">
                    🔍 Buka gambar
                </a>
                <a href="{{ $filepath }}" download class="file-action-btn">
                    ⬇️ Download gambar
                </a>
            </div>

        @else
            <p class="unsupported">
                Format file tidak dikenali atau tidak bisa dipreview.
            </p>

            <div class="download-bar">
                <a href="{{ $filepath }}" target="_blank" class="file-action-btn">
                    🔍 Coba buka file
                </a>
                <a href="{{ $filepath }}" download class="file-action-btn">
                    ⬇️ Download file
                </a>
            </div>
        @endif
    </div>
</body>

</html>