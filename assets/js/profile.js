$(document).ready(function() {
    const $form = $('#profileForm');
    const $submitBtn = $('#submitBtn');
    const $btnText = $('#btnText');
    const $btnSpinner = $('#btnSpinner');
    const $loadingOverlay = $('#loadingOverlay');

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

    // Load Profile Data on Page Load
    function loadProfile() {
        $.ajax({
            url: 'php/profile.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const profile = response.profile;

                    // Update display components
                    $('#displayName').text(profile.name || 'Anonymous User');
                    $('#displayUsername').text(profile.username);
                    $('#displayEmail').text(profile.email);
                    $('#displayAge').text(profile.age ? `${profile.age} years old` : 'Not specified');
                    $('#displayBio').text(profile.bio || 'No biography written yet.');

                    // Update form values
                    $('#name').val(profile.name);
                    $('#age').val(profile.age);
                    $('#bio').val(profile.bio);

                    // Render Interests Badges
                    const interests = profile.interests || [];
                    if (interests.length > 0) {
                        const badges = interests.map(item => `<span class="interest-badge"><i class="bi bi-tag-fill me-1"></i>${item}</span>`).join('');
                        $('#displayInterests').html(badges);
                        $('#interests').val(interests.join(', '));
                    } else {
                        $('#displayInterests').html('<span class="text-secondary fst-italic">None specified yet.</span>');
                        $('#interests').val('');
                    }

                    // Hide Loading Overlay
                    $loadingOverlay.fadeOut(400);
                } else {
                    // Unauthorized or server error, redirect
                    window.location.href = 'login.html';
                }
            },
            error: function(xhr, status, error) {
                // If unauthorized HTTP status is returned
                if (xhr.status === 401) {
                    window.location.href = 'login.html';
                } else {
                    showToast('Failed to load profile details.', 'Error', 'error');
                    $loadingOverlay.fadeOut(400);
                }
            }
        });
    }

    // Initialize Profile Load
    loadProfile();

    // Live validation input triggers
    $('#age').on('input', function() {
        $(this).removeClass('is-invalid');
    });

    // Handle Edit Form Submission
    $form.on('submit', function(e) {
        e.preventDefault();
        $('#age').removeClass('is-invalid');

        const name = $('#name').val().trim();
        const age = $('#age').val().trim();
        const bio = $('#bio').val().trim();
        const interests = $('#interests').val().trim();

        // Client-side validation
        if (age !== '') {
            const ageNum = parseInt(age, 10);
            if (isNaN(ageNum) || ageNum < 0 || ageNum > 150) {
                $('#age').addClass('is-invalid').siblings('.invalid-feedback').text('Age must be a number between 0 and 150.');
                return;
            }
        }

        // Disable elements & Show loader
        $submitBtn.prop('disabled', true);
        $btnText.addClass('d-none');
        $btnSpinner.removeClass('d-none');

        // Submit via AJAX
        $.ajax({
            url: 'php/profile.php',
            type: 'POST',
            data: {
                name: name,
                age: age,
                bio: bio,
                interests: interests
            },
            dataType: 'json',
            success: function(response) {
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $btnSpinner.addClass('d-none');

                if (response.status === 'success') {
                    showToast(response.message, 'Success', 'success');
                    
                    // Reload display values in background
                    loadProfile();
                } else {
                    showToast(response.message, 'Update Failed', 'error');
                }
            },
            error: function(xhr, status, error) {
                showToast('An unexpected server error occurred.', 'Error', 'error');
                $submitBtn.prop('disabled', false);
                $btnText.removeClass('d-none');
                $btnSpinner.addClass('d-none');
            }
        });
    });

    // Handle Logout
    function handleLogout() {
        $.ajax({
            url: 'php/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                window.location.href = 'login.html';
            },
            error: function() {
                // If endpoint fails, redirect user anyway
                window.location.href = 'login.html';
            }
        });
    }

    $('#logoutBtn, #logoutNavbarBtn').on('click', function(e) {
        e.preventDefault();
        handleLogout();
    });
});
