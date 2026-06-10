<?php
/**
 * Public layout header.
 * Defines shared assets and navigation for pages visitors can see before login.
 */
$publicBase = '/timetable_project/';
$publicActive = basename($_SERVER['PHP_SELF']);

$publicNavItems = [
    'index.php' => 'Home',
    'about.php' => 'About',
    'features.php' => 'Features',
    'support.php' => 'Support',
    'contact.php' => 'Contact',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Academic Scheduling & Seating Suite') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="min-h-screen bg-emerald-50/30 text-slate-900 antialiased">
<header class="sticky top-0 z-50 border-b border-emerald-200 bg-white/95 shadow-md backdrop-blur">
    <div class="bg-emerald-950 text-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-2 text-xs font-semibold sm:px-6 lg:px-8 lg:flex-row lg:items-center lg:justify-between">
            <span class="uppercase tracking-wide">University Administration System</span>
            <span class="text-emerald-100">Working Hours: Monday-Friday 8:00am - 16:00pm | Phone: +92419200161-70</span>
        </div>
    </div>

    <div class="mx-auto flex min-h-20 max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="<?= $publicBase ?>index.php" class="flex min-w-0 items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-md border border-emerald-200 bg-white shadow-sm">
                <img src="<?= $publicBase ?>assets/logo.png" alt="University Logo" class="h-10 w-10 object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-base font-extrabold uppercase tracking-wide text-emerald-950 sm:text-lg">
                    Academic Scheduling & Seating Suite
                </span>
                <span class="hidden text-xs font-semibold text-emerald-700 sm:block">
                    Scheduling and Seating Management Portal
                </span>
            </span>
        </a>

        <nav class="hidden items-center gap-6 lg:flex">
            <?php foreach ($publicNavItems as $file => $label): ?>
                <a href="<?= $publicBase . $file ?>"
                   class="border-b-2 py-7 text-sm font-bold uppercase tracking-wide transition <?= $publicActive === $file ? 'border-emerald-800 text-emerald-900' : 'border-transparent text-slate-700 hover:border-emerald-300 hover:text-emerald-900' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="flex items-center gap-2">
            <a href="<?= $publicBase ?>login.php"
               class="rounded-md bg-emerald-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                Portal Login
            </a>
        </div>
    </div>

    <nav class="flex gap-2 overflow-x-auto border-t border-emerald-100 bg-emerald-50 px-4 py-2 lg:hidden">
        <?php foreach ($publicNavItems as $file => $label): ?>
            <a href="<?= $publicBase . $file ?>"
               class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-bold <?= $publicActive === $file ? 'bg-emerald-900 text-white' : 'text-emerald-900' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </nav>
</header>
