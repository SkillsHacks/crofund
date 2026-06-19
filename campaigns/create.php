<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/User.php'; // Add User class

// Check if user is logged in using session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_URL . "/login.php?message=" . urlencode('Please login to create a campaign'));
    exit();
}

// Initialize User object
$user = new User();
if (!$user->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_URL . "/login.php?message=" . urlencode('Please login to create a campaign'));
    exit();
}

$error = null;
$success = null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Log the attempt
        error_log("Campaign creation attempt for user " . $_SESSION['user_id'] . " with data: " . json_encode($_POST));
        
        // Basic input validation
        $required_fields = ['title', 'category', 'goal', 'description'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            error_log("Campaign creation validation failed for user " . $_SESSION['user_id'] . ": Missing fields: " . implode(', ', $missing_fields));
            throw new Exception("Please fill in all required fields.");
        }
        
        // Validate goal amount
        if (!is_numeric($_POST['goal']) || $_POST['goal'] < 100) {
            error_log("Campaign creation validation failed for user " . $_SESSION['user_id'] . ": Invalid goal amount: " . $_POST['goal']);
            throw new Exception("Please enter a valid goal amount (minimum $100).");
        }
        
        // Sanitize input data (preserve emojis and special characters but prevent XSS)
        $sanitized_data = [
            'title' => trim(filter_var($_POST['title'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)),
            'category' => trim(filter_var($_POST['category'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES)),
            'goal' => (float)$_POST['goal'],
            'description' => trim(filter_var($_POST['description'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES))
        ];
        
        // Add user_id to the data
        $sanitized_data['user_id'] = $_SESSION['user_id'];
        
        // Handle file upload if present
        if (isset($_FILES['campaign_image']) && $_FILES['campaign_image']['error'] === UPLOAD_ERR_OK) {
            $sanitized_data['campaign_image'] = $_FILES['campaign_image'];
        }
        
        $campaign = new Campaign();
        $result = $campaign->create($sanitized_data);
        
        if ($result) {
            error_log("Campaign created successfully with ID: " . $result . " for user " . $_SESSION['user_id']);
            $success = "Campaign created successfully!";
            header("Location: view.php?id=" . $result);
            exit();
        } else {
            // Log the error instead of showing to user
            error_log("Campaign creation failed for user " . $_SESSION['user_id'] . ": Campaign::create() returned false");
            throw new Exception("Something went wrong. Please try again.");
        }
    } catch (Exception $e) {
        // Log the actual error details with stack trace
        error_log("Campaign creation error for user " . $_SESSION['user_id'] . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Show generic error message to user
        $error = "Something went wrong. Please try again.";
    } catch (Error $e) {
        // Log PHP errors (fatal errors, etc.)
        error_log("Campaign creation PHP error for user " . $_SESSION['user_id'] . ": " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        // Show generic error message to user
        $error = "Something went wrong. Please try again.";
    }
}

// Include header AFTER all potential redirects
include '../components/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Campaign - CroFunding</title>
    
    <!-- Tailwind CSS -->
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
</head>
<body class="bg-gray-50">
    <!-- Include Header -->
    <?php include '../components/header.php'; ?>

    <div class="flex h-screen pt-16">
        <!-- Include Sidebar -->
        <?php include '../components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 overflow-auto bg-gray-50">
            <div class="min-h-screen px-4 sm:px-6 lg:px-8 py-8 max-w-4xl mx-auto">
                <!-- Page Header -->
                <div class="mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="mb-4 sm:mb-0">
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create New Campaign</h1>
                            <p class="mt-2 text-base sm:text-lg text-gray-600">Launch your fundraising campaign and start making a difference.</p>
                        </div>
                        <!-- Back Button -->
                        <a href="<?php echo BASE_URL; ?>/dashboard.php" 
                           class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to Campaigns
                        </a>
                    </div>
                </div>

                <!-- Error Messages -->
                <?php if (isset($error)): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-red-700"><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Campaign Creation Form -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-4 sm:p-6 md:p-8">
                        <form method="POST" enctype="multipart/form-data" class="space-y-8">
                            <!-- Basic Information Section -->
                            <div class="space-y-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h2 class="ml-3 text-xl font-semibold text-gray-900">Basic Information</h2>
                                </div>
                                
                                <!-- Campaign Title -->
                                <div class="space-y-1">
                                    <label for="title" class="block text-sm font-medium text-gray-700">Campaign Title</label>
                                    <input type="text" id="title" name="title" required
                                        placeholder="Enter a clear, descriptive title"
                                        class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 text-base">
                                    <p class="mt-1 text-sm text-gray-500">Make it clear and attention-grabbing</p>
                                </div>

                                <!-- Campaign Category -->
                                <div class="space-y-1">
                                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                                    <select id="category" name="category" required
                                        class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 text-base">
                                        <option value="">Select a category</option>
                                        <option value="education">Education</option>
                                        <option value="medical">Medical & Health</option>
                                        <option value="emergency">Emergency Relief</option>
                                        <option value="community">Community Project</option>
                                        <option value="creative">Creative Project</option>
                                        <option value="nonprofit">Nonprofit</option>
                                        <option value="business">Small Business</option>
                                        <option value="technology">Technology</option>
                                        <option value="environment">Environment</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Funding Details Section -->
                            <div class="space-y-6 pt-6 border-t border-gray-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <h2 class="ml-3 text-xl font-semibold text-gray-900">Funding Details</h2>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <!-- Funding Goal -->
                                    <div class="space-y-1">
                                        <label for="goal" class="block text-sm font-medium text-gray-700">Funding Goal ($)</label>
                                        <div class="mt-1 relative rounded-lg">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <input type="number" id="goal" name="goal" min="100" required
                                                placeholder="5000"
                                                class="block w-full pl-8 pr-12 py-3 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 text-base">
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500">Minimum $100</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Campaign Content Section -->
                            <div class="space-y-6 pt-6 border-t border-gray-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        </div>
                                    <h2 class="ml-3 text-xl font-semibold text-gray-900">Campaign Content</h2>
                                </div>

                                <!-- Campaign Description -->
                                <div class="space-y-1">
                                    <label for="description" class="block text-sm font-medium text-gray-700">Campaign Story</label>
                                    <textarea id="description" name="description" rows="8" required
                                        placeholder="Tell your story. Why are you raising funds? How will the money be used? What impact will it make?"
                                        class="mt-1 block w-full px-4 py-3 rounded-lg border border-gray-300 shadow-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 text-base"></textarea>
                                    <p class="mt-1 text-sm text-gray-500">Be detailed and transparent about your campaign's purpose and goals</p>
                                </div>

                                <!-- Campaign Image -->
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700">Campaign Image</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-emerald-500 transition-colors duration-150">
                                        <div class="space-y-2 text-center">
                                            <div id="image-preview" class="hidden mb-4">
                                                <img src="" alt="Preview" class="mx-auto rounded-lg max-h-48 object-cover">
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                                <div class="flex text-sm text-gray-600 mt-2">
                                                    <label for="campaign_image" class="relative cursor-pointer bg-white rounded-md font-medium text-emerald-600 hover:text-emerald-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-emerald-500">
                                                        <span>Upload a file</span>
                                                        <input id="campaign_image" name="campaign_image" type="file" class="sr-only" accept="image/*" required>
                                                    </label>
                                                    <p class="pl-1">or drag and drop</p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-2">PNG, JPG, GIF up to 10MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Section -->
                            <div class="pt-6 border-t border-gray-200">
                                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <p class="text-sm text-gray-500 text-center sm:text-left">
                                        By creating this campaign, you agree to our Terms of Service and Privacy Policy.
                                    </p>
                                    <button type="submit" 
                                        class="w-full sm:w-auto px-6 py-3 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition duration-150">
                                        Create Campaign
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Preview Image Script -->
    <script>
        const imageInput = document.getElementById('campaign_image');
        const imagePreview = document.getElementById('image-preview');
        const defaultUploadContent = document.querySelector('.space-y-2 .flex.flex-col');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.querySelector('img').src = e.target.result;
                    imagePreview.classList.remove('hidden');
                    defaultUploadContent.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        });

        // Drag and drop functionality
        const dropZone = document.querySelector('.border-dashed');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-emerald-500', 'bg-emerald-50');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-emerald-500', 'bg-emerald-50');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];
            
            if (file && file.type.startsWith('image/')) {
                imageInput.files = dt.files;
                const event = new Event('change');
                imageInput.dispatchEvent(event);
            }
        }
    </script>
</body>
</html>