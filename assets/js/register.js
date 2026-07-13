$(document).ready(function() {
    const $form = $('#registerForm');
    const $submitBtn = $('#submitBtn');
    const $btnText = $('#btnText');
    const $btnSpinner = $('#btnSpinner');

    // Toast Utility
    function showToast(message, title = 'Notification', type = 'info') {
        const toastEl = document.getElementById('statusToast');
        const toastBody = document.getElementById('toastBody');
        const toastTitle = document.getElementById('toastTitle');
        const toastIcon = document.getElementById('toastIcon');

        toastBody.textContent = message;
        toastTitle.textContent = title;

        // Reset classes
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

    // Input Validation Helpers
    function showError($input, message) {
        $input.addClass('is-invalid');
        $input.siblings('.invalid-feedback').text(message);
    }

    function clearErrors() {
        $form.find('.glass-input').removeClass('is-invalid');
        $form.find('.invalid-feedback').text('');
    }

    // Live validation triggers
    $form.find('.glass-input').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // Handle Form Submission
    $form.on('submit', function(e) {
        e.preventDefault();
        clearErrors();

        const username = $('#username').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        let isValid = true;

        // Client-side validations
        if (!username) {
            showError($('#username'), 'Username is required.');
            isValid = false;
        } else if (!/^[a-zA-Z0-9_]{3,30}$/.test(username)) {
            showError($('#username'), 'Username must be 3-30 characters, containing only letters, numbers, and underscores.');
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

        // Disable elements & Show loader
        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $btnSpinner.removeClass('d-none');

        // Submit via AJAX
        $.ajax({
            url: 'php/register.php',
            type: 'POST',
            data: {
                username: username,
                email: email,
                password: password,
                confirm_password: confirmPassword
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'Success', 'success');
                    
                    // Redirect to login page after successful registration
                    setTimeout(function() {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    showToast(response.message, 'Registration Failed', 'error');
                    
                    // If validation field errors are returned
                    if (response.field) {
                        showError($(`#${response.field}`), response.message);
                    }
                    
                    // Re-enable form
                    $submitBtn.prop('disabled', false);
                    $btnText.removeClass('d-none');
                    $btnSpinner.addClass('d-none');
                }
            },
            error: function(xhr, status, error) {
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
