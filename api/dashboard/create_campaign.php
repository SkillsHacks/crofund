<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../config/Campaign.php';
    require_once __DIR__ . '/../../config/CampaignDraft.php';
    require_once __DIR__ . '/../../config/User.php';
    require_once __DIR__ . '/../../config/TrustRiskGuard.php';
    require_once __DIR__ . '/../../config/Mail.php';
    require_once __DIR__ . '/../../config/EmailValidationService.php';
    require_once __DIR__ . '/../../config/EmailVerificationService.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        exit();
    }

    if (($_POST['action'] ?? '') === 'discard_draft') {
        CampaignDraft::remove();
        if (($_SESSION['redirect_after_login'] ?? '') === '/campaigns/create.php?resume=1') {
            unset($_SESSION['redirect_after_login']);
        }
        echo json_encode(['success' => true]);
        exit();
    }

    $draftId = trim((string) ($_POST['campaign_draft_id'] ?? ''));
    $isPublishingDraft = $draftId !== '';
    $user = new User();
    $isAuthenticated = isset($_SESSION['user_id']) && $user->isLoggedIn();
    $action = (string) ($_POST['action'] ?? '');

    if (in_array($action, ['send_signup_otp', 'verify_signup_otp'], true)) {
        if ($isAuthenticated) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'You are already signed in.']);
            exit();
        }

        try {
            $emailRestrictions = require __DIR__ . '/../../config/email_restrictions.php';
            $emailValidationService = new EmailValidationService($emailRestrictions);
            $emailVerificationService = new EmailVerificationService($emailValidationService);
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $deviceId = 'campaign-' . substr(hash('sha256', session_id()), 0, 40);
            $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

            if ($action === 'send_signup_otp') {
                unset(
                    $_SESSION['campaign_signup_email_verified'],
                    $_SESSION['campaign_signup_email_verified_at'],
                    $_SESSION['campaign_signup_email']
                );

                $validationResult = $emailVerificationService->validateEmailForRegistration($email, $deviceId, $ipAddress);
                if (!$validationResult['success']) {
                    http_response_code(422);
                    echo json_encode([
                        'success' => false,
                        'errors' => ['account_email' => $validationResult['error'] ?? 'Please enter a valid email address.'],
                    ]);
                    exit();
                }

                $email = $validationResult['validation']['email'];
                $permission = $emailVerificationService->canRequestOtp($email, $deviceId, $ipAddress);
                if (!$permission['allowed']) {
                    http_response_code(429);
                    echo json_encode([
                        'success' => false,
                        'errors' => ['account_email' => $permission['message']],
                    ]);
                    exit();
                }

                $riskScore = (int) ($validationResult['risk']['score'] ?? 0);
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['campaign_signup_otp'] = [
                    'email' => $email,
                    'code' => $otp,
                    'expires_at' => time() + 3600,
                    'attempts' => 0,
                ];
                $emailVerificationService->logOtpRequest($email, $deviceId, $ipAddress, $riskScore);
                (new Mail())->sendVerificationCode($email, $otp);

                echo json_encode(['success' => true, 'message' => 'We sent a verification code to your email.']);
                exit();
            }

            $otp = trim((string) ($_POST['code'] ?? ''));
            $otpData = $_SESSION['campaign_signup_otp'] ?? null;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)
                || !is_array($otpData)
                || ($otpData['email'] ?? '') !== $email
                || !preg_match('/^\d{6}$/', $otp)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => ['signup_code' => 'Enter the 6-digit code sent to your email.']]);
                exit();
            }

            if ((int) ($otpData['attempts'] ?? 0) >= 5 || (int) ($otpData['expires_at'] ?? 0) < time()) {
                unset($_SESSION['campaign_signup_otp']);
                $emailVerificationService->logOtpAttempt($email, $deviceId, $ipAddress, false);
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => ['signup_code' => 'This code expired or has too many attempts. Request a new code.']]);
                exit();
            }

            $_SESSION['campaign_signup_otp']['attempts']++;
            $isValidCode = hash_equals((string) $otpData['code'], $otp);
            $emailVerificationService->logOtpAttempt($email, $deviceId, $ipAddress, $isValidCode);
            if (!$isValidCode) {
                http_response_code(422);
                echo json_encode(['success' => false, 'errors' => ['signup_code' => 'That code is not correct. Please try again.']]);
                exit();
            }

            $_SESSION['campaign_signup_email'] = $email;
            $_SESSION['campaign_signup_email_verified'] = $email;
            $_SESSION['campaign_signup_email_verified_at'] = time();
            unset($_SESSION['campaign_signup_otp']);
            echo json_encode(['success' => true, 'message' => 'Email verified. You can now publish your fundraiser.']);
            exit();
        } catch (Throwable $e) {
            error_log('[api/dashboard/create_campaign] Signup email verification failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to verify your email right now. Please try again.']);
            exit();
        }
    }

    if (!$isAuthenticated && $isPublishingDraft) {
        $_SESSION['redirect_after_login'] = '/campaigns/create.php?resume=1';
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Please sign in again to review and publish your saved draft.',
            'redirect_url' => BASE_URL . '/login.php',
        ]);
        exit();
    }

    $draft = null;
    if ($isPublishingDraft) {
        $draft = CampaignDraft::get();
        if ($draft === null || !hash_equals($draft['id'], $draftId)) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Your saved fundraiser draft has expired. Please start again.',
            ]);
            exit();
        }
    }

    $requiredFields = [
        'campaign_country_code',
        'campaign_city',
        'campaign_postal_code',
        'category',
        'beneficiary_type',
        'title',
        'goal',
        'description',
    ];
    $fieldErrors = [];
    foreach ($requiredFields as $field) {
        if (trim((string) ($_POST[$field] ?? '')) === '') {
            $fieldErrors[$field] = 'This field is required.';
        }
    }

    $campaignCountryCode = strtoupper(trim((string) ($_POST['campaign_country_code'] ?? '')));
    $campaignCity = trim((string) ($_POST['campaign_city'] ?? ''));
    $campaignPostalCode = trim((string) ($_POST['campaign_postal_code'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $beneficiaryType = trim((string) ($_POST['beneficiary_type'] ?? ''));
    $beneficiaryName = trim((string) ($_POST['beneficiary_name'] ?? ''));
    $beneficiaryRelationship = trim((string) ($_POST['beneficiary_relationship'] ?? ''));
    $goal = $_POST['goal'] ?? null;
    $description = trim((string) ($_POST['description'] ?? ''));
    $imageUpload = $_FILES['campaign_image'] ?? null;
    $hasImageUpload = is_array($imageUpload)
        && (int) ($imageUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $removeImage = ($_POST['remove_campaign_image'] ?? '') === '1';

    if ($title !== '' && mb_strlen($title) > 255) {
        $fieldErrors['title'] = 'Campaign title must be 255 characters or less.';
    }
    if ($campaignCity !== '' && mb_strlen($campaignCity) > 150) {
        $fieldErrors['campaign_city'] = 'City or town must be 150 characters or less.';
    }
    if ($campaignPostalCode !== '' && mb_strlen($campaignPostalCode) > 32) {
        $fieldErrors['campaign_postal_code'] = 'ZIP or postal code must be 32 characters or less.';
    }
    $countries = require __DIR__ . '/../../config/countries.php';
    $countryCodes = array_map(static function (array $country): string {
        return strtoupper((string) ($country['code'] ?? ''));
    }, $countries);
    if ($campaignCountryCode !== '' && !in_array($campaignCountryCode, $countryCodes, true)) {
        $fieldErrors['campaign_country_code'] = 'Please select a valid country.';
    }
    $allowedCategories = [
        'animals', 'business', 'community', 'competitions', 'creative', 'education',
        'emergency', 'environment', 'events', 'faith', 'family', 'funerals',
        'medical', 'monthly_bills', 'newlyweds', 'other', 'sports', 'travel',
        'volunteer', 'wishes',
    ];
    if ($category !== '' && !in_array($category, $allowedCategories, true)) {
        $fieldErrors['category'] = 'Please select a valid fundraiser category.';
    }
    if ($beneficiaryType !== '' && !in_array($beneficiaryType, ['self', 'individual', 'organization'], true)) {
        $fieldErrors['beneficiary_type'] = 'Please choose who the fundraiser is for.';
    }
    if (in_array($beneficiaryType, ['individual', 'organization'], true)) {
        if ($beneficiaryName === '') {
            $fieldErrors['beneficiary_name'] = 'Please enter the beneficiary or organization name.';
        } elseif (mb_strlen($beneficiaryName) > 255) {
            $fieldErrors['beneficiary_name'] = 'Name must be 255 characters or less.';
        }
        if ($beneficiaryRelationship === '') {
            $fieldErrors['beneficiary_relationship'] = 'Please tell us your relationship to them.';
        } elseif (mb_strlen($beneficiaryRelationship) > 255) {
            $fieldErrors['beneficiary_relationship'] = 'Relationship must be 255 characters or less.';
        }
    }
    if ($goal !== null && (!is_numeric($goal) || (float) $goal < 100)) {
        $fieldErrors['goal'] = 'Please enter a valid goal amount of at least $100.';
    }
    $accountName = trim((string) ($_POST['account_name'] ?? ''));
    $accountEmail = strtolower(trim((string) ($_POST['account_email'] ?? '')));
    $accountPassword = (string) ($_POST['account_password'] ?? '');
    $accountPasswordConfirmation = (string) ($_POST['account_password_confirmation'] ?? '');
    if (!$isAuthenticated) {
        if ($accountName === '' || mb_strlen($accountName) > 255) {
            $fieldErrors['account_name'] = 'Enter your name (up to 255 characters).';
        }
        if (!filter_var($accountEmail, FILTER_VALIDATE_EMAIL) || strlen($accountEmail) > 255) {
            $fieldErrors['account_email'] = 'Enter a valid email address.';
        }
        if (strlen($accountPassword) < 8
            || strlen($accountPassword) > 128
            || !preg_match('/[a-z]/', $accountPassword)
            || !preg_match('/[A-Z]/', $accountPassword)
            || !preg_match('/[0-9]/', $accountPassword)
            || !preg_match('/[^A-Za-z0-9]/', $accountPassword)) {
            $fieldErrors['account_password'] = 'Use at least 8 characters, including uppercase and lowercase letters, a number, and a symbol.';
        }
        if ($accountPassword !== $accountPasswordConfirmation) {
            $fieldErrors['account_password_confirmation'] = 'Passwords do not match.';
        }
        if (($_POST['accept_terms'] ?? '') !== '1') {
            $fieldErrors['accept_terms'] = 'You must accept the Terms of Service and Privacy Policy.';
        }
        if (
            (string) ($_SESSION['campaign_signup_email_verified'] ?? '') !== $accountEmail
            || (string) ($_SESSION['campaign_signup_email'] ?? '') !== $accountEmail
            || (int) ($_SESSION['campaign_signup_email_verified_at'] ?? 0) < time() - 3600
        ) {
            $fieldErrors['signup_code'] = 'Verify your email address before publishing.';
        }
    }
    if (!$isPublishingDraft && !$hasImageUpload) {
        $fieldErrors['campaign_image'] = 'Campaign image is required.';
    } elseif ($isPublishingDraft && ($removeImage || !CampaignDraft::isStagedImagePath($draft['image_path'])) && !$hasImageUpload) {
        $fieldErrors['campaign_image'] = 'Please choose a campaign image.';
    }

    if ($fieldErrors) {
        http_response_code(422);
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the highlighted fields and try again.',
            'errors' => $fieldErrors,
        ]);
        exit();
    }

    $campaignData = [
        'campaign_country_code' => $campaignCountryCode,
        'campaign_city' => $campaignCity,
        'campaign_postal_code' => $campaignPostalCode,
        'beneficiary_type' => $beneficiaryType,
        'beneficiary_name' => $beneficiaryType === 'self' ? null : $beneficiaryName,
        'beneficiary_relationship' => $beneficiaryType === 'self' ? null : $beneficiaryRelationship,
        'title' => $title,
        'category' => $category,
        'goal' => (float) $goal,
        'description' => $description,
        'user_id' => (int) ($_SESSION['user_id'] ?? 0),
    ];

    if (!$isAuthenticated) {
        if ($user->getUserByEmail($accountEmail)) {
            CampaignDraft::save($campaignData, $imageUpload);
            $_SESSION['redirect_after_login'] = '/campaigns/create.php?resume=1';
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'This email already has an account. Sign in to publish your saved fundraiser.',
                'redirect_url' => BASE_URL . '/login.php',
            ]);
            exit();
        }

        $accountData = [
            'name' => $accountName,
            'email' => $accountEmail,
            'password' => $accountPassword,
            'phone' => null,
            'phone_country_code' => null,
            'nin' => null,
            'country' => null,
            'nationality' => null,
            'city' => null,
            'newsletter' => false,
            'account_type' => 'personal',
            'organization_name' => null,
            'organization_type' => null,
            'registration_number' => null,
            'organization_website' => null,
            'organization_contact' => null,
            'avatar' => null,
            'is_phone_verified' => false,
            'phone_verification_status' => 'pending',
        ];
        $registration = $user->register($accountData);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $registration['id'];
        $_SESSION['user_name'] = $accountName;
        $_SESSION['user_email'] = $accountEmail;
        $_SESSION['last_activity'] = time();
        unset(
            $_SESSION['campaign_signup_otp'],
            $_SESSION['campaign_signup_email'],
            $_SESSION['campaign_signup_email_verified'],
            $_SESSION['campaign_signup_email_verified_at']
        );

        try {
            (new Mail())->sendWelcomeEmail($accountEmail, $accountName);
        } catch (Throwable $mailError) {
            error_log('[api/dashboard/create_campaign] Welcome email could not be sent: ' . $mailError->getMessage());
        }
        try {
            $notificationData = array_replace($accountData, [
                'phone' => '',
                'country' => '',
                'city' => '',
                'nin' => '',
            ]);
            (new Mail())->sendAdminRegistrationNotification($notificationData);
        } catch (Throwable $mailError) {
            error_log('[api/dashboard/create_campaign] Registration notification could not be sent: ' . $mailError->getMessage());
        }
    }

    TrustRiskGuard::enforceOrAbort((int) $_SESSION['user_id'], 'create_campaign', true);

    $campaign = new Campaign();
    if (!$campaign->canCreateCampaign((int) $_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Free accounts can create only one campaign. Upgrade to premium to manage more than one campaign.',
        ]);
        exit();
    }

    if ($isPublishingDraft) {
        $draft = CampaignDraft::update(
            $campaignData,
            $draftId,
            $hasImageUpload ? $imageUpload : null,
            $removeImage
        );
        $campaignId = $campaign->createFromDraft($campaignData, $draft['image_path']);
        if (!$campaignId) {
            throw new Exception('Campaign creation failed. Please try again.');
        }

        CampaignDraft::remove();
    } else {
        $campaignData['campaign_image'] = $imageUpload;
        $campaignId = $campaign->create($campaignData);
        if (!$campaignId) {
            throw new Exception('Campaign creation failed. Please try again.');
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Campaign created successfully.',
        'campaign_id' => (int) $campaignId,
        'redirect_url' => BASE_URL . '/campaigns/view.php?id=' . (int) $campaignId,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => ['campaign_image' => $e->getMessage()],
    ]);
} catch (Throwable $e) {
    $bufferedOutput = ob_get_contents();
    if ($bufferedOutput !== false && trim($bufferedOutput) !== '') {
        error_log('[api/dashboard/create_campaign] Unexpected output before JSON: ' . trim($bufferedOutput));
    }

    if (ob_get_level() > 0) {
        ob_clean();
    }

    error_log('[api/dashboard/create_campaign] ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to create the campaign right now. Please try again.',
    ]);
}

if (ob_get_level() > 0) {
    ob_end_flush();
}
