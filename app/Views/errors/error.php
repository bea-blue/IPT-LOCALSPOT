<?php
$headings = [403 => 'Not allowed', 404 => 'Page not found', 405 => 'Method not allowed', 419 => 'Form expired', 500 => 'Something went wrong'];
?>
<section class="container error-page">
    <p class="error-code"><?= (int) $status ?></p>
    <h1><?= e($headings[$status] ?? 'Error') ?></h1>
    <p class="muted"><?= e($message) ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to Explore</a>
</section>
