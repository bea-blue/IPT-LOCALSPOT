<?php use App\Models\Place; use SpotCore\View; ?>
<div class="container owner-page">
    <div class="page-head">
        <div>
            <h1>My shops</h1>
            <p class="muted">You manage everything about your own shops. LocalSpot staff only check your documents and approve the listing.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/my/shops/create')) ?>">List a shop</a>
    </div>
    <?= View::partial('partials/flash') ?>
    <?php if (!$places): ?>
        <div class="empty">
            <h3>You have not listed a shop yet</h3>
            <p>Add your shop's details, upload your business permit and a photo of the shop, then submit it. Staff approve it before it appears on LocalSpot.</p>
            <a class="btn btn-primary" href="<?= e(url('/my/shops/create')) ?>">List your shop</a>
        </div>
    <?php else: ?>
        <ul class="owner-shops">
            <?php foreach ($places as $p): ?>
                <li class="panel owner-shop">
                    <div class="owner-shop-head">
                        <h2><?= e($p['name']) ?></h2>
                        <span class="status status-<?= e($p['status']) ?>"><?= e(Place::STATUSES[$p['status']] ?? $p['status']) ?></span>
                    </div>
                    <p class="muted"><?= e($p['category_name']) ?> · <?= e($p['address']) ?></p>
                    <?php if (in_array($p['status'], ['rejected', 'suspended'], true) && $p['review_note']): ?>
                        <p class="staff-reason"><strong><?= $p['status'] === 'rejected' ? 'Why it was rejected' : 'Why it was suspended' ?>:</strong> <?= e($p['review_note']) ?></p>
                    <?php elseif ($p['status'] === 'pending'): ?>
                        <p class="muted">Staff are checking your documents.</p>
                    <?php endif; ?>
                    <div class="owner-shop-actions">
                        <?php if ($p['status'] !== 'suspended'): ?><a class="btn btn-primary btn-small" href="<?= e(url('/my/shops/' . $p['id'] . '/edit')) ?>">Manage</a><?php else: ?><a class="btn btn-secondary btn-small" href="<?= e(url('/my/shops/' . $p['id'] . '/edit')) ?>">View</a><?php endif; ?>
                        <a class="btn btn-secondary btn-small" href="<?= e(url('/places/' . $p['id'])) ?>"><?= $p['status'] === 'published' ? 'View public page' : 'Preview' ?></a>
                        <?php if (in_array($p['status'], ['published', 'hidden'], true)): ?>
                            <form method="post" action="<?= e(url('/my/shops/' . $p['id'] . '/visibility')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-secondary btn-small" type="submit"><?= $p['status'] === 'published' ? 'Hide from visitors' : 'Show to visitors' ?></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
