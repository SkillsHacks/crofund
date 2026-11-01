<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Campaign.php';
require_once __DIR__ . '/../config/CampaignDraft.php';
require_once __DIR__ . '/../config/User.php';
$countries = require __DIR__ . '/../config/countries.php';

$user = new User();
$isAuthenticated = isset($_SESSION['user_id']) && $user->isLoggedIn();
$draft = CampaignDraft::get();
$draftId = $draft['id'] ?? '';
$draftPreviewUrl = $draft ? BASE_URL . '/api/campaign-draft-image.php' : '';
$draftNotice = $draft
    ? 'Your fundraiser draft is saved in this browser session for up to 24 hours. Finish your details and publish when you are ready.'
    : (isset($_GET['resume']) ? 'Your saved fundraiser draft is no longer available. Please start again.' : '');
$verifiedSignupEmail = (string) ($_SESSION['campaign_signup_email_verified'] ?? '');
$signupEmailVerifiedAt = (int) ($_SESSION['campaign_signup_email_verified_at'] ?? 0);
$isSignupEmailVerified = !$isAuthenticated
    && $verifiedSignupEmail !== ''
    && $verifiedSignupEmail === (string) ($_SESSION['campaign_signup_email'] ?? '')
    && $signupEmailVerifiedAt >= time() - 3600;

