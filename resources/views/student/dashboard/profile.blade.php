@extends('layouts.student-dashboard')

@section('title', 'Profile')
@php $dashboardTitle = 'Profile'; @endphp

@section('dashboard_content')
<header class="mb-6">
    <h1 class="text-xl font-semibold text-gray-800">Profile & account</h1>
    <p class="text-sm text-gray-500 mt-1">Keep your details up to date so supervisors can reach you. Update your name; phone is tied to your account for login.</p>
</header>

@if(($levelLabel ?? null) || ($qualificationType ?? null) || ($currentSemester ?? null) || ($institution ?? null) || ($faculty ?? null) || ($department ?? null) || (isset($academicYears) && $academicYears->isNotEmpty()) || (isset($docuMentorGroups) && $docuMentorGroups->isNotEmpty()))
<section class="mb-8" aria-label="Academic info">
    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Academic info</h2>
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5">
        <dl class="space-y-2 text-sm">
            @if($institution ?? null)
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Institution</dt><dd class="text-gray-800">{{ $institution->name ?? '—' }}</dd></div>
            @endif
            @if($faculty ?? null)
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Faculty</dt><dd class="text-gray-800">{{ $faculty->name ?? '—' }}</dd></div>
            @endif
            @if($department ?? null)
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Department</dt><dd class="text-gray-800">{{ $department->name ?? '—' }}</dd></div>
            @endif
            @if(isset($academicYears) && $academicYears->isNotEmpty())
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Academic year</dt><dd class="text-gray-800">{{ $academicYears->pluck('year')->unique()->sort()->values()->implode(', ') }}</dd></div>
            @endif
            @if($levelLabel ?? null)
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Level</dt><dd class="font-medium text-gray-800">{{ $levelLabel }}</dd></div>
            @endif
            @if($qualificationType ?? null)
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Qualification</dt><dd class="text-gray-800">{{ $qualificationType }}</dd></div>
            @endif
            @if($currentSemester ?? null)
            <div class="flex gap-2"><dt class="text-gray-500 w-28 shrink-0">Semester</dt><dd class="text-gray-800">{{ $currentSemester }}</dd></div>
            @endif
            @if(isset($docuMentorGroups) && $docuMentorGroups->isNotEmpty())
            <div class="flex gap-2">
                <dt class="text-gray-500 w-24 shrink-0">Project group</dt>
                <dd class="text-gray-800">
                    @foreach($docuMentorGroups as $g)
                    <span class="inline-flex rounded-lg bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ $g->name }}</span>
                    @if(!$loop->last) <span class="text-gray-400">, </span> @endif
                    @endforeach
                </dd>
            </div>
            @endif
        </dl>
    </div>
</section>
@endif

