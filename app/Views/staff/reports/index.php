<?php use App\Models\ReviewReport; ?>
<div class="page-head">
    <div>
        <h1>Review reports</h1>
        <p class="muted">Reviews users reported as spam, abusive or off-topic. Staff can hide a reported review or dismiss the report, but never edit or delete a review.</p>
    </div>
</div>
<nav class="filter-tabs" aria-label="Filter reports">
    <?php foreach (ReviewReport::STATUSES as $key => $label): ?>
        <a class="<?= $status === $key ? 'is-active' : '' ?>" href="<?= e(url('/admin/reports?status=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if (!$reports): ?>
    <div class="empty"><h3>No reports here</h3><p><?= $status === 'open' ? 'When a user reports a review, it shows up here.' : 'Nothing yet.' ?></p></div>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <caption class="sr-only">Reported reviews</caption>
        <thead><tr><th scope="col">Review</th><th scope="col">Place</th><th scope="col">Reason</th><th scope="col">Reported by</th><th scope="col"><?= $status === 'open' ? '<span class="sr-only">Actions</span>' : 'Handled by' ?></th></tr></thead>
        <tbody>
        <?php foreach ($reports as $r): ?>
            <tr>
                <td class="cell-comment"><strong><?= e($r['author_name']) ?></strong> <span class="muted"><?= (int) $r['rating'] ?>/5</span><br><?= e($r['comment']) ?></td>
                <td><a href="<?= e(url('/places/' . $r['place_id'])) ?>"><?= e($r['place_name']) ?></a></td>
                <td><?= e(ReviewReport::REASONS[$r['reason']] ?? $r['reason']) ?><?php if ($r['note']): ?><br><span class="muted"><?= e($r['note']) ?></span><?php endif; ?></td>
                <td><?= e($r['reporter_name']) ?><br><span class="muted"><?= e(date('M j, g:i A', strtotime($r['created_at']))) ?></span></td>
                <td>
                    <?php if ($status === 'open' && $canResolve): ?>
                        <div class="actions">
                            <form method="post" action="<?= e(url('/admin/reports/' . $r['id'] . '/hide')) ?>" data-confirm="Hide this review from the public page?"><?= csrf_field() ?><button class="btn btn-danger btn-small" type="submit">Hide review</button></form>
                            <form method="post" action="<?= e(url('/admin/reports/' . $r['id'] . '/dismiss')) ?>"><?= csrf_field() ?><button class="btn btn-secondary btn-small" type="submit">Dismiss</button></form>
                        </div>
                    <?php elseif ($status === 'open'): ?>
                        <span class="muted">A manager or developer decides.</span>
                    <?php else: ?>
                        <?= e($r['handler_name'] ?? '') ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
