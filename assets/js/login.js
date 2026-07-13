$(document).ready(function() {
    const $form = $('#loginForm');
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

        const email = $('#email').val().trim();
        const password = $('#password').val();
        const rememberMe = $('#remember_me').is(':checked');

        let isValid = true;

        // Client-side validations
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
        }

        if (!isValid) return;

        // Disable elements & Show loader
        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $btnSpinner.removeClass('d-none');

        // Submit via AJAX
        $.ajax({
            url: 'php/login.php',
            type: 'POST',
            data: {
                email: email,
                password: password,
                remember_me: rememberMe
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast(response.message, 'Success', 'success');
                    
                    // Redirect to profile page after successful authentication
                    setTimeout(function() {
                        window.location.href = 'profile.html';
                    }, 1500);
                } else {
                    showToast(response.message, 'Login Failed', 'error');
                    
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
