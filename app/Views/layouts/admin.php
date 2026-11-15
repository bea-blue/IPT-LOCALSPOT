<?php
use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\View;
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$active = fn(string $p, bool $exact = false) => ($exact ? rtrim($path, '/') === rtrim(url($p), '/') : str_starts_with($path, url($p))) ? 'is-active' : '';
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> | LocalSpot staff</title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="admin-body">
<a class="skip-link" href="#main">Skip to content</a>
<div class="admin">
    <aside class="sidebar">
        <?= View::partial('partials/brand') ?>
        <nav class="side-nav" aria-label="Staff">
            <a class="<?= $active('/admin', true) ?>" href="<?= e(url('/admin')) ?>">Dashboard</a>
            <a class="<?= $active('/admin/shops') ?>" href="<?= e(url('/admin/shops')) ?>">Shops</a>
            <a class="<?= $active('/admin/reports') ?>" href="<?= e(url('/admin/reports')) ?>">Review reports</a>
            <?php if (Roles::can($user['role'] ?? null, 'manage_staff')): ?><a class="<?= $active('/admin/staff') ?>" href="<?= e(url('/admin/staff')) ?>">Staff</a><?php endif; ?>
            <a href="<?= e(url('/')) ?>">View public site</a>
        </nav>
        <div class="side-user">
            <p>Signed in as<br><strong><?= e($user['email'] ?? '') ?></strong><br><span class="muted"><?= e(Roles::label($user['role'] ?? null)) ?></span></p>
            <form method="post" action="<?= e(url('/logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-secondary btn-block">Log out</button>
            </form>
        </div>
    </aside>
    <main id="main" class="admin-main">
        <?= View::partial('partials/flash') ?>
        <?= $content ?>
    </main>
</div>
</body>
</html>
