<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $file->namaasli }}</title>
    <style>
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            background: #f3f4f6;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
        }

        .viewer {
            display: flex;
            width: 100%;
            height: 100%;
            flex-direction: column;
        }

        .preview {
            width: 100%;
            flex: 1;
            border: 0;
            background: #fff;
        }

        .image-preview {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 18px;
            overflow: auto;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            background: #fff;
            box-shadow: 0 3px 14px rgba(0, 0, 0, .12);
        }

        .unsupported {
            display: flex;
            flex: 1;
            align-items: center;
            justify-content: center;
            padding: 24px;
            text-align: center;
        }

        .action-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 16px;
            border-top: 1px solid #d1d5db;
            background: #fff;
        }

        .filename {
            min-width: 0;
            overflow: hidden;
            font-size: 14px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .actions {
            display: flex;
            flex: 0 0 auto;
            gap: 8px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border: 1px solid #2b6f9f;
            border-radius: 7px;
            color: #2b6f9f;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
        }

        .button.primary {
            background: #2b6f9f;
            color: #fff;
        }

        @media (max-width: 680px) {
            .action-bar {
                align-items: stretch;
                flex-direction: column;
            }

            .actions,
            .button {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>

<body>
    @php
        $isPdf = $extension === 'pdf';
        $isImage = in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp']);
        $isOffice = in_array($extension, ['doc', 'docx', 'xls', 'xlsx']);
    @endphp

    <div class="viewer">
        @if ($isPdf)
            <iframe class="preview" src="{{ $filepath }}"></iframe>
        @elseif ($isImage)
            <div class="image-preview"><img src="{{ $filepath }}" alt="{{ $file->namaasli }}"></div>
        @elseif ($isOffice)
            <iframe class="preview"
                src="https://docs.google.com/gview?url={{ urlencode($filepath) }}&embedded=true"></iframe>
        @else
            <div class="unsupported">Format file ini belum dapat dipratinjau langsung. Silakan unduh untuk membukanya.</div>
        @endif

        <div class="action-bar">
            <div class="filename">{{ $file->namaasli }}</div>
            <div class="actions">
                <a class="button" href="{{ $filepath }}" target="_blank">Buka File</a>
                <a class="button primary" href="{{ $filepath }}" download="{{ $file->namaasli }}">Download</a>
            </div>
        </div>
    </div>
</body>

</html>
