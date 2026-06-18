<?php
session_start();
ob_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/User.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Initialize success message only (no error messages shown to users)
$successMessage = '';

// Initialize User object
$user = new User();
$userData = $user->getUserById($_SESSION['user_id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update Profile
        if (isset($_POST['update_profile'])) {
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            
            if (empty($email)) {
                error_log("Profile update validation failed for user " . $_SESSION['user_id'] . ": Email is required");
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                error_log("Profile update validation failed for user " . $_SESSION['user_id'] . ": Invalid email format");
            } else {
                try {
                    // Note: Name is not included to prevent manipulation for ID verification
                    $user->updateProfile($_SESSION['user_id'], [
                        'email' => $email,
                        'phone' => $phone
                    ]);
                    
                    $successMessage = "Profile updated successfully";
                } catch (Exception $e) {
                    error_log("Profile update failed for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
                }
            }
        }
        
        // Change Password
        if (isset($_POST['change_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($newPassword !== $confirmPassword) {
                error_log("Password change validation failed for user " . $_SESSION['user_id'] . ": Passwords do not match");
            } elseif (strlen($newPassword) < 8) {
                error_log("Password change validation failed for user " . $_SESSION['user_id'] . ": Password too short");
            } else {
                try {
                    $user->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
                    $successMessage = "Password changed successfully";
                } catch (Exception $e) {
                    error_log("Password change failed for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
                }
            }
        }
        
    } catch (Exception $e) {
        // Log the error professionally - no error shown to user
        error_log("Account settings error for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    } catch (Error $e) {
        // Log PHP errors (fatal errors, etc.) - no error shown to user
        error_log("Account settings PHP error for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

// Include header
include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <div class="p-6">
                <?php if ($successMessage): ?>
                    <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700"><?php echo htmlspecialchars($successMessage); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Profile Settings -->
                <div class="bg-white rounded-lg shadow-sm mb-6">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Profile Settings</h2>
                    </div>
                    <div class="p-6">
                        <!-- Full Name Display (Read-only for security) -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-gray-900">Full Name</h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($userData['name']); ?></p>
                                    
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="email" name="email" 
                                            value="<?php echo htmlspecialchars($userData['email']); ?>" 
                                            class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="tel" name="phone" 
                                            value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" 
                                            class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300">
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" name="update_profile" 
                                        class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-colors duration-200 ease-in-out shadow-sm">
                                    Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">Change Password</h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="password" name="current_password" 
                                            class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="password" name="new_password" 
                                            class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="password" name="confirm_password" 
                                            class="block w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 transition-colors duration-200 ease-in-out hover:border-gray-300"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" name="change_password" 
                                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
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