<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';

$user = new User();
if (!$user->isLoggedIn()) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$currentUser = $user->getCurrentUser();

$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_profile'])) {
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            
            if (empty($email)) {
                error_log("Profile update validation failed for user " . $_SESSION['user_id'] . ": Email is required");
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Profile update validation failed for user " . $_SESSION['user_id'] . ": Invalid email format");
            } else {
                $user->updateProfile($_SESSION['user_id'], [
                    'email' => $email,
                    'phone' => $phone
                ]);
                
                $successMessage = "Profile updated successfully";
                $currentUser = $user->getCurrentUser();
            }
        }
        
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($newPassword !== $confirmPassword) {
                error_log("Password change validation failed for user " . $_SESSION['user_id'] . ": Passwords do not match");
            } elseif (strlen($newPassword) < 8) {
                error_log("Password change validation failed for user " . $_SESSION['user_id'] . ": Password too short");
            } else {
                $user->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                $successMessage = "Password changed successfully";
            }
        }
    } catch (Exception $e) {
        error_log("Account settings error for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    } catch (Error $e) {
        error_log("Account settings PHP error for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="shortcut icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/Logo.png">
    <title>Account Settings - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen pt-16">
        <?php include '../components/sidebar.php'; ?>
        
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50 lg:ml-64">
            <div class="p-3 sm:p-6">
                <?php if ($successMessage): ?>
                    <div class="mb-4 sm:mb-6 bg-green-50 border-l-4 border-green-500 p-3 sm:p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs sm:text-sm text-green-700"><?php echo htmlspecialchars($successMessage); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-sm mb-4 sm:mb-6">
                    <div class="p-3 sm:p-6 border-b border-gray-200">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Profile Settings</h2>
                    </div>
                    <div class="p-3 sm:p-6">
                        <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-xs sm:text-sm font-medium text-gray-900">Full Name</h3>
                                    <p class="text-xs sm:text-sm text-gray-600"><?php echo htmlspecialchars($currentUser['name']); ?></p>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="space-y-4 sm:space-y-6">
                            <div class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2">
                                <div class="space-y-1 sm:space-y-2">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700">Email Address</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="email" name="email" 
                                            value="<?php echo htmlspecialchars($currentUser['email']); ?>" 
                                            class="block w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                                
                                <div class="space-y-1 sm:space-y-2">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700">Phone Number</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="tel" name="phone" 
                                            value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" 
                                            class="block w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300">
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" name="update_profile" 
                                    class="px-4 sm:px-6 py-2 sm:py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200 ease-in-out shadow-sm text-xs sm:text-sm">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-3 sm:p-6 border-b border-gray-200">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900">Change Password</h2>
                    </div>
                    <div class="p-3 sm:p-6">
                        <form method="POST" class="space-y-4 sm:space-y-6">
                            <div class="grid grid-cols-1 gap-4 sm:gap-6 sm:grid-cols-2">
                                <div class="space-y-1 sm:space-y-2">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700">Current Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="password" name="current_password" 
                                            class="block w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                                
                                <div class="space-y-1 sm:space-y-2">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700">New Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="password" name="new_password" 
                                            class="block w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                                
                                <div class="space-y-1 sm:space-y-2">
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="password" name="confirm_password" 
                                            class="block w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" name="change_password" 
                                    class="px-3 sm:px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 text-xs sm:text-sm">
                                    Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
