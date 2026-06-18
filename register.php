<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/User.php';
require_once __DIR__ . '/config/Mail.php'; // Add this line

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = null;
$user = new User();

// Redirect if already logged in
if ($user->isLoggedIn()) {
    header("Location: dashboard");
    exit();
}

// Handle registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate password match
        if ($_POST['password'] !== $_POST['password_confirmation']) {
            throw new Exception("Passwords do not match");
        }

        // Validate terms acceptance
        if (!isset($_POST['terms'])) {
            throw new Exception("You must accept the Terms of Service and Privacy Policy");
        }

        // Validate NIN format (basic validation)
        if (empty($_POST['nin'])) {
            throw new Exception("National ID Number is required");
        }
        
        // NIN validation - 14 characters starting with alphabet
        $nin = trim($_POST['nin']);
        if (strlen($nin) !== 14) {
            throw new Exception("National ID Number must be exactly 14 characters");
        }
        
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]{13}$/', $nin)) {
            throw new Exception("National ID Number must start with a letter and contain only letters and numbers");
        }

        // Prepare user data
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

        // Register user
        $registrationData = $user->register($userData);

        // Send welcome email with verification instructions
        $mailer = new Mail();
        $verificationLink = BASE_URL . "/account/verify.php?token=" . $registrationData['verification_token'];
        
        try {
            $mailer->sendWelcomeEmail($registrationData['email'], [
                'name' => $userData['name'],
                'verificationLink' => $verificationLink
            ]);
            
            $_SESSION['success'] = "Registration successful!" . " "
                          ."Verify your email to complete your registration.";
        } catch (Exception $e) {
            // Log email error but don't stop registration process
            error_log("Failed to send welcome email: " . $e->getMessage());
            $_SESSION['success'] = "Registration successful! However, we could not send the verification email. " .
                          "Please contact support if you don't receive it within a few minutes.";
        }

        header("Location: login.php");
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta name="google-adsense-account" content="ca-pub-9839651797371714">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <!-- Favicon -->
        <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
        <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
        <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
        <title>Register - CroFunding</title>
        
        <!-- Tailwind CSS CDN -->
        <script src="https://cdn.tailwindcss.com"></script>
        
        <!-- Custom Tailwind Configuration -->
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

        <!-- Custom Styles -->
        <style type="text/tailwindcss">
            @layer utilities {
                .bg-pattern {
                    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                }
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Set default country to Uganda (most common for your users)
                const countrySelect = document.getElementById('country');
                if (countrySelect) {
                    const ugandaOption = countrySelect.querySelector('option[value="UG"]');
                    if (ugandaOption) {
                        ugandaOption.selected = true;
                    }
                }
            });

            // NIN validation function
            function validateNIN(nin) {
                if (nin.length !== 14) {
                    return { valid: false, message: "NIN is Invalid" };
                }
                
                if (!/^[A-Za-z][A-Za-z0-9]{13}$/.test(nin)) {
                    return { valid: false, message: "NIN is Invalid" };
                }
                
                return { valid: true, message: "Valid NIN format" };
            }

            // Add real-time NIN validation and character counter
            document.addEventListener('DOMContentLoaded', function() {
                const ninInput = document.getElementById('nin');
                const ninCounter = document.getElementById('nin-counter');
                
                if (ninInput && ninCounter) {
                    ninInput.addEventListener('input', function() {
                        const nin = this.value.trim();
                        const validation = validateNIN(nin);
                        
                        // Update character counter
                        ninCounter.textContent = nin.length;
                        
                        // Update counter color based on length
                        if (nin.length === 14) {
                            ninCounter.className = 'text-green-600 font-semibold';
                        } else if (nin.length > 14) {
                            ninCounter.className = 'text-red-600 font-semibold';
                        } else {
                            ninCounter.className = 'text-gray-400';
                        }
                        
                        // Remove existing validation messages
                        const existingMessage = this.parentNode.querySelector('.nin-validation-message');
                        if (existingMessage) {
                            existingMessage.remove();
                        }
                        
                        if (nin.length > 0) {
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'nin-validation-message mt-2 text-sm';
                            
                            if (validation.valid) {
                                messageDiv.className += ' text-green-600';
                                messageDiv.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' + validation.message;
                            } else {
                                messageDiv.className += ' text-red-600';
                                messageDiv.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>' + validation.message;
                            }
                            
                            this.parentNode.appendChild(messageDiv);
                        }
                    });
                    
                    // Convert to uppercase automatically
                    ninInput.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
                }
            });

            // Avatar preview functionality
            function previewAvatar(input) {
                const file = input.files[0];
                const preview = document.getElementById('avatar-preview');
                const fileInfo = document.getElementById('file-info');
                const fileName = document.getElementById('file-name');
                
                if (file) {
                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        alert('Please select a valid image file');
                        input.value = '';
                        return;
                    }
                    
                    // Validate file size (10MB max)
                    if (file.size > 10 * 1024 * 1024) {
                        alert('File size must be less than 10MB');
                        input.value = '';
                        return;
                    }
                    
                    // Create preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Avatar preview" class="h-full w-full object-cover">`;
                    };
                    reader.readAsDataURL(file);
                    
                    // Show file info
                    fileName.textContent = file.name;
                    fileInfo.classList.remove('hidden');
                }
            }
            
            function removeAvatar() {
                const input = document.getElementById('avatar');
                const preview = document.getElementById('avatar-preview');
                const fileInfo = document.getElementById('file-info');
                
                // Reset input
                input.value = '';
                
                // Reset preview
                preview.innerHTML = `
                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                `;
                
                // Hide file info
                fileInfo.classList.add('hidden');
            }
        </script>
    </head>
    <body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-emerald-600">CroFunding</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="login" class="text-gray-600 hover:text-emerald-600 transition-colors duration-200">
                            Sign In
                        </a>
                        <a href="login" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition-colors duration-200">
                            Get Started
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <!-- Left Side - Brand & Features -->
                <div class="space-y-8">
                    <!-- Back to Home Link -->
                    <div class="text-center lg:text-left">
                        <a href="<?php echo BASE_URL; ?>" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 transition-colors duration-200 group">
                            <svg class="w-5 h-5 mr-2 transform group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span class="font-medium">Back to Home</span>
                        </a>
                    </div>
                    
                    <div class="text-center lg:text-left">
                        <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                            Start Your
                            <span class="text-emerald-600">Fundraising</span>
                            Journey
                        </h2>
                        <p class="text-xl text-gray-600 mt-4 leading-relaxed">
                            Join thousands of creators who are making a difference in their communities through powerful fundraising campaigns.
                        </p>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:border-emerald-200 transition-all duration-300">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Create Campaigns</h3>
                            <p class="text-gray-600 text-sm">Share your story and start raising funds for your cause</p>
                        </div>

                        <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:border-emerald-200 transition-all duration-300">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Join Community</h3>
                            <p class="text-gray-600 text-sm">Connect with supporters and like-minded creators</p>
                        </div>

                        <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:border-emerald-200 transition-all duration-300">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Secure Platform</h3>
                            <p class="text-gray-600 text-sm">Your funds are protected with bank-level security</p>
                        </div>

                        <div class="bg-white/60 backdrop-blur-sm rounded-2xl p-6 border border-gray-200/50 hover:border-emerald-200 transition-all duration-300">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Fast Setup</h3>
                            <p class="text-gray-600 text-sm">Get your campaign live in minutes, not hours</p>
                        </div>
                </div>
            </div>

                <!-- Right Side - Registration Form -->
                <div class="w-full max-w-md mx-auto lg:mx-0">
                    <!-- Registration Form -->
                    <div class="bg-white/80 backdrop-blur-sm p-8 rounded-3xl shadow-xl border border-gray-200/50">
                        <div class="text-center mb-8">
                            <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold text-gray-900">Create Account</h2>
                            <p class="text-gray-600 mt-2">Join our community of changemakers</p>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                <?php echo $error; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="space-y-5" enctype="multipart/form-data">
                            <!-- Name Input -->
                            <div>
                                <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                                <input id="name" name="name" type="text" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="Enter your name exactly as it appears on your National ID" />
                                
                                <!-- Important Notice -->
                                <div class="mt-3 p-3 bg-blue-50/80 border border-blue-200 rounded-xl">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                Use the exact name from your National ID card.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Email Input -->
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                                <input id="email" name="email" type="email" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="Enter your email address" />
                            </div>

                            <!-- Password Input -->
                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                                <input id="password" name="password" type="password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="Create a strong password" />
                            </div>

                            <!-- Confirm Password Input -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="Confirm your password" />
                            </div>

                       

                            <!-- Phone Number Input -->
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="Internation Format Required" />
                            </div>

                            <!-- National Identification Number (NIN) Input -->
                            <div>
                                <label for="nin" class="block text-sm font-semibold text-gray-700 mb-2">National ID Number (NIN)</label>
                                <div class="relative">
                                    <input type="text" id="nin" name="nin" required maxlength="14" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="A1234567890123" />
                                    <div class="absolute right-3 top-3 text-xs text-gray-400">
                                        <span id="nin-counter">0</span>/14
                                    </div>
                                </div>
                                
                                <!-- NIN Notice -->
                                <div class="mt-3 p-3 bg-blue-50/80 border border-blue-200 rounded-xl">
                                    <div class="flex items-start">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-blue-700">
                                                Please Provide your National Identification Number exactly as it appears on your ID card.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Country Selection -->
                            <div>
                                <label for="country" class="block text-sm font-semibold text-gray-700 mb-2">Country</label>
                                <select id="country" name="country" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50">
                                    <option value="">Select a country</option>
                                    <?php
                                    $countries = array(
                                        "US" => "United States",
                                        "GB" => "United Kingdom",
                                        "CA" => "Canada",
                                        "AU" => "Australia",
                                        "DE" => "Germany",
                                        "FR" => "France",
                                        "IT" => "Italy",
                                        "ES" => "Spain",
                                        "BR" => "Brazil",
                                        "IN" => "India",
                                        "CN" => "China",
                                        "JP" => "Japan",
                                        "RU" => "Russia",
                                        "ZA" => "South Africa",
                                        "NG" => "Nigeria",
                                        "KE" => "Kenya",
                                        "UG" => "Uganda",
                                        "TZ" => "Tanzania"
                                    );
                                    foreach ($countries as $code => $name): ?>
                                        <option value="<?php echo $code; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- City/Region Input -->
                            <div>
                                <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">City/Region</label>
                                <input type="text" id="city" name="city" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 bg-gray-50/50" placeholder="Enter your city or region" />
                            </div>


                            <!-- Terms and Conditions -->
                            <div class="flex items-start space-x-3">
                                <div class="flex items-center h-5">
                                    <input id="terms" name="terms" type="checkbox" required class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded-xl">
                                </div>
                                <div class="text-sm">
                                    <label for="terms" class="font-semibold text-gray-700">
                                        I agree to the <a href="terms" class="text-emerald-600 hover:text-emerald-500 underline">Terms of Service</a> and 
                                        <a href="privacy" class="text-emerald-600 hover:text-emerald-500 underline">Privacy Policy</a>
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="w-full flex justify-center items-center py-4 px-6 border border-transparent rounded-xl shadow-lg text-lg font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200 transform hover:scale-[1.02]">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Create Account
                            </button>
                        </form>

                        <!-- Login Link -->
                        <div class="text-center mt-8">
                            <p class="text-gray-600">
                                Already have an account? 
                                <a href="login" class="font-semibold text-emerald-600 hover:text-emerald-500 underline">
                                    Sign in instead
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white/80 backdrop-blur-sm border-t border-gray-200 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <p class="text-gray-600 text-sm">
                        © 2024 CroFunding. All rights reserved. | 
                        <a href="terms" class="text-emerald-600 hover:text-emerald-500">Terms</a> | 
                        <a href="privacy" class="text-emerald-600 hover:text-emerald-500">Privacy</a>
                    </p>
                </div>
            </div>
        </footer>
    </body>
</html>