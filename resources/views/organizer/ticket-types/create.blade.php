{{-- Create ticket type — form language from organizer/events/create.blade.php. --}}
<x-app-layout :activeNav="'oevents'">
    <main style="max-width:800px;margin:0 auto;padding:32px 26px 60px">
        <a href="{{ route('organizer.ticket-types.index', $event) }}" style="border:0;background:none;cursor:pointer;font-size:13px;font-weight:700;color:var(--muted);padding:8px 0;margin-bottom:10px;text-decoration:none;display:inline-block">&larr; Back to ticket types</a>
        <h1 style="margin:0 0 6px;font-size:28px;font-weight:800;letter-spacing:-.9px">Create Ticket Type</h1>
        <p style="margin:0 0 24px;color:var(--muted);font-size:14.5px">Configure pricing, capacity and the sales window for {{ $event->title }}.</p>

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

        <form method="POST" action="{{ route('organizer.ticket-types.store', $event) }}" style="background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:26px">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <label style="grid-column:1/-1;display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Name *</span>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. General Admission" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                    @error('name') <span style="color:var(--err);font-size:12px;font-weight:600">{{ $message }}</span> @enderror
                </label>
                <label style="grid-column:1/-1;display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Description <span style="color:var(--muted);font-weight:600">(optional)</span></span>
                    <textarea name="description" rows="3" placeholder="What does this tier include?" style="padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none;resize:vertical;line-height:1.6">{{ old('description') }}</textarea>
                </label>
                <label style="display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Price (MAD) *</span>
                    <input type="number" name="price" value="{{ old('price', '0') }}" min="0" step="0.01" placeholder="0.00" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                    @error('price') <span style="color:var(--err);font-size:12px;font-weight:600">{{ $message }}</span> @enderror
                </label>
                <label style="display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Quantity *</span>
                    <input type="number" name="quantity" value="{{ old('quantity', '100') }}" min="1" placeholder="100" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                    @error('quantity') <span style="color:var(--err);font-size:12px;font-weight:600">{{ $message }}</span> @enderror
                </label>
                <label style="display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Min per booking *</span>
                    <input type="number" name="min_per_booking" value="{{ old('min_per_booking', '1') }}" min="1" placeholder="1" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                </label>
                <label style="display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Max per booking *</span>
                    <input type="number" name="max_per_booking" value="{{ old('max_per_booking', '10') }}" min="1" placeholder="10" required style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                </label>
                <label style="display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Sales start <span style="color:var(--muted);font-weight:600">(optional)</span></span>
                    <input type="datetime-local" name="sales_start_at" value="{{ old('sales_start_at') }}" style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                </label>
                <label style="display:flex;flex-direction:column;gap:7px">
                    <span style="font-size:12.5px;font-weight:700">Sales end <span style="color:var(--muted);font-weight:600">(optional)</span></span>
                    <input type="datetime-local" name="sales_end_at" value="{{ old('sales_end_at') }}" style="min-height:48px;padding:13px 15px;border:1px solid var(--border);background:var(--surface2);border-radius:12px;font-size:14.5px;outline:none">
                    @error('sales_end_at') <span style="color:var(--err);font-size:12px;font-weight:600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div style="display:flex;gap:10px;margin-top:24px;padding-top:20px;border-top:1px solid var(--border)">
                <a href="{{ route('organizer.ticket-types.index', $event) }}" style="border:1px solid var(--border);background:var(--surface2);cursor:pointer;font-weight:700;font-size:14px;padding:13px 20px;border-radius:12px;min-height:48px;text-decoration:none;color:inherit;display:inline-flex;align-items:center">Cancel</a>
                <div style="flex:1"></div>
                <button type="submit" style="border:0;cursor:pointer;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:800;font-size:14px;padding:13px 24px;border-radius:12px;min-height:48px">Create Ticket Type</button>
            </div>
        </form>
    </main>
</x-app-layout>