<section class="mb-8" aria-label="Account">
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-5 sm:p-6">
        @if(isset($student) && $student)
        <form action="{{ route('dashboard.my-profile.update') }}" method="post" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                <input type="text" id="index_number" value="{{ old('index_number', $student->index_number) }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 bg-gray-50" readonly disabled>
            </div>
            <div>
                <label for="phone_display" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" id="phone_display" value="{{ $student->phone_contact ?: 'Not set' }}" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 bg-gray-50" readonly disabled>
                <p class="text-xs text-gray-500 mt-1">Used for login; cannot be edited here.</p>
            </div>
            <div>
                <label for="student_name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" id="student_name" name="student_name" value="{{ old('student_name', $student->student_name) }}" placeholder="Full name" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500" maxlength="255" autocomplete="name" style="text-transform: capitalize;">
                @error('student_name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            @if($levelLabel ?? null)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                <p class="text-sm text-gray-800">{{ $levelLabel }}</p>
            </div>
            @endif
            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-sm font-medium bg-amber-500 text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 min-h-[44px]">Save changes</button>
        </form>
        @else
        <p class="text-sm text-gray-600">Your account is linked to your user profile. Name and index are shown in the sidebar. Contact your coordinator to update your details.</p>
        @if(isset($user) && $user)
        <dl class="mt-4 space-y-1 text-sm">
            <div class="flex gap-2"><dt class="text-gray-500 w-24 shrink-0">Name</dt><dd class="text-gray-800">{{ $user->name ?? '—' }}</dd></div>
            <div class="flex gap-2"><dt class="text-gray-500 w-24 shrink-0">Index</dt><dd class="font-mono text-gray-800">{{ $user->index_number ?? '—' }}</dd></div>
        </dl>
        @endif
        @endif
    </div>
</section>

@if(isset($classGroups) && $classGroups->isNotEmpty())
<section class="mb-8" aria-label="My groups">
    <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">My groups</h2>
    <div class="bg-white rounded-lg border border-gray-100 shadow-sm p-4 sm:p-5">
        <ul class="flex flex-wrap gap-2">
            @foreach($classGroups as $group)
            <li class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700">{{ $group->name }}</li>
            @endforeach
        </ul>
    </div>
</section>
@endif

@push('scripts')
<script>
(function() {
    var section = document.querySelector('.passkey-section');
    var isMobileDevice = /Android|iPhone|iPod/i.test((navigator.userAgent || ''));
    // Only allow passkey setup on supported browsers and mobile phones (fingerprint / Face ID on phone).
    if (typeof PublicKeyCredential === 'undefined' || !isMobileDevice) {
        if (section) section.classList.add('hidden');
        return;
    }
    var btn = document.getElementById('btn-profile-add-passkey');
    var msg = document.getElementById('passkey-profile-message');
    if (!btn) return;
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    function base64urlToBuffer(str) {
        var bin = atob(str.replace(/-/g, '+').replace(/_/g, '/'));
        var buf = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        return buf.buffer;
    }
    function bufferToBase64url(buf) {
        var u8 = new Uint8Array(buf);
        var bin = '';
        for (var i = 0; i < u8.length; i++) bin += String.fromCharCode(u8[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
    function showMsg(text, isError) {
        if (!msg) return;
        msg.textContent = text;
        msg.classList.remove('hidden');
        msg.className = 'text-sm mt-2 ' + (isError ? 'text-red-600' : 'text-green-600');
    }
    var optionsResponse;
    btn.addEventListener('click', function() {
        btn.disabled = true;
        if (msg) msg.classList.add('hidden');
        fetch('{{ route("student.passkey.register-options") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            optionsResponse = data;
            if (!data.success || !data.options || !data.options.publicKey) {
                showMsg(data.message || 'Could not prepare passkey.', true);
                btn.disabled = false;
                return;
            }
            var pk = data.options.publicKey;
        var publicKey = {
            rp: pk.rp || { name: 'Docu Mento', id: window.location.hostname },
                user: { id: base64urlToBuffer(pk.user.id), name: pk.user.name || '', displayName: pk.user.displayName || '' },
                challenge: base64urlToBuffer(pk.challenge),
                pubKeyCredParams: pk.pubKeyCredParams || [{ type: 'public-key', alg: -7 }, { type: 'public-key', alg: -257 }],
                timeout: pk.timeout || 60000,
                authenticatorSelection: pk.authenticatorSelection || { userVerification: 'preferred', residentKey: 'required' }
            };
            if (pk.excludeCredentials && pk.excludeCredentials.length) {
                publicKey.excludeCredentials = pk.excludeCredentials.map(function(c) { return { type: 'public-key', id: base64urlToBuffer(c.id) }; });
            }
            return navigator.credentials.create({ publicKey: publicKey });
        })
        .then(function(cred) {
            btn.disabled = false;
            if (!cred) return;
            var r = cred.response;
            var challengeBase64 = (optionsResponse && optionsResponse.options && optionsResponse.options.publicKey && optionsResponse.options.publicKey.challenge) ? optionsResponse.options.publicKey.challenge : null;
            return fetch('{{ route("student.passkey.register") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ clientDataJSON: bufferToBase64url(r.clientDataJSON), attestationObject: bufferToBase64url(r.attestationObject), challenge: challengeBase64 })
            });
        })
        .then(function(r) {
            if (!r) return;
            return r.json();
        })
        .then(function(res) {
            if (res && res.success) showMsg('Passkey added. You can sign in with fingerprint or Face ID on this device next time.', false);
            else if (res && !res.success) {
                var text = res.message || 'Could not add passkey.';
                if (res.debug_error) text += ' Debug: ' + res.debug_error;
                if (res.debug_rp_id) text += ' [Server rp_id: ' + res.debug_rp_id + ' — URL host must match.]';
                if (res.debug_tip) text += ' ' + res.debug_tip;
                showMsg(text, true);
            }
        })
        .catch(function(err) {
            btn.disabled = false;
            showMsg('Could not add passkey. Try again. ' + (err && err.message ? err.message : ''), true);
        });
    });
})();
</script>
@endpush
@endsection
