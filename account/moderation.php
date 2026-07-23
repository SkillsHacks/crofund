<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - CroFunding</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-6 py-16">
        <div class="w-full rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">404</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight">This page is not available here</h1>
            <p class="mt-4 text-sm leading-6 text-slate-600">
                Moderation tools are part of the admin application and are not served from the user-facing CroFund site.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?php echo htmlspecialchars(BASE_URL); ?>/dashboard.php" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">Go to dashboard</a>
                <a href="<?php echo htmlspecialchars(BASE_URL); ?>/" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300">Back to home</a>
            </div>
        </div>
    </main>
</body>
</html>
