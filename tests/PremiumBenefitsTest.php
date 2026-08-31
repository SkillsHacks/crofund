<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Campaign.php';

$requiredMethods = [
    'canCreateCampaign',
    'validateDonationDailyLimit',
    'isPremiumCampaignOwner',
];

foreach ($requiredMethods as $method) {
    if (!method_exists(Campaign::class, $method)) {
        throw new RuntimeException("Missing method: {$method}");
    }
}

echo "Premium benefit rule hooks are present.\n";
