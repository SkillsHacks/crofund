<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/config/Mail.php';
require_once __DIR__ . '/config/PhoneRestrictionUtils.php';
require_once __DIR__ . '/config/PhoneValidationService.php';
require_once __DIR__ . '/config/PhoneVerificationService.php';
require_once __DIR__ . '/config/EmailValidationService.php';
require_once __DIR__ . '/config/EmailVerificationService.php';
require_once __DIR__ . '/config/location/LocationService.php';
$countries = require_once __DIR__ . '/config/countries.php';
$citiesByCountry = require_once __DIR__ . '/config/location/cities.php';
$locationService = new LocationService($countries, $citiesByCountry);
$phoneRestrictions = require_once __DIR__ . '/config/phone_restrictions.php';
$phoneRules = require_once __DIR__ . '/config/phone_rules.php';
$emailRestrictions = require_once __DIR__ . '/config/email_restrictions.php';
$phoneRuleMap = [];

foreach ($countries as $country) {
    $isoCode = strtoupper($country['code']);
    $rule = $phoneRules[$isoCode] ?? $phoneRules['_default'];
    $phoneRuleMap[$isoCode] = [
        'iso_code' => $isoCode,
        'dial_code' => $country['dial_code'],
        'placeholder' => trim(sprintf('Enter phone number e.g. %s %s', $country['dial_code'], $rule['example'] ?? '123 456 789')),
        'example' => $rule['example'] ?? '123 456 789',
        'grouping' => $rule['grouping'] ?? [3, 3, 3, 3],
        'min_length' => $rule['min_length'] ?? 7,
        'max_length' => $rule['max_length'] ?? 12,
        'strip_leading_zero' => $rule['strip_leading_zero'] ?? true,
        'allowed_prefixes' => $rule['allowed_prefixes'] ?? [],
        'risk_level' => $rule['risk_level'] ?? 'medium',
    ];
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$unsupportedDialCodes = $phoneRestrictions['unsupported_dial_codes'] ?? [];
$unsupportedCountryMessage = $phoneRestrictions['unsupported_country_message'] ?? 'Our services are currently not available in the selected country.';
$allowNonFixedVoip = (bool) ($phoneRestrictions['allow_nonfixedvoip'] ?? true);
$phoneValidationService = new PhoneValidationService($phoneRules, $unsupportedDialCodes, $unsupportedCountryMessage, $allowNonFixedVoip);
$phoneVerificationService = new PhoneVerificationService($phoneValidationService);
$emailValidationService = new EmailValidationService($emailRestrictions);
$emailVerificationService = new EmailVerificationService($emailValidationService);

// Clear temporary registration session data on initial page load
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
if (!$is_ajax && !$is_post) {
    unset($_SESSION['registration_otp']);
    unset($_SESSION['email_verified']);
    unset($_SESSION['verified_email']);
    unset($_SESSION['email_verification_risk']);
    unset($_SESSION['pending_verified_email']);
    unset($_SESSION['phone_verified']);
    unset($_SESSION['verified_phone']);
    unset($_SESSION['phone_verification_risk']);
    unset($_SESSION['pending_phone_country_code']);
    unset($_SESSION['pending_verified_phone']);
}

$error = null;
$user = new User();

if ($user->isLoggedIn()) {
    header("Location: dashboard");
    exit();
}

// Handle OTP-related requests via AJAX
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'validate_email') {
                $email = trim($_POST['email'] ?? '');
                $deviceId = trim($_POST['device_id'] ?? 'web-browser');
                $validationResult = $emailVerificationService->validateEmailForRegistration($email, $deviceId, $_SERVER['REMOTE_ADDR']);

                if (!$validationResult['success']) {
                    echo json_encode([
                        'valid' => false,
                        'error' => $validationResult['error'],
                        'validation' => $validationResult['validation'] ?? null,
                        'risk' => $validationResult['risk'] ?? null,
                    ]);
                    exit;
                }

                $existingUser = $user->getUserByEmail($validationResult['validation']['email']);
                if ($existingUser) {
                    echo json_encode([
                        'valid' => false,
                        'error' => ($emailRestrictions['public_messages']['already_registered'] ?? 'Email already registered'),
                    ]);
                    exit;
                }

                echo json_encode([
                    'valid' => true,
                    'message' => 'Email address looks good.',
                    'validation' => $validationResult['validation'],
                    'risk' => $validationResult['risk'],
                ]);
                exit;

            } elseif ($_POST['action'] === 'send_otp') {
                $email = trim($_POST['email'] ?? '');
                $deviceId = trim($_POST['device_id'] ?? 'web-browser');
                $validationResult = $emailVerificationService->validateEmailForRegistration($email, $deviceId, $_SERVER['REMOTE_ADDR']);
                if (!$validationResult['success']) {
                    throw new Exception($validationResult['error'] ?? 'We cannot verify this email address. Please try another email.');
                }

                $email = $validationResult['validation']['email'];
                $otpPermission = $emailVerificationService->canRequestOtp($email, $deviceId, $_SERVER['REMOTE_ADDR']);
                if (!$otpPermission['allowed']) {
                    throw new Exception($otpPermission['message']);
                }

                $existingUser = $user->getUserByEmail($email);
                if ($existingUser) {
                    throw new Exception($emailRestrictions['public_messages']['already_registered'] ?? 'Email already registered');
                }
                
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', time() + 3600);
                
                $_SESSION['registration_otp'] = [
                    'email' => $email,
                    'otp' => $otp,
                    'expires_at' => $expiresAt,
                    'attempts' => 0
                ];
                $_SESSION['email_verification_risk'] = (int) ($validationResult['risk']['score'] ?? 0);
                $_SESSION['pending_verified_email'] = $email;

                $emailVerificationService->logOtpRequest($email, $deviceId, $_SERVER['REMOTE_ADDR'], (int) ($validationResult['risk']['score'] ?? 0));
                
                $mailer = new Mail();
                $mailer->sendVerificationCode($email, $otp);
                
                echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
                exit;
                
            } elseif ($_POST['action'] === 'verify_otp') {
                $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
                $otp = trim($_POST['otp']);
                $deviceId = trim($_POST['device_id'] ?? 'web-browser');
                
                if (!$email) {
                    throw new Exception('Invalid email');
                }

                if (!isset($_SESSION['pending_verified_email']) || $_SESSION['pending_verified_email'] !== $email) {
                    throw new Exception('OTP expired or invalid. Please request a new one.');
                }
                
                if (!isset($_SESSION['registration_otp']) || $_SESSION['registration_otp']['email'] !== $email) {
                    throw new Exception('OTP expired or invalid. Please request a new one.');
                }
                
                $otpData = $_SESSION['registration_otp'];
                
                if ($otpData['attempts'] >= 5) {
                    unset($_SESSION['registration_otp']);
                    $emailVerificationService->logOtpAttempt($email, $deviceId, $_SERVER['REMOTE_ADDR'], false);
                    throw new Exception('Too many verification attempts. Please request a new OTP.');
                }
                
                if (strtotime($otpData['expires_at']) < time()) {
                    unset($_SESSION['registration_otp']);
                    $emailVerificationService->logOtpAttempt($email, $deviceId, $_SERVER['REMOTE_ADDR'], false);
                    throw new Exception('OTP expired. Please request a new one.');
                }
                
                $_SESSION['registration_otp']['attempts']++;
                
                if ($otpData['otp'] !== $otp) {
                    $emailVerificationService->logOtpAttempt($email, $deviceId, $_SERVER['REMOTE_ADDR'], false);
                    throw new Exception('Invalid OTP. Please try again.');
                }
                
                $emailVerificationService->logOtpAttempt($email, $deviceId, $_SERVER['REMOTE_ADDR'], true);
                $_SESSION['email_verified'] = true;
                $_SESSION['verified_email'] = $email;
                
                echo json_encode(['success' => true, 'message' => 'Email verified successfully']);
                exit;
                
            } elseif ($_POST['action'] === 'check_phone') {
                $phone = trim($_POST['phone'] ?? '');
                $countryCode = strtoupper(trim($_POST['country_code'] ?? ''));
                $deviceId = trim($_POST['device_id'] ?? 'web-browser');
                $validationResult = $phoneVerificationService->validatePhoneForVerification($phone, $countryCode, $deviceId, $_SERVER['REMOTE_ADDR']);

                if (!$validationResult['success']) {
                    echo json_encode([
                        'available' => false,
                        'error' => $validationResult['error'],
                        'validation' => $validationResult['validation'] ?? null,
                        'risk' => $validationResult['risk'] ?? null,
                    ]);
                    exit;
                }

                $normalizedPhone = $validationResult['validation']['normalized_phone'];
                
                // Check if phone already exists
                $db = Database::getInstance();
                $existing = $db->fetchOne(
                    "SELECT id FROM users WHERE phone = ?",
                    [$normalizedPhone]
                );
                
                if ($existing) {
                    echo json_encode(['available' => false, 'error' => 'Phone number already registered']);
                    exit;
                }
                
                echo json_encode([
                    'available' => true,
                    'message' => 'Phone number is available',
                    'validation' => $validationResult['validation'],
                    'risk' => $validationResult['risk'],
                ]);
                exit;
            } elseif ($_POST['action'] === 'send_phone_otp') {
                $phone = trim($_POST['phone'] ?? '');
                $countryCode = strtoupper(trim($_POST['country_code'] ?? ''));
                $deviceId = trim($_POST['device_id'] ?? 'web-browser');
                $validationResult = $phoneVerificationService->validatePhoneForVerification($phone, $countryCode, $deviceId, $_SERVER['REMOTE_ADDR']);

                if (!$validationResult['success']) {
                    throw new Exception($validationResult['error'] ?? 'We are unable to verify this number. Please try another number.');
                }

                $normalizedPhone = $validationResult['validation']['normalized_phone'];
                $otpPermission = $phoneVerificationService->canRequestOtp($normalizedPhone, $deviceId, $_SERVER['REMOTE_ADDR']);

                if (!$otpPermission['allowed']) {
                    throw new Exception($otpPermission['message']);
                }

                $otpSendResult = $phoneVerificationService->sendOtp($normalizedPhone);
                if (!$otpSendResult['success']) {
                    throw new Exception($otpSendResult['error']);
                }

                $riskScore = (int) ($validationResult['risk']['score'] ?? 0);
                $phoneVerificationService->logOtpRequest($normalizedPhone, $deviceId, $_SERVER['REMOTE_ADDR'], $riskScore);

                $_SESSION['phone_verification_risk'] = $riskScore;
                $_SESSION['pending_phone_country_code'] = $countryCode;
                $_SESSION['pending_verified_phone'] = $normalizedPhone;

                echo json_encode([
                    'success' => true,
                    'message' => $otpSendResult['message'],
                    'phone' => $normalizedPhone,
                ]);
                exit;
            } elseif ($_POST['action'] === 'verify_phone_otp') {
                $phone = trim($_POST['phone'] ?? '');
                $code = trim($_POST['otp'] ?? '');
                $deviceId = trim($_POST['device_id'] ?? 'web-browser');

                if ($phone === '' || $code === '') {
                    throw new Exception('Please enter the verification code.');
                }

                if (!isset($_SESSION['pending_verified_phone']) || $_SESSION['pending_verified_phone'] !== $phone) {
                    throw new Exception('Please request a new verification code and try again.');
                }

                $verifyResult = $phoneVerificationService->verifyOtp($phone, $code);
                $phoneVerificationService->logOtpAttempt($phone, $deviceId, $_SERVER['REMOTE_ADDR'], $verifyResult['success']);

                if (!$verifyResult['success']) {
                    throw new Exception($verifyResult['error']);
                }

                $_SESSION['phone_verified'] = true;
                $_SESSION['verified_phone'] = $phone;

                echo json_encode([
                    'success' => true,
                    'message' => $verifyResult['message'],
                ]);
                exit;
            }
        } catch (Exception $e) {
            error_log('Register phone verification error: ' . $e->getMessage());
            $message = $e->getMessage();
            if (
                stripos($message, 'SQLSTATE') !== false ||
                stripos($message, 'Fatal error') !== false ||
                stripos($message, 'Stack trace') !== false ||
                stripos($message, 'SMTP') !== false ||
                stripos($message, 'Mailer Error') !== false ||
                stripos($message, 'Failed to connect') !== false
            ) {
                $message = 'We could not complete that request right now. Please try again in a few minutes.';
            }
            echo json_encode(['success' => false, 'error' => $message]);
            exit;
        }
    }
    
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if email is verified
        if (!isset($_SESSION['email_verified']) || !$_SESSION['email_verified']) {
            throw new Exception('Please verify your email first');
        }
        
        if ($_POST['email'] !== $_SESSION['verified_email']) {
            throw new Exception('Email mismatch. Please verify your email again');
        }
        
        if ($_POST['password'] !== $_POST['password_confirmation']) {
            throw new Exception("Passwords do not match");
        }

        if (!isset($_POST['terms'])) {
            throw new Exception("You must accept the Terms of Service and Privacy Policy");
        }
        
        if (!isset($_POST['account_type']) || !in_array($_POST['account_type'], ['personal', 'organization'])) {
            throw new Exception("Please select an account type");
        }

        if (empty($_POST['nin'])) {
            throw new Exception("National ID Number is required");
        }
        
        $nin = trim($_POST['nin']);
        if (strlen($nin) !== 14) {
            throw new Exception("National ID Number must be exactly 14 characters");
        }
        
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]{13}$/', $nin)) {
            throw new Exception("National ID Number must start with a letter and contain only letters and numbers");
        }
        
        $submittedPhone = trim($_POST['phone'] ?? '');
        if ($submittedPhone === '' && !empty($_SESSION['verified_phone'])) {
            $submittedPhone = trim((string) $_SESSION['verified_phone']);
        }

        $phoneCountryCode = strtoupper(trim($_POST['country_code'] ?? ''));
        if ($phoneCountryCode === '' && !empty($_SESSION['pending_phone_country_code'])) {
            $phoneCountryCode = strtoupper(trim((string) $_SESSION['pending_phone_country_code']));
        }

        if (!isset($_SESSION['phone_verified']) || !$_SESSION['phone_verified']) {
            throw new Exception('Please verify your phone number first');
        }

        if (!isset($_SESSION['verified_phone']) || $_SESSION['verified_phone'] === '') {
            throw new Exception('Please verify your phone number first');
        }

        $deviceId = trim($_POST['device_id'] ?? 'web-browser');
        $phoneValidationResult = $phoneVerificationService->validatePhoneForVerification($submittedPhone, $phoneCountryCode, $deviceId, $_SERVER['REMOTE_ADDR']);
        $normalizedPhone = $phoneValidationResult['validation']['normalized_phone'] ?? $submittedPhone;

        if ($normalizedPhone === '') {
            throw new Exception('Please enter a valid phone number for the selected country.');
        }

        if ($_SESSION['verified_phone'] !== $normalizedPhone) {
            throw new Exception('Phone mismatch. Please verify your phone number again');
        }
        
        // Check phone uniqueness
        $db = Database::getInstance();
        $existingPhone = $db->fetchOne(
            "SELECT id FROM users WHERE phone = ?",
            [$normalizedPhone]
        );
        
        if ($existingPhone) {
            throw new Exception('Phone number already registered');
        }

        $countryCode = strtoupper(trim($_POST['country'] ?? ''));
        $nationality = trim($_POST['nationality'] ?? '');
        $submittedCity = trim($_POST['city'] ?? '');

        if (!$locationService->isValidCountry($countryCode)) {
            throw new Exception('Please select a valid country.');
        }

        $selectedCountry = $locationService->getCountryByCode($countryCode);
        if (!$selectedCountry) {
            throw new Exception('Please select a valid country.');
        }

        if ($nationality === '') {
            throw new Exception('Please select your nationality.');
        }

        if (strcasecmp($selectedCountry['nationality'] ?? '', $nationality) !== 0) {
            throw new Exception('Selected nationality does not match the selected country.');
        }

        $normalizedCity = $locationService->normalizeCityForCountry($countryCode, $submittedCity);
        if ($normalizedCity === null) {
            throw new Exception('Please select a valid city for the selected country.');
        }

        $userData = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'phone' => $normalizedPhone,
            'phone_country_code' => $phoneCountryCode,
            'nin' => $_POST['nin'] ?? '',
            'country' => $countryCode,
            'nationality' => $selectedCountry['nationality'] ?? $nationality,
            'city' => $normalizedCity,
            'newsletter' => isset($_POST['newsletter']),
            'avatar' => $_FILES['avatar'] ?? null,
            'account_type' => $_POST['account_type'] ?? 'personal',
            'organization_name' => $_POST['organization_name'] ?? null,
            'organization_type' => $_POST['organization_type'] ?? null,
            'registration_number' => $_POST['registration_number'] ?? null,
            'organization_website' => $_POST['organization_website'] ?? null,
            'organization_contact' => $_POST['organization_contact'] ?? null,
            'is_phone_verified' => true,
            'phone_verified_at' => date('Y-m-d H:i:s'),
            'phone_risk_score' => (int) ($_SESSION['phone_verification_risk'] ?? ($phoneValidationResult['risk']['score'] ?? 0)),
            'phone_verification_status' => 'verified'
        ];

        $registrationData = $user->register($userData);

        $mailer = new Mail();
        
        try {
            $mailer->sendWelcomeEmail($registrationData['email'], $userData['name']);
            
            try {
                $mailer->sendAdminRegistrationNotification($userData);
            } catch (Exception $adminMailException) {
                error_log("Failed to send admin registration notification: " . $adminMailException->getMessage());
            }
            
            // Clear registration session data
            unset($_SESSION['registration_otp']);
            unset($_SESSION['email_verified']);
            unset($_SESSION['verified_email']);
            unset($_SESSION['email_verification_risk']);
            unset($_SESSION['pending_verified_email']);
            unset($_SESSION['phone_verified']);
            unset($_SESSION['verified_phone']);
            unset($_SESSION['phone_verification_risk']);
            unset($_SESSION['pending_phone_country_code']);
            unset($_SESSION['pending_verified_phone']);
            
            $_SESSION['success'] = "Registration successful! You can now log in to your account.";
        } catch (Exception $e) {
            error_log("Failed to send welcome email: " . $e->getMessage());
            
            // Clear registration session data
            unset($_SESSION['registration_otp']);
            unset($_SESSION['email_verified']);
            unset($_SESSION['verified_email']);
            unset($_SESSION['email_verification_risk']);
            unset($_SESSION['pending_verified_email']);
            unset($_SESSION['phone_verified']);
            unset($_SESSION['verified_phone']);
            unset($_SESSION['phone_verification_risk']);
            unset($_SESSION['pending_phone_country_code']);
            unset($_SESSION['pending_verified_phone']);
            
            $_SESSION['success'] = "Registration successful! You can now log in to your account.";
        }

        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="google-adsense-account" content="ca-pub-9839651797371714">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    
    <title>Register - CroFunding</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
        }
        .step-indicator {
            transition: all 0.3s ease;
        }
        .step-content {
            display: none;
        }
        .step-content.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .password-strength-bar {
            transition: all 0.3s ease;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 sm:pt-32 pb-12 sm:pb-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            <!-- Left Side - Brand Info -->
            <div class="space-y-6 lg:space-y-8">
                <div>
                    <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 transition-colors font-semibold group">
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Home
                    </a>
                </div>
                
                <div>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-slate-900 mb-4">
                        Start Your
                        <span class="text-emerald-600">Fundraising</span>
                        Journey
                    </h2>
                    <p class="text-base sm:text-lg text-slate-600 leading-relaxed">
                        Join thousands of verified fundraisers and donors making a real impact in their communities.
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-soft">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-3 sm:mb-4">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-2">Create Campaigns</h3>
                        <p class="text-slate-600 text-sm">Share your story and start raising funds for your cause</p>
                    </div>

                    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-soft">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-3 sm:mb-4">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-2">Join Community</h3>
                        <p class="text-slate-600 text-sm">Connect with supporters and like-minded changemakers</p>
                    </div>

                    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-soft">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-3 sm:mb-4">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-2">Secure Platform</h3>
                        <p class="text-slate-600 text-sm">Your funds are protected with bank-level security</p>
                    </div>

                    <div class="bg-white rounded-2xl p-4 sm:p-6 border border-slate-100 shadow-soft">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-3 sm:mb-4">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-2">Fast Setup</h3>
                        <p class="text-slate-600 text-sm">Get your campaign live in minutes, not hours</p>
                    </div>
                </div>
            </div>

            <!-- Right Side - Registration Form -->
            <div class="w-full max-w-md mx-auto lg:mx-0">
                <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-6 sm:p-8">
                    <div class="text-center mb-6 sm:mb-8">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 sm:w-8 sm:h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900">Create Account</h2>
                        <p class="text-slate-600 mt-2 text-sm sm:text-base">Join our community of changemakers</p>
                    </div>

                    <!-- Step Indicators -->
                    <div class="flex items-center justify-between mb-6 sm:mb-8">
                        <div class="flex flex-col items-center" id="step-1">
                            <div id="step-circle-1" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-semibold text-xs sm:text-sm">1</div>
                            <span class="text-[10px] sm:text-xs mt-1 sm:mt-2 text-slate-600">Personal</span>
                        </div>
                        <div id="step-line-1" class="flex-1 h-1 mx-1 sm:mx-2 bg-slate-200"></div>
                        <div class="flex flex-col items-center" id="step-2">
                            <div id="step-circle-2" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-semibold text-xs sm:text-sm">2</div>
                            <span class="text-[10px] sm:text-xs mt-1 sm:mt-2 text-slate-600">Security</span>
                        </div>
                        <div id="step-line-2" class="flex-1 h-1 mx-1 sm:mx-2 bg-slate-200"></div>
                        <div class="flex flex-col items-center" id="step-3">
                            <div id="step-circle-3" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-semibold text-xs sm:text-sm">3</div>
                            <span class="text-[10px] sm:text-xs mt-1 sm:mt-2 text-slate-600">Profile</span>
                        </div>
                        <div id="step-line-3" class="flex-1 h-1 mx-1 sm:mx-2 bg-slate-200"></div>
                        <div class="flex flex-col items-center" id="step-4">
                            <div id="step-circle-4" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-semibold text-xs sm:text-sm">4</div>
                            <span class="text-[10px] sm:text-xs mt-1 sm:mt-2 text-slate-600">Location</span>
                        </div>
                        <div id="step-line-4" class="flex-1 h-1 mx-1 sm:mx-2 bg-slate-200"></div>
                        <div class="flex flex-col items-center" id="step-5">
                            <div id="step-circle-5" class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-semibold text-xs sm:text-sm">5</div>
                            <span class="text-[10px] sm:text-xs mt-1 sm:mt-2 text-slate-600">Review</span>
                        </div>
                    </div>

                    <?php if (isset($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl flex items-center">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <form id="registration-form" method="POST" action="" class="space-y-5" enctype="multipart/form-data">
                        <input type="hidden" id="email-hidden" name="email" value="">
                        <input type="hidden" id="phone-hidden" name="phone" value="">
                        <input type="hidden" id="phone-country-code-hidden" name="country_code" value="">
                        <input type="hidden" id="phone-device-id" name="device_id" value="">

                        <!-- Step 1 -->
                        <div id="step-content-1" class="step-content active space-y-5">
                            <div>
                                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                                <input id="name" name="name" type="text" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter your name exactly as it appears on your National ID">
                                <div id="name-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Account Type</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <label class="flex items-center p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-500 transition-colors" id="account-type-personal-label">
                                        <input type="radio" name="account_type" value="personal" id="account-type-personal" class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 border-slate-300 mr-3" checked>
                                        <span class="text-sm font-medium text-slate-700">Personal Account</span>
                                    </label>
                                    <label class="flex items-center p-3 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-emerald-500 transition-colors" id="account-type-organization-label">
                                        <input type="radio" name="account_type" value="organization" id="account-type-organization" class="w-5 h-5 text-emerald-600 focus:ring-emerald-500 border-slate-300 mr-3">
                                        <span class="text-sm font-medium text-slate-700">Organization Account</span>
                                    </label>
                                </div>
                                <div id="account_type-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div id="organization-fields" class="space-y-5 hidden">
                                <div>
                                    <label for="organization_name" class="block text-sm font-semibold text-slate-700 mb-2">Organization Name</label>
                                    <input id="organization_name" name="organization_name" type="text" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter your organization name">
                                </div>
                                
                                <div>
                                    <label for="organization_type" class="block text-sm font-semibold text-slate-700 mb-2">Organization Type</label>
                                    <select id="organization_type" name="organization_type" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200">
                                        <option value="">Select organization type</option>
                                        <option value="ngo">Non-Governmental Organization (NGO)</option>
                                        <option value="company">Company/Corporation</option>
                                        <option value="foundation">Foundation</option>
                                        <option value="association">Association</option>
                                        <option value="religious">Religious Organization</option>
                                        <option value="educational">Educational Institution</option>
                                        <option value="government">Government/Parastatal</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="registration_number" class="block text-sm font-semibold text-slate-700 mb-2">Registration Number (Optional)</label>
                                    <input id="registration_number" name="registration_number" type="text" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter registration number">
                                </div>
                                
                                <div>
                                    <label for="organization_website" class="block text-sm font-semibold text-slate-700 mb-2">Website (Optional)</label>
                                    <input id="organization_website" name="organization_website" type="url" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="https://example.com">
                                </div>
                                
                                <div>
                                    <label for="organization_contact" class="block text-sm font-semibold text-slate-700 mb-2">Contact Information (Optional)</label>
                                    <input id="organization_contact" name="organization_contact" type="text" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Phone or email for organization contact">
                                </div>
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr),auto] sm:items-center">
                                    <input id="email" type="email" required class="w-full min-w-0 px-3 py-2.5 border border-slate-200 rounded-lg bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 text-sm" placeholder="Enter your email address">
                                    <button type="button" id="send-otp-btn" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium text-center whitespace-nowrap">
                                        Send OTP
                                    </button>
                                </div>
                                <div id="email-error" class="text-red-600 text-sm mt-1 hidden"></div>
                                <div id="email-success" class="text-emerald-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div id="otp-section" class="hidden">
                                <label for="otp-input" class="block text-sm font-semibold text-slate-700 mb-2">Verification Code</label>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr),auto] sm:items-center">
                                    <input id="otp-input" type="text" maxlength="6" class="w-full min-w-0 px-3 py-2.5 border border-slate-200 rounded-lg bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 text-sm" placeholder="Enter 6-digit OTP">
                                    <button type="button" id="verify-otp-btn" class="w-full sm:w-auto px-4 py-2.5 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors text-sm font-medium text-center whitespace-nowrap">
                                        Verify
                                    </button>
                                </div>
                                <div id="otp-error" class="text-red-600 text-sm mt-1 hidden"></div>
                                <div id="otp-success" class="text-emerald-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div class="pt-2">
                                <button type="button" id="next-step-1" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                                    Continue
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 2 -->
                        <div id="step-content-2" class="step-content space-y-5">
                            <div>
                                <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                                <div class="relative">
                                    <input id="password" name="password" type="password" required class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Create a strong password">
                                    <button type="button" id="toggle-password" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <svg id="eye-icon-password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <svg id="eye-off-icon-password" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div id="password-strength-bar" class="password-strength-bar h-full bg-red-500" style="width: 0%"></div>
                                    </div>
                                    <p id="password-strength-text" class="text-xs mt-1 text-slate-500">Password strength</p>
                                </div>
                                <div id="password-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                                <div class="relative">
                                    <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Confirm your password">
                                    <button type="button" id="toggle-password-confirm" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <svg id="eye-icon-confirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        <svg id="eye-off-icon-confirm" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div id="password-confirm-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-2">
                                <button type="button" id="prev-step-2" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-slate-200 rounded-xl text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                                <button type="button" id="next-step-2" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                                    Continue
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 3 -->
                        <div id="step-content-3" class="step-content space-y-5">
                            <div>
                                <label for="country_code" class="block text-sm font-semibold text-slate-700 mb-2">Country Code</label>
                                <select id="country_code" name="country_code" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200"></select>
                            </div>
                            
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-slate-700 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter phone number">
                                <div id="phone-hint" class="text-xs text-slate-500 mt-2"></div>
                                <div id="phone-error" class="text-red-600 text-sm mt-1 hidden"></div>
                                <div id="phone-success" class="text-emerald-600 text-sm mt-1 hidden"></div>
                            </div>

                            <div id="phone-otp-section" class="space-y-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div id="phone-verification-badge" class="hidden inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        <span>Phone verified and locked</span>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[auto,minmax(0,1fr),auto] sm:items-center">
                                    <button type="button" id="send-phone-otp-btn" class="w-full sm:w-auto px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-medium text-center whitespace-nowrap">
                                        Verify Phone
                                    </button>
                                    <input type="text" id="phone-otp-input" maxlength="6" class="w-full min-w-0 px-3 py-2.5 border border-slate-200 rounded-lg bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 text-sm" placeholder="Enter SMS code">
                                    <button type="button" id="verify-phone-otp-btn" class="w-full sm:w-auto px-4 py-2.5 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors text-sm font-medium text-center whitespace-nowrap">
                                        Confirm
                                    </button>
                                </div>
                                <div id="phone-otp-error" class="text-red-600 text-sm mt-1 hidden"></div>
                                <div id="phone-otp-success" class="text-emerald-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div>
                                <label for="nin" class="block text-sm font-semibold text-slate-700 mb-2">National ID Number (NIN)</label>
                                <div class="relative">
                                    <input type="text" id="nin" name="nin" required maxlength="14" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="A1234567890123">
                                    <div class="absolute right-4 top-3 text-xs text-slate-400">
                                        <span id="nin-counter">0</span>/14
                                    </div>
                                </div>
                                <div id="nin-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-2">
                                <button type="button" id="prev-step-3" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-slate-200 rounded-xl text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                                <button type="button" id="next-step-3" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                                    Continue
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 4 -->
                        <div id="step-content-4" class="step-content space-y-5">
                            <div>
                                <label for="country" class="block text-sm font-semibold text-slate-700 mb-2">Country of Residence</label>
                                <select id="country" name="country" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200">
                                    <option value="">Select a country</option>
                                </select>
                                <div id="country-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div>
                                <label for="nationality" class="block text-sm font-semibold text-slate-700 mb-2">Nationality</label>
                                <select id="nationality" name="nationality" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200">
                                    <option value="">Select your nationality</option>
                                </select>
                                <div id="nationality-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            </div>
                            
                            <div>
                                <label for="city-search" class="block text-sm font-semibold text-slate-700 mb-2">City/Region</label>
                                <div class="space-y-2">
                                    <input type="text" id="city-search" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60" placeholder="Select a country first" disabled autocomplete="off">
                                    <select id="city" name="city" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60" disabled>
                                        <option value="">Select a country first</option>
                                    </select>
                                    <p id="city-status" class="text-sm text-slate-500">Select a country first</p>
                                    <div id="city-error" class="text-red-600 text-sm hidden"></div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-2">
                                <button type="button" id="prev-step-4" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-slate-200 rounded-xl text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                                <button type="button" id="next-step-4" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                                    Continue
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Step 5 -->
                        <div id="step-content-5" class="step-content space-y-5">
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input id="terms" name="terms" type="checkbox" required class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 rounded">
                                </div>
                                <div class="text-sm">
                                    <label for="terms" class="font-semibold text-slate-700">
                                        I agree to the <a href="terms" class="text-emerald-600 hover:text-emerald-700 underline">Terms of Service</a> and 
                                        <a href="privacy" class="text-emerald-600 hover:text-emerald-700 underline">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>
                            <div id="terms-error" class="text-red-600 text-sm mt-1 hidden"></div>
                            
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input id="newsletter" name="newsletter" type="checkbox" class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-slate-300 rounded">
                                </div>
                                <div class="text-sm">
                                    <label for="newsletter" class="font-semibold text-slate-700">
                                        Subscribe to our newsletter for updates and success stories
                                    </label>
                                </div>
                            </div>
                            
                            <div class="pt-4">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">Profile Picture (Optional)</h4>
                                <div class="flex items-start space-x-4">
                                    <div id="avatar-preview" class="w-20 h-20 bg-slate-100 rounded-xl flex items-center justify-center border-2 border-dashed border-slate-300">
                                        <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <label class="flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-medium text-slate-700 bg-white hover:bg-slate-50 cursor-pointer">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            Upload
                                            <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewAvatar(this)" class="hidden">
                                        </label>
                                        <div id="avatar-error" class="text-red-600 text-sm mt-2 hidden"></div>
                                        <div id="file-info" class="mt-2 hidden">
                                            <div class="flex items-center justify-between bg-slate-50 rounded-lg p-2">
                                                <span id="file-name" class="text-xs text-slate-600 truncate"></span>
                                                <button type="button" onclick="removeAvatar()" class="text-red-500 hover:text-red-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-4">
                                <button type="button" id="prev-step-5" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-slate-200 rounded-xl text-base font-semibold text-slate-700 bg-white hover:bg-slate-50 transition-all duration-200">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                                <button type="submit" class="w-full inline-flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Create Account
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-8">
                        <p class="text-slate-600">
                            Already have an account? 
                            <a href="login.php" class="font-semibold text-emerald-600 hover:text-emerald-700 underline">
                                Sign in instead
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/public-footer.php'; ?>

    <script>
        const countries = <?php echo json_encode($countries); ?>;
        const phoneRules = <?php echo json_encode($phoneRuleMap); ?>;
        const unsupportedDialCodes = <?php echo json_encode($unsupportedDialCodes); ?>;
        const unsupportedCountryMessage = <?php echo json_encode($unsupportedCountryMessage); ?>;
        const cityCache = {};
        let currentStep = 1;
        const totalSteps = 5;
        let emailVerified = false;
        let emailValid = false;
        let emailCheckTimeout = null;
        let latestValidatedEmail = '';
        let otpCountdown = 0;
        let isSubmitting = false;
        let phoneValid = false;
        let phoneVerified = false;
        let phoneCheckTimeout = null;
        let latestNormalizedPhone = '';
        let lastPhoneOtpRequest = '';
        const messageTimeouts = {};

        function resetRegistrationForm() {
            currentStep = 1;
            emailVerified = false;
            emailValid = false;
            latestValidatedEmail = '';
            otpCountdown = 0;
            isSubmitting = false;
            phoneValid = false;
            phoneVerified = false;
            latestNormalizedPhone = '';
            lastPhoneOtpRequest = '';

            document.getElementById('name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('email-hidden').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password_confirmation').value = '';
            document.getElementById('phone').value = '';
            document.getElementById('phone-otp-input').value = '';
            document.getElementById('nin').value = '';
            document.getElementById('city-search').value = '';
            document.getElementById('city').value = '';
            document.getElementById('terms').checked = false;
            document.getElementById('newsletter').checked = false;
            document.getElementById('avatar').value = '';
            document.getElementById('otp-input').value = '';
            document.getElementById('account-type-personal').checked = true;
            document.getElementById('organization_name').value = '';
            document.getElementById('organization_type').value = '';
            document.getElementById('registration_number').value = '';
            document.getElementById('organization_website').value = '';
            document.getElementById('organization_contact').value = '';

            document.getElementById('organization-fields').classList.add('hidden');

            document.getElementById('account-type-personal-label').classList.remove('border-emerald-500');
            document.getElementById('account-type-personal-label').classList.add('border-slate-200');
            document.getElementById('account-type-organization-label').classList.remove('border-emerald-500');
            document.getElementById('account-type-organization-label').classList.add('border-slate-200');

            const avatarPreview = document.getElementById('avatar-preview');
            avatarPreview.innerHTML = `
                <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            `;
            document.getElementById('file-info').classList.add('hidden');
            document.getElementById('file-name').textContent = '';
            document.getElementById('nin-counter').textContent = '0';
            document.getElementById('password-strength-bar').style.width = '0%';
            document.getElementById('password-strength-bar').className = 'password-strength-bar h-full bg-red-500';
            document.getElementById('password-strength-text').textContent = 'Password strength';
            document.getElementById('password-strength-text').className = 'text-xs mt-1 text-slate-500';

            document.getElementById('email').disabled = false;
            document.getElementById('send-otp-btn').disabled = false;
            document.getElementById('verify-otp-btn').disabled = false;
            document.getElementById('otp-input').disabled = false;
            document.getElementById('country_code').disabled = false;
            document.getElementById('phone-country-code-hidden').value = document.getElementById('country_code').value;
            document.getElementById('phone-hidden').value = '';
            document.getElementById('phone').disabled = false;
            document.getElementById('send-phone-otp-btn').disabled = false;
            document.getElementById('verify-phone-otp-btn').disabled = false;
            document.getElementById('phone-otp-input').disabled = false;

            document.getElementById('otp-section').classList.add('hidden');
            document.getElementById('send-otp-btn').textContent = 'Send OTP';
            document.getElementById('verify-otp-btn').textContent = 'Verify';
            document.getElementById('send-phone-otp-btn').textContent = 'Verify Phone';
            document.getElementById('verify-phone-otp-btn').textContent = 'Confirm';
            document.getElementById('phone-hint').textContent = '';
            document.getElementById('phone-verification-badge').classList.add('hidden');

            hideAllErrors();
            resetCitySelectionState();
            updateStepIndicators();
            goToStep(1);
            updateStep3ContinueButton();
            syncPhonePlaceholder();
        }

        function resetCitySelectionState(placeholder = 'Select a country first') {
            const citySelect = document.getElementById('city');
            const citySearch = document.getElementById('city-search');
            const cityStatus = document.getElementById('city-status');

            citySearch.value = '';
            citySearch.disabled = true;
            citySearch.placeholder = placeholder;
            citySelect.disabled = true;
            citySelect.innerHTML = `<option value="">${placeholder}</option>`;
            cityStatus.textContent = placeholder;
            cityStatus.className = 'text-sm text-slate-500';
            document.getElementById('city-error').classList.add('hidden');
        }

        function setCityLoadingState() {
            const citySearch = document.getElementById('city-search');
            const citySelect = document.getElementById('city');
            const cityStatus = document.getElementById('city-status');

            citySearch.value = '';
            citySearch.disabled = true;
            citySearch.placeholder = 'Loading cities...';
            citySelect.disabled = true;
            citySelect.innerHTML = '<option value="">Loading cities...</option>';
            cityStatus.textContent = 'Loading cities...';
            cityStatus.className = 'text-sm text-slate-500';
        }

        function renderCityOptions(cities, selectedValue = '') {
            const citySelect = document.getElementById('city');
            const citySearch = document.getElementById('city-search');
            const cityStatus = document.getElementById('city-status');
            const normalizedSelectedValue = selectedValue.trim().toLowerCase();

            citySelect.innerHTML = '<option value="">Select your city</option>';

            cities.forEach(city => {
                const option = document.createElement('option');
                option.value = city.city_name;
                option.textContent = city.city_name;
                if (normalizedSelectedValue !== '' && city.city_name.toLowerCase() === normalizedSelectedValue) {
                    option.selected = true;
                }
                citySelect.appendChild(option);
            });

            citySearch.disabled = false;
            citySearch.placeholder = cities.length > 0 ? 'Search cities...' : 'No cities available';
            citySelect.disabled = cities.length === 0;
            cityStatus.textContent = cities.length > 0 ? `${cities.length} cities available` : 'No cities available for this country';
            cityStatus.className = cities.length > 0 ? 'text-sm text-slate-500' : 'text-sm text-amber-600';
        }

        function filterCityOptions() {
            const searchTerm = document.getElementById('city-search').value.trim().toLowerCase();
            const citySelect = document.getElementById('city');
            const cityStatus = document.getElementById('city-status');
            let visibleCount = 0;

            Array.from(citySelect.options).forEach((option, index) => {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                const matches = option.textContent.toLowerCase().includes(searchTerm);
                option.hidden = !matches;
                if (matches) {
                    visibleCount++;
                }
            });

            if (searchTerm === '') {
                cityStatus.textContent = `${Math.max(citySelect.options.length - 1, 0)} cities available`;
                cityStatus.className = 'text-sm text-slate-500';
            } else if (visibleCount > 0) {
                cityStatus.textContent = `${visibleCount} matching cities`;
                cityStatus.className = 'text-sm text-slate-500';
            } else {
                cityStatus.textContent = 'No matching cities found';
                cityStatus.className = 'text-sm text-amber-600';
            }
        }

        async function loadCitiesForCountry(countryCode) {
            const normalizedCountryCode = (countryCode || '').trim().toUpperCase();
            if (!normalizedCountryCode) {
                resetCitySelectionState();
                return;
            }

            document.getElementById('city').value = '';
            document.getElementById('city-error').classList.add('hidden');

            if (cityCache[normalizedCountryCode]) {
                renderCityOptions(cityCache[normalizedCountryCode]);
                return;
            }

            setCityLoadingState();

            try {
                const response = await fetch(`<?php echo BASE_URL; ?>/api/cities.php?country=${encodeURIComponent(normalizedCountryCode)}`);
                const result = await response.json();

                if (!response.ok || !result.success) {
                    throw new Error(result.error || 'Unable to load cities.');
                }

                cityCache[normalizedCountryCode] = result.cities || [];
                renderCityOptions(cityCache[normalizedCountryCode]);
            } catch (error) {
                console.error('Failed to load cities:', error);
                resetCitySelectionState('Unable to load cities');
                const cityStatus = document.getElementById('city-status');
                cityStatus.textContent = 'Unable to load cities. Please try again.';
                cityStatus.className = 'text-sm text-red-600';
            }
        }

        function updateAccountTypeStyling() {
            const personalLabel = document.getElementById('account-type-personal-label');
            const orgLabel = document.getElementById('account-type-organization-label');
            const personalRadio = document.getElementById('account-type-personal');
            
            if (personalRadio.checked) {
                personalLabel.classList.remove('border-slate-200');
                personalLabel.classList.add('border-emerald-500');
                orgLabel.classList.remove('border-emerald-500');
                orgLabel.classList.add('border-slate-200');
            } else {
                orgLabel.classList.remove('border-slate-200');
                orgLabel.classList.add('border-emerald-500');
                personalLabel.classList.remove('border-emerald-500');
                personalLabel.classList.add('border-slate-200');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            populateDropdowns();
            generateDeviceId();
            resetRegistrationForm();
            updateAccountTypeStyling();

            document.querySelectorAll('input[name="account_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const orgFields = document.getElementById('organization-fields');
                    if (this.value === 'organization') {
                        orgFields.classList.remove('hidden');
                    } else {
                        orgFields.classList.add('hidden');
                    }
                    updateAccountTypeStyling();
                });
            });

            document.getElementById('email').addEventListener('input', function() {
                document.getElementById('email-hidden').value = this.value;
                emailVerified = false;
                emailValid = false;
                latestValidatedEmail = '';
                document.getElementById('otp-section').classList.add('hidden');
                document.getElementById('otp-input').value = '';
                document.getElementById('email-success').classList.add('hidden');
                document.getElementById('otp-success').classList.add('hidden');
                document.getElementById('email').disabled = false;
                document.getElementById('send-otp-btn').disabled = false;
                document.getElementById('verify-otp-btn').disabled = false;
                document.getElementById('otp-input').disabled = false;
                validateEmailInput();
                if (emailCheckTimeout) clearTimeout(emailCheckTimeout);
                emailCheckTimeout = setTimeout(checkEmail, 400);
            });
            document.getElementById('send-otp-btn').addEventListener('click', sendOTP);
            document.getElementById('verify-otp-btn').addEventListener('click', verifyOTP);
            document.getElementById('send-phone-otp-btn').addEventListener('click', sendPhoneOtp);
            document.getElementById('verify-phone-otp-btn').addEventListener('click', verifyPhoneOtp);
            document.getElementById('next-step-1').addEventListener('click', nextStep);
            document.getElementById('next-step-2').addEventListener('click', nextStep);
            document.getElementById('next-step-3').addEventListener('click', nextStep);
            document.getElementById('next-step-4').addEventListener('click', nextStep);
            document.getElementById('prev-step-2').addEventListener('click', prevStep);
            document.getElementById('prev-step-3').addEventListener('click', prevStep);
            document.getElementById('prev-step-4').addEventListener('click', prevStep);
            document.getElementById('prev-step-5').addEventListener('click', prevStep);

            document.getElementById('registration-form').addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return;
                }

                if (!emailValid) {
                    e.preventDefault();
                    showError('email-error', 'Please enter a valid email address.');
                    return;
                }

                if (!emailVerified) {
                    e.preventDefault();
                    showError('email-error', 'Please verify your email first');
                    return;
                }

                if (!phoneVerified || !latestNormalizedPhone) {
                    e.preventDefault();
                    showError('phone-error', 'Please verify your phone number first');
                    return;
                }

                document.getElementById('phone-hidden').value = latestNormalizedPhone;
                document.getElementById('phone-country-code-hidden').value = document.getElementById('country_code').value;
                isSubmitting = true;
                const submitBtn = document.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Creating Account...';
            });

            document.getElementById('password').addEventListener('input', updatePasswordStrength);
            document.getElementById('toggle-password').addEventListener('click', function() {
                togglePasswordVisibility('password', 'eye-icon-password', 'eye-off-icon-password');
            });
            document.getElementById('toggle-password-confirm').addEventListener('click', function() {
                togglePasswordVisibility('password_confirmation', 'eye-icon-confirm', 'eye-off-icon-confirm');
            });

            document.getElementById('phone').addEventListener('input', function() {
                const phoneData = validatePhone();
                if (phoneData) {
                    if (phoneCheckTimeout) clearTimeout(phoneCheckTimeout);
                    phoneCheckTimeout = setTimeout(checkPhone, 500);
                }
            });

            document.getElementById('country_code').addEventListener('change', function() {
                document.getElementById('phone-country-code-hidden').value = this.value;
                markPhoneAsUnverified();
                latestNormalizedPhone = '';
                document.getElementById('phone').value = '';
                syncPhonePlaceholder();
                document.getElementById('phone-success').classList.add('hidden');
                document.getElementById('phone-error').classList.add('hidden');
                document.getElementById('phone-otp-error').classList.add('hidden');
                updateStep3ContinueButton();
            });

            document.getElementById('phone').addEventListener('blur', checkPhone);
            document.getElementById('city-search').addEventListener('input', filterCityOptions);
            document.getElementById('city').addEventListener('change', function() {
                document.getElementById('city-error').classList.add('hidden');
            });

            const ninInput = document.getElementById('nin');
            ninInput.addEventListener('input', function() {
                document.getElementById('nin-counter').textContent = this.value.length;
            });
        });

        function populateDropdowns() {
            const countrySelect = document.getElementById('country');
            const nationalitySelect = document.getElementById('nationality');
            const countryCodeSelect = document.getElementById('country_code');
            
            countries.forEach(country => {
                const countryOption = document.createElement('option');
                countryOption.value = country.code;
                countryOption.textContent = country.name;
                countrySelect.appendChild(countryOption);
                
                const nationalityOption = document.createElement('option');
                nationalityOption.value = country.nationality;
                nationalityOption.textContent = country.nationality;
                nationalitySelect.appendChild(nationalityOption);
                
                const codeOption = document.createElement('option');
                codeOption.value = country.code;
                codeOption.textContent = `${country.code} (${country.dial_code})`;
                countryCodeSelect.appendChild(codeOption);
            });

            resetCitySelectionState();
            
            const defaultCountry = countries.find(c => c.code === 'US') || countries[0];
            if (defaultCountry) {
                countrySelect.value = defaultCountry.code;
                nationalitySelect.value = defaultCountry.nationality;
                countryCodeSelect.value = defaultCountry.code;
                document.getElementById('phone-country-code-hidden').value = defaultCountry.code;
                loadCitiesForCountry(defaultCountry.code);
            }
            
            countrySelect.addEventListener('change', function() {
                const selectedCountry = countries.find(c => c.code === this.value);
                document.getElementById('city-search').value = '';
                document.getElementById('city').value = '';

                if (selectedCountry) {
                    nationalitySelect.value = selectedCountry.nationality;
                    countryCodeSelect.value = selectedCountry.code;
                    document.getElementById('phone-country-code-hidden').value = selectedCountry.code;
                    syncPhonePlaceholder();
                    loadCitiesForCountry(selectedCountry.code);
                } else {
                    nationalitySelect.value = '';
                    resetCitySelectionState();
                }
            });
        }

        function updateStepIndicators() {
            for (let i = 1; i <= totalSteps; i++) {
                const indicatorCircle = document.getElementById(`step-circle-${i}`);
                
                if (i < currentStep) {
                    indicatorCircle.className = 'w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-semibold';
                    indicatorCircle.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    document.getElementById(`step-line-${i}`)?.classList.add('bg-emerald-600');
                    document.getElementById(`step-line-${i}`)?.classList.remove('bg-slate-200');
                } else if (i === currentStep) {
                    indicatorCircle.className = 'w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-semibold';
                    indicatorCircle.textContent = i;
                } else {
                    indicatorCircle.className = 'w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-500 font-semibold';
                    indicatorCircle.textContent = i;
                    document.getElementById(`step-line-${i-1}`)?.classList.remove('bg-emerald-600');
                    document.getElementById(`step-line-${i-1}`)?.classList.add('bg-slate-200');
                }
            }
        }

        function goToStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.getElementById(`step-content-${step}`).classList.add('active');
            currentStep = step;
            updateStepIndicators();
        }

        function validateStep(step) {
            hideAllErrors();
            let isValid = true;
            
            if (step === 1) {
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const accountType = document.querySelector('input[name="account_type"]:checked');
                
                if (!name) {
                    showError('name-error', 'Name is required');
                    isValid = false;
                }
                
                if (!accountType) {
                    showError('account_type-error', 'Please select an account type');
                    isValid = false;
                }
                
                if (!email) {
                    showError('email-error', 'Email is required');
                    isValid = false;
                } else if (!emailValid) {
                    showError('email-error', 'Please enter a valid email address.');
                    isValid = false;
                }
                
                if (!emailVerified) {
                    showError('email-error', 'Please verify your email first');
                    isValid = false;
                }
            }
            
            if (step === 2) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('password_confirmation').value;
                
                if (!password) {
                    showError('password-error', 'Password is required');
                    isValid = false;
                } else if (!isPasswordStrong(password)) {
                    showError('password-error', 'Password must be at least 8 characters, include uppercase, lowercase, number, and special character');
                    isValid = false;
                }
                
                if (!confirmPassword) {
                    showError('password-confirm-error', 'Please confirm your password');
                    isValid = false;
                } else if (password !== confirmPassword) {
                    showError('password-confirm-error', 'Passwords do not match');
                    isValid = false;
                }
            }
            
            if (step === 3) {
                const phone = document.getElementById('phone').value.trim();
                const nin = document.getElementById('nin').value.trim();
                
                if (!phone) {
                    showError('phone-error', 'Phone number is required');
                    isValid = false;
                } else if (!phoneValid) {
                    showError('phone-error', 'Please use a valid mobile number supported by our services.');
                    isValid = false;
                } else if (!phoneVerified) {
                    showError('phone-otp-error', 'Please verify your phone number first.');
                    isValid = false;
                }
                
                if (!nin) {
                    showError('nin-error', 'National ID Number is required');
                    isValid = false;
                }
            }
            
            if (step === 4) {
                const country = document.getElementById('country').value;
                const nationality = document.getElementById('nationality').value;
                const city = document.getElementById('city').value;
                
                if (!country) {
                    showError('country-error', 'Please select a country');
                    isValid = false;
                }
                
                if (!nationality) {
                    showError('nationality-error', 'Please select your nationality');
                    isValid = false;
                }

                if (!city) {
                    showError('city-error', 'Please select a city for the selected country');
                    isValid = false;
                }
            }
            
            if (step === 5) {
                const terms = document.getElementById('terms').checked;
                
                if (!terms) {
                    showError('terms-error', 'You must accept the Terms of Service and Privacy Policy');
                    isValid = false;
                }
            }
            
            return isValid;
        }

        function nextStep() {
            if (validateStep(currentStep) && currentStep < totalSteps) {
                goToStep(currentStep + 1);
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                goToStep(currentStep - 1);
            }
        }

        function clearMessageTimeout(elementId) {
            if (messageTimeouts[elementId]) {
                clearTimeout(messageTimeouts[elementId]);
                delete messageTimeouts[elementId];
            }
        }

        function hideMessage(elementId) {
            clearMessageTimeout(elementId);
            const el = document.getElementById(elementId);
            if (!el) return;
            el.textContent = '';
            el.classList.add('hidden');
        }

        function hideMessageGroup(group) {
            const groups = {
                email: ['email-error', 'email-success', 'otp-error', 'otp-success'],
                phone: ['phone-error', 'phone-success', 'phone-otp-error', 'phone-otp-success'],
                location: ['city-error'],
            };

            (groups[group] || []).forEach(hideMessage);
        }

        function getMessageGroup(elementId) {
            if (['email-error', 'email-success', 'otp-error', 'otp-success'].includes(elementId)) {
                return 'email';
            }
            if (['phone-error', 'phone-success', 'phone-otp-error', 'phone-otp-success'].includes(elementId)) {
                return 'phone';
            }
            if (['city-error'].includes(elementId)) {
                return 'location';
            }
            return null;
        }

        function showError(elementId, message, options = {}) {
            const group = options.group || getMessageGroup(elementId);
            if (group) {
                hideMessageGroup(group);
            } else {
                hideMessage(elementId);
            }

            const el = document.getElementById(elementId);
            el.textContent = message;
            el.classList.remove('hidden');

            if (options.autoHideMs) {
                clearMessageTimeout(elementId);
                messageTimeouts[elementId] = setTimeout(() => hideMessage(elementId), options.autoHideMs);
            }
        }

        function showSuccess(elementId, message, options = {}) {
            const group = options.group || getMessageGroup(elementId);
            if (group) {
                hideMessageGroup(group);
            } else {
                hideMessage(elementId);
            }

            const el = document.getElementById(elementId);
            el.textContent = message;
            el.classList.remove('hidden');

            const autoHideMs = options.autoHideMs ?? 1000;
            if (autoHideMs) {
                clearMessageTimeout(elementId);
                messageTimeouts[elementId] = setTimeout(() => hideMessage(elementId), autoHideMs);
            }
        }

        function togglePasswordVisibility(inputId, eyeIconId, eyeOffIconId) {
            const input = document.getElementById(inputId);
            const eyeIcon = document.getElementById(eyeIconId);
            const eyeOffIcon = document.getElementById(eyeOffIconId);

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.classList.add('hidden');
                eyeOffIcon.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeIcon.classList.remove('hidden');
                eyeOffIcon.classList.add('hidden');
            }
        }

        function hideAllErrors() {
            document.querySelectorAll('[id$="-error"]').forEach(el => {
                hideMessage(el.id);
            });
            document.querySelectorAll('[id$="-success"]').forEach(el => {
                hideMessage(el.id);
            });
        }

        function isPasswordStrong(password) {
            return password.length >= 8 &&
                   /[A-Z]/.test(password) &&
                   /[a-z]/.test(password) &&
                   /[0-9]/.test(password) &&
                   /[^A-Za-z0-9]/.test(password);
        }

        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            if (strength <= 2) {
                strengthBar.className = 'password-strength-bar h-2 rounded-full bg-red-500';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-xs mt-1 text-red-600';
                strengthBar.style.width = '25%';
            } else if (strength <= 4) {
                strengthBar.className = 'password-strength-bar h-2 rounded-full bg-yellow-500';
                strengthText.textContent = 'Medium';
                strengthText.className = 'text-xs mt-1 text-yellow-600';
                strengthBar.style.width = '50%';
            } else {
                strengthBar.className = 'password-strength-bar h-2 rounded-full bg-emerald-500';
                strengthText.textContent = 'Strong';
                strengthText.className = 'text-xs mt-1 text-emerald-600';
                strengthBar.style.width = '100%';
            }
        }

        function validateEmailInput() {
            const email = document.getElementById('email').value.trim().toLowerCase();
            const emailError = document.getElementById('email-error');
            const emailSuccess = document.getElementById('email-success');

            if (!email) {
                emailValid = false;
                latestValidatedEmail = '';
                emailError.classList.add('hidden');
                emailSuccess.classList.add('hidden');
                return null;
            }

            const parts = email.split('@');
            if (parts.length !== 2 || !parts[0] || !parts[1]) {
                emailValid = false;
                showError('email-error', 'Please enter a valid email address.', { group: 'email' });
                return null;
            }

            const domain = parts[1];
            if (!/^(?=.{1,253}$)(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i.test(domain)) {
                emailValid = false;
                showError('email-error', 'This email address format is not supported.', { group: 'email' });
                return null;
            }

            hideMessage('email-error');
            return email;
        }

        async function checkEmail() {
            const email = validateEmailInput();
            if (!email) {
                return;
            }

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'validate_email',
                        email: email,
                        device_id: generateDeviceId()
                    })
                });

                const result = await response.json();
                if (!result.valid) {
                    emailValid = false;
                    latestValidatedEmail = '';
                    showError('email-error', result.error || 'We cannot verify this email address. Please try another email.', { group: 'email' });
                    return;
                }

                emailValid = true;
                latestValidatedEmail = result.validation?.email || email;
                showSuccess('email-success', result.message || 'Email address looks good.', { group: 'email', autoHideMs: 1000 });
            } catch (e) {
                console.error(e);
                emailValid = false;
                latestValidatedEmail = '';
                showError('email-error', 'We cannot verify this email address. Please try another email.', { group: 'email' });
            }
        }

        async function sendOTP() {
            hideAllErrors();
            const email = validateEmailInput();
            
            if (!email) {
                return;
            }

            await checkEmail();
            if (!emailValid || !latestValidatedEmail) {
                return;
            }
            
            const btn = document.getElementById('send-otp-btn');
            btn.disabled = true;
            btn.textContent = 'Sending...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'send_otp',
                        email: latestValidatedEmail,
                        device_id: generateDeviceId()
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('email-success', result.message, { group: 'email', autoHideMs: 1000 });
                    document.getElementById('otp-section').classList.remove('hidden');
                    startOtpCountdown();
                } else {
                    showError('email-error', result.error, { group: 'email' });
                }
            } catch (e) {
                showError('email-error', 'An error occurred. Please try again.', { group: 'email' });
            } finally {
                if (otpCountdown === 0) {
                    btn.disabled = false;
                    btn.textContent = 'Send OTP';
                }
            }
        }

        async function verifyOTP() {
            hideAllErrors();
            const email = latestValidatedEmail || document.getElementById('email').value.trim().toLowerCase();
            const otp = document.getElementById('otp-input').value.trim();
            
            if (!otp || otp.length !== 6) {
                showError('otp-error', 'Please enter a valid 6-digit OTP', { group: 'email' });
                return;
            }
            
            const btn = document.getElementById('verify-otp-btn');
            btn.disabled = true;
            btn.textContent = 'Verifying...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'verify_otp',
                        email: email,
                        otp: otp,
                        device_id: generateDeviceId()
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccess('otp-success', result.message, { group: 'email', autoHideMs: 1000 });
                    emailVerified = true;
                    emailValid = true;
                    latestValidatedEmail = email;
                    document.getElementById('email').disabled = true;
                    document.getElementById('email').value = email;
                    document.getElementById('email-hidden').value = email;
                    document.getElementById('send-otp-btn').disabled = true;
                    document.getElementById('verify-otp-btn').disabled = true;
                    document.getElementById('otp-input').disabled = true;
                } else {
                    showError('otp-error', result.error, { group: 'email' });
                }
            } catch (e) {
                showError('otp-error', 'An error occurred. Please try again.', { group: 'email' });
            } finally {
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        }

        function startOtpCountdown() {
            otpCountdown = 60;
            const btn = document.getElementById('send-otp-btn');
            btn.disabled = true;
            
            const interval = setInterval(() => {
                otpCountdown--;
                btn.textContent = `Resend (${otpCountdown}s)`;
                
                if (otpCountdown <= 0) {
                    clearInterval(interval);
                    btn.textContent = 'Resend OTP';
                    btn.disabled = false;
                }
            }, 1000);
        }

        function updateStep3ContinueButton() {
            const btn = document.getElementById('next-step-3');
            if (phoneValid && phoneVerified) {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        function getSelectedPhoneRule() {
            const countryCode = document.getElementById('country_code').value;
            return phoneRules[countryCode] || phoneRules._default || null;
        }

        function generateDeviceId() {
            const input = document.getElementById('phone-device-id');
            if (input.value) {
                return input.value;
            }

            const storageKey = 'crofund_phone_device_id';
            const stored = window.localStorage.getItem(storageKey);
            if (stored) {
                input.value = stored;
                return stored;
            }

            const generated = `web-${Math.random().toString(36).slice(2)}${Date.now().toString(36)}`;
            window.localStorage.setItem(storageKey, generated);
            input.value = generated;
            return generated;
        }

        function syncPhonePlaceholder() {
            const rule = getSelectedPhoneRule();
            const phoneInput = document.getElementById('phone');
            const hint = document.getElementById('phone-hint');

            if (!rule) {
                phoneInput.placeholder = 'Enter phone number';
                hint.textContent = '';
                return;
            }

            phoneInput.placeholder = rule.placeholder || 'Enter phone number';
            hint.textContent = `Use a mobile number in the format ${rule.dial_code} ${rule.example}.`;
        }

        function normalizeNationalDigits(rawValue, rule) {
            let digits = String(rawValue || '').replace(/\D+/g, '');
            const dialDigits = String(rule?.dial_code || '').replace(/\D+/g, '');

            if (dialDigits && digits.startsWith(dialDigits)) {
                digits = digits.slice(dialDigits.length);
            }

            if (rule?.strip_leading_zero && digits.startsWith('0')) {
                digits = digits.slice(1);
            }

            return digits;
        }

        function formatNationalDigits(digits, grouping) {
            if (!digits) {
                return '';
            }

            const groups = Array.isArray(grouping) ? grouping : [];
            if (!groups.length) {
                return digits;
            }

            let offset = 0;
            const segments = [];
            groups.forEach(size => {
                if (offset >= digits.length) {
                    return;
                }
                segments.push(digits.slice(offset, offset + size));
                offset += size;
            });

            if (offset < digits.length) {
                segments.push(digits.slice(offset));
            }

            return segments.filter(Boolean).join(' ');
        }

        function getNationalDisplayFromNormalized(normalizedPhone, rule) {
            const dialDigits = String(rule?.dial_code || '').replace(/\D+/g, '');
            let digits = String(normalizedPhone || '').replace(/\D+/g, '');
            if (dialDigits && digits.startsWith(dialDigits)) {
                digits = digits.slice(dialDigits.length);
            }
            return formatNationalDigits(digits, rule?.grouping || []);
        }

        function markPhoneAsUnverified() {
            phoneVerified = false;
            lastPhoneOtpRequest = '';
            document.getElementById('phone-otp-input').value = '';
            hideMessageGroup('phone');
            document.getElementById('phone-verification-badge').classList.add('hidden');
            updateStep3ContinueButton();
        }

        function validatePhone() {
            const rule = getSelectedPhoneRule();
            const phoneInput = document.getElementById('phone');
            const rawValue = phoneInput.value.trim();

            if (!rule) {
                phoneValid = false;
                latestNormalizedPhone = '';
                showError('phone-error', 'Please select a valid country code.', { group: 'phone' });
                updateStep3ContinueButton();
                return null;
            }

            syncPhonePlaceholder();

            if (!rawValue) {
                phoneValid = false;
                latestNormalizedPhone = '';
                hideMessageGroup('phone');
                updateStep3ContinueButton();
                return null;
            }

            const previousNormalized = latestNormalizedPhone;
            const digits = normalizeNationalDigits(rawValue, rule);
            const formattedNational = formatNationalDigits(digits, rule.grouping || []);
            if (phoneInput.value !== formattedNational) {
                phoneInput.value = formattedNational;
            }

            const normalizedPhone = `${rule.dial_code}${digits}`;
            if (previousNormalized && previousNormalized !== normalizedPhone) {
                markPhoneAsUnverified();
            }
            latestNormalizedPhone = normalizedPhone;

            if (unsupportedDialCodes.includes(rule.dial_code)) {
                showError('phone-error', unsupportedCountryMessage, { group: 'phone' });
                phoneValid = false;
                updateStep3ContinueButton();
                return null;
            }

            if (digits.length < (rule.min_length || 7) || digits.length > (rule.max_length || 12)) {
                showError('phone-error', 'Please enter a valid phone number for the selected country.', { group: 'phone' });
                phoneValid = false;
                updateStep3ContinueButton();
                return null;
            }

            if ((rule.allowed_prefixes || []).length > 0) {
                const hasAllowedPrefix = rule.allowed_prefixes.some(prefix => digits.startsWith(prefix));
                if (!hasAllowedPrefix) {
                    showError('phone-error', 'Please enter a valid mobile number for the selected country.', { group: 'phone' });
                    phoneValid = false;
                    updateStep3ContinueButton();
                    return null;
                }
            }

            hideMessage('phone-error');
            phoneValid = true;
            updateStep3ContinueButton();

            return {
                countryCode: document.getElementById('country_code').value,
                normalizedPhone,
                nationalNumber: digits,
                formattedNational,
            };
        }

        async function checkPhone() {
            const phoneData = validatePhone();
            if (!phoneData) return;

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'check_phone',
                        phone: phoneData.normalizedPhone,
                        country_code: phoneData.countryCode,
                        device_id: generateDeviceId()
                    })
                });

                const result = await response.json();

                if (!result.available) {
                    showError('phone-error', result.error || 'This phone number cannot be used for verification.', { group: 'phone' });
                    phoneValid = false;
                } else {
                    const rule = getSelectedPhoneRule();
                    latestNormalizedPhone = result.validation?.normalized_phone || phoneData.normalizedPhone;
                    document.getElementById('phone').value = getNationalDisplayFromNormalized(latestNormalizedPhone, rule);
                    document.getElementById('phone-hint').textContent = result.validation?.placeholder || document.getElementById('phone-hint').textContent;
                    showSuccess('phone-success', phoneVerified ? 'Phone number verified successfully.' : 'Phone number looks good. Continue with SMS verification.', { group: 'phone', autoHideMs: 1000 });
                    phoneValid = true;
                }
            } catch (e) {
                console.error(e);
                phoneValid = false;
                showError('phone-error', 'We could not validate this number right now. Please try again.', { group: 'phone' });
            } finally {
                updateStep3ContinueButton();
            }
        }

        async function sendPhoneOtp() {
            hideAllErrors();
            const phoneData = validatePhone();
            if (!phoneData) {
                return;
            }

            const btn = document.getElementById('send-phone-otp-btn');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'send_phone_otp',
                        phone: phoneData.normalizedPhone,
                        country_code: phoneData.countryCode,
                        device_id: generateDeviceId()
                    })
                });

                const result = await response.json();
                if (!result.success) {
                    showError('phone-otp-error', result.error || 'We are unable to verify this number. Please try another number.', { group: 'phone' });
                    return;
                }

                latestNormalizedPhone = result.phone || phoneData.normalizedPhone;
                lastPhoneOtpRequest = latestNormalizedPhone;
                document.getElementById('phone-hidden').value = latestNormalizedPhone;
                phoneVerified = false;
                showSuccess('phone-otp-success', result.message || 'Verification code sent successfully.', { group: 'phone', autoHideMs: 1000 });
                updateStep3ContinueButton();
            } catch (e) {
                console.error(e);
                showError('phone-otp-error', 'We are unable to verify this number. Please try another number.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Verify Phone';
            }
        }

        async function verifyPhoneOtp() {
            hideAllErrors();
            const code = document.getElementById('phone-otp-input').value.trim();
            if (!lastPhoneOtpRequest) {
                showError('phone-otp-error', 'Please request a verification code first.', { group: 'phone' });
                return;
            }

            if (!/^\d{4,8}$/.test(code)) {
                showError('phone-otp-error', 'Please enter the verification code.', { group: 'phone' });
                return;
            }

            const btn = document.getElementById('verify-phone-otp-btn');
            btn.disabled = true;
            btn.textContent = 'Checking...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'verify_phone_otp',
                        phone: lastPhoneOtpRequest,
                        otp: code,
                        device_id: generateDeviceId()
                    })
                });

                const result = await response.json();
                if (!result.success) {
                    showError('phone-otp-error', result.error || 'This phone number cannot be used for verification.', { group: 'phone' });
                    return;
                }

                phoneVerified = true;
                latestNormalizedPhone = lastPhoneOtpRequest;
                document.getElementById('phone-hidden').value = latestNormalizedPhone;
                showSuccess('phone-otp-success', result.message || 'Phone number verified successfully.', { group: 'phone', autoHideMs: 1000 });
                document.getElementById('phone-verification-badge').classList.remove('hidden');
                document.getElementById('country_code').disabled = true;
                document.getElementById('phone').disabled = true;
                document.getElementById('send-phone-otp-btn').disabled = true;
                document.getElementById('verify-phone-otp-btn').disabled = true;
                document.getElementById('phone-otp-input').disabled = true;
            } catch (e) {
                console.error(e);
                showError('phone-otp-error', 'We are unable to verify this number. Please try another number.', { group: 'phone' });
            } finally {
                updateStep3ContinueButton();
                if (!phoneVerified) {
                    btn.disabled = false;
                    btn.textContent = 'Confirm';
                }
            }
        }

        function previewAvatar(input) {
            hideAllErrors();
            const file = input.files[0];
            const preview = document.getElementById('avatar-preview');
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            
            if (file) {
                if (!file.type.startsWith('image/')) {
                    showError('avatar-error', 'Please select a valid image file');
                    input.value = '';
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) {
                    showError('avatar-error', 'File size must be less than 10MB');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Avatar preview" class="h-full w-full object-cover">`;
                };
                reader.onerror = function() {
                    showError('avatar-error', 'Error reading file. Please try again.');
                    input.value = '';
                };
                reader.readAsDataURL(file);
                
                fileName.textContent = file.name;
                fileInfo.classList.remove('hidden');
            }
        }

        function removeAvatar() {
            const input = document.getElementById('avatar');
            const preview = document.getElementById('avatar-preview');
            const fileInfo = document.getElementById('file-info');
            
            input.value = '';
            
            preview.innerHTML = `
                <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            `;
            
            fileInfo.classList.add('hidden');
        }
    </script>
</body>
</html>
