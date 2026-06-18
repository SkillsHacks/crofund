<?php
?>
<div id="cookie-consent" class="fixed bottom-0 left-0 right-0 bg-gray-900 text-white p-4 z-50" style="display: none;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-sm">
                We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.
                <a href="<?php echo BASE_URL; ?>/cookies.php" class="underline">Learn more</a>
            </div>
            <div class="flex gap-4">
                <button onclick="acceptCookies()" class="bg-emerald-600 px-4 py-2 rounded-lg text-sm hover:bg-emerald-700">
                    Accept
                </button>
                <button onclick="rejectCookies()" class="bg-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-600">
                    Reject
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function checkCookieConsent() {
    return localStorage.getItem('cookie_consent');
}

function showCookieConsent() {
    document.getElementById('cookie-consent').style.display = 'block';
}

function acceptCookies() {
    localStorage.setItem('cookie_consent', 'accepted');
    document.getElementById('cookie-consent').style.display = 'none';
}

function rejectCookies() {
    localStorage.setItem('cookie_consent', 'rejected');
    document.getElementById('cookie-consent').style.display = 'none';
}

// Show banner if consent not given
if (!checkCookieConsent()) {
    showCookieConsent();
}
</script>