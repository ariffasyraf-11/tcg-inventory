<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>My invitations · TCG Inventory</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="bg-slate-50"><x-app-shell title="My invitations">
<h1 class="mb-3 text-2xl font-bold">My workspace invitations</h1>
<p class="mb-5 text-sm text-slate-600">Invitations sent to {{ auth()->user()->email }}. You can accept directly here without a separate invitation link. Accepted workspace access will become available when workspace switching is enabled.</p>
@if(session('status'))<p role="status" class="mb-4 rounded bg-emerald-50 p-3">{{ session('status') }}</p>@endif
<ul class="space-y-3">@forelse($invitations as $invitation)
<li class="rounded-xl border bg-white p-4"><div class="font-semibold">{{ $invitation->workspace->name }}</div><p class="text-sm text-slate-600">Role: {{ str_replace('_', ' ', $invitation->role) }} · @if($invitation->accepted_at)Accepted@elseif($invitation->revoked_at)Declined or revoked@elseif($invitation->expires_at->isPast())Expired@else Pending · expires {{ $invitation->expires_at->format('Y-m-d') }}@endif</p>
@if(! $invitation->accepted_at && ! $invitation->revoked_at && $invitation->expires_at->isFuture())
<div class="mt-3 flex gap-3"><form method="POST" action="{{ route('workspace-invitations.inbox.accept', $invitation) }}">@csrf<button class="rounded bg-indigo-700 px-3 py-2 text-sm text-white">Accept</button></form><form method="POST" action="{{ route('workspace-invitations.inbox.decline', $invitation) }}">@csrf<button class="rounded border px-3 py-2 text-sm">Decline</button></form></div>@endif</li>
@empty<li class="rounded border bg-white p-4 text-slate-600">No invitations addressed to your account yet.</li>@endforelse</ul>
<div class="mt-5">{{ $invitations->links() }}</div>
</x-app-shell></body></html>