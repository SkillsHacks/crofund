<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/User.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_URL . "/login.php?message=" . urlencode('Please login to create a campaign'));
    exit();
}

$user = new User();
if (!$user->isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: " . BASE_URL . "/login.php?message=" . urlencode('Please login to create a campaign'));
    exit();
}

$error = null;
$success = null;
$fieldErrors = [];
$oldInput = [
    'title' => '',
    'category' => '',
    'goal' => '',
    'description' => '',
];

$campaignLimits = new Campaign();
if (!$campaignLimits->canCreateCampaign((int) ($_SESSION['user_id'] ?? 0))) {
    $_SESSION['error'] = 'Free accounts can create only one campaign. Upgrade to premium to manage more than one campaign.';
    header('Location: ' . BASE_URL . '/account/premium.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldInput = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'goal' => trim((string) ($_POST['goal'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
    ];

    if ($oldInput['title'] === '') {
        $fieldErrors['title'] = 'Campaign title is required.';
    }
    if ($oldInput['category'] === '') {
        $fieldErrors['category'] = 'Category is required.';
    }
    if ($oldInput['goal'] === '' || !is_numeric($oldInput['goal']) || (float) $oldInput['goal'] < 100) {
        $fieldErrors['goal'] = 'Please enter a valid goal amount of at least $100.';
    }
    if ($oldInput['description'] === '') {
        $fieldErrors['description'] = 'Campaign description is required.';
    }
    if (!isset($_FILES['campaign_image']) || ($_FILES['campaign_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $fieldErrors['campaign_image'] = 'Campaign image is required.';
    }

    if (empty($fieldErrors)) {
        try {
            $campaign = new Campaign();
            $campaignId = $campaign->create($oldInput);
            if (!$campaignId) {
                throw new Exception('Campaign creation failed. Please try again.');
            }
            header('Location: ' . BASE_URL . '/campaigns/view.php?id=' . (int) $campaignId);
            exit();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    } else {
        $error = 'Please correct the fields below and try again.';
    }
}

$isAjaxRequest = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($isAjaxRequest) {
    ob_start();
}

function renderCreateCampaignContent(?string $error = null, array $fieldErrors = [], array $oldInput = []): void {
?>
<div class="w-full px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.65fr),360px]">
        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-slate-50 shadow-sm">
                <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-gradient-to-l from-white/50 to-transparent lg:block"></div>
                <div class="relative px-6 py-7 sm:px-8 sm:py-8 lg:px-10 lg:py-10">
                    <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                        <div class="max-w-2xl">
                            <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                New campaign
                            </div>
                            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Bring your story to life</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                                Create a compelling fundraiser with a clear goal, a strong story, and an image that helps supporters instantly understand your cause.
                            </p>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/campaigns/"
                           class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-200 hover:text-emerald-700">
                            <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            Back to Campaigns
                        </a>
                    </div>
                </div>
            </section>

            <div data-form-alert class="<?php echo $error ? '' : 'hidden '; ?>rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-red-700 shadow-sm">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold">We couldn’t create your campaign</p>
                        <p class="mt-1 text-sm" data-form-alert-message><?php echo htmlspecialchars($error ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-6" data-campaign-create-form="true">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Basic information</h2>
                                <p class="text-sm text-slate-500">Start with a title and category people can immediately understand.</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-6 px-5 py-5 sm:px-6 sm:py-6">
                        <div>
                            <label for="title" class="block text-sm font-semibold text-slate-700">Campaign title</label>
                            <input type="text" id="title" name="title" required
                                   placeholder="e.g. Help rebuild a family home after the fire"
                                   value="<?php echo htmlspecialchars($oldInput['title'] ?? ''); ?>"
                                   class="mt-2 block w-full rounded-2xl border <?php echo !empty($fieldErrors['title']) ? 'border-red-300' : 'border-slate-200'; ?> bg-slate-50 px-4 py-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-<?php echo !empty($fieldErrors['title']) ? 'red-500 focus:ring-red-500/10' : 'emerald-500 focus:ring-emerald-500/10'; ?> focus:bg-white focus:outline-none">
                            <p class="mt-2 text-sm text-red-600 <?php echo empty($fieldErrors['title']) ? 'hidden' : ''; ?>" data-field-error="title"><?php echo htmlspecialchars($fieldErrors['title'] ?? ''); ?></p>
                            <p class="mt-2 text-sm text-slate-500">Aim for a clear, specific title that builds trust quickly.</p>
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-semibold text-slate-700">Category</label>
                            <select id="category" name="category" required
                                    class="mt-2 block w-full rounded-2xl border <?php echo !empty($fieldErrors['category']) ? 'border-red-300' : 'border-slate-200'; ?> bg-slate-50 px-4 py-3 text-base text-slate-900 shadow-sm transition focus:border-<?php echo !empty($fieldErrors['category']) ? 'red-500 focus:ring-red-500/10' : 'emerald-500 focus:ring-emerald-500/10'; ?> focus:bg-white focus:outline-none">
                                <option value=""<?php echo ($oldInput['category'] ?? '') === '' ? ' selected' : ''; ?>>Select a category</option>
                                <option value="education"<?php echo ($oldInput['category'] ?? '') === 'education' ? ' selected' : ''; ?>>Education</option>
                                <option value="medical"<?php echo ($oldInput['category'] ?? '') === 'medical' ? ' selected' : ''; ?>>Medical & Health</option>
                                <option value="emergency"<?php echo ($oldInput['category'] ?? '') === 'emergency' ? ' selected' : ''; ?>>Emergency Relief</option>
                                <option value="community"<?php echo ($oldInput['category'] ?? '') === 'community' ? ' selected' : ''; ?>>Community Project</option>
                                <option value="creative"<?php echo ($oldInput['category'] ?? '') === 'creative' ? ' selected' : ''; ?>>Creative Project</option>
                                <option value="nonprofit"<?php echo ($oldInput['category'] ?? '') === 'nonprofit' ? ' selected' : ''; ?>>Nonprofit</option>
                                <option value="business"<?php echo ($oldInput['category'] ?? '') === 'business' ? ' selected' : ''; ?>>Small Business</option>
                                <option value="technology"<?php echo ($oldInput['category'] ?? '') === 'technology' ? ' selected' : ''; ?>>Technology</option>
                                <option value="environment"<?php echo ($oldInput['category'] ?? '') === 'environment' ? ' selected' : ''; ?>>Environment</option>
                            </select>
                            <p class="mt-2 text-sm text-red-600 <?php echo empty($fieldErrors['category']) ? 'hidden' : ''; ?>" data-field-error="category"><?php echo htmlspecialchars($fieldErrors['category'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Funding goal</h2>
                                <p class="text-sm text-slate-500">Set a realistic target that reflects what you actually need.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-5 sm:px-6 sm:py-6">
                        <div class="max-w-md">
                            <label for="goal" class="block text-sm font-semibold text-slate-700">Funding goal (USD)</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">$</span>
                                <input type="number" id="goal" name="goal" min="100" required placeholder="5000"
                                       value="<?php echo htmlspecialchars($oldInput['goal'] ?? ''); ?>"
                                       class="block w-full rounded-2xl border <?php echo !empty($fieldErrors['goal']) ? 'border-red-300' : 'border-slate-200'; ?> bg-slate-50 py-3 pl-8 pr-4 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-<?php echo !empty($fieldErrors['goal']) ? 'red-500 focus:ring-red-500/10' : 'emerald-500 focus:ring-emerald-500/10'; ?> focus:bg-white focus:outline-none">
                            </div>
                            <p class="mt-2 text-sm text-red-600 <?php echo empty($fieldErrors['goal']) ? 'hidden' : ''; ?>" data-field-error="goal"><?php echo htmlspecialchars($fieldErrors['goal'] ?? ''); ?></p>
                            <p class="mt-2 text-sm text-slate-500">Minimum campaign goal is $100.</p>
                        </div>
                    </div>
                </div>


                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-50 text-violet-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Story & media</h2>
                                <p class="text-sm text-slate-500">Add the details and imagery that make people want to support your cause.</p>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-6 px-5 py-5 sm:px-6 sm:py-6">
                        <div>
                            <label for="description" class="block text-sm font-semibold text-slate-700">Campaign story</label>
                            <textarea id="description" name="description" rows="8" required
                                      placeholder="Tell your story. Why are you raising funds? How will the money be used? What impact will it make?"
                                      class="mt-2 block w-full rounded-2xl border <?php echo !empty($fieldErrors['description']) ? 'border-red-300' : 'border-slate-200'; ?> bg-slate-50 px-4 py-3 text-base text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-<?php echo !empty($fieldErrors['description']) ? 'red-500 focus:ring-red-500/10' : 'emerald-500 focus:ring-emerald-500/10'; ?> focus:bg-white focus:outline-none focus:ring-4">
                                <?php echo htmlspecialchars($oldInput['description'] ?? ''); ?>
                            </textarea>
                            <p class="mt-2 text-sm text-red-600 <?php echo empty($fieldErrors['description']) ? 'hidden' : ''; ?>" data-field-error="description"><?php echo htmlspecialchars($fieldErrors['description'] ?? ''); ?></p>
                            <p class="mt-2 text-sm text-slate-500">Be honest, specific, and transparent about where the money will go.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Campaign image</label>
                            <div id="campaign-image-dropzone" class="mt-2 rounded-3xl border-2 border-dashed border-slate-300 bg-slate-50/80 p-6 text-center transition hover:border-emerald-400 hover:bg-emerald-50/40">
                                <input id="campaign_image" name="campaign_image" type="file" class="sr-only" accept="image/*" required>

                                <div id="image-preview" class="hidden">
                                    <img src="" alt="Preview" class="mx-auto max-h-64 rounded-2xl object-cover shadow-sm">
                                    <div class="mt-4 flex flex-col items-center justify-center gap-2 sm:flex-row">
                                        <label for="campaign_image" class="inline-flex cursor-pointer items-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus-within:outline-none focus-within:ring-4 focus-within:ring-emerald-500/20">
                                            Change image
                                        </label>
                                        <button type="button" id="remove-campaign-image" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                                            Remove
                                        </button>
                                    </div>
                                </div>

                                <div id="upload-placeholder" class="flex flex-col items-center">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                        <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <p class="mt-4 text-sm font-semibold text-slate-700">Upload a strong cover image</p>
                                    <p class="mt-1 max-w-md text-sm text-slate-500">Choose a clear image that reflects your campaign and helps donors connect with it instantly.</p>
                                    <div class="mt-4 flex flex-col items-center gap-2 sm:flex-row">
                                        <label for="campaign_image" class="inline-flex cursor-pointer items-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus-within:outline-none focus-within:ring-4 focus-within:ring-emerald-500/20">
                                            <span>Choose image</span>
                                        </label>
                                        <span class="text-sm text-slate-500">or drag and drop</span>
                                    </div>
                                </div>

                                <p class="mt-3 text-sm text-red-600 <?php echo empty($fieldErrors['campaign_image']) ? 'hidden' : ''; ?>" data-field-error="campaign_image"><?php echo htmlspecialchars($fieldErrors['campaign_image'] ?? ''); ?></p>
                                <p class="mt-3 text-xs text-slate-500">PNG, JPG, GIF up to 10MB</p>
                            </div>
                        </div>
                    </div>

                <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white px-5 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="max-w-2xl text-sm leading-6 text-slate-500">
                        By creating this campaign, you confirm that the information you provide is accurate and that you agree to the platform terms and privacy policy.
                    </p>
                    <button type="submit"
                            class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 sm:w-auto">
                        Create campaign
                    </button>
                </div>
            </form>
        </div>

        <aside class="space-y-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">What makes a campaign work?</h2>
                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">1. Be specific</p>
                        <p class="mt-1 text-sm text-slate-500">Explain exactly what you need, who it helps, and how the funds will be used.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">2. Use a strong image</p>
                        <p class="mt-1 text-sm text-slate-500">A real, relevant image improves trust and helps people engage faster.</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-4">
                        <p class="text-sm font-semibold text-slate-900">3. Keep supporters updated</p>
                        <p class="mt-1 text-sm text-slate-500">After publishing, share updates so donors can see progress and stay involved.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-emerald-100 bg-emerald-50 p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-900">Before you publish</h2>
                <ul class="mt-4 space-y-3 text-sm text-emerald-900/80">
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>Use a title that explains the campaign clearly in one line.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>Set a funding goal that feels realistic and easy to justify.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>Write a story that shows urgency, transparency, and expected impact.</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>
</div>

<script>
    function initializeCampaignCreateForm() {
        const form = document.querySelector('form[data-campaign-create-form="true"]');
        const container = form ? form.closest('.w-full') : null;
        const imageInput = form ? form.querySelector('#campaign_image') : null;
        const imagePreview = form ? form.querySelector('#image-preview') : null;
        const imagePreviewImg = imagePreview ? imagePreview.querySelector('img') : null;
        const uploadPlaceholder = form ? form.querySelector('#upload-placeholder') : null;
        const dropZone = form ? form.querySelector('#campaign-image-dropzone') : null;
        const alertBox = container ? container.querySelector('[data-form-alert]') : null;
        const alertMessage = container ? container.querySelector('[data-form-alert-message]') : null;

        if (!form || !container) {
            return;
        }

        if (form.dataset.initialized === 'true') {
            return;
        }
        form.dataset.initialized = 'true';

        const PREVIEW_STORAGE_KEY = 'campaign-create-image-preview';

        const saveImagePreviewState = (dataUrl) => {
            try {
                if (dataUrl) {
                    sessionStorage.setItem(PREVIEW_STORAGE_KEY, dataUrl);
                } else {
                    sessionStorage.removeItem(PREVIEW_STORAGE_KEY);
                }
            } catch (storageError) {
                console.warn('[campaign-create] Unable to save image preview state', storageError);
            }
        };

        const restoreImagePreviewState = () => {
            if (!imageInput || !imagePreview || !imagePreviewImg || !uploadPlaceholder) {
                return;
            }

            try {
                const storedPreview = sessionStorage.getItem(PREVIEW_STORAGE_KEY);
                if (!storedPreview) {
                    return;
                }

                imagePreviewImg.src = storedPreview;
                imagePreview.classList.remove('hidden');
                uploadPlaceholder.classList.add('hidden');
            } catch (storageError) {
                console.warn('[campaign-create] Unable to restore image preview state', storageError);
            }
        };

        const setFieldError = (fieldName, message) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            const errorNode = container.querySelector(`[data-field-error="${fieldName}"]`);

            if (field) {
                field.classList.toggle('border-red-300', Boolean(message));
                field.classList.toggle('focus:border-red-500', Boolean(message));
                field.classList.toggle('focus:ring-red-500/10', Boolean(message));
            }

            if (errorNode) {
                errorNode.textContent = message || '';
                errorNode.classList.toggle('hidden', !message);
            }
        };

        const clearErrors = () => {
            form.querySelectorAll('[name]').forEach((field) => {
                field.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500/10');
            });
            container.querySelectorAll('[data-field-error]').forEach((node) => {
                node.textContent = '';
                node.classList.add('hidden');
            });
            if (alertBox) {
                alertBox.classList.add('hidden');
            }
            if (alertMessage) {
                alertMessage.textContent = '';
            }
        };

        if (imageInput && dropZone && imagePreview && imagePreviewImg && uploadPlaceholder) {
            const resetImagePreview = () => {
                imageInput.value = '';
                imagePreviewImg.src = '';
                imagePreview.classList.add('hidden');
                uploadPlaceholder.classList.remove('hidden');
                saveImagePreviewState('');
            };

            const handleSelectedImage = (file) => {
                if (!file) {
                    resetImagePreview();
                    return;
                }

                if (!file.type || !file.type.startsWith('image/')) {
                    resetImagePreview();
                    setFieldError('campaign_image', 'Please choose a valid image file.');
                    return;
                }

                const maxFileSize = 10 * 1024 * 1024;
                if (file.size > maxFileSize) {
                    resetImagePreview();
                    setFieldError('campaign_image', 'Image size must be 10MB or less.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(loadEvent) {
                    const dataUrl = loadEvent.target.result;
                    imagePreviewImg.src = dataUrl;
                    imagePreview.classList.remove('hidden');
                    uploadPlaceholder.classList.add('hidden');
                    saveImagePreviewState(dataUrl);
                };
                reader.readAsDataURL(file);
                setFieldError('campaign_image', '');
            };

            imageInput.addEventListener('click', function() {
                this.value = '';
            });

            imageInput.addEventListener('change', function(event) {
                handleSelectedImage(event.target.files[0] || null);
            });

            const removeImageButton = form.querySelector('#remove-campaign-image');
            if (removeImageButton) {
                removeImageButton.addEventListener('click', resetImagePreview);
            }

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
                dropZone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            ['dragenter', 'dragover'].forEach((eventName) => {
                dropZone.addEventListener(eventName, function() {
                    dropZone.classList.add('border-emerald-400', 'bg-emerald-50');
                }, false);
            });

            ['dragleave', 'drop'].forEach((eventName) => {
                dropZone.addEventListener(eventName, function() {
                    dropZone.classList.remove('border-emerald-400', 'bg-emerald-50');
                }, false);
            });

            form.addEventListener('submit', function() {
                if (imagePreviewImg && imagePreviewImg.src) {
                    saveImagePreviewState(imagePreviewImg.src);
                }
            });

            restoreImagePreviewState();

            dropZone.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt ? dt.files : null;
                const file = files && files.length ? files[0] : null;

                if (!file) {
                    return;
                }

                if (!file.type || !file.type.startsWith('image/')) {
                    setFieldError('campaign_image', 'Please choose a valid image file.');
                    return;
                }

                try {
                    imageInput.files = files;
                } catch (assignmentError) {
                    console.warn('[campaign-create] Unable to assign dropped file to input', assignmentError);
                }

                handleSelectedImage(file);
            }, false);
        }
    }

    initializeCampaignCreateForm();
</script>
<?php
}

if (!$isAjaxRequest) {
    include __DIR__ . '/../components/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="shortcut icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <title>Create Campaign - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-gray-50 min-h-screen">
    <div class="flex min-h-screen pt-16">
        <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50 lg:ml-64">
            <?php renderCreateCampaignContent($error, $fieldErrors, $oldInput); ?>
        </main>
    </div>
</body>
</html>
<?php
} else {
    renderCreateCampaignContent($error, $fieldErrors, $oldInput);
    echo ob_get_clean();
}
