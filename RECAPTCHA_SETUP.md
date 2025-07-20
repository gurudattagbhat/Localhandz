# reCAPTCHA Setup Instructions

## Overview
This project uses Google reCAPTCHA v3 for enhanced security on login forms. You need to obtain your own reCAPTCHA keys and configure them in the project.

## Step 1: Get reCAPTCHA Keys

1. Go to [Google reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
2. Click "Create" or "+" to add a new site
3. Fill in the form:
   - **Label**: Your project name (e.g., "Local Handz")
   - **reCAPTCHA type**: Choose "reCAPTCHA v3"
   - **Domains**: Add your domains:
     - `localhost` (for local development)
     - `yourdomain.com` (for production)
4. Accept the terms and click "Submit"
5. Copy the **Site Key** and **Secret Key**

## Step 2: Configure the Keys

### Frontend Configuration (login.html)

Replace `YOUR_RECAPTCHA_SITE_KEY` in these locations:

**File: `login.html`**
```html
<!-- Line ~108: Replace in script tag -->
<script src="https://www.google.com/recaptcha/api.js?render=YOUR_RECAPTCHA_SITE_KEY"></script>

<!-- Line ~167: Replace in JavaScript -->
const recaptchaToken = await grecaptcha.execute('YOUR_RECAPTCHA_SITE_KEY', { action: 'login' });
```

### Backend Configuration (login.php)

Replace `YOUR_RECAPTCHA_SECRET_KEY` in this location:

**File: `login.php`**
```php
// Line ~57: Replace the secret key
$recaptchaSecret = 'YOUR_RECAPTCHA_SECRET_KEY';
```

## Step 3: Test the Setup

1. Open your application in a web browser
2. Try to log in with valid credentials
3. Check browser console for any reCAPTCHA errors
4. Verify that login works correctly

## Step 4: Production Considerations

### Security Best Practices
- Never commit your actual keys to version control
- Use environment variables in production:
  ```php
  $recaptchaSecret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? 'YOUR_RECAPTCHA_SECRET_KEY';
  ```
- Consider using a configuration file that's excluded from git

### Troubleshooting

**Common Issues:**

1. **"Invalid site key" error**
   - Verify the site key is correct
   - Check that your domain is registered in reCAPTCHA admin

2. **"Invalid secret key" error**
   - Verify the secret key is correct in PHP file
   - Ensure you're using the secret key (not site key) in backend

3. **Score too low (< 0.5)**
   - This is normal for new sites/users
   - You can adjust the score threshold in `login.php` line 61
   - Consider lowering to 0.3 for testing: `$recaptchaData['score'] < 0.3`

4. **Network errors**
   - Check firewall settings
   - Ensure your server can make outbound HTTPS requests

### Testing Score Thresholds

reCAPTCHA v3 returns a score from 0.0 to 1.0:
- **1.0**: Very likely a good interaction
- **0.5**: Default threshold (recommended)
- **0.0**: Very likely a bot

You can adjust the threshold in `login.php`:
```php
// More strict (fewer false positives)
if (!$recaptchaData['success'] || $recaptchaData['score'] < 0.7) {

// More lenient (fewer false negatives)  
if (!$recaptchaData['success'] || $recaptchaData['score'] < 0.3) {
```

## Files Modified for reCAPTCHA

The following files contain reCAPTCHA configuration that needs to be updated:

1. `login.html` - Contains site key in 2 locations
2. `login.php` - Contains secret key validation

## Additional Security

Consider implementing these additional security measures:

1. **Rate Limiting**: Limit login attempts per IP
2. **CSRF Protection**: Add CSRF tokens to forms
3. **Account Lockout**: Lock accounts after failed attempts
4. **2FA**: Implement two-factor authentication for admin accounts

## Resources

- [reCAPTCHA v3 Documentation](https://developers.google.com/recaptcha/docs/v3)
- [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
- [Score Interpretation Guide](https://developers.google.com/recaptcha/docs/v3#interpreting_the_score)
