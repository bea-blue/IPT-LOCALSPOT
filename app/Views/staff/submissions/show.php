<?php
use App\Models\Place;
use App\Models\PlaceDocument;
use App\Support\PlaceFormat;
use App\Support\Roles;
$p = $place;
$b = PlaceFormat::budget($p);
$isDemo = ($p['owner_email'] ?? '') === 'demo-owner@localspot.test';
?>
<p><a class="back" href="<?= e(url('/admin/shops')) ?>">Back to Shops</a></p>
<div class="page-head">
    <div>
        <h1><?= e($p['name']) ?></h1>
        <p><span class="status status-<?= e($p['status']) ?>"><?= e(Place::STATUSES[$p['status']] ?? $p['status']) ?></span>
            <span class="muted">Owner: <?= e($p['owner_name'] ?? 'None') ?> (<?= e($p['owner_email'] ?? '—') ?>)</span></p>
    </div>
    <a class="btn btn-secondary" href="<?= e(url('/places/' . $p['id'])) ?>" target="_blank" rel="noopener">Preview page</a>
</div>
<?php if ($p['review_note'] && in_array($p['status'], ['rejected', 'suspended'], true)): ?>
    <div class="callout callout-warn"><strong>Reason given:</strong> <?= e($p['review_note']) ?><?php if ($p['reviewer_name']): ?> <span class="muted">(<?= e($p['reviewer_name']) ?>)</span><?php endif; ?></div>
<?php endif; ?>

<div class="staff-grid">
    <div>
        <section class="panel">
            <h2>Proof <span class="private-tag">Private</span></h2>
            <?php if ($isDemo): ?>
                <p class="callout">Demo listing imported for the school project from public sources. No permit is on file.</p>
            <?php endif; ?>
            <?php if ($documents): ?>
                <ul class="file-list">
                    <?php foreach ($documents as $d): ?>
                        <li><span class="file-kind"><?= e(PlaceDocument::KINDS[$d['kind']] ?? $d['kind']) ?></span>
                            <a href="<?= e(url('/documents/' . $d['id'])) ?>" target="_blank" rel="noopener"><?= e($d['original_name']) ?></a>
                            <span class="muted"><?= e(strtoupper($d['file_type'])) ?>, <?= e(round($d['size'] / 1024)) ?> KB, <?= e(date('M j, g:i A', strtotime($d['created_at']))) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif (!$isDemo): ?>
                <p class="muted">No documents uploaded.</p>
            <?php endif; ?>
            <?php if ($missing && $p['status'] === 'pending'): ?>
                <p class="field-error">Missing: <?= e(implode(' and ', array_map(fn($k) => strtolower(PlaceDocument::KINDS[$k]), $missing))) ?>. It cannot be approved yet.</p>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>Listing details</h2>
            <dl class="facts">
                <div><dt>Category</dt><dd><?= e($p['category_name']) ?></dd></div>
                <div><dt>Address</dt><dd><?= e($p['address']) ?>, <?= e($p['city']) ?></dd></div>
                <div><dt>Budget</dt><dd><?= $b ? e(($b['estimate'] ? 'Est. ' : '') . $b['amount'] . ($b['unit'] ? ' ' . $b['unit'] : '')) : 'Not given' ?></dd></div>
                <div><dt>Hours</dt><dd><?= e($p['hours_note'] ?? 'Not given') ?></dd></div>
                <div><dt>Payment</dt><dd><?= e($p['payment_options'] ?? 'Not given') ?></dd></div>
                <div><dt>Photos</dt><dd><?= count($photos) ?> · Menu: <?= count($menus) + $menuItemCount ?> entries</dd></div>
            </dl>
            <p><?= nl2br(e($p['description'])) ?></p>
            <p class="hint">Staff cannot change these details. If something is wrong, reject with a reason so the owner can fix it.</p>
        </section>
    </div>

    <div>
        <?php if (Roles::can($role, 'decide_submission') && $p['status'] === 'pending'): ?>
            <section class="panel decision">
                <h2>Decision</h2>
                <form method="post" action="<?= e(url('/admin/shops/' . $p['id'] . '/approve')) ?>" data-confirm="Approve and publish &quot;<?= e($p['name']) ?>&quot;?">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary btn-block" type="submit" <?= $missing ? 'disabled' : '' ?>>Approve and publish</button>
                </form>
                <form method="post" action="<?= e(url('/admin/shops/' . $p['id'] . '/reject')) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="field"><label for="reject-reason">Reason for rejecting (the owner sees this)</label>
                        <textarea id="reject-reason" name="reason" rows="3" maxlength="500" required></textarea></div>
                    <button class="btn btn-danger btn-block" type="submit">Reject</button>
                </form>
            </section>
        <?php elseif (Roles::can($role, 'suspend_place') && in_array($p['status'], ['published', 'hidden', 'suspended'], true)): ?>
            <section class="panel decision">
                <h2><?= $p['status'] === 'suspended' ? 'Reinstate' : 'Suspend' ?></h2>
                <form method="post" action="<?= e(url('/admin/shops/' . $p['id'] . ($p['status'] === 'suspended' ? '/reinstate' : '/suspend'))) ?>" class="form">
                    <?= csrf_field() ?>
                    <div class="field"><label for="decision-reason">Reason (the owner sees this)</label>
                        <textarea id="decision-reason" name="reason" rows="3" maxlength="500" required></textarea></div>
                    <button class="btn <?= $p['status'] === 'suspended' ? 'btn-primary' : 'btn-danger' ?> btn-block" type="submit"><?= $p['status'] === 'suspended' ? 'Reinstate and publish' : 'Suspend listing' ?></button>
                </form>
            </section>
        <?php elseif (!Roles::can($role, 'decide_submission')): ?>
            <section class="panel"><h2>Decision</h2><p class="muted">As <?= e(Roles::label($role)) ?>, you check the proof and add notes. A manager or developer approves or rejects.</p></section>
        <?php endif; ?>

        <section class="panel">
            <h2>Check notes</h2>
            <?php if ($checks): ?>
                <ul class="checks">
                    <?php foreach ($checks as $c): ?>
                        <li><strong><?= e($c['staff_name'] ?? 'Former staff') ?></strong> <span class="muted"><?= e(Roles::label($c['staff_role'])) ?>, <?= e(date('M j, g:i A', strtotime($c['created_at']))) ?></span><br><?= e($c['note']) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="muted">No notes yet.</p>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/admin/shops/' . $p['id'] . '/notes')) ?>" class="form">
                <?= csrf_field() ?>
                <div class="field"><label for="note">Add a note</label><textarea id="note" name="note" rows="2" maxlength="1000" placeholder="e.g. Permit name and address match the listing." required></textarea></div>
                <button class="btn btn-secondary" type="submit">Add note</button>
            </form>
        </section>
    </div>
</div>
