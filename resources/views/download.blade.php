<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download - {{ $file->display_name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat flex items-center justify-center bg-wbg">
    <div class="bg-white pt-30 px-30 pb-20 min-w-[500px] flex flex-col">
        <a href="{{ route('download.file', $file->token) }}"
           class="hover:opacity-70 transition-opacity">
            {{ $file->display_name }}
        </a>

        <footer class="mt-48 text-crimson uppercase">
            <p>WBG AG – Visuelle Kommunikation</p>
            <p>Binzstrasse 39, CH-8045 Zürich, +41 44 269 43 43, mail@wbg.ch</p>
        </footer>
    </div>
</body>
</html>
