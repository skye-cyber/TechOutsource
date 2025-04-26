// signup_validator.js
document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form");

    const username = document.getElementById("username");
    const email = document.getElementById("email");
    const phoneNumber = document.getElementById("phoneNumber");
    const country = document.getElementById("country");
    const password1 = document.getElementById("password1");
    const password2 = document.getElementById("password2");
    const emailError = document.getElementById("emailError");
    const phoneError = document.getElementById("phoneError");
    const passError = document.getElementById("passError");
    const errorModal = document.getElementById('errorModal');
    const modalTitle = document.getElementById('modalErrTitle');
    const modalMessage = document.getElementById('modalErrMessage');
    const closeErrModal = document.getElementById('closeErrModal');

    const shakeClass = 'animate-shake';

    form.addEventListener("submit", async function(e) {
        e.preventDefault();

        let errors = [];

        if (!username.value.trim()) {
            errors.push("Username is required.");
        }

        if (!email.value.trim()) {
            errors.push("Email is required.");
        } else if (!validateEmail(email.value.trim())) {
            triggerShake(email);
            showErrorModal("Validation Error", "Invalid Email format.", 5000);
            emailError.textContent = "Email format is invalid.";
            errors.push("Email format is invalid.");
        }

        if (!phoneNumber.value.trim()) {
            errors.push("Phone Number is required.");
        } else if (!validatePhone(phoneNumber.value.trim())) {
            triggerShake(phoneNumber);
            showErrorModal("Validation Error", "Invalid Phone number format.", 5000);
            phoneError.textContent = "Phone number format is invalid.";
            errors.push("Phone number format is invalid.");
        }

        if (!country.value.trim()) {
            errors.push("Country is required.");
        }

        if (!password1.value.trim() || !password2.value.trim()) {
            errors.push("Both password fields are required.");
        } else if (password1.value !== password2.value) {
            errors.push("Passwords do not match.");
            triggerShake(password2);
            showErrorModal('Validation Error', 'Passwords do not match.', 5000);
            passError.textContent = 'Passwords do not match.';
        } else if (password1.value.length < 6) {
            triggerShake(password1);
            passError.textContent = 'Password must be at least 6 characters long.';
            errors.push("Password must be at least 6 characters long.");
            showErrorModal('Validation Error', 'Password must be at least 6 characters long.', 5000);
        }

        if (errors.length > 0) return;

        const formData = new FormData(form);
        // Ensure that the form is submitting the necessary fields, including the 'register' flag if needed
        formData.append('register', '1');  // Add any extra data if required

        try {
            // Send data to the backend using AJAX (fetch)
            const resp = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await resp.json();

            if (data.status==="success") {
                // If registration is successful, redirect user based on PHP response
                window.location.href = data.redirect;
            } else {
                // Display error from PHP response
                showErrorModal("SignUp Error", data.message || 'SignUp failed', 5000);
                emailError.textContent = data.message || 'SignUp failed';
                console.log(data)
            }
        } catch (err) {
            console.error('SignUp AJAX error', err);
            showErrorModal("SignUp Error", err.message || 'Unexpected error', 5000);
            emailError.textContent = `Server error — ${err.message || 'Please try again.'}`;
        }

        function triggerShake(el) {
            el.classList.add(shakeClass);
            el.addEventListener('animationend', () => {
                el.classList.remove(shakeClass);
            }, { once: true });
        }
    });

    // Helper function to validate email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Helper function to validate phone number
    function validatePhone(phone) {
        const re = /^\+?\d{10,15}$/;
        return re.test(phone);
    }

    closeErrModal.addEventListener("click", function() {
        hideErrorModal();
    });

    function showErrorModal(title, message, timeout = null) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;

        errorModal.classList.remove("-translate-x-[100vw]", "pointer-events-none");
        errorModal.classList.add("-translate-x-0");

        if (timeout) {
            setTimeout(() => {
                hideErrorModal();
            }, timeout);
        }
    }

    function hideErrorModal() {
        setTimeout(() => {
            errorModal.classList.remove("-translate-x-0");
            errorModal.classList.add("-translate-x-[100vw]", "pointer-events-none");
        }, 310);
    }
});
