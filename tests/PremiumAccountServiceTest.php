<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/PremiumAccountService.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE premium_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    title TEXT NOT NULL,
    price REAL NOT NULL,
    currency TEXT NOT NULL DEFAULT "USD",
    duration_days INTEGER NOT NULL,
    features TEXT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);');
$pdo->exec('CREATE TABLE premium_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    transaction_id TEXT NOT NULL UNIQUE,
    amount REAL NOT NULL,
    currency TEXT NOT NULL DEFAULT "USD",
    gateway TEXT NOT NULL DEFAULT "pending",
    payment_reference TEXT NULL,
    status TEXT NOT NULL DEFAULT "pending",
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at TEXT NULL
);');
$pdo->exec('CREATE TABLE premium_memberships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL UNIQUE,
    plan_code TEXT NOT NULL,
    plan_title TEXT NOT NULL,
    starts_at TEXT NOT NULL,
    expires_at TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT "active",
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NULL
);');

$pdo->exec("INSERT INTO premium_plans (code, title, price, currency, duration_days, features, is_active) VALUES ('starter', 'Starter Plan', 29.99, 'USD', 30, '{\"feature\":\"basic\"}', 1)");

$service = new PremiumAccountService($pdo);

$plans = $service->getPlans();
if (count($plans) !== 1) {
    throw new RuntimeException('Expected one premium plan.');
}

if ($service->hasPremiumAccess(42)) {
    throw new RuntimeException('User should not have premium access before purchase.');
}

$purchase = $service->createUpgradeRequest(42, 'starter');
if (!isset($purchase['transaction_id'], $purchase['plan_code']) || $purchase['plan_code'] !== 'starter') {
    throw new RuntimeException('Purchase request was not created correctly.');
}

if (!$service->completeUpgrade(42, $purchase['transaction_id'], 'paypal', 'ref-123')) {
    throw new RuntimeException('Premium upgrade was not completed.');
}

if (!$service->hasPremiumAccess(42)) {
    throw new RuntimeException('User should have premium access after completion.');
}

$followUpPurchase = $service->createUpgradeRequest(43, 'starter');
if (!$service->completeUpgradeByReference($followUpPurchase['transaction_id'], 'relworx', $followUpPurchase['transaction_id'])) {
    throw new RuntimeException('Premium upgrade by reference was not completed.');
}

echo "PremiumAccountService tests passed.\n";
