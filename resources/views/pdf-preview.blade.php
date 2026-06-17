<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fileName }}</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        .toolbar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            background: #111827;
            border-bottom: 1px solid #1f2937;
        }
        .toolbar button {
            background: #0f766e;
            color: #fff;
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        .toolbar button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .toolbar .meta {
            margin-left: auto;
            font-size: 12px;
            color: #94a3b8;
        }
        .viewer {
            display: flex;
            justify-content: center;
            padding: 16px;
            background: #0f172a;
        }
        canvas {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button id="prev">Prev</button>
        <button id="next">Next</button>
        <span id="page-info">Page 1 of 1</span>
        <div class="meta">{{ $fileName }}</div>
    </div>
    <div class="viewer">
        <canvas id="pdf-canvas"></canvas>
    </div>
    <script>
        window.__PDF_PREVIEW__ = {
            fileUrl: @json($fileUrl),
        };
    </script>
    @vite('resources/js/pdf-preview.js')
</body>
</html>
