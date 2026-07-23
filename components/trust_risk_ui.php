<?php
require_once __DIR__ . '/../config/TrustRiskService.php';

if (!function_exists('getTrustRiskUiState')) {
    function getTrustRiskUiState(?array $currentUser): array
    {
        $defaultState = [
            'status' => 'ACTIVE',
            'message' => [
                'short' => '',
                'full' => '',
            ],
            'restrictions' => [],
            'show_banner' => false,
            'banner_class' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'badge_class' => 'bg-emerald-100 text-emerald-700',
        ];

        if (empty($currentUser['id'])) {
            return $defaultState;
        }

        try {
            $service = new TrustRiskService();
            $profile = $service->getUserRiskProfile((int) $currentUser['id']);
            $status = strtoupper((string) ($profile['status'] ?? 'ACTIVE'));

            $styleMap = [
                'UNDER_REVIEW' => [
                    'banner_class' => 'border-amber-200 bg-amber-50 text-amber-950',
                    'badge_class' => 'bg-amber-100 text-amber-700',
                ],
                'LIMITED' => [
                    'banner_class' => 'border-orange-200 bg-orange-50 text-orange-950',
                    'badge_class' => 'bg-orange-100 text-orange-700',
                ],
                'SUSPENDED' => [
                    'banner_class' => 'border-rose-200 bg-rose-50 text-rose-950',
                    'badge_class' => 'bg-rose-100 text-rose-700',
                ],
                'BANNED' => [
                    'banner_class' => 'border-red-200 bg-red-50 text-red-950',
                    'badge_class' => 'bg-red-100 text-red-700',
                ],
            ];

            $style = $styleMap[$status] ?? $defaultState;

            return [
                'status' => $status,
                'message' => $profile['message'] ?? $defaultState['message'],
                'restrictions' => $profile['restrictions'] ?? [],
                'show_banner' => $status !== 'ACTIVE',
                'banner_class' => $style['banner_class'] ?? $defaultState['banner_class'],
                'badge_class' => $style['badge_class'] ?? $defaultState['badge_class'],
            ];
        } catch (Throwable $e) {
            error_log('Trust risk UI state unavailable: ' . $e->getMessage());
            return $defaultState;
        }
    }
}

if (!function_exists('renderTrustRiskBanner')) {
    function renderTrustRiskBanner(array $trustRiskUi): void
    {
        if (empty($trustRiskUi['show_banner'])) {
            return;
        }
        ?>
        <section class="rounded-3xl border px-5 py-5 shadow-sm <?php echo htmlspecialchars((string) $trustRiskUi['banner_class']); ?>">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] <?php echo htmlspecialchars((string) $trustRiskUi['badge_class']); ?>">
                        <span><?php echo htmlspecialchars((string) $trustRiskUi['status']); ?></span>
                    </div>
                    <h2 class="mt-3 text-lg font-bold">Account restriction notice</h2>
                    <p class="mt-2 text-sm leading-6"><?php echo htmlspecialchars((string) ($trustRiskUi['message']['full'] ?? $trustRiskUi['message']['short'] ?? 'Some account actions are currently unavailable.')); ?></p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="<?php echo htmlspecialchars(BASE_URL); ?>/account/notifications.php" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">View notices</a>
                </div>
            </div>
        </section>
        <?php
    }
}

if (!function_exists('trustRiskActionDisabled')) {
    function trustRiskActionDisabled(array $trustRiskUi, string $actionKey): bool
    {
        return !empty($trustRiskUi['restrictions'][$actionKey]);
    }
}

if (!function_exists('renderSidebarNavItem')) {
    function renderSidebarNavItem(array $item, string $currentClass, array $trustRiskUi): void
    {
        $disabled = !empty($item['action_key']) && trustRiskActionDisabled($trustRiskUi, (string) $item['action_key']);
        $baseClass = $currentClass . ' group flex items-center rounded-lg px-3 py-3 transition-all duration-200';
        $enabledClass = $baseClass . ' hover:bg-emerald-50 hover:text-emerald-600';
        $disabledClass = 'group flex items-center rounded-lg px-3 py-3 text-gray-400 opacity-60 cursor-not-allowed bg-gray-50 border border-dashed border-gray-200';
        $title = $disabled && !empty($trustRiskUi['message']['short'])
            ? ((string) $item['label']) . ' unavailable: ' . (string) $trustRiskUi['message']['short']
            : (string) $item['label'];
        ?>
        <?php if ($disabled): ?>
            <span class="sidebar-link <?php echo $disabledClass; ?>" title="<?php echo htmlspecialchars($title); ?>" aria-disabled="true">
                <?php echo $item['icon_html']; ?>
                <span class="sidebar-label ml-3 truncate text-sm font-medium"><?php echo htmlspecialchars((string) $item['label']); ?></span>
            </span>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars((string) $item['href']); ?>" class="sidebar-link <?php echo $enabledClass; ?>" title="<?php echo htmlspecialchars($title); ?>">
                <?php echo $item['icon_html']; ?>
                <span class="sidebar-label ml-3 truncate text-sm font-medium"><?php echo htmlspecialchars((string) $item['label']); ?></span>
            </a>
        <?php endif; ?>
        <?php
    }
}
