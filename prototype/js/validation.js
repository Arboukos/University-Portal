document.addEventListener('DOMContentLoaded', function() {
    // Validate registration form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', validateRegistration);
    }
    
    // Validate login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', validateLogin);
    }
    
    // Real-time password confirmation validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordInput = document.getElementById('password');
    
    if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (confirmPasswordInput.value && confirmPasswordInput.value !== this.value) {
                confirmPasswordInput.setCustomValidity('Passwords do not match');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });
    }
});

function validateRegistration(event) {
    const form = event.target;
    const username = form.querySelector('#username').value.trim();
    const email = form.querySelector('#email').value.trim();
    const password = form.querySelector('#password').value;
    const confirmPassword = form.querySelector('#confirm_password').value;
    const roleCode = form.querySelector('input[name="role_code"]:checked');
    
    let isValid = true;
    let errorMessage = '';
    
    // Validate username
    if (username.length < 3) {
        errorMessage = 'Username must be at least 3 characters long';
        isValid = false;
    }
    
    // Validate email
    if (!isValidEmail(email)) {
        errorMessage = 'Please enter a valid email address';
        isValid = false;
    }
    
    // Validate password
    if (password.length < 6) {
        errorMessage = 'Password must be at least 6 characters long';
        isValid = false;
    }
    
    // Validate password confirmation
    if (password !== confirmPassword) {
        errorMessage = 'Passwords do not match';
        isValid = false;
    }
    
    // Validate role selection
    if (!roleCode) {
        errorMessage = 'Please select your role (Student or Professor)';
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
        showValidationError(errorMessage);
        return false;
    }
    
    return true;
}

function validateLogin(event) {
    const form = event.target;
    const email = form.querySelector('#email').value.trim();
    const password = form.querySelector('#password').value;
    
    let isValid = true;
    let errorMessage = '';
    
    // Validate email
    if (!email) {
        errorMessage = 'Email is required';
        isValid = false;
    } else if (!isValidEmail(email)) {
        errorMessage = 'Please enter a valid email address';
        isValid = false;
    }
    
    // Validate password
    if (!password) {
        errorMessage = 'Password is required';
        isValid = false;
    }
    
    if (!isValid) {
        event.preventDefault();
        showValidationError(errorMessage);
        return false;
    }
    
    return true;
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}


function showValidationError(message) {
    // Check if error alert already exists
    let existingAlert = document.querySelector('.alert-error');
    
    if (existingAlert) {
        existingAlert.textContent = message;
        return;
    }
    
    // Create new error alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.textContent = message;
    
    // Insert at the beginning of the form
    const form = document.querySelector('.auth-form');
    if (form) {
        form.parentElement.insertBefore(alert, form);
        
        // Scroll to alert
        alert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Remove alert after 5 seconds
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.parentElement.removeChild(alert);
                }
            }, 300);
        }, 5000);
    }
}


document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-group input');
    
    inputs.forEach(input => {
        // Add focus styling
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        // Remove focus styling
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
        
        // Add filled class if input has value
        input.addEventListener('input', function() {
            if (this.value) {
                this.parentElement.classList.add('filled');
            } else {
                this.parentElement.classList.remove('filled');
            }
        });
        
        // Check initial value
        if (input.value) {
            input.parentElement.classList.add('filled');
        }
    });
});