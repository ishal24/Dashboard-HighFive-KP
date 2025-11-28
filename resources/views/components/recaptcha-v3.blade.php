<head>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.v3.site') }}"></script>
</head>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof grecaptcha === 'undefined') {
        console.error('reCAPTCHA script failed to load');
        return;
    }

    function getToken(action) {
        return grecaptcha.execute('{{ config('services.recaptcha.v3.site') }}', { action });
    }

    // Attach on submit so tokens are fresh
    document.querySelectorAll('form[data-recaptcha-action]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            // If a token is already present (rare), skip
            const input = form.querySelector('input[name="recaptcha_token"]');
            if (!input) return;

            e.preventDefault();
            try {
                const action = form.getAttribute('data-recaptcha-action') || 'submit';
                const token = await getToken(action);
                input.value = token;
                form.submit();
            } catch (err) {
                console.error('reCAPTCHA v3 error', err);
                // Optional: show a friendly error or block submission
            }
        });
    });
});
</script>
<input type="hidden" name="recaptcha_token" value="">
