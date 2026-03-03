@extends('layouts.student-dashboard')

@section('title', 'Create Project')
@php $dashboardTitle = 'Create Project'; @endphp

@section('dashboard_content')
<header class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Create project</h1>
            <p class="text-slate-500 mt-1" id="create-step-indicator">Step 1 of 3</p>
            <p class="text-sm text-slate-500 mt-0.5 min-h-[1.25rem]" id="create-step-next-label" aria-live="polite">Next: Project Details</p>
        </div>
        <button type="button" id="clear-project-form-btn" class="text-sm font-medium text-slate-500 hover:text-slate-700 underline underline-offset-2 focus:outline-none focus:ring-2 focus:ring-slate-300 rounded self-start sm:self-center">
            Start fresh
        </button>
    </div>

    {{-- Step progress: minimal horizontal stepper --}}
    <div class="mt-6 flex items-center gap-0" id="create-step-ovals" aria-label="Progress">
        <div class="flex items-center" data-step-label-wrap="step-1">
            <span data-step-oval="step-1" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-white text-sm font-medium transition-colors">1</span>
            <span class="ml-2 text-sm font-medium text-slate-900" data-step-label="step-1">Basic</span>
        </div>
        <div class="flex-1 min-w-[24px] h-px bg-slate-200 mx-2 sm:mx-3" aria-hidden="true"></div>
        <div class="flex items-center" data-step-label-wrap="step-2">
            <span data-step-oval="step-2" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-400 text-sm font-medium transition-colors">2</span>
            <span class="ml-2 text-sm font-medium text-slate-400" data-step-label="step-2">Details</span>
        </div>
        <div class="flex-1 min-w-[24px] h-px bg-slate-200 mx-2 sm:mx-3" aria-hidden="true"></div>
        <div class="flex items-center" data-step-label-wrap="step-3">
            <span data-step-oval="step-3" class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-400 text-sm font-medium transition-colors">3</span>
            <span class="ml-2 text-sm font-medium text-slate-400" data-step-label="step-3">Finish</span>
        </div>
    </div>
</header>

