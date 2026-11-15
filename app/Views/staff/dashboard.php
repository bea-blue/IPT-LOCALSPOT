<?php use App\Support\Roles; ?>
<div class="page-head">
    <div>
        <h1>Staff dashboard</h1>
        <p class="muted">You are signed in as <strong><?= e(Roles::label($role)) ?></strong>. Owners manage their own shops; staff check proof and decide.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/admin/shops?status=pending')) ?>">Check submissions</a>
</div>
<div class="stats stats-4">
    <div class="stat stat-pending"><span class="stat-value"><?= (int) $counts['pending'] ?></span><span class="stat-label">Waiting for approval</span></div>
    <div class="stat stat-published"><span class="stat-value"><?= (int) $counts['published'] ?></span><span class="stat-label">Published</span></div>
    <div class="stat"><span class="stat-value"><?= (int) $counts['suspended'] ?></span><span class="stat-label">Suspended</span></div>
    <div class="stat"><span class="stat-value"><?= (int) $openReports ?></span><span class="stat-label">Open review reports</span></div>
</div>

<section class="panel">
    <div class="panel-head">
        <h2>Waiting for approval</h2>
        <a href="<?= e(url('/admin/shops')) ?>">See all shops</a>
    </div>
    <?php if (!$pending): ?>
        <p class="muted">Nothing to check right now.</p>
    <?php else: ?>
        <ul class="recent">
            <?php foreach ($pending as $p): ?>
                <li>
                    <a href="<?= e(url('/admin/shops/' . $p['id'])) ?>"><?= e($p['name']) ?></a>
                    <span class="muted">by <?= e($p['owner_name'] ?? 'unknown') ?></span>
                    <span class="muted"><?= (int) $p['document_count'] ?> document(s)</span>
                    <span class="muted"><?= $p['submitted_at'] ? e(date('M j, g:i A', strtotime($p['submitted_at']))) : '' ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="panel admin-rights" aria-labelledby="rights-title">
    <h2 id="rights-title">Who can do what</h2>
    <?= \SpotCore\View::partial('staff/roles-table') ?>
    <p class="hint">No staff role can create, edit or delete a shop or a review. Upgrading or updating the website itself is done in the code by the developers, not from this panel.</p>
</section>
