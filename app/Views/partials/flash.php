<?php use SpotCore\Session; ?>
<?php foreach (['success' => 'status', 'error' => 'alert', 'info' => 'status'] as $type => $role): ?>
    <?php if ($msg = Session::getFlash($type)): ?>
        <div class="flash flash-<?= $type ?>" role="<?= $role ?>"><?= e($msg) ?></div>
    <?php endif; ?>
<?php endforeach; ?>
