<?php use App\Support\Roles; use SpotCore\Auth; use SpotCore\View; $user = Auth::user(); $isStaff = Roles::isStaff($user['role'] ?? null); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'LocalSpot') ?> | <?= e(config('app.name')) ?></title>
    <link rel="preload" href="<?= e(asset('fonts/inter-regular.woff')) ?>" as="font" type="font/woff" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container header-inner">
        <?= View::partial('partials/brand') ?>
        <nav class="nav" aria-label="Main">
            <a href="<?= e(url('/')) ?>">Explore</a>
            <?php if ($user): ?>
                <?php if ($isStaff): ?><a class="nav-secondary" href="<?= e(url('/admin')) ?>">Staff panel</a><?php else: ?><a class="nav-secondary" href="<?= e(url('/my/shops')) ?>"><?= ($user['role'] ?? '') === 'owner' ? 'My shops' : 'List your shop' ?></a><?php endif; ?>
                <span class="nav-user">Hi, <?= e($user['name']) ?></span>
                <form class="nav-form" method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="link-button" type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a class="nav-secondary" href="<?= e(url('/register')) ?>">List your shop</a>
                <a class="nav-secondary" href="<?= e(url('/login')) ?>">Log in</a>
                <a class="btn btn-primary btn-small" href="<?= e(url('/register')) ?>">Sign up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main" class="main">
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <p>LocalSpot lists cafés and food spots around Angeles City. Details are checked against public sources; confirm hours and prices with each venue before visiting.</p>
        <p>Weather data by <a href="https://open-meteo.com/" rel="noopener" target="_blank">Open-Meteo.com</a>. Built on SpotCore, a custom PHP framework.</p>
    </div>
</footer>
</body>
</html>
