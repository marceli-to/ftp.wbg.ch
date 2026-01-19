<!DOCTYPE html>
<html lang="de" class="overflow-auto">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Download - {{ $file->original_name }} – wbg.ch</title>
@vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat flex items-center justify-center bg-wbg text-sm">
    <div class="bg-white pt-30 px-30 pb-20 min-w-[500px] min-h-300 flex flex-col justify-between">
        <h1>Download</h1>
        <a href="{{ route('download.file', $file->token) }}"
           class="hover:opacity-70 transition-opacity text-md font-light">
            {{ $file->original_name }}
        </a>
        <footer class="text-crimson uppercase">
            <p>WBG AG – Visuelle Kommunikation</p>
            <p>Binzstrasse 39, CH-8045 Zürich, +41 44 269 43 43, mail@wbg.ch</p>
        </footer>
    </div>
</body>
</html>