<section class="mb-8">
    @if($errors->has('session'))
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-800" role="alert">
            {{ $errors->first('session') }}
        </div>
    @endif
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden w-full">
        <form action="{{ route('dashboard.projects.store') }}" method="post" enctype="multipart/form-data" id="project-create-form" class="p-6 sm:p-8">
            @csrf

            {{-- Step 1: Basic Details --}}
            <div id="step-1" class="project-step space-y-5">
                <div class="pb-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-800">Basic details</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Group, title, and optional description.</p>
                </div>
                <div>
                    <label for="group_id" class="block text-sm font-medium text-slate-700 mb-1.5">Group <span class="text-slate-400">*</span></label>
                    <select name="group_id" id="group_id" required class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 focus:bg-white transition-colors">
                        @foreach($groupsWithoutProject as $g)
                            <option value="{{ $g->id }}" {{ old('group_id') == $g->id ? 'selected' : '' }}>
                                {{ $g->name }} ({{ $g->academicYear?->year ?? '—' }})
                            </option>
                        @endforeach
                    </select>
                    @error('group_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700 mb-1.5">Title <span class="text-slate-400">*</span></label>
                    <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g. E-Learning Platform"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 focus:bg-white transition-colors">
                    @error('title')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700 mb-1.5">Description <span class="text-slate-400 font-normal">(optional, max 700 characters)</span></label>
                    <textarea name="description" id="description" rows="4" maxlength="700" placeholder="Brief overview of your project goals and scope."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 focus:bg-white transition-colors resize-none">{{ old('description') }}</textarea>
                    <p class="mt-1.5 text-xs text-slate-400">700 characters max.</p>
                    @error('description')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category_id" class="block text-sm font-medium text-slate-700 mb-1.5">Category <span class="text-slate-400 font-normal">(optional)</span></label>
                    <select name="category_id" id="category_id" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 focus:bg-white transition-colors">
                        <option value="">— None —</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="parent_project_id" class="block text-sm font-medium text-slate-700 mb-1.5">Previous project <span class="text-slate-400 font-normal">(optional)</span></label>
                    <select name="parent_project_id" id="parent_project_id" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:border-slate-300 focus:bg-white transition-colors">
                        <option value="">— None —</option>
                        @foreach($previousProjects as $pp)
                            <option value="{{ $pp->id }}" {{ old('parent_project_id') == $pp->id ? 'selected' : '' }}>{{ $pp->title }} ({{ $pp->academicYear?->year ?? '—' }})</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500">Tag a prior project to link proposal and Chapter 6 access for you and your supervisor.</p>
                    @error('parent_project_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="pt-2">
                    <button type="button" class="step-next inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium bg-slate-800 text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors min-h-[44px] sm:min-h-0" data-next="step-2">
                        Next: Project Details
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- Step 2: Project Details --}}
            <div id="step-2" class="project-step hidden space-y-5">
                <div class="pb-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-800">Project details</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Proposal upload and features.</p>
                </div>
                <div>
                    <label for="proposal_file" class="block text-sm font-medium text-slate-700 mb-1.5">Proposal (PDF, max 1MB) <span class="text-slate-400">*</span></label>
                    <div class="flex flex-wrap items-center gap-3">
                        <label for="proposal_file" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-medium bg-slate-800 text-white hover:bg-slate-700 cursor-pointer transition-colors">
                            <i class="fas fa-upload text-xs"></i>
                            <span>Select file</span>
                        </label>
                        <span id="proposal-file-name" class="text-sm text-slate-500 truncate">No file selected</span>
                    </div>
                    <input type="file" name="proposal_file" id="proposal_file" accept=".pdf" class="hidden">
                    <input type="hidden" name="proposal_uploaded_url" id="proposal_uploaded_url" value="">
                    <div class="mt-3 flex items-center gap-3">
                        <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div id="proposal-upload-progress" class="h-2 w-0 rounded-full bg-slate-400 transition-all duration-200 hidden"></div>
                        </div>
                        <p id="proposal-upload-label" class="text-xs text-slate-500 whitespace-nowrap">Not uploaded yet</p>
                    </div>
                    @error('proposal_file')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Features</label>
                    <div id="features-container" class="space-y-3">
                        @php $featuresOld = old('features'); @endphp
                        @if(is_array($featuresOld) && count($featuresOld) > 0)
                            @foreach($featuresOld as $idx => $f)
                                <div class="feature-row flex flex-wrap gap-2 items-end">
                                    <input type="text" name="features[{{ $idx }}][name]" value="{{ $f['name'] ?? '' }}" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">
                                    <input type="text" name="features[{{ $idx }}][description]" value="{{ $f['description'] ?? '' }}" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">
                                    <button type="button" class="remove-feature px-3 py-2 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">Remove</button>
                                </div>
                            @endforeach
                        @else
                            <div class="feature-row flex flex-wrap gap-2 items-end">
                                <input type="text" name="features[0][name]" value="" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">
                                <input type="text" name="features[0][description]" value="" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">
                                <button type="button" class="remove-feature px-3 py-2 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">Remove</button>
                            </div>
                        @endif
                    </div>
                    <button type="button" id="add-feature" class="mt-3 px-4 py-2 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0 transition-colors">+ Add feature</button>
                </div>
                <div>
                    <label for="budget" class="block text-sm font-medium text-slate-700 mb-1.5">Budget <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="number" name="budget" id="budget" value="{{ old('budget') }}" min="0" step="0.01" placeholder="0.00" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">
                    @error('budget')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="button" class="step-prev px-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 text-slate-700 hover:bg-slate-50 shrink-0 transition-colors" data-prev="step-1">Back</button>
                    <button type="button" class="step-next inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium bg-slate-800 text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 min-h-[44px] sm:min-h-0 transition-colors" data-next="step-3">
                        Next: Finish
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- Step 3: Finish --}}
            <div id="step-3" class="project-step hidden space-y-5">
                <div class="pb-4 border-b border-slate-100">
                    <h2 class="text-base font-semibold text-slate-800">Review & submit</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Your project will be set to <strong>Pending</strong>. The coordinator will review and assign a supervisor.</p>
                </div>
                <div id="step-3-summary" class="rounded-xl border border-slate-200 bg-slate-50/80 px-5 py-4 text-sm text-slate-700 hidden" role="status" aria-live="polite">
                    <p class="font-medium text-slate-800">Summary</p>
                    <p class="mt-1.5"><strong id="step-3-title">—</strong></p>
                    <p class="text-slate-600 mt-0.5">Group: <span id="step-3-group">—</span></p>
                </div>
                <div class="flex flex-wrap gap-3 pt-2">
                    <button type="button" class="step-prev px-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 text-slate-700 hover:bg-slate-50 shrink-0 transition-colors" data-prev="step-2">Back</button>
                    <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-medium bg-slate-800 text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 min-h-[44px] sm:min-h-0 transition-colors">
                        Submit project
                    </button>
                    <a href="{{ route('dashboard.projects.index') }}" class="px-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 inline-block transition-colors">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
