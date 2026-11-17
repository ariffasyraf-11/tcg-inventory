<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Register · TCG Inventory</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 text-slate-900">
<main class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-7 shadow-sm">
    <h1 class="text-2xl font-bold">Create your inventory account</h1>
    <p class="mt-2 text-sm text-slate-600">For individual TCG sellers and small businesses.</p>
    @if ($errors->any())
        <div role="alert" class="mt-4 rounded-lg bg-red-100 p-3 text-sm text-red-900"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form action="{{ route('register.store') }}" method="POST" class="mt-6 space-y-4">
        @csrf
        <label class="block text-sm font-semibold">Name
            <input name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 p-2">
        </label>
        <label class="block text-sm font-semibold">Email
            <input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
        </label>
        <label class="block text-sm font-semibold">Password
            <input name="password" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
        </label>
        <label class="block text-sm font-semibold">Confirm password
            <input name="password_confirmation" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-lg border border-slate-300 p-2">
        </label>
        <button class="w-full rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Create account</button>
    </form>
    <p class="mt-5 text-sm">Already registered? <a href="{{ route('login') }}" class="font-semibold text-indigo-700 underline">Sign in</a></p>
</main>
</body>
</html>
