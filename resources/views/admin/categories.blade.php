<x-app-layout :activeNav="'categories'">

    <div style="max-width:1380px;margin:0 auto;padding:30px 26px 60px">
        <a href="{{ route('admin.events.index') }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to admin console</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Categories</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Events are grouped into categories shown across the platform.</p>

        @if (session('success'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(22,163,74,.12);border:1px solid rgba(22,163,74,.3);color:var(--ok);font-size:13.5px;font-weight:700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:700">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom:20px;padding:13px 16px;border-radius:12px;background:rgba(220,38,38,.1);border:1px solid rgba(220,38,38,.3);color:var(--err);font-size:13.5px;font-weight:600">
                <strong>Please fix the following:</strong>
                <ul style="margin:6px 0 0;padding-left:18px">
                    @foreach ($errors->all() as $error)
                        <li style="margin-bottom:2px">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Add category --}}
        <form method="POST" action="{{ route('admin.categories.store') }}" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:22px;margin-bottom:20px">
            @csrf
            <div style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap">
                <label style="display:flex;flex-direction:column;gap:7px;flex:1;min-width:220px"><span style="font-size:12.5px;font-weight:700">New category name</span><input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Wellness" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                <label style="display:flex;flex-direction:column;gap:7px;flex:1.4;min-width:260px"><span style="font-size:12.5px;font-weight:700">Description <span style="color:var(--muted);font-weight:600">(optional)</span></span><input type="text" name="description" value="{{ old('description') }}" placeholder="Short description" style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none"></label>
                <button type="submit" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:14px;padding:13px 22px;border-radius:12px;min-height:48px">Add category</button>
            </div>
        </form>

        {{-- Categories list --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:20px">
            <div style="display:grid;grid-template-columns:1.4fr .8fr 1fr .6fr 1.5fr;gap:12px;padding:0 4px 11px;border-bottom:1px solid var(--border);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.7px;color:var(--muted)">
                <span>Category</span><span>Slug</span><span>Description</span><span>Events</span><span style="text-align:right">Actions</span>
            </div>
            @forelse($categories as $category)
                <div style="display:grid;grid-template-columns:1.4fr .8fr 1fr .6fr 1.5fr;gap:12px;padding:13px 4px;border-bottom:1px solid var(--border);align-items:center;font-size:13.5px">
                    <div style="min-width:0">
                        <div style="font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $category->name }}</div>
                        <div style="font-size:11.5px;color:var(--muted);font-weight:600">{{ $category->slug }}</div>
                    </div>
                    <span style="font-size:12px;color:var(--muted);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $category->description ?? 'â€”' }}</span>
                    <span><span style="padding:4px 10px;border-radius:8px;font-size:11px;font-weight:800;background:var(--chip);color:var(--primary)">{{ $category->events_count }} events</span></span>
                    <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;flex-wrap:wrap">
                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" style="display:flex;gap:6px;align-items:center">
                            @csrf
                            @method('PATCH')
                            <input type="text" name="name" value="{{ $category->name }}" aria-label="Rename {{ $category->name }}" required style="min-height:36px;padding:8px 11px;border:1px solid var(--border);background:var(--surface2);border-radius:9px;font-size:12.5px;outline:none;width:130px">
                            <button type="submit" title="Save" aria-label="Save" style="width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--border);background:var(--surface2);border-radius:9px;cursor:pointer;color:var(--primary)">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" x-on:submit.prevent="$dispatch('confirm-ask', { form: $event.target, title: 'Delete category?', message: 'This permanently deletes the category. This action cannot be undone.', confirmLabel: 'Delete category' })">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="{{ $category->events_count > 0 ? 'Cannot delete â€” category has events' : 'Delete' }}" aria-label="Delete {{ $category->name }}" {{ $category->events_count > 0 ? 'disabled' : '' }} style="width:34px;height:34px;display:grid;place-items:center;border:1px solid {{ $category->events_count > 0 ? 'var(--border)' : 'rgba(220,38,38,.35)' }};background:var(--surface2);border-radius:9px;cursor:{{ $category->events_count > 0 ? 'not-allowed' : 'pointer' }};color:var(--err);opacity:{{ $category->events_count > 0 ? '.4' : '1' }}">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="padding:36px 20px;text-align:center">
                    <div style="font-size:14px;font-weight:800">No categories yet</div>
                    <div style="font-size:12.5px;color:var(--muted);font-weight:600;margin-top:4px">Add your first category above.</div>
                </div>
            @endforelse
        </div>
    </div>

</x-app-layout>
