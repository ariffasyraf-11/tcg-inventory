<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sign in · TCG Inventory</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 text-slate-900">
<main class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-7 shadow-sm">
    <h1 class="text-2xl font-bold">Sign in to TCG Inventory</h1>
    <p class="mt-2 text-sm text-slate-600">Your private singles and sealed-product catalog.</p>
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-lg bg-red-100 p-3 text-sm text-red-900">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('login.attempt') }}" method="POST" class="mt-6 space-y-4">
        @csrf
        <label class="block text-sm font-semibold">Email
            <input name="email" type="email" autocomplete="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
        </label>
        <label class="block text-sm font-semibold">Password
            <input name="password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
        </label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Sign in</button>
    </form>
    <p class="mt-5 text-sm">New seller? <a href="{{ route('register') }}" class="font-semibold text-indigo-700 underline">Create an account</a></p>
</main>
</body>
</html>
