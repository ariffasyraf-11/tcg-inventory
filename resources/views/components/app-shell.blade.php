@props(['title' => 'Workspace'])

<div class="min-h-screen bg-slate-50 text-slate-900 lg:flex">
    <aside class="border-b border-slate-800 bg-slate-950 text-slate-100 lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:shrink-0 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-5 py-5">
            <a href="{{ route('dashboard.index') }}" class="text-lg font-bold tracking-tight text-white">TCG Inventory</a>
            <span class="rounded bg-indigo-500/20 px-2 py-1 text-xs text-indigo-200">Seller</span>
        </div>
        <nav aria-label="Workspace navigation" class="flex gap-1 overflow-x-auto px-3 pb-4 text-sm lg:block lg:space-y-1 lg:overflow-visible">
            @foreach ([
                ['dashboard.index', 'Dashboard', 'dashboard.*'],
                ['catalog.index', 'Catalog', 'catalog.*'],
                ['inventory.index', 'Inventory', 'inventory.*'],
                ['sales.index', 'Sales', 'sales.*'],
                ['imports.index', 'CSV preview', 'imports.*'],
                ['demo.persistent', 'Private demo', 'demo.persistent*'],
                ['workspaces.switch.index', 'Switch workspace', 'workspaces.switch.*'],
                ['workspace-invitations.inbox', 'My invitations', 'workspace-invitations.inbox*'],
                ['workspace-invitations.index', 'Invite staff', 'workspace-invitations.index'],
                ['settings.index', 'Settings', 'settings.*'],
            ] as [$route, $label, $pattern])
                <a href="{{ route($route) }}" @if(request()->routeIs($pattern)) aria-current="page" @endif
                    class="block shrink-0 rounded-lg px-3 py-2.5 font-medium transition hover:bg-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-300 {{ request()->routeIs($pattern) ? 'bg-indigo-600 text-white' : 'text-slate-300' }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="border-t border-slate-800 px-5 py-4 lg:absolute lg:bottom-0 lg:w-64">
            <p class="truncate text-sm font-medium">{{ auth()->user()->name }}</p>
            <p class="truncate text-xs text-slate-400">{{ auth()->user()->email }}</p>
            <form action="{{ route('logout') }}" method="POST" class="mt-3">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-slate-700 px-3 py-2 text-left text-sm font-medium hover:bg-slate-800">Log out</button>
            </form>
        </div>
    </aside>
    <div class="min-w-0 flex-1">
        <div class="border-b border-slate-200 bg-white px-5 py-4 sm:px-8">
            <p class="text-xs font-semibold uppercase tracking-widest text-indigo-700">Seller workspace</p>
            <h1 class="text-xl font-bold">{{ $title }}</h1>
        </div>
        <main id="main-content" class="mx-auto w-full max-w-7xl space-y-6 px-5 py-8 sm:px-8">
            {{ $slot }}
        </main>
    </div>
</div>