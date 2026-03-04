@extends('layouts.app')

@section('title', 'Student Login')
@section('body_class', 'bg-[#e0e5ec]')

@section('content')
<div class="min-h-[100dvh] min-h-screen flex items-center justify-center px-4 py-6 bg-[#e0e5ec]">
    <div class="max-w-md w-full">
        <div class="bg-[#e0e5ec] rounded-[24px] p-6 sm:p-8 shadow-[8px_8px_16px_#c3c8d0,-8px_-8px_16px_#ffffff]">
            <div class="flex items-center gap-3 mb-6">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm"><i class="fas fa-graduation-cap text-sm"></i></span>
                <div>
                    <h1 class="text-xl font-semibold text-gray-800">Student login</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Docu Mento</p>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-5">Enter your index number. First-time users complete a quick setup with name, phone, and OTP; returning users sign in with a code.</p>

            {{-- Step 1: Index number --}}
            <div id="step-index" class="space-y-4">
                <div>
                    <label for="index_number" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <input type="text" id="index_number" name="index_number" required placeholder="e.g. BC/ITS/24/047" class="w-full px-3 py-2.5 rounded-[14px] bg-[#e0e5ec] text-gray-800 placeholder-gray-400 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff] focus:outline-none focus:ring-2 focus:ring-emerald-500" style="text-transform: uppercase;" autocomplete="off">
                </div>
                <div id="index-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="index-error-text"></div>
                    <p id="index-error-support-wrap" class="hidden mt-2 text-sm text-gray-500">
                        <a id="index-error-support" href="#" target="_blank" rel="noopener noreferrer" class="text-emerald-600 hover:text-emerald-700 font-medium">Get in touch</a>
                    </p>
                </div>
                <button type="button" id="btn-index" class="w-full py-2.5 px-4 text-sm font-semibold rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-gray-50 transition-colors">Continue</button>
            </div>

            {{-- Step 2: Name & Phone (only what's missing) --}}
            <div id="step-phone" class="space-y-4 hidden">
                <p class="text-sm text-gray-500" id="phone-step-message">Enter your full name and active phone number to receive a one-time code (e.g. 233XXXXXXXXX).</p>
                <div id="phone-name-wrap">
                    <label for="phone_name" class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                    <input type="text" id="phone_name" name="phone_name" placeholder="Your full name" class="w-full px-3 py-2.5 rounded-[14px] bg-[#e0e5ec] text-gray-800 placeholder-gray-400 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff] focus:outline-none focus:ring-2 focus:ring-emerald-500" autocomplete="name" style="text-transform: capitalize;">
                </div>
                <div id="phone-number-wrap">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone number</label>
                    <input type="tel" id="phone" name="phone" placeholder="233XXXXXXXXX" class="w-full px-3 py-2.5 rounded-[14px] bg-[#e0e5ec] text-gray-800 placeholder-gray-400 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff] focus:outline-none focus:ring-2 focus:ring-emerald-500" autocomplete="tel">
                </div>
                <div id="phone-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="phone-error-text"></div>
                </div>
                <button type="button" id="btn-send-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-[14px] text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors shadow-[4px_4px_10px_rgba(0,0,0,0.1),-2px_-2px_6px_rgba(255,255,255,0.6)] hover:shadow-[6px_6px_14px_rgba(0,0,0,0.12),-3px_-3px_8px_rgba(255,255,255,0.7)]">Send code</button>
                <button type="button" id="btn-back-to-index" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-800 bg-gray-100 hover:bg-gray-200 focus:outline-none transition-colors">← Back</button>
            </div>

            {{-- Step 3: OTP --}}
            <div id="step-otp" class="space-y-4 hidden">
                <p class="text-sm text-gray-500" id="otp-step-message">Enter the 6-digit code sent to your phone.</p>
                <div id="otp-code-fields" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code</label>
                        <div class="flex justify-center gap-2" id="otp-boxes-wrap">
                            @for($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" data-otp-index="{{ $i }}" autocomplete="off"
                                class="w-11 h-12 text-center text-xl font-semibold rounded-[14px] bg-[#e0e5ec] text-gray-800 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff] focus:outline-none focus:ring-2 focus:ring-emerald-500 otp-digit">
                            @endfor
                        </div>
                        <input type="hidden" id="otp_code" name="code" value="">
                    </div>
                    <div>
                        <label for="otp_name" class="block text-sm font-medium text-gray-700 mb-1">Your name</label>
                        <input type="text" id="otp_name" name="student_name" placeholder="Full name (required for first-time login)" class="w-full px-3 py-2.5 rounded-[14px] bg-[#e0e5ec] text-gray-800 placeholder-gray-400 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff] focus:outline-none focus:ring-2 focus:ring-emerald-500" autocomplete="name" style="text-transform: capitalize;">
                    </div>
                    <div id="otp-error" class="hidden">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="otp-error-text"></div>
                    </div>
                    <button type="button" id="btn-verify-otp" class="w-full py-2.5 px-4 text-sm font-semibold rounded-[14px] text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors shadow-[4px_4px_10px_rgba(0,0,0,0.1),-2px_-2px_6px_rgba(255,255,255,0.6)] hover:shadow-[6px_6px_14px_rgba(0,0,0,0.12),-3px_-3px_8px_rgba(255,255,255,0.7)]">Verify and sign in</button>
                    <p class="text-center text-sm text-gray-500">Didn't get the code? <button type="button" id="btn-resend-otp" class="text-emerald-600 hover:text-emerald-700 font-medium">Resend code</button></p>
                    <p id="otp-days-remaining" class="text-center text-sm text-gray-500 mt-1 hidden" aria-live="polite"></p>
                    <button type="button" id="btn-back-to-phone" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-800 bg-gray-100 hover:bg-gray-200 focus:outline-none transition-colors">← Back</button>
                </div>
            </div>

            {{-- Step 4: Password --}}
            <div id="step-password" class="space-y-4 hidden">
                <p class="text-sm text-gray-500" id="password-step-message">Enter your password to sign in.</p>
                <div>
                    <label for="password_index" class="block text-sm font-medium text-gray-700 mb-1">Index number</label>
                    <input type="text" id="password_index" readonly class="w-full px-3 py-2.5 rounded-[14px] bg-[#e0e5ec] text-gray-700 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff]" aria-readonly="true">
                </div>
                <div>
                    <label for="login_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="login_password" placeholder="Your password" class="w-full px-3 py-2.5 rounded-[14px] bg-[#e0e5ec] text-gray-800 placeholder-gray-400 shadow-[inset_4px_4px_8px_#c3c8d0,inset_-4px_-4px_8px_#ffffff] focus:outline-none focus:ring-2 focus:ring-emerald-500" autocomplete="current-password">
                </div>
                <div id="password-error" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800" id="password-error-text"></div>
                </div>
                <button type="button" id="btn-password-login" class="w-full py-2.5 px-4 text-sm font-semibold rounded-[14px] text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 transition-colors shadow-[4px_4px_10px_rgba(0,0,0,0.1),-2px_-2px_6px_rgba(255,255,255,0.6)] hover:shadow-[6px_6px_14px_rgba(0,0,0,0.12),-3px_-3px_8px_rgba(255,255,255,0.7)]">Sign in</button>
                <button type="button" id="btn-back-to-index-from-password" class="w-full py-2 px-4 text-sm font-medium rounded-lg text-gray-800 bg-gray-100 hover:bg-gray-200 focus:outline-none transition-colors">← Back</button>
            </div>
        </div>
        <p class="text-center mt-6">
            <a href="{{ route('student.landing') }}" class="text-sm text-gray-500 hover:text-gray-800 no-underline">← Back to home</a>
        </p>
    </div>
</div>

@push('scripts')
<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
    var stepIndex = document.getElementById('step-index');
    var stepPhone = document.getElementById('step-phone');
    var stepOtp = document.getElementById('step-otp');
    var stepPassword = document.getElementById('step-password');
    var indexInput = document.getElementById('index_number');
    var phoneInput = document.getElementById('phone');
    var otpInput = document.getElementById('otp_code');
    var nameInput = document.getElementById('otp_name');
    var phoneNameInput = document.getElementById('phone_name');
    var currentIndexNumber = '';
    var lastPhoneUsed = '';
    var stepHasName = false;
    var stepHasPhone = false;

    function showStep(step) {
        stepIndex.classList.add('hidden');
        stepPhone.classList.add('hidden');
        stepOtp.classList.add('hidden');
        if (stepPassword) stepPassword.classList.add('hidden');
        if (step === 'index') stepIndex.classList.remove('hidden');
        else if (step === 'phone') stepPhone.classList.remove('hidden');
        else if (step === 'otp') {
            stepOtp.classList.remove('hidden');
            initOtpBoxes();
        }
        else if (step === 'password' && stepPassword) {
            stepPassword.classList.remove('hidden');
            var msgEl = document.getElementById('password-step-message');
            if (msgEl) msgEl.textContent = 'Enter your password to sign in.';
            var idxEl = document.getElementById('password_index');
            if (idxEl) idxEl.value = currentIndexNumber;
            var pwdEl = document.getElementById('login_password');
            if (pwdEl) { pwdEl.value = ''; pwdEl.focus(); }
            var errWrap = document.getElementById('password-error');
            if (errWrap) errWrap.classList.add('hidden');
        }
    }

    // Initialize OTP fields when needed

    var whatsappNumber = '233552477942';
    function supportMessage(errorText, indexNumber) {
        var msg = 'Hi, I\'m having trouble with Docu Mento login. I got this message: ' + (errorText || '') + '.';
        if (indexNumber) msg += ' My index number: ' + indexNumber + '.';
        msg += ' Can you help?';
        return encodeURIComponent(msg);
    }
    function showError(elId, text) {
        var wrap = document.getElementById(elId);
        var textEl = document.getElementById(elId + '-text');
        if (!wrap || !textEl) return;
        textEl.textContent = text || '';
        wrap.classList.toggle('hidden', !text);
        var supportWrap = document.getElementById('index-error-support-wrap');
        var supportLink = document.getElementById('index-error-support');
        if (supportWrap && supportLink && elId === 'index-error') {
            if (text) {
                supportLink.href = 'https://wa.me/' + whatsappNumber + '?text=' + supportMessage(text, (indexInput && indexInput.value) ? indexInput.value.trim() : '');
                supportWrap.classList.remove('hidden');
            } else {
                supportWrap.classList.add('hidden');
            }
        }
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        btn.disabled = loading;
        btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
        btn.textContent = loading ? 'Please wait…' : (btn.dataset.originalText || 'Continue');
    }

    document.getElementById('btn-index').addEventListener('click', function() {
        var index = (indexInput && indexInput.value) ? indexInput.value.trim().toUpperCase() : '';
        if (!index) {
            showError('index-error', 'Please enter your index number.');
            return;
        }
        showError('index-error', '');
        setLoading(this, true);
        fetch('{{ route("student.account.verify-index") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ index_number: index })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-index'), false);
            if (!data.success) {
                showError('index-error', data.message || 'Verification failed. Please try again.');
                var btnIndex = document.getElementById('btn-index');
                if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
                return;
            }
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) btnIndex.dataset.originalText = 'Continue';
            currentIndexNumber = data.index_number || index;
            if (data.step === 'phone') {
                document.getElementById('phone-step-message').textContent = data.message || 'Enter your full name and active phone number to receive a one-time code.';
                stepHasName = !!data.has_name;
                stepHasPhone = !!data.has_phone;
                var phoneNameWrap = document.getElementById('phone-name-wrap');
                var phoneNumberWrap = document.getElementById('phone-number-wrap');
                if (phoneNameInput) { phoneNameInput.value = ''; }
                if (phoneInput) { phoneInput.value = ''; }
                if (phoneNameWrap) phoneNameWrap.style.display = stepHasName ? 'none' : '';
                if (phoneNumberWrap) phoneNumberWrap.style.display = stepHasPhone ? 'none' : '';
                showStep('phone');
            } else if (data.step === 'otp') {
                document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your phone.';
                if (data.can_resend) {
                    lastPhoneUsed = '__registered__';
                }
                if (data.has_name && nameInput) {
                    nameInput.closest('div').style.display = 'none';
                }
                var resendBtn = document.getElementById('btn-resend-otp');
                if (resendBtn) {
                    resendBtn.disabled = data.can_resend === false;
                    resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                        ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
                }
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl && data.days_remaining != null) {
                    daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                    daysEl.style.display = 'block';
                }
                showStep('otp');
            }
        })
        .catch(function() {
            setLoading(document.getElementById('btn-index'), false);
            showError('index-error', 'Network error. Please try again.');
            var btnIndex = document.getElementById('btn-index');
            if (btnIndex) { btnIndex.dataset.originalText = 'Try again'; btnIndex.textContent = 'Try again'; }
        });
    });

    document.getElementById('btn-back-to-index').addEventListener('click', function() {
        showStep('index');
        showError('phone-error', '');
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    if (indexInput) {
        indexInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var btn = document.getElementById('btn-index');
                if (btn && !btn.disabled) {
                    btn.click();
                }
            }
        });
    }

    function showPasswordError(text) {
        var wrap = document.getElementById('password-error');
        var textEl = document.getElementById('password-error-text');
        if (wrap && textEl) {
            textEl.textContent = text || '';
            wrap.classList.toggle('hidden', !text);
        }
    }

    document.getElementById('btn-back-to-index-from-password').addEventListener('click', function() {
        showStep('index');
        showPasswordError('');
    });

    document.getElementById('btn-password-login').addEventListener('click', function() {
        var pwd = document.getElementById('login_password');
        var password = (pwd && pwd.value) ? pwd.value : '';
        if (!currentIndexNumber) {
            showPasswordError('Session lost. Please enter your index number again.');
            return;
        }
        if (!password) {
            showPasswordError('Please enter your password.');
            return;
        }
        showPasswordError('');
        setLoading(this, true);
        this.dataset.originalText = this.textContent;
        fetch('{{ route("student.account.login-password") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ index_number: currentIndexNumber, password: password })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-password-login'), false);
            if (!data.success) {
                showPasswordError(data.message || 'Incorrect password. Please try again.');
                return;
            }
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(function() {
            setLoading(document.getElementById('btn-password-login'), false);
            showPasswordError('Network error. Please try again.');
        });
    });

    document.getElementById('btn-send-otp').addEventListener('click', function() {
        var fullName = phoneNameInput ? phoneNameInput.value.trim() : '';
        var phone = (phoneInput && phoneInput.value) ? phoneInput.value.trim() : '';
        if (!stepHasName && !fullName) {
            showError('phone-error', 'Please enter your full name.');
            return;
        }
        if (!stepHasPhone && !phone) {
            showError('phone-error', 'Please enter your phone number.');
            return;
        }
        showError('phone-error', '');
        setLoading(this, true);
        this.dataset.originalText = this.textContent;
        fetch('{{ route("student.account.send-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({ index_number: currentIndexNumber, phone: phone || undefined, student_name: fullName || undefined })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-send-otp'), false);
            if (!data.success) {
                showError('phone-error', data.message || 'We couldn\'t send the code. Please try again.');
                var sendBtn = document.getElementById('btn-send-otp');
                if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
                return;
            }
            lastPhoneUsed = phone;
            document.getElementById('otp-step-message').textContent = data.message || 'Enter the 6-digit code sent to your number.';
            // Hide name field if student already has a name
            if (data.has_name && nameInput) {
                nameInput.closest('div').style.display = 'none';
            }
            showStep('otp');
            showError('otp-error', '');
        })
        .catch(function() {
            setLoading(document.getElementById('btn-send-otp'), false);
            showError('phone-error', 'Network error. Please try again.');
            var sendBtn = document.getElementById('btn-send-otp');
            if (sendBtn) { sendBtn.dataset.originalText = 'Try again'; sendBtn.textContent = 'Try again'; }
        });
    });

    document.getElementById('btn-back-to-phone').addEventListener('click', function() {
        showStep('index');
        showError('otp-error', '');
        var sendBtn = document.getElementById('btn-send-otp');
        if (sendBtn) { sendBtn.dataset.originalText = 'Send code'; sendBtn.textContent = 'Send code'; }
    });

    document.getElementById('btn-resend-otp').addEventListener('click', function() {
        if (!currentIndexNumber) {
            showError('otp-error', 'Go back and enter your index number, then try again.');
            return;
        }
        var resendBtn = document.getElementById('btn-resend-otp');
        if (resendBtn.disabled) return;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Sending…';
        showError('otp-error', '');
        var payload = { index_number: currentIndexNumber };
        if (lastPhoneUsed && lastPhoneUsed !== '__registered__') {
            payload.phone = lastPhoneUsed;
        }
        fetch('{{ route("student.account.send-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('otp-step-message').textContent = data.message || 'A new code has been sent. Enter it above.';
                resendBtn.disabled = true;
                resendBtn.textContent = 'Resend available in ' + (data.days_remaining || 90) + ' day(s)';
                var daysEl = document.getElementById('otp-days-remaining');
                if (daysEl && data.days_remaining != null) {
                    daysEl.textContent = 'Valid for ' + data.days_remaining + ' more day(s).';
                    daysEl.style.display = 'block';
                }
            } else {
                resendBtn.disabled = data.can_resend === false;
                resendBtn.textContent = (data.can_resend === false && data.days_remaining != null)
                    ? 'Resend available in ' + data.days_remaining + ' day(s)' : 'Resend code';
                showError('otp-error', data.message || 'Could not resend. Please try again.');
            }
        })
        .catch(function() {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            showError('otp-error', 'Network error. Please try again.');
        });
    });

    function getOtpCode() {
        var boxes = document.querySelectorAll('.otp-digit');
        var code = '';
        for (var i = 0; i < (boxes.length || 6); i++) {
            if (boxes[i]) code += (boxes[i].value || '').trim();
        }
        return code;
    }
    function setOtpHidden(val) {
        var h = document.getElementById('otp_code');
        if (h) h.value = val;
    }
    function initOtpBoxes() {
        var boxes = document.querySelectorAll('.otp-digit');
        setOtpHidden('');
        boxes.forEach(function(b) { b.value = ''; });
        if (boxes[0]) boxes[0].focus();

        function syncAndMaybeSubmit() {
            var code = getOtpCode();
            setOtpHidden(code);
            if (code.length === 6) {
                var btn = document.getElementById('btn-verify-otp');
                if (btn && !btn.disabled) btn.click();
            }
        }
        boxes.forEach(function(box, i) {
            box.onkeydown = function(e) {
                if (/^[0-9]$/.test(e.key)) {
                    e.preventDefault();
                    this.value = e.key;
                    if (boxes[i + 1]) boxes[i + 1].focus();
                    else this.blur();
                    syncAndMaybeSubmit();
                    return;
                }
                if (e.key === 'Backspace' && !this.value && boxes[i - 1]) {
                    e.preventDefault();
                    boxes[i - 1].focus();
                }
            };
            box.oninput = function() {
                var v = this.value.replace(/\D/g, '').slice(0, 1);
                this.value = v;
                if (v && boxes[i + 1]) boxes[i + 1].focus();
                syncAndMaybeSubmit();
            };
            box.onpaste = function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                for (var j = 0; j < pasted.length && j < boxes.length; j++) {
                    boxes[j].value = pasted[j];
                }
                if (pasted.length > 0 && boxes[pasted.length - 1]) boxes[pasted.length - 1].focus();
                syncAndMaybeSubmit();
            };
        });
    }

    document.getElementById('btn-verify-otp').addEventListener('click', function() {
        var code = getOtpCode();
        if (!code || code.length !== 6) {
            showError('otp-error', 'Please enter the 6-digit code.');
            return;
        }
        if (nameInput && nameInput.closest('div') && nameInput.closest('div').style.display !== 'none' && !nameInput.value.trim()) {
            showError('otp-error', 'Please enter your full name.');
            return;
        }
        showError('otp-error', '');
        setLoading(this, true);
        this.dataset.originalText = this.textContent;
        var payload = { index_number: currentIndexNumber, code: code };
        if (nameInput && nameInput.value.trim()) payload.student_name = nameInput.value.trim();
        fetch('{{ route("student.account.verify-otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            setLoading(document.getElementById('btn-verify-otp'), false);
            if (!data.success) {
                showError('otp-error', data.message || 'Invalid or expired code.');
                return;
            }
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(function() {
            setLoading(document.getElementById('btn-verify-otp'), false);
            showError('otp-error', 'Network error. Please try again.');
        });
    });
})();
</script>
@endpush
@endsection