$error = null;
$success = null;
$fieldErrors = [];
$oldInput = [
    'campaign_country_code' => '',
    'campaign_city' => '',
    'campaign_postal_code' => '',
    'beneficiary_type' => '',
    'beneficiary_name' => '',
    'beneficiary_relationship' => '',
    'title' => '',
    'category' => '',
    'goal' => '',
    'description' => '',
];
if ($draft) {
    $oldInput = array_merge($oldInput, $draft['data']);
}
$canCreateCampaign = !$isAuthenticated || (new Campaign())->canCreateCampaign((int) $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldInput = [
        'campaign_country_code' => strtoupper(trim((string) ($_POST['campaign_country_code'] ?? ''))),
        'campaign_city' => trim((string) ($_POST['campaign_city'] ?? '')),
        'campaign_postal_code' => trim((string) ($_POST['campaign_postal_code'] ?? '')),
        'beneficiary_type' => trim((string) ($_POST['beneficiary_type'] ?? '')),
        'beneficiary_name' => trim((string) ($_POST['beneficiary_name'] ?? '')),
        'beneficiary_relationship' => trim((string) ($_POST['beneficiary_relationship'] ?? '')),
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

function renderCreateCampaignContent(
    ?string $error = null,
    array $fieldErrors = [],
    array $oldInput = [],
    array $countries = [],
    bool $canCreateCampaign = true,
    bool $isAuthenticated = true,
    string $draftId = '',
    string $draftPreviewUrl = '',
    string $draftNotice = ''
): void {
$initialStep = 0;
foreach ([
    'campaign_country_code' => 0,
    'campaign_city' => 0,
    'campaign_postal_code' => 0,
    'category' => 1,
    'beneficiary_type' => 2,
    'beneficiary_name' => 2,
    'beneficiary_relationship' => 2,
    'goal' => 3,
    'title' => 4,
    'description' => 4,
    'campaign_image' => 5,
] as $field => $step) {
    if (!empty($fieldErrors[$field])) {
        $initialStep = $step;
        break;
    }
}
if ($error && empty($fieldErrors)) {
    $initialStep = 6;
}
?>
<div class="w-full px-0.5 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6">
    <div class="mx-auto max-w-[1500px]">
        <section class="mb-4 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white px-4 py-4 shadow-sm sm:mb-5 sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:rounded-2xl sm:px-6 sm:py-5">
            <div class="min-w-0">
                <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-3xl">Create campaign</h1>
                <?php if (!$isAuthenticated && $draftId === ''): ?>
                    <p class="mt-1.5 max-w-2xl text-xs leading-5 text-slate-600 sm:text-sm">
                        Build your fundraiser first, then create an account and verify your email to publish it.
                    </p>
                <?php endif; ?>
                <?php if ($draftNotice !== ''): ?>
                    <p class="mt-1.5 max-w-2xl text-xs leading-5 text-emerald-800 sm:text-sm" role="status"><?php echo htmlspecialchars($draftNotice, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
                <?php if (!$canCreateCampaign): ?>
                    <p class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs leading-5 text-slate-600 sm:text-sm" role="status" aria-labelledby="campaign-limit-title">
                        <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3h.008v.008H12v-.008ZM10.29 3.86 1.82 18.52A1.7 1.7 0 0 0 3.29 21h17.42a1.7 1.7 0 0 0 1.47-2.48L13.71 3.86a1.98 1.98 0 0 0-3.42 0Z"/>
                        </svg>
                        <span id="campaign-limit-title" class="font-semibold text-slate-800">Campaign limit reached.</span>
                        <span>Your plan allows one campaign.</span>
                        <a href="<?php echo BASE_URL; ?>/account/premium.php" class="font-semibold text-emerald-800 underline decoration-emerald-800/30 underline-offset-2 transition hover:text-emerald-900 hover:decoration-emerald-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                            Upgrade to premium
                        </a>
                    </p>
                <?php endif; ?>
            </div>
            <a href="<?php echo $isAuthenticated ? BASE_URL . '/campaigns/' : BASE_URL . '/campaigns/view_compaigns.php'; ?>"
               class="inline-flex min-h-10 items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700 sm:rounded-xl">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <?php echo $isAuthenticated ? 'My campaigns' : 'Browse campaigns'; ?>
            </a>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:gap-5 xl:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="rounded-lg bg-emerald-800 p-4 text-white shadow-sm sm:rounded-2xl sm:p-5 xl:sticky xl:top-24 xl:self-start">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-100" data-step-counter>Step 1 of 7</p>
                <h2 class="mt-1 text-lg font-bold tracking-tight sm:mt-2 sm:text-xl" data-step-title>Fundraiser location</h2>
                <p class="mt-2 hidden text-sm leading-5 text-emerald-100 sm:block" data-step-description>Where is the fundraiser based?</p>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/20 sm:mt-5" role="progressbar" aria-label="Campaign creation progress" aria-valuemin="1" aria-valuemax="7" aria-valuenow="1">
                    <span class="block h-full rounded-full bg-white transition-all duration-300" data-step-progress style="width: 14.286%"></span>
                </div>
                <ol class="mt-3 grid grid-cols-4 gap-1 text-xs sm:mt-5 sm:gap-2 xl:grid-cols-1 xl:gap-3">
                    <li class="flex items-center gap-2 font-semibold text-white" data-step-indicator="0" aria-current="step">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-white text-emerald-800">1</span>
                        <span>Location</span>
                    </li>
                    <li class="flex items-center gap-2 text-emerald-100" data-step-indicator="1">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-white/40">2</span>
                        <span>Category</span>
                    </li>
                    <li class="flex items-center gap-2 text-emerald-100" data-step-indicator="2">
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-white/40">3</span>
                        <span>Who</span>
                    </li>
                    <li class="flex items-center gap-2 text-emerald-100" data-step-indicator="3"><span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-white/40">4</span><span>Goal</span></li>
                    <li class="flex items-center gap-2 text-emerald-100" data-step-indicator="4"><span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-white/40">5</span><span>Story</span></li>
                    <li class="flex items-center gap-2 text-emerald-100" data-step-indicator="5"><span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-white/40">6</span><span>Photo</span></li>
                    <li class="flex items-center gap-2 text-emerald-100" data-step-indicator="6"><span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-white/40">7</span><span>Account</span></li>
                </ol>
            </aside>

        <div class="space-y-5">
            <div data-form-alert class="<?php echo $error ? '' : 'hidden '; ?>rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold"><?php echo !$isAuthenticated && $draftId === '' ? 'Couldn’t save your draft' : ($draftId !== '' ? 'Couldn’t publish campaign' : 'Couldn’t create campaign'); ?></p>
                        <p class="mt-1 text-sm" data-form-alert-message><?php echo htmlspecialchars($error ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <form method="POST" action="<?php echo BASE_URL; ?>/api/dashboard/create_campaign.php" enctype="multipart/form-data" class="space-y-5" data-campaign-create-form="true" data-authenticated="<?php echo $isAuthenticated ? 'true' : 'false'; ?>" data-email-verified="<?php echo $isSignupEmailVerified ? 'true' : 'false'; ?>" data-initial-step="<?php echo $initialStep; ?>" data-draft-id="<?php echo htmlspecialchars($draftId, ENT_QUOTES, 'UTF-8'); ?>" data-image-preview-url="<?php echo htmlspecialchars($draftPreviewUrl, ENT_QUOTES, 'UTF-8'); ?>" data-submit-label="<?php echo !$isAuthenticated ? 'Creating account...' : 'Creating...'; ?>">
                <?php if ($draftId !== ''): ?>
                    <input type="hidden" name="campaign_draft_id" value="<?php echo htmlspecialchars($draftId, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="remove_campaign_image" value="0" data-remove-campaign-image>
                <?php endif; ?>
                <div data-campaign-step="0" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="hidden border-b border-slate-200 px-4 py-3 sm:block sm:px-6 sm:py-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900">Where is the fundraiser based?</h2>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 px-4 py-4 sm:grid-cols-2 sm:gap-5 sm:px-6 sm:py-5">
                        <div>
                            <label for="campaign_country_code" class="block text-sm font-semibold text-slate-700">Country</label>
                            <select id="campaign_country_code" name="campaign_country_code" required class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                                <option value="">Select a country</option>
                                <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars(strtoupper($country['code']), ENT_QUOTES, 'UTF-8'); ?>"<?php echo strtoupper((string) ($oldInput['campaign_country_code'] ?? '')) === strtoupper($country['code']) ? ' selected' : ''; ?>><?php echo htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-2 text-sm text-red-600 hidden" data-field-error="campaign_country_code"></p>
                        </div>

                        <div>
                            <label for="campaign_city" class="block text-sm font-semibold text-slate-700">City or town</label>
                            <input id="campaign_city" name="campaign_city" type="text" maxlength="150" required placeholder="e.g. Kampala" value="<?php echo htmlspecialchars($oldInput['campaign_city'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            <p class="mt-2 text-sm text-red-600 hidden" data-field-error="campaign_city"></p>
                        </div>
                        <div>
                            <label for="campaign_postal_code" class="block text-sm font-semibold text-slate-700">ZIP or postal code</label>
                            <input id="campaign_postal_code" name="campaign_postal_code" type="text" maxlength="32" required placeholder="Enter ZIP or postal code" value="<?php echo htmlspecialchars($oldInput['campaign_postal_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            <p class="mt-2 text-sm text-red-600 hidden" data-field-error="campaign_postal_code"></p>
                        </div>
                    </div>
                </div>

                <div data-campaign-step="1" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="hidden border-b border-slate-200 px-4 py-3 sm:block sm:px-6 sm:py-4">
                        <h2 class="text-base font-semibold text-slate-900">What best describes your fundraiser?</h2>
                    </div>
                    <div class="px-4 py-4 sm:px-6 sm:py-6">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <?php foreach ([
                                'animals' => 'Animals', 'business' => 'Business', 'community' => 'Community',
                                'competitions' => 'Competitions', 'creative' => 'Creative', 'education' => 'Education',
                                'emergency' => 'Emergencies', 'environment' => 'Environment', 'events' => 'Events',
                                'faith' => 'Faith', 'family' => 'Family', 'funerals' => 'Funerals & Memorials',
                                'medical' => 'Medical', 'monthly_bills' => 'Monthly Bills', 'newlyweds' => 'Newlyweds',
                                'other' => 'Other', 'sports' => 'Sports', 'travel' => 'Travel',
                                'volunteer' => 'Volunteer', 'wishes' => 'Wishes',
                            ] as $value => $label): ?>
                            <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-800">
                                <input type="radio" name="category" value="<?php echo $value; ?>" required <?php echo ($oldInput['category'] ?? '') === $value ? 'checked' : ''; ?> class="accent-emerald-600">
                                <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="mt-2 text-sm text-red-600 hidden" data-field-error="category"></p>
                    </div>
                </div>


                <div data-campaign-step="2" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                        <h2 class="text-base font-semibold text-slate-900">Who are you raising money for?</h2>
                        <div class="flex flex-wrap gap-3">
                            <?php foreach (['self' => 'Myself', 'individual' => 'Someone else', 'organization' => 'Charity or group'] as $value => $label): ?>
                            <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-700 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-800">
                                <input type="radio" name="beneficiary_type" value="<?php echo $value; ?>" required <?php echo ($oldInput['beneficiary_type'] ?? '') === $value ? 'checked' : ''; ?> class="accent-emerald-600">
                                <span><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-sm text-red-600 hidden" data-field-error="beneficiary_type"></p>
                        <div data-beneficiary-details class="<?php echo in_array($oldInput['beneficiary_type'] ?? '', ['individual', 'organization'], true) ? '' : 'hidden '; ?>space-y-4">
                            <div>
                                <label for="beneficiary_name" class="block text-sm font-semibold text-slate-700">Beneficiary or organization name</label>
                                <input id="beneficiary_name" name="beneficiary_name" type="text" maxlength="255" value="<?php echo htmlspecialchars($oldInput['beneficiary_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                                <p class="mt-2 text-sm text-red-600 hidden" data-field-error="beneficiary_name"></p>
                            </div>
                            <div>
                                <label for="beneficiary_relationship" class="block text-sm font-semibold text-slate-700" data-beneficiary-relationship-label>Your relationship to them</label>
                                <input id="beneficiary_relationship" name="beneficiary_relationship" type="text" maxlength="255" value="<?php echo htmlspecialchars($oldInput['beneficiary_relationship'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                                <p class="mt-2 text-sm text-red-600 hidden" data-field-error="beneficiary_relationship"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div data-campaign-step="3" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="px-4 py-4 sm:px-6 sm:py-6">
                        <div class="max-w-sm">
                            <label for="goal" class="block text-sm font-semibold text-slate-700">Goal amount (USD)</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">$</span>
                                <input type="number" id="goal" name="goal" min="100" step="0.01" required placeholder="e.g. 5000" value="<?php echo htmlspecialchars($oldInput['goal'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="block w-full rounded-lg border border-slate-200 bg-white py-2.5 pl-8 pr-3.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            </div>
                            <p class="mt-2 text-sm text-red-600 hidden" data-field-error="goal"></p>
                            <p class="mt-2 text-xs text-slate-500">You can change this later. Minimum $100.</p>
                        </div>
                    </div>
                </div>

                <div data-campaign-step="4" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                        <h2 class="text-base font-semibold text-slate-900">Tell your story</h2>
                        <p class="text-sm text-slate-600">Add a clear title and explain why you’re raising money.</p>
                        <div>
                            <label for="title" class="block text-sm font-semibold text-slate-700">Title</label>
                            <input id="title" name="title" type="text" maxlength="255" required placeholder="e.g. Help Molly get surgery" value="<?php echo htmlspecialchars($oldInput['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            <p class="mt-2 text-sm text-red-600 hidden" data-field-error="title"></p>
                        </div>
                        <div>
                            <label for="description" class="block text-sm font-semibold text-slate-700">Story</label>
                            <textarea id="description" name="description" rows="6" required placeholder="Share why you are raising money and how donations will help..." class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-3 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl"><?php echo htmlspecialchars($oldInput['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <p class="mt-2 text-sm text-red-600 hidden" data-field-error="description"></p>
                        </div>
                    </div>
                </div>

                <div data-campaign-step="5" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="space-y-4 px-4 py-4 sm:px-6 sm:py-5">
                        <h2 class="text-base font-semibold text-slate-900">Add a photo</h2>
                        <p class="text-sm text-slate-600">A campaign photo is required. Video upload is not available yet.</p>
                        <div>
                            <label for="campaign_image" class="block text-sm font-semibold text-slate-700">Photo</label>
                            <div id="campaign-image-dropzone" class="mt-2 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition hover:border-emerald-400 hover:bg-emerald-50/40 sm:rounded-2xl sm:p-6">
                                <input id="campaign_image" name="campaign_image" type="file" class="sr-only" accept="image/jpeg,image/png,image/gif,image/webp"<?php echo $draftId === '' ? ' required' : ''; ?>>
                                <div id="image-preview" class="hidden">
                                    <img src="" alt="Campaign photo preview" class="mx-auto max-h-64 rounded-2xl object-cover shadow-sm">
                                    <div class="mt-4 flex flex-col items-center justify-center gap-2 sm:flex-row">
                                        <label for="campaign_image" class="inline-flex cursor-pointer items-center rounded-full bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Change photo</label>
                                        <button type="button" id="remove-campaign-image" class="inline-flex items-center rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-red-50 hover:text-red-700">Remove</button>
                                    </div>
                                </div>
                                <div id="upload-placeholder" class="flex flex-col items-center">
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                                        <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-slate-800">Choose a photo</p>
                                    <label for="campaign_image" class="mt-3 inline-flex cursor-pointer items-center rounded-full bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Choose photo</label>
                                </div>
                                <p class="mt-3 text-sm text-red-600 hidden" data-field-error="campaign_image"></p>
                                <p class="mt-3 text-xs text-slate-500">JPG, PNG, GIF or WEBP · up to 10MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div data-campaign-step="6" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm sm:rounded-2xl">
                    <div class="space-y-4 px-4 py-5 sm:px-6 sm:py-6">
                        <h2 class="text-base font-semibold text-slate-900"><?php echo $isAuthenticated ? 'Your account' : 'Create your account'; ?></h2>
                        <?php if ($isAuthenticated): ?>
                        <p class="text-sm text-slate-600">You’re signed in. Continue to publish your fundraiser now.</p>
                        <?php else: ?>
                        <p class="text-sm text-slate-600">Enter your details, verify your email with the code we send, then publish your fundraiser.</p>
                        <div>
                            <label for="account_name" class="block text-sm font-semibold text-slate-700">Your name</label>
                            <input id="account_name" name="account_name" type="text" maxlength="255" required autocomplete="name" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            <p class="mt-2 hidden text-sm text-red-600" data-field-error="account_name"></p>
                        </div>
                        <div>
                            <label for="account_email" class="block text-sm font-semibold text-slate-700">Email address</label>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <input id="account_email" name="account_email" type="email" maxlength="255" required autocomplete="email" value="<?php echo htmlspecialchars($isSignupEmailVerified ? $verifiedSignupEmail : '', ENT_QUOTES, 'UTF-8'); ?>" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60 sm:rounded-xl" data-send-email-code>
                                    Send code
                                </button>
                            </div>
                            <p class="mt-2 hidden text-sm text-red-600" data-field-error="account_email"></p>
                            <p class="mt-2 hidden text-sm text-red-600" data-field-error="signup_code"></p>
                        </div>
                        <div data-email-code-section class="hidden space-y-3">
                            <label for="signup_code" class="block text-sm font-semibold text-slate-700">Email verification code</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input id="signup_code" name="signup_code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm tracking-widest focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl" placeholder="6-digit code">
                                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900 disabled:cursor-not-allowed disabled:opacity-60 sm:rounded-xl" data-verify-email-code>
                                    Verify email
                                </button>
                            </div>
                        </div>
                        <p class="text-sm text-slate-600" role="status" aria-live="polite" data-email-code-status><?php echo $isSignupEmailVerified ? 'Email verified.' : ''; ?></p>
                        <div>
                            <label for="account_password" class="block text-sm font-semibold text-slate-700">Password</label>
                            <input id="account_password" name="account_password" type="password" minlength="8" maxlength="128" required autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}" title="Use at least 8 characters, including uppercase and lowercase letters, a number, and a special character." class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            <p class="mt-1 text-xs text-slate-500">At least 8 characters with uppercase and lowercase letters, a number, and a symbol.</p>
                            <p class="mt-2 hidden text-sm text-red-600" data-field-error="account_password"></p>
                        </div>
                        <div>
                            <label for="account_password_confirmation" class="block text-sm font-semibold text-slate-700">Confirm password</label>
                            <input id="account_password_confirmation" name="account_password_confirmation" type="password" minlength="8" maxlength="128" required autocomplete="new-password" class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-500/10 sm:rounded-xl">
                            <p class="mt-2 hidden text-sm text-red-600" data-field-error="account_password_confirmation"></p>
                        </div>
                        <label class="flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="accept_terms" value="1" required class="mt-1 accent-emerald-700">
                            <span>I agree to the <a href="<?php echo BASE_URL; ?>/terms.php" target="_blank" rel="noopener" class="font-semibold text-emerald-800 underline">Terms of Service</a> and <a href="<?php echo BASE_URL; ?>/privacy.php" target="_blank" rel="noopener" class="font-semibold text-emerald-800 underline">Privacy Policy</a>.</span>
                        </label>
                        <p class="hidden text-sm text-red-600" data-field-error="accept_terms"></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm sm:rounded-2xl sm:p-3 sm:px-4">
                    <p class="mb-3 hidden text-xs leading-5 text-slate-500 sm:text-sm" data-campaign-terms>
                        <?php echo $isAuthenticated ? 'Publishing will make this fundraiser visible to supporters.' : 'Your account will be created after email verification, and your fundraiser will publish immediately.'; ?>
                    </p>
                    <div class="flex items-center gap-3">
                        <button type="button" class="hidden h-11 w-11 flex-shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 sm:rounded-xl" data-step-back aria-label="Go to previous step">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button type="button" class="hidden min-h-11 flex-1 items-center justify-center rounded-md border border-emerald-700 bg-transparent px-5 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 sm:rounded-xl" data-step-next>
                            Continue
                        </button>
                        <button type="submit"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-md bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600 disabled:hover:bg-slate-300 sm:w-auto sm:rounded-xl"
                                <?php echo $canCreateCampaign ? '' : 'disabled aria-describedby="campaign-limit-title"'; ?>
                                data-step-submit>
                            <?php echo !$canCreateCampaign ? 'Campaign limit reached' : (!$isAuthenticated ? 'Verify and publish' : 'Publish campaign'); ?>
                        </button>
                        <?php if ($draftId !== ''): ?>
                            <button type="button" class="min-h-11 shrink-0 rounded-md px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:rounded-xl" data-discard-campaign-draft>
                                Discard draft
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
        const removeImageField = form ? form.querySelector('[data-remove-campaign-image]') : null;
        const dropZone = form ? form.querySelector('#campaign-image-dropzone') : null;
        const alertBox = container ? container.querySelector('[data-form-alert]') : null;
        const alertMessage = container ? container.querySelector('[data-form-alert-message]') : null;
        const steps = form ? Array.from(form.querySelectorAll('[data-campaign-step]')) : [];
        const stepPanel = container ? container.querySelector('aside') : null;
        const stepCounter = stepPanel ? stepPanel.querySelector('[data-step-counter]') : null;
        const stepTitle = stepPanel ? stepPanel.querySelector('[data-step-title]') : null;
        const stepDescription = stepPanel ? stepPanel.querySelector('[data-step-description]') : null;
        const stepProgress = stepPanel ? stepPanel.querySelector('[data-step-progress]') : null;
        const progressBar = stepPanel ? stepPanel.querySelector('[role="progressbar"]') : null;
        const backButton = form ? form.querySelector('[data-step-back]') : null;
        const nextButton = form ? form.querySelector('[data-step-next]') : null;
        const submitButton = form ? form.querySelector('[data-step-submit]') : null;
        const discardDraftButton = form ? form.querySelector('[data-discard-campaign-draft]') : null;
        const terms = form ? form.querySelector('[data-campaign-terms]') : null;
        const beneficiaryDetails = form ? form.querySelector('[data-beneficiary-details]') : null;
        const beneficiaryTypeInputs = form ? Array.from(form.querySelectorAll('input[name="beneficiary_type"]')) : [];
        const beneficiaryName = form ? form.querySelector('#beneficiary_name') : null;
        const beneficiaryRelationship = form ? form.querySelector('#beneficiary_relationship') : null;
        const beneficiaryRelationshipLabel = form ? form.querySelector('[data-beneficiary-relationship-label]') : null;
        const accountName = form ? form.querySelector('#account_name') : null;
        const accountEmail = form ? form.querySelector('#account_email') : null;
        const accountPassword = form ? form.querySelector('#account_password') : null;
        const accountPasswordConfirmation = form ? form.querySelector('#account_password_confirmation') : null;
        const signupCode = form ? form.querySelector('#signup_code') : null;
        const emailCodeSection = form ? form.querySelector('[data-email-code-section]') : null;
        const emailCodeStatus = form ? form.querySelector('[data-email-code-status]') : null;
        const sendEmailCodeButton = form ? form.querySelector('[data-send-email-code]') : null;
        const verifyEmailCodeButton = form ? form.querySelector('[data-verify-email-code]') : null;

        if (!form || !container || steps.length === 0 || !stepPanel) {
            return;
        }

        if (form.dataset.initialized === 'true') {
            return;
        }
        form.dataset.initialized = 'true';
        form.noValidate = true;
        let emailVerified = form.dataset.emailVerified === 'true';
        let codeSent = false;
        let requestedEmail = emailVerified && accountEmail ? accountEmail.value.trim().toLowerCase() : '';
        if (emailVerified && sendEmailCodeButton) {
            sendEmailCodeButton.disabled = true;
            sendEmailCodeButton.textContent = 'Email verified';
        }
        const stepDetails = [
            {title: 'Fundraiser location', description: 'Where is the fundraiser based?'},
            {title: 'Fundraiser category', description: 'Choose a category so people can find it.'},
            {title: 'Who is this for?', description: 'Tell supporters who will benefit.'},
            {title: 'Set your goal', description: 'You can change this later. Amounts are in USD.'},
            {title: 'Tell your story', description: 'Add a clear title and explain why people should care.'},
            {title: 'Add a photo', description: 'A campaign photo is required. Video upload is not available yet.'},
            {
                title: form.dataset.authenticated === 'true' ? 'Publish your fundraiser' : 'Create your account',
                description: form.dataset.authenticated === 'true'
                    ? 'Your campaign is ready to publish.'
                    : 'Verify your email and create an account to publish.'
            }
        ];
        let currentStep = Math.min(Number(form.dataset.initialStep) || 0, steps.length - 1);

        const showStep = (stepIndex) => {
            currentStep = Math.max(0, Math.min(stepIndex, steps.length - 1));
            steps.forEach((step, index) => {
                step.hidden = index !== currentStep;
            });

            const details = stepDetails[currentStep];
            if (stepCounter) {
                stepCounter.textContent = `Step ${currentStep + 1} of ${steps.length}`;
            }
            if (stepTitle) {
                stepTitle.textContent = details.title;
            }
            if (stepDescription) {
                stepDescription.textContent = details.description;
            }
            if (stepProgress) {
                stepProgress.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
            }
            if (progressBar) {
                progressBar.setAttribute('aria-valuenow', String(currentStep + 1));
            }

            stepPanel.querySelectorAll('[data-step-indicator]').forEach((indicator) => {
                const index = Number(indicator.getAttribute('data-step-indicator'));
                const isCurrent = index === currentStep;
                const number = indicator.querySelector('span');
                indicator.classList.toggle('font-semibold', isCurrent);
                indicator.classList.toggle('text-white', isCurrent);
                indicator.classList.toggle('text-emerald-100', !isCurrent);
                if (isCurrent) {
                    indicator.setAttribute('aria-current', 'step');
                } else {
                    indicator.removeAttribute('aria-current');
                }
                if (number) {
                    number.classList.toggle('bg-white', isCurrent);
                    number.classList.toggle('text-emerald-800', isCurrent);
                    number.classList.toggle('border', !isCurrent);
                    number.classList.toggle('border-white/40', !isCurrent);
                }
            });

            backButton?.classList.toggle('hidden', currentStep === 0);
            nextButton?.classList.toggle('hidden', currentStep === steps.length - 1);
            submitButton?.classList.toggle('hidden', currentStep !== steps.length - 1);
            terms?.classList.toggle('hidden', currentStep !== steps.length - 1);
        };

        const validateCurrentStep = () => {
            const requiredFields = Array.from(steps[currentStep].querySelectorAll('[required]'));
            const firstInvalidField = requiredFields.find((field) => !field.checkValidity());
            if (!firstInvalidField) {
                if (currentStep === 6 && form.dataset.authenticated !== 'true') {
                    if (accountPassword.value !== accountPasswordConfirmation.value) {
                        setFieldError('account_password_confirmation', 'Passwords do not match.');
                        accountPasswordConfirmation.focus();
                        return false;
                    }
                    if (!emailVerified || accountEmail.value.trim().toLowerCase() !== requestedEmail) {
                        setFieldError('signup_code', 'Verify your email address before publishing.');
                        emailCodeStatus.textContent = 'Verify your email address before publishing.';
                        accountEmail.focus();
                        return false;
                    }
                }
                if (currentStep === 2) {
                    const selectedType = form.querySelector('input[name="beneficiary_type"]:checked')?.value;
                    const requiresDetails = selectedType === 'individual' || selectedType === 'organization';
                    const missingName = requiresDetails && !beneficiaryName.value.trim();
                    const missingRelationship = requiresDetails && !beneficiaryRelationship.value.trim();
                    if (missingName || missingRelationship) {
                        if (missingName) {
                            setFieldError('beneficiary_name', 'Please enter the beneficiary or organization name.');
                        }
                        if (missingRelationship) {
                            setFieldError('beneficiary_relationship', 'Please tell us your relationship to them.');
                        }
                        (missingName ? beneficiaryName : beneficiaryRelationship)?.focus();
                        return false;
                    }
                }
                return true;
            }

            firstInvalidField.reportValidity();
            return false;
        };

        const postSignupAction = async (data) => {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(data)
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                const errors = result.errors || {};
                Object.entries(errors).forEach(([fieldName, message]) => setFieldError(fieldName, message));
                if (result.message && emailCodeStatus) {
                    emailCodeStatus.textContent = result.message;
                    emailCodeStatus.classList.add('text-red-600');
                    emailCodeStatus.classList.remove('text-emerald-700');
                }
                if (result.redirect_url) {
                    window.location.assign(result.redirect_url);
                }
                throw new Error(result.message || Object.values(errors)[0] || 'Unable to verify your email right now.');
            }
            return result;
        };

        sendEmailCodeButton?.addEventListener('click', async () => {
            clearErrors();
            if (!accountEmail.checkValidity()) {
                accountEmail.reportValidity();
                return;
            }

            sendEmailCodeButton.disabled = true;
            sendEmailCodeButton.textContent = 'Sending...';
            try {
                const result = await postSignupAction({
                    action: 'send_signup_otp',
                    email: accountEmail.value.trim()
                });
                requestedEmail = accountEmail.value.trim().toLowerCase();
                emailVerified = false;
                codeSent = true;
                signupCode.required = true;
                emailCodeSection.classList.remove('hidden');
                emailCodeStatus.textContent = result.message;
                emailCodeStatus.classList.add('text-emerald-700');
                emailCodeStatus.classList.remove('text-red-600');
                sendEmailCodeButton.textContent = 'Resend code';
                sendEmailCodeButton.disabled = false;
                signupCode.focus();
            } catch (requestError) {
                if (emailCodeStatus && !emailCodeStatus.textContent) {
                    emailCodeStatus.textContent = requestError.message;
                }
                sendEmailCodeButton.disabled = false;
                sendEmailCodeButton.textContent = codeSent ? 'Resend code' : 'Send code';
            }
        });

        verifyEmailCodeButton?.addEventListener('click', async () => {
            clearErrors();
            if (!signupCode.value.trim()) {
                setFieldError('signup_code', 'Enter the 6-digit code sent to your email.');
                signupCode.focus();
                return;
            }

            verifyEmailCodeButton.disabled = true;
            verifyEmailCodeButton.textContent = 'Verifying...';
            try {
                const result = await postSignupAction({
                    action: 'verify_signup_otp',
                    email: accountEmail.value.trim(),
                    code: signupCode.value.trim()
                });
                emailVerified = true;
                requestedEmail = accountEmail.value.trim().toLowerCase();
                signupCode.required = false;
                emailCodeSection.classList.add('hidden');
                emailCodeStatus.textContent = result.message;
                emailCodeStatus.classList.add('text-emerald-700');
                emailCodeStatus.classList.remove('text-red-600');
                sendEmailCodeButton.disabled = true;
                sendEmailCodeButton.textContent = 'Email verified';
            } catch (verificationError) {
                if (emailCodeStatus && !emailCodeStatus.textContent) {
                    emailCodeStatus.textContent = verificationError.message;
                }
                verifyEmailCodeButton.disabled = false;
                verifyEmailCodeButton.textContent = 'Verify email';
            }
        });

        accountEmail?.addEventListener('input', () => {
            const currentEmail = accountEmail.value.trim().toLowerCase();
            if (currentEmail === requestedEmail) {
                return;
            }
            emailVerified = false;
            codeSent = false;
            requestedEmail = '';
            signupCode.value = '';
            signupCode.required = false;
            emailCodeSection.classList.add('hidden');
            emailCodeStatus.textContent = '';
            emailCodeStatus.classList.remove('text-red-600', 'text-emerald-700');
            sendEmailCodeButton.disabled = false;
            sendEmailCodeButton.textContent = 'Send code';
        });

        beneficiaryTypeInputs.forEach((input) => {
            input.addEventListener('change', () => {
                const requiresDetails = input.value === 'individual' || input.value === 'organization';
                beneficiaryDetails?.classList.toggle('hidden', !requiresDetails);
                beneficiaryName.required = requiresDetails;
                beneficiaryRelationship.required = requiresDetails;
                if (beneficiaryRelationshipLabel) {
                    beneficiaryRelationshipLabel.textContent = input.value === 'organization'
                        ? 'Your role or relationship to the organization'
                        : 'Your relationship to them';
                }
            });
        });
        const selectedBeneficiaryType = form.querySelector('input[name="beneficiary_type"]:checked');
        if (selectedBeneficiaryType) {
            selectedBeneficiaryType.dispatchEvent(new Event('change'));
        }

        backButton?.addEventListener('click', () => showStep(currentStep - 1));
        nextButton?.addEventListener('click', () => {
            if (validateCurrentStep()) {
                showStep(currentStep + 1);
                steps[currentStep].querySelector('input, select, textarea, button')?.focus({preventScroll: true});
            }
        });
        form.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA' && currentStep < steps.length - 1) {
                event.preventDefault();
                if (validateCurrentStep()) {
                    showStep(currentStep + 1);
                }
            }
        });
        discardDraftButton?.addEventListener('click', async () => {
            discardDraftButton.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'action=discard_draft'
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    throw new Error(result.message || 'Unable to discard this draft.');
                }
                sessionStorage.removeItem('campaign-create-image-preview');
                window.location.reload();
            } catch (discardError) {
                console.error('[campaign-create] Unable to discard draft', discardError);
                discardDraftButton.disabled = false;
                if (alertBox) {
                    alertBox.classList.remove('hidden');
                }
                if (alertMessage) {
                    alertMessage.textContent = 'Unable to discard this draft right now. Please try again.';
                }
            }
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!validateCurrentStep()) {
                return;
            }

            clearErrors();
            if (imagePreviewImg && imagePreviewImg.src) {
                saveImagePreviewState(imagePreviewImg.src);
            }

            const submitLabel = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = form.dataset.draftId ? 'Publishing...' : (form.dataset.submitLabel || 'Saving...');
                submitButton.classList.add('cursor-wait', 'opacity-70');
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();

                if (!response.ok || !result.success) {
                    if (result.redirect_url) {
                        window.location.assign(result.redirect_url);
                        return;
                    }
                    if (response.status === 401) {
                        window.location.assign(result.redirect_url || '<?php echo BASE_URL; ?>/login.php');
                        return;
                    }

                    const errors = result.errors || {};
                    Object.entries(errors).forEach(([fieldName, message]) => {
                        setFieldError(fieldName, message);
                    });
                    const firstInvalidField = Object.keys(errors)[0];
                    const stepForField = {
                        campaign_country_code: 0,
                        campaign_city: 0,
                        campaign_postal_code: 0,
                        category: 1,
                        beneficiary_type: 2,
                        beneficiary_name: 2,
                        beneficiary_relationship: 2,
                        goal: 3,
                        title: 4,
                        description: 4,
                        campaign_image: 5,
                        account_name: 6,
                        account_email: 6,
                        account_password: 6,
                        account_password_confirmation: 6,
                        signup_code: 6,
                        accept_terms: 6
                    };
                    if (firstInvalidField && stepForField[firstInvalidField] !== undefined) {
                        showStep(stepForField[firstInvalidField]);
                    }
                    if (alertBox) {
                        alertBox.classList.remove('hidden');
                    }
                    if (alertMessage) {
                        alertMessage.textContent = result.message || result.error || 'Please review your details and try again.';
                    }
                    alertBox?.scrollIntoView({behavior: 'smooth', block: 'center'});
                    return;
                }

                saveImagePreviewState('');
                if (result.redirect_url) {
                    window.location.assign(result.redirect_url);
                    return;
                }

                throw new Error('Campaign was created, but the next page could not be opened.');
            } catch (submitError) {
                console.error('[campaign-create] Submission failed', submitError);
                if (alertBox) {
                    alertBox.classList.remove('hidden');
                }
                if (alertMessage) {
                    alertMessage.textContent = 'Unable to continue right now. Please try again.';
                }
                alertBox?.scrollIntoView({behavior: 'smooth', block: 'center'});
            } finally {
                if (submitButton && submitButton.disabled) {
                    submitButton.disabled = false;
                    submitButton.textContent = submitLabel;
                    submitButton.classList.remove('cursor-wait', 'opacity-70');
                }
            }
        });
        showStep(currentStep);

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

            const draftPreviewUrl = form.dataset.imagePreviewUrl;
            if (draftPreviewUrl) {
                imagePreviewImg.src = draftPreviewUrl;
                imagePreview.classList.remove('hidden');
                uploadPlaceholder.classList.add('hidden');
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
                imageInput.required = true;
                if (removeImageField) {
                    removeImageField.value = '1';
                }
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
                    imageInput.required = false;
                    if (removeImageField) {
                        removeImageField.value = '0';
                    }
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
    if ($isAuthenticated) {
        include __DIR__ . '/../components/header.php';
    }
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
    <?php if (!$isAuthenticated): ?>
        <?php include __DIR__ . '/../components/public-header.php'; ?>
    <?php endif; ?>
    <div class="<?php echo $isAuthenticated ? 'flex min-h-screen pt-2 sm:pt-3 lg:pt-4' : 'min-h-screen pt-16'; ?>">
        <?php if ($isAuthenticated): ?>
            <?php include __DIR__ . '/../components/sidebar.php'; ?>
        <?php endif; ?>
        <main class="app-main flex-1 min-w-0 bg-gray-50<?php echo $isAuthenticated ? ' lg:ml-64' : ''; ?>">
            <?php renderCreateCampaignContent($error, $fieldErrors, $oldInput, $countries, $canCreateCampaign, $isAuthenticated, $draftId, $draftPreviewUrl, $draftNotice); ?>
        </main>
    </div>
    <?php if (!$isAuthenticated): ?>
        <?php include __DIR__ . '/../components/public-footer.php'; ?>
    <?php endif; ?>
</body>
</html>
<?php
} else {
    renderCreateCampaignContent($error, $fieldErrors, $oldInput, $countries, $canCreateCampaign, $isAuthenticated, $draftId, $draftPreviewUrl, $draftNotice);
    echo ob_get_clean();
}
