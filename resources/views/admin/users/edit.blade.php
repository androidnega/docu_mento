@extends('layouts.dashboard')

@section('title', 'Edit user')
@section('admin_heading', 'Edit user')

@section('dashboard_content')
<div class="w-full min-w-0 max-w-full space-y-6">
        @if(!isset($isProfileCompletion) || !$isProfileCompletion)
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-primary-600">Dashboard</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('dashboard.users.index') }}" class="hover:text-primary-600">User management</a>
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-gray-900 font-medium min-w-0 truncate">Edit {{ $user->username }}</span>
        </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 sm:p-6 w-full min-w-0 max-w-full overflow-hidden">
            @if(isset($isProfileCompletion) && $isProfileCompletion)
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Complete Your Profile</p>
                <p class="text-sm text-gray-600 mb-4">Please select your school and department to complete your profile.</p>
            @else
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-4">Edit user</p>
            @endif

            <form action="{{ route('dashboard.users.update', $user) }}" method="post" class="space-y-4 w-full min-w-0">
                @csrf
                @method('PUT')
                @if(!isset($isProfileCompletion) || !$isProfileCompletion)
                <div>
                    <label for="username" class="block text-xs font-medium text-gray-500 mb-0.5">Username</label>
                    <input type="text" name="username" id="username" value="{{ old('username', $user->username) }}" required class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('username') border-red-500 @enderror">
                    @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-xs font-medium text-gray-500 mb-0.5">Email (optional, for password reset)</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" placeholder="user@example.com" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('email') border-red-500 @enderror">
                    @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="name" class="block text-xs font-medium text-gray-500 mb-0.5">Name (optional)</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('name') border-red-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label for="role" class="block text-xs font-medium text-gray-500 mb-0.5">Role</label>
                    <select name="role" id="role" required class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('role') border-red-500 @enderror">
                        <option value="super_admin" {{ old('role', $user->role) === 'super_admin' ? 'selected' : '' }}>Admin</option>
                        <option value="supervisor" {{ old('role', $user->role) === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        <option value="coordinator" {{ old('role', $user->role) === 'coordinator' ? 'selected' : '' }}>Coordinator (Docu Mentor)</option>
                        <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>Student (Docu Mentor)</option>
                        <option value="leader" {{ old('role', $user->role) === 'leader' ? 'selected' : '' }}>Leader (Docu Mentor)</option>
                    </select>
                    @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @else
                <input type="hidden" name="role" value="{{ $user->role }}">
                @endif
                @else
                <input type="hidden" name="username" value="{{ $user->username }}">
                <input type="hidden" name="name" value="{{ $user->name }}">
                <input type="hidden" name="email" value="{{ $user->email }}">
                <input type="hidden" name="role" value="{{ $user->role }}">
                @endif

                @if(auth()->user()->isSuperAdmin())
                @if($user->isDocuMentorSupervisor() || $user->role === \App\Models\User::DM_ROLE_COORDINATOR)
                <div>
                    <label for="sms_allocation" class="block text-xs font-medium text-gray-500 mb-0.5">SMS allocation (Supervisor & Coordinator)</label>
                    <input type="number" name="sms_allocation" id="sms_allocation" value="{{ old('sms_allocation', $user->sms_allocation ?? 0) }}" min="0" step="1" placeholder="0" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('sms_allocation') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">SMS credits for login tokens and group/supervisor messaging (e.g. 20).</p>
                    @error('sms_allocation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif
                @if($user->isDocuMentorSupervisor() || $user->role === \App\Models\User::DM_ROLE_COORDINATOR)
                <div>
                    <label for="ai_tokens_allocation" class="block text-xs font-medium text-gray-500 mb-0.5">AI tokens (per period)</label>
                    <input
                        type="number"
                        name="ai_tokens_allocation"
                        id="ai_tokens_allocation"
                        value="{{ old('ai_tokens_allocation', $user->ai_tokens_allocation ?? ($user->role === \App\Models\User::DM_ROLE_COORDINATOR ? 3 : 10)) }}"
                        min="0"
                        step="1"
                        placeholder="{{ $user->role === \App\Models\User::DM_ROLE_COORDINATOR ? '3' : '10' }}"
                        class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('ai_tokens_allocation') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">
                        Number of AI generations allowed for this user. When exhausted, they wait for the cooldown (Settings → AI) before refill.
                    </p>
                    @error('ai_tokens_allocation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @endif
                @endif

                @if($user->isDocuMentorSupervisor() || $user->role === \App\Models\User::DM_ROLE_COORDINATOR)
                <div class="mt-4 space-y-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">School &amp; Department</p>
                    <div>
                        <label for="school_id" class="block text-xs font-medium text-gray-500 mb-0.5">School</label>
                        <select name="school_id" id="school_id" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none" onchange="loadDepartmentsBySchool()">
                            <option value="">— Select school —</option>
                            @foreach($schools ?? [] as $school)
                                <option value="{{ $school->id }}" {{ (old('school_id', $selectedSchoolId ?? null) == $school->id) ? 'selected' : '' }}>{{ $school->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="department_id" class="block text-xs font-medium text-gray-500 mb-0.5">
                            Department
                            @if((isset($isProfileCompletion) && $isProfileCompletion && !$user->department_id))
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <select
                            name="department_id"
                            id="department_id"
                            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('department_id') border-red-500 @enderror"
                            {{ (isset($isProfileCompletion) && $isProfileCompletion && !$user->department_id) ? 'required' : '' }}
                        >
                            <option value="">— Select department —</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                @if(!isset($isProfileCompletion) || !$isProfileCompletion)
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <label for="password" class="block text-xs font-medium text-gray-500 mb-0.5">New password (leave blank to keep current)</label>
                    <input type="password" name="password" id="password" placeholder="Set or reset password" minlength="8" autocomplete="new-password" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none @error('password') border-red-500 @enderror">
                    <p class="mt-1 text-xs text-gray-500">At least 8 characters, including one letter and one number.</p>
                    @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-medium text-gray-500 mb-0.5">Confirm new password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-gray-400 focus:ring-1 focus:ring-gray-300 focus:outline-none">
                </div>
                @endif
                @endif
                @endif
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-yellow-500 px-4 py-2 text-sm font-medium text-yellow-900 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-1">
                        @if(isset($isProfileCompletion) && $isProfileCompletion)
                            Complete Profile
                        @else
                            Update user
                        @endif
                    </button>
                    @if(isset($isProfileCompletion) && $isProfileCompletion)
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">Cancel</a>
                    @else
                        <a href="{{ route('dashboard.users.index') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// AJAX: School → Department cascading dropdowns on edit
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const baseUrl = "{{ url('/') }}";
const currentDepartmentId = {{ json_encode(old('department_id', $user->department_id)) }};

function loadDepartmentsBySchool() {
    const schoolSelect = document.getElementById('school_id');
    const departmentSelect = document.getElementById('department_id');
    if (!schoolSelect || !departmentSelect) return;

    const schoolId = schoolSelect.value;
    departmentSelect.innerHTML = '<option value="">— Select department —</option>';
    if (!schoolId) return;

    fetch(baseUrl + '/dashboard/schools/' + schoolId + '/departments', {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(r => {
            if (!r.ok) throw new Error('Failed to load departments');
            return r.json();
        })
        .then(data => {
            if (data.success && data.departments) {
                data.departments.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.name;
                    if (d.id == currentDepartmentId) opt.selected = true;
                    departmentSelect.appendChild(opt);
                });
            }
        })
        .catch(e => console.error('Error loading departments:', e));
}
</script>
@endpush
@endsection
