@extends('layouts.dashboard')

@section('title', 'Edit school')
@section('admin_heading', 'Edit school')

@section('dashboard_content')
<div class="w-full max-w-lg space-y-6">
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-6">
        <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('dashboard.schools.index') }}" class="hover:text-primary-600">Schools</a>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-medium">Edit {{ $school->name }}</span>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-6">
        <form action="{{ route('dashboard.schools.update', $school) }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $school->name) }}" required class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 @error('name') border-red-500 @enderror">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @if(isset($school->is_active))
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $school->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>
            @endif
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-white bg-primary-600 hover:bg-primary-700">Save</button>
                <a href="{{ route('dashboard.schools.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">Cancel</a>
            </div>
        </form>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Departments</h2>

        <div class="mb-4">
            <label for="new_department_name" class="block text-xs font-medium text-gray-500 mb-1">Add department</label>
            <div class="flex gap-2">
                <input type="text" id="new_department_name" class="block w-full max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" placeholder="e.g. Computer Science">
                <button type="button" id="btn_add_department" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">Add</button>
            </div>
            <p id="department_message" class="mt-1 text-xs hidden"></p>
        </div>

        <ul id="department_list" class="text-sm text-gray-600 space-y-2">
            @foreach($school->departments as $dept)
            <li class="flex items-center justify-between gap-2 py-1 border-b border-gray-100 last:border-0">
                <span>{{ $dept->name }}</span>
                <form action="{{ route('dashboard.departments.destroy', $dept) }}" method="post" class="inline department-delete-form" data-name="{{ $dept->name }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                </form>
            </li>
            @endforeach
        </ul>
        @if($school->departments->isEmpty())
        <p id="department_empty" class="text-xs text-gray-500 py-2">No departments yet. Add one above.</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function() {
    const schoolId = {{ $school->id }};
    const listEl = document.getElementById('department_list');
    const emptyEl = document.getElementById('department_empty');
    const nameInput = document.getElementById('new_department_name');
    const msgEl = document.getElementById('department_message');
    const addBtn = document.getElementById('btn_add_department');
    const storeUrl = '{{ route("dashboard.departments.store") }}';
    const destroyUrlTemplate = '{{ route("dashboard.departments.destroy", ["department" => "__ID__"]) }}'.replace('__ID__', '');
    const csrf = '{{ csrf_token() }}';

    function showMsg(text, isError) {
        msgEl.textContent = text;
        msgEl.classList.toggle('text-red-600', isError);
        msgEl.classList.toggle('text-green-600', !isError);
        msgEl.classList.remove('hidden');
    }

    addBtn.addEventListener('click', async function() {
        const name = (nameInput.value || '').trim();
        if (!name) {
            showMsg('Enter a department name.', true);
            return;
        }
        addBtn.disabled = true;
        msgEl.classList.add('hidden');
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: name, school_id: schoolId })
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between gap-2 py-1 border-b border-gray-100 last:border-0';
                li.innerHTML = '<span>' + escapeHtml(data.department.name) + '</span>' +
                    '<form action="' + destroyUrlTemplate + data.department.id + '" method="post" class="inline department-delete-form" data-name="' + escapeHtml(data.department.name) + '">' +
                    '<input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="_method" value="DELETE">' +
                    '<button type="submit" class="text-red-600 hover:text-red-800 text-xs">Remove</button></form>';
                listEl.appendChild(li);
                if (emptyEl) emptyEl.classList.add('hidden');
                nameInput.value = '';
                showMsg('Department added.', false);
            } else {
                showMsg(data.message || 'Failed to add department.', true);
            }
        } catch (e) {
            showMsg('Request failed.', true);
        }
        addBtn.disabled = false;
    });

    function escapeHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    listEl.addEventListener('submit', async function(e) {
        if (!e.target.classList.contains('department-delete-form')) return;
        e.preventDefault();
        const form = e.target;
        const name = form.getAttribute('data-name') || 'this department';
        if (!confirm('Remove department “‘ + name + ’”? Users in this department will need to be reassigned.')) return;
        try {
            const res = await fetch(form.action, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                form.closest('li').remove();
                if (emptyEl && listEl.children.length === 0) emptyEl.classList.remove('hidden');
            } else {
                alert(data.message || 'Failed to remove department.');
            }
        } catch (err) {
            alert('Request failed.');
        }
    });
})();
</script>
@endpush
@endsection
