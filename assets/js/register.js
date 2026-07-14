$(document).ready(function() {
    const $form = $('#registerForm');
    const $submitBtn = $('#submitBtn');
    const $btnText = $('#btnText');
    const $btnSpinner = $('#btnSpinner');

    // ── Toast Utility ──────────────────────────────────────────
    function showToast(message, title = 'Notification', type = 'info') {
        const toastEl = document.getElementById('statusToast');
        const toastBody = document.getElementById('toastBody');
        const toastTitle = document.getElementById('toastTitle');
        const toastIcon = document.getElementById('toastIcon');

        toastBody.textContent = message;
        toastTitle.textContent = title;

        toastIcon.className = 'bi me-2';
        if (type === 'success') {
            toastIcon.classList.add('bi-check-circle-fill', 'text-success');
        } else if (type === 'error') {
            toastIcon.classList.add('bi-exclamation-triangle-fill', 'text-danger');
        } else {
            toastIcon.classList.add('bi-info-circle-fill', 'text-info');
        }

        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    // ── Validation Helpers ─────────────────────────────────────
    function showError($input, message) {
        $input.addClass('is-invalid');
        const feedbackId = $input.attr('id') + 'Error';
        const $fb = $('#' + feedbackId).length
            ? $('#' + feedbackId)
            : $input.siblings('.invalid-feedback');
        $fb.text(message);
    }

    function clearErrors() {
        $form.find('.glass-input').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    }

    $form.find('.glass-input').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // ── Show / Hide Password Toggles ───────────────────────────
    function bindToggle(btnId, iconId, inputId) {
        $('#' + btnId).on('click', function() {
            const $input = $('#' + inputId);
            const $icon  = $('#' + iconId);
            const isPass = $input.attr('type') === 'password';
            $input.attr('type', isPass ? 'text' : 'password');
            $icon.toggleClass('bi-eye', !isPass).toggleClass('bi-eye-slash', isPass);
        });
    }
    bindToggle('togglePassword', 'togglePasswordIcon', 'password');
    bindToggle('toggleConfirm',  'toggleConfirmIcon',  'confirm_password');

    // ── Password Strength Evaluator ────────────────────────────
    const strengthClasses = ['', 'weak', 'fair', 'good', 'strong'];
    const strengthLabels  = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColors  = ['', '#ef4444', '#f97316', '#eab308', '#22c55e'];

    function evaluateStrength(pwd) {
        let score = 0;
        if (pwd.length >= 8)                             score++;
        if (pwd.length >= 12)                            score++;
        if (/[A-Z]/.test(pwd) && /[a-z]/.test(pwd))    score++;
        if (/[0-9]/.test(pwd))                          score++;
        if (/[^A-Za-z0-9]/.test(pwd))                  score++;
        return Math.min(4, Math.max(0, Math.round(score * 4 / 5)));
    }

    $('#password').on('input', function() {
        const pwd = $(this).val();
        if (!pwd) {
            $('#strengthFill').attr('class', 'strength-fill');
            $('#strengthLabel').text('').css('color', '');
            return;
        }
        const level = evaluateStrength(pwd);
        $('#strengthFill').attr('class', 'strength-fill ' + (strengthClasses[level] || ''));
        $('#strengthLabel').text(strengthLabels[level] || '').css('color', strengthColors[level] || '');
    });

    // ── Form Submission ────────────────────────────────────────
    $form.on('submit', function(e) {
        e.preventDefault();
        clearErrors();

        const username        = $('#username').val().trim();
        const email           = $('#email').val().trim();
        const password        = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        let isValid = true;

        if (!username) {
            showError($('#username'), 'Username is required.');
            isValid = false;
        } else if (!/^[a-zA-Z0-9_]{3,30}$/.test(username)) {
            showError($('#username'), 'Username must be 3-30 characters (letters, numbers, underscores only).');
            isValid = false;
        }

        if (!email) {
            showError($('#email'), 'Email is required.');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showError($('#email'), 'Please enter a valid email address.');
            isValid = false;
        }

        if (!password) {
            showError($('#password'), 'Password is required.');
            isValid = false;
        } else if (password.length < 8) {
            showError($('#password'), 'Password must be at least 8 characters long.');
            isValid = false;
        }

        if (!confirmPassword) {
            showError($('#confirm_password'), 'Please confirm your password.');
            isValid = false;
        } else if (password !== confirmPassword) {
            showError($('#confirm_password'), 'Passwords do not match.');
            isValid = false;
        }

        if (!isValid) return;

        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $btnSpinner.removeClass('d-none');

        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            data: {
                username:         username,
                email:            email,
                password:         password,
                confirm_password: confirmPassword
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'Success', 'success');
                    setTimeout(function() {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showToast(response.message, 'Registration Failed', 'error');
                    if (response.field) {
                        showError($('#' + response.field), response.message);
                    }
                    $submitBtn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnSpinner.addClass('d-none');
                }
            },
            error: function(xhr) {
                let errorMsg = 'An unexpected error occurred on the server.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                showToast(errorMsg, 'Error', 'error');
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $btnSpinner.addClass('d-none');
            }
        });
    });
});
