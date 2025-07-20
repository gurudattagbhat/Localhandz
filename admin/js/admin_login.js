// Simple captcha logic (client-side for demo; move to server for production)
function generateCaptcha() {
    return Math.floor(1000 + Math.random() * 9000).toString();
}

let currentCaptcha = generateCaptcha();
function setCaptcha() {
    document.getElementById('captcha-img').textContent = currentCaptcha;
}
setCaptcha();

document.getElementById('refresh-captcha').onclick = function() {
    currentCaptcha = generateCaptcha();
    setCaptcha();
};

document.getElementById('admin-login-form').onsubmit = function(e) {
    e.preventDefault();
    const username = document.getElementById('admin-username').value.trim();
    const password = document.getElementById('admin-password').value;
    const captcha = document.getElementById('admin-captcha').value.trim();
    const errorDiv = document.getElementById('login-error');
    errorDiv.style.display = 'none';
    if (captcha !== currentCaptcha) {
        errorDiv.textContent = 'Captcha is incorrect.';
        errorDiv.style.display = 'block';
        currentCaptcha = generateCaptcha();
        setCaptcha();
        return;
    }
    fetch('admin_api.php?action=login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&captcha=${encodeURIComponent(captcha)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'admin_dashboard.html';
        } else {
            errorDiv.textContent = data.message || 'Login failed.';
            errorDiv.style.display = 'block';
            currentCaptcha = generateCaptcha();
            setCaptcha();
        }
    })
    .catch(() => {
        errorDiv.textContent = 'Server error.';
        errorDiv.style.display = 'block';
        currentCaptcha = generateCaptcha();
        setCaptcha();
    });
}; 