<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/config/Mail.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$user = new User();

if ($user->isLoggedIn()) {
    header("Location: dashboard");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
        try {
            if ($_POST['password'] !== $_POST['password_confirmation']) {
                throw new Exception("Passwords do not match");
            }

            if (!isset($_POST['terms'])) {
                throw new Exception("You must accept the Terms of Service and Privacy Policy");
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

            $userData = [
                'name' => $_POST['name'] ?? '',
                'email' => $_POST['email'] ?? '',
                'password' => $_POST['password'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'nin' => $_POST['nin'] ?? '',
                'country' => $_POST['country'] ?? '',
                'city' => $_POST['city'] ?? '',
                'newsletter' => isset($_POST['newsletter']),
                'avatar' => $_FILES['avatar'] ?? null
            ];

            $registrationData = $user->register($userData);

            $mailer = new Mail();
            
            try {
                $mailer->sendVerificationCode($registrationData['email'], $registrationData['verification_code'], $userData['name']);
                
                // Send admin notification
                try {
                    $mailer->sendAdminRegistrationNotification($userData);
                } catch (Exception $adminMailException) {
                    error_log("Failed to send admin registration notification: " . $adminMailException->getMessage());
                }
                
                // Store email in session for verification page
                $_SESSION['verify_email'] = $registrationData['email'];
                $_SESSION['success'] = "Registration successful! Please check your email for the verification code.";
            } catch (Exception $e) {
                error_log("Failed to send verification email: " . $e->getMessage());
                $_SESSION['verify_email'] = $registrationData['email'];
                $_SESSION['error'] = "Registration successful! However, we could not send the verification email. Please click 'Resend Code' to try again.";
            }

            header("Location: verify-email.php");
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
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const countrySelect = document.getElementById('country');
            if (countrySelect) {
                const ugandaOption = countrySelect.querySelector('option[value="UG"]');
                if (ugandaOption) {
                    ugandaOption.selected = true;
                }
            }

            const ninInput = document.getElementById('nin');
            const ninCounter = document.getElementById('nin-counter');
            
            if (ninInput && ninCounter) {
                ninInput.addEventListener('input', function() {
                    const nin = this.value.trim();
                    const validation = validateNIN(nin);
                    
                    ninCounter.textContent = nin.length;
                    
                    if (nin.length === 14) {
                        ninCounter.className = 'text-emerald-600 font-semibold';
                    } else if (nin.length > 14) {
                        ninCounter.className = 'text-red-600 font-semibold';
                    } else {
                        ninCounter.className = 'text-slate-400';
                    }
                    
                    const existingMessage = this.parentNode.querySelector('.nin-validation-message');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                    
                    if (nin.length > 0) {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = 'nin-validation-message mt-2 text-sm';
                        
                        if (validation.valid) {
                            messageDiv.className += ' text-emerald-600';
                            messageDiv.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' + validation.message;
                        } else {
                            messageDiv.className += ' text-red-600';
                            messageDiv.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' + validation.message;
                        }
                        
                        this.parentNode.appendChild(messageDiv);
                    }
                });
                
                ninInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
            }
        });

        function validateNIN(nin) {
            if (nin.length !== 14) {
                return { valid: false, message: "NIN is Invalid" };
            }
            
            if (!/^[A-Za-z][A-Za-z0-9]{13}$/.test(nin)) {
                return { valid: false, message: "NIN is Invalid" };
            }
            
            return { valid: true, message: "Valid NIN format" };
        }

        function previewAvatar(input) {
            const file = input.files[0];
            const preview = document.getElementById('avatar-preview');
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            
            if (file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file');
                    input.value = '';
                    return;
                }
                
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Avatar preview" class="h-full w-full object-cover">`;
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
</head>
<body class="bg-slate-50 min-h-screen">
    <?php include __DIR__ . '/components/public-header.php'; ?>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            <!-- Left Side - Brand Info -->
            <div class="space-y-8">
                <div>
                    <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 transition-colors font-semibold group">
                        <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Home
                    </a>
                </div>
                
                <div>
                    <h2 class="text-4xl font-bold text-slate-900 mb-4">
                        Start Your
                        <span class="text-emerald-600">Fundraising</span>
                        Journey
                    </h2>
                    <p class="text-lg text-slate-600 leading-relaxed">
                        Join thousands of verified fundraisers and donors making a real impact in their communities.
                    </p>
                </div>

                <!-- Features Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-soft">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Create Campaigns</h3>
                        <p class="text-slate-600 text-sm">Share your story and start raising funds for your cause</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-soft">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Join Community</h3>
                        <p class="text-slate-600 text-sm">Connect with supporters and like-minded changemakers</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-soft">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Secure Platform</h3>
                        <p class="text-slate-600 text-sm">Your funds are protected with bank-level security</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-soft">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">Fast Setup</h3>
                        <p class="text-slate-600 text-sm">Get your campaign live in minutes, not hours</p>
                    </div>
                </div>
            </div>

            <!-- Right Side - Registration Form -->
            <div class="w-full max-w-md mx-auto lg:mx-0">
                <div class="bg-white rounded-2xl shadow-soft border border-slate-100 p-8">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-slate-900">Create Account</h2>
                        <p class="text-slate-600 mt-2">Join our community of changemakers</p>
                    </div>

                    <?php if (isset($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo $error; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-5" enctype="multipart/form-data">
                        <!-- Name Input -->
                        <div>
                            <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                            <input id="name" name="name" type="text" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter your name exactly as it appears on your National ID">
                            
                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm text-blue-800">
                                        Use the exact name from your National ID card.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                            <input id="email" name="email" type="email" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter your email address">
                        </div>

                        <!-- Password Input -->
                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                            <input id="password" name="password" type="password" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Create a strong password">
                        </div>

                        <!-- Confirm Password Input -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Confirm your password">
                        </div>

                        <!-- Phone Input -->
                        <div>
                            <label for="phone" class="block text-sm font-semibold text-slate-700 mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="International Format Required">
                        </div>

                        <!-- NIN Input -->
                        <div>
                            <label for="nin" class="block text-sm font-semibold text-slate-700 mb-2">National ID Number (NIN)</label>
                            <div class="relative">
                                <input type="text" id="nin" name="nin" required maxlength="14" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="A1234567890123">
                                <div class="absolute right-4 top-3 text-xs text-slate-400">
                                    <span id="nin-counter">0</span>/14
                                </div>
                            </div>
                            
                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="text-sm text-blue-800">
                                        Please provide your National Identification Number exactly as it appears on your ID card.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Country Selection -->
                        <div>
                            <label for="country" class="block text-sm font-semibold text-slate-700 mb-2">Country</label>
                            <select id="country" name="country" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200">
                                <option value="">Select a country</option>
                                <?php
                                $countries = [
                                    "US" => "United States", "GB" => "United Kingdom", "CA" => "Canada", "AU" => "Australia",
                                    "DE" => "Germany", "FR" => "France", "IT" => "Italy", "ES" => "Spain",
                                    "BR" => "Brazil", "IN" => "India", "CN" => "China", "JP" => "Japan",
                                    "RU" => "Russia", "ZA" => "South Africa", "NG" => "Nigeria", "KE" => "Kenya",
                                    "UG" => "Uganda", "TZ" => "Tanzania"
                                ];
                                foreach ($countries as $code => $name): ?>
                                    <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- City Input -->
                        <div>
                            <label for="city" class="block text-sm font-semibold text-slate-700 mb-2">City/Region</label>
                            <input type="text" id="city" name="city" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-200" placeholder="Enter your city or region">
                        </div>

                        <!-- Terms and Conditions -->
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

                        <!-- Submit Button -->
                        <button type="submit" class="w-full flex items-center justify-center py-3.5 px-4 border border-transparent rounded-xl text-base font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200 shadow-soft">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Create Account
                        </button>
                    </form>

                    <!-- Login Link -->
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
</body>
</html>