(function() {
    var steps = document.querySelectorAll('.project-step');
    var form = document.getElementById('project-create-form');
    var stepIndicator = document.getElementById('create-step-indicator');
    var stepOrder = ['step-1', 'step-2', 'step-3'];
    var stepOvals = document.querySelectorAll('[data-step-oval]');
    var fileInput = document.getElementById('proposal_file');
    var fileNameEl = document.getElementById('proposal-file-name');
    var progressBar = document.getElementById('proposal-upload-progress');
    var progressLabel = document.getElementById('proposal-upload-label');
    var uploadedUrlInput = document.getElementById('proposal_uploaded_url');
    var uploadEndpoint = "{{ route('docu-mentor.students.projects.proposals.upload-temp') }}";
    var csrfRefreshUrl = "{{ route('dashboard.csrf-refresh') }}";
    var STORAGE_KEY_STEP = 'dm_project_create_step';
    var STORAGE_KEY_FORM = 'dm_project_create_form';

    var nextLabelEl = document.getElementById('create-step-next-label');
    var stepLabelEls = document.querySelectorAll('[data-step-label]');
    var step3Summary = document.getElementById('step-3-summary');
    var step3TitleEl = document.getElementById('step-3-title');
    var step3GroupEl = document.getElementById('step-3-group');

    function updateStepOvals(activeStepId) {
        if (!stepOvals.length) return;
        var idx = stepOrder.indexOf(activeStepId);
        stepOvals.forEach(function(oval) {
            var stepId = oval.getAttribute('data-step-oval');
            var stepIdx = stepOrder.indexOf(stepId);
            var isActive = stepIdx === idx;
            var isCompleted = stepIdx < idx;
            if (isActive) {
                oval.className = 'inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-800 text-white text-sm font-medium transition-colors';
                oval.textContent = stepIdx + 1;
            } else if (isCompleted) {
                oval.className = 'inline-flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 text-sm transition-colors';
                oval.innerHTML = '<i class="fas fa-check text-xs" aria-hidden="true"></i>';
            } else {
                oval.className = 'inline-flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-400 text-sm font-medium transition-colors';
                oval.textContent = stepIdx + 1;
            }
        });
        stepLabelEls.forEach(function(lbl) {
            var stepId = lbl.getAttribute('data-step-label');
            var stepIdx = stepOrder.indexOf(stepId);
            lbl.classList.toggle('text-slate-900', stepIdx === idx);
            lbl.classList.toggle('text-slate-400', stepIdx !== idx);
        });
        if (nextLabelEl) {
            if (idx === 0) nextLabelEl.textContent = 'Next: Project Details';
            else if (idx === 1) nextLabelEl.textContent = 'Next: Finish';
            else nextLabelEl.textContent = 'Review and submit below.';
        }
    }

    function persistStep(stepId) {
        try {
            window.localStorage && localStorage.setItem(STORAGE_KEY_STEP, stepId);
        } catch (e) {}
    }

    function saveFormState() {
        if (!form) return;
        var data = {};
        var elements = form.querySelectorAll('input, select, textarea');
        elements.forEach(function(el) {
            if (!el.name) return;
            if (el.type === 'file') return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            data[el.name] = el.value;
        });
        try {
            window.localStorage && localStorage.setItem(STORAGE_KEY_FORM, JSON.stringify(data));
        } catch (e) {}
    }

    function restoreFormState() {
        if (!form) return;
        var raw;
        try {
            raw = window.localStorage && localStorage.getItem(STORAGE_KEY_FORM);
        } catch (e) {
            raw = null;
        }
        if (!raw) return;
        var data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            return;
        }
        Object.keys(data || {}).forEach(function(name) {
            var els = form.querySelectorAll('[name=\"' + name.replace(/\"/g, '\\\"') + '\"]');
            if (!els.length) return;
            els.forEach(function(el) {
                if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = el.value === data[name];
                } else {
                    el.value = data[name];
                }
            });
        });
    }

    function applyUploadStateFromHidden() {
        if (!progressLabel || !progressBar || !uploadedUrlInput) return;
        if (uploadedUrlInput.value) {
            // Uploaded (success): full-width green bar
            progressBar.classList.remove('hidden');
            progressBar.style.width = '100%';
            progressBar.style.backgroundColor = '#16a34a';
            progressLabel.textContent = 'Upload complete';
            progressLabel.classList.remove('text-slate-500', 'text-red-600');
            progressLabel.classList.add('text-green-600');
        } else {
            // Initial / reset state: zero progress
            progressBar.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressBar.style.backgroundColor = '';
            progressLabel.textContent = 'Not uploaded yet';
            progressLabel.classList.remove('text-red-600', 'text-green-600');
            progressLabel.classList.add('text-slate-500');
        }
    }

    function showStep(stepId) {
        steps.forEach(function(s) {
            s.classList.toggle('hidden', s.id !== stepId);
        });
        var idx = stepOrder.indexOf(stepId);
        if (stepIndicator && idx >= 0) stepIndicator.textContent = 'Step ' + (idx + 1) + ' of 3';
        updateStepOvals(stepId);
        persistStep(stepId);
        if (stepId === 'step-3' && step3Summary && step3TitleEl && step3GroupEl) {
            var titleInput = document.getElementById('title');
            var groupSelect = document.getElementById('group_id');
            var title = (titleInput && titleInput.value) ? titleInput.value.trim() : '';
            var groupText = '—';
            if (groupSelect && groupSelect.selectedIndex >= 0 && groupSelect.options[groupSelect.selectedIndex]) {
                groupText = groupSelect.options[groupSelect.selectedIndex].text || groupSelect.value;
            }
            step3TitleEl.textContent = title || '—';
            step3GroupEl.textContent = groupText;
            step3Summary.classList.remove('hidden');
            // Refresh CSRF token before submit to avoid 419 on live (session/cookie issues)
            if (csrfRefreshUrl && form) {
                fetch(csrfRefreshUrl, { redirect: 'manual', credentials: 'same-origin' })
                    .then(function(r) {
                        if (r.status === 302 || r.type === 'opaqueredirect') {
                            // If the student session expired, send them back to the student login flow,
                            // not the staff /login page.
                            window.location.href = "{{ route('student.login.form') }}";
                            return null;
                        }
                        return r.ok ? r.json() : null;
                    })
                    .then(function(data) {
                        if (data && data.token) {
                            var tokenInput = form.querySelector('input[name=\"_token\"]');
                            if (tokenInput) tokenInput.value = data.token;
                            var meta = document.querySelector('meta[name=\"csrf-token\"]');
                            if (meta) meta.setAttribute('content', data.token);
                        }
                    });
            }
        }
    }

    function clearFormAndStorage() {
        try {
            if (window.localStorage) {
                localStorage.removeItem(STORAGE_KEY_STEP);
                localStorage.removeItem(STORAGE_KEY_FORM);
            }
        } catch (e) {}
        if (!form) return;
        var groupSelect = document.getElementById('group_id');
        if (groupSelect && groupSelect.options.length) groupSelect.selectedIndex = 0;
        var titleEl = document.getElementById('title');
        if (titleEl) titleEl.value = '';
        var descEl = document.getElementById('description');
        if (descEl) descEl.value = '';
        var catSelect = document.getElementById('category_id');
        if (catSelect && catSelect.options.length) catSelect.selectedIndex = 0;
        var parentSelect = document.getElementById('parent_project_id');
        if (parentSelect && parentSelect.options.length) parentSelect.selectedIndex = 0;
        var budgetEl = document.getElementById('budget');
        if (budgetEl) budgetEl.value = '';
        if (uploadedUrlInput) uploadedUrlInput.value = '';
        if (fileInput) fileInput.value = '';
        if (fileNameEl) fileNameEl.textContent = 'No file selected';
        // Reset upload UI to initial grey state
        applyUploadStateFromHidden();
        var featContainer = document.getElementById('features-container');
        if (featContainer) {
            featContainer.innerHTML = '<div class="feature-row flex flex-wrap gap-2 items-end">' +
                '<input type="text" name="features[0][name]" value="" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">' +
                '<input type="text" name="features[0][description]" value="" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">' +
                '<button type="button" class="remove-feature px-3 py-2 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">Remove</button>' +
                '</div>';
        }
        showStep('step-1');
    }

    var clearBtn = document.getElementById('clear-project-form-btn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (confirm('Clear the form and start a new project? Any unsent data will be removed.')) {
                clearFormAndStorage();
            }
        });
    }

    // Restore saved form + step on load
    restoreFormState();
    (function initStep() {
        var savedStep = null;
        try {
            savedStep = window.localStorage && localStorage.getItem(STORAGE_KEY_STEP);
        } catch (e) {
            savedStep = null;
        }
        if (!savedStep || stepOrder.indexOf(savedStep) === -1) {
            savedStep = 'step-1';
        }
        showStep(savedStep);
    })();

    // Reflect any restored upload state
    applyUploadStateFromHidden();

    if (form) {
        form.addEventListener('input', saveFormState);
        form.addEventListener('change', saveFormState);
    }

    form.addEventListener('click', function(e) {
        var next = e.target.closest('.step-next');
        var prev = e.target.closest('.step-prev');
        if (next) {
            e.preventDefault();
            showStep(next.getAttribute('data-next'));
        }
        if (prev) {
            e.preventDefault();
            showStep(prev.getAttribute('data-prev'));
        }
    });
    var container = document.getElementById('features-container');
    var addBtn = document.getElementById('add-feature');
    if (addBtn && container) {
        addBtn.addEventListener('click', function() {
            var idx = container.querySelectorAll('.feature-row').length;
            var div = document.createElement('div');
            div.className = 'feature-row flex flex-wrap gap-2 items-end';
            div.innerHTML = '<input type="text" name="features[' + idx + '][name]" placeholder="Feature name" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">' +
                '<input type="text" name="features[' + idx + '][description]" placeholder="Description (optional)" class="flex-1 min-w-[120px] rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300 focus:bg-white">' +
                '<button type="button" class="remove-feature px-3 py-2 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 shrink-0">Remove</button>';
            container.appendChild(div);
            div.querySelector('.remove-feature').addEventListener('click', function() { div.remove(); });
        });
    }
    container && container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-feature')) {
            e.target.closest('.feature-row').remove();
        }
    });

    // File name + progress reset on file select
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                if (fileNameEl) fileNameEl.textContent = fileInput.files[0].name;
            } else if (fileNameEl) {
                fileNameEl.textContent = 'No file selected';
            }

            if (!fileInput.files || fileInput.files.length === 0) {
                // Reset state if no file
                if (uploadedUrlInput) uploadedUrlInput.value = '';
                applyUploadStateFromHidden();
                return;
            }

            if (!(progressBar && progressLabel && window.XMLHttpRequest && uploadEndpoint)) {
                return;
            }

            // Start upload immediately after file is added
            progressBar.classList.remove('hidden');
            progressBar.style.width = '0%';
            // Upload in progress: slate bar
            progressBar.style.backgroundColor = '#64748b';
            progressLabel.textContent = 'Upload progress: 0%';
            progressLabel.classList.remove('text-red-600', 'text-green-600');
            progressLabel.classList.add('text-slate-500');

            var xhr = new XMLHttpRequest();
            var data = new FormData();
            data.append('proposal_file', fileInput.files[0]);

            xhr.open('POST', uploadEndpoint, true);

            var tokenMeta = document.querySelector('meta[name=\"csrf-token\"]');
            if (tokenMeta) {
                xhr.setRequestHeader('X-CSRF-TOKEN', tokenMeta.getAttribute('content'));
            }
            xhr.upload.onprogress = function (event) {
                if (!event.lengthComputable) return;
                var percent = Math.round((event.loaded / event.total) * 100);
                progressBar.style.width = percent + '%';
                progressLabel.textContent = 'Upload progress: ' + percent + '%';
            };

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 400) {
                    try {
                        var resp = JSON.parse(xhr.responseText || '{}');
                        if (resp.ok && resp.url && uploadedUrlInput) {
                            uploadedUrlInput.value = resp.url;
                            // Let the shared helper handle success styling (including green bar)
                            applyUploadStateFromHidden();
                            saveFormState();
                            return;
                        }
                    } catch (e) {}
                    progressLabel.textContent = 'Upload failed. Please try again.';
                    progressLabel.classList.remove('text-slate-500', 'text-green-600');
                    progressLabel.classList.add('text-red-600');
                } else {
                    progressLabel.textContent = 'Upload failed. Please try again.';
                    progressLabel.classList.remove('text-slate-500', 'text-green-600');
                    progressLabel.classList.add('text-red-600');
                }
            };

            xhr.onerror = function () {
                progressLabel.textContent = 'Upload failed. Please check your connection.';
                progressLabel.classList.remove('text-slate-500', 'text-green-600');
                progressLabel.classList.add('text-red-600');
            };

            xhr.send(data);
        });
    }
})();
</script>
@endpush
@endsection
