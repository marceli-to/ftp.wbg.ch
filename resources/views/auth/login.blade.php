<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - {{ config('app.name') }}</title>
@vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-cover bg-center bg-no-repeat flex items-center justify-center bg-wbg text-sm">
    <div class="bg-white pt-30 px-30 pb-20 min-w-[400px] flex flex-col">
        <h1 class="mb-48 font-medium">Login</h1>

        <form method="POST" action="{{ route('login') }}" class="w-full space-y-16">
            @csrf

            <div>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="E-Mail"
                    required
                    autofocus
                    class="w-full px-12 py-8 border border-black focus:outline-none focus:border-crimson @error('email') border-crimson @enderror"
                />
                @error('email')
                    <p class="mt-4 text-crimson">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input
                    type="password"
                    name="password"
                    placeholder="Passwort"
                    required
                    class="w-full px-12 py-8 border border-black focus:outline-none focus:border-crimson"
                />
            </div>

            <button
              type="submit"
              class="w-full py-8 bg-crimson cursor-pointer text-white font-medium hover:opacity-80 transition-opacity">
              Anmelden
            </button>
        </form>

        <footer class="mt-48 text-crimson uppercase">
            <p>WBG AG – Visuelle Kommunikation</p>
            <p>Binzstrasse 39, CH-8045 Zürich, +41 44 269 43 43, mail@wbg.ch</p>
        </footer>
    </div>
</body>
</html>
