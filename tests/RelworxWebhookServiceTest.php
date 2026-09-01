<?php

declare(strict_types=1);

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
$purchase = $service->createUpgradeRequest(21, 'starter');

$paymentId = $pdo->query("SELECT id FROM premium_payments WHERE transaction_id = '{$purchase['transaction_id']}'")->fetchColumn();
if ($paymentId === false) {
    throw new RuntimeException('Payment record was not created.');
}

$service->completeUpgradeByReference('RELWORX_ABC_123', 'relworx', 'RELWORX_ABC_123');

$membership = $pdo->query("SELECT plan_code, status FROM premium_memberships WHERE user_id = 21")->fetch(PDO::FETCH_ASSOC);
if ($membership === false || $membership['status'] !== 'active') {
    throw new RuntimeException('Premium membership was not activated by reference completion.');
}

echo "Relworx webhook reference completion works.\n";
