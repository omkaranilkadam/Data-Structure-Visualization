// Toggle to registration form
document.querySelector('.register-link').addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelector('.loginpage').classList.add('active');
});

// Toggle back to login form
document.querySelector('.login-link').addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelector('.loginpage').classList.remove('active');
});
