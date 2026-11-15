<?php use App\Models\Place; ?>
<div class="page-head">
    <div>
        <h1>Shops</h1>
        <p class="muted">Every shop an owner has submitted. Drafts stay private to their owner.</p>
    </div>
</div>
<nav class="filter-tabs" aria-label="Filter by status">
    <a class="<?= $status === null ? 'is-active' : '' ?>" href="<?= e(url('/admin/shops')) ?>">All</a>
    <?php foreach (Place::STATUSES as $key => $label): if ($key === 'draft') continue; ?>
        <a class="<?= $status === $key ? 'is-active' : '' ?>" href="<?= e(url('/admin/shops?status=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
<?php if (!$places): ?>
    <div class="empty"><h3>No shops here</h3><p>Nothing matches this status.</p></div>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <caption class="sr-only">Submitted shops</caption>
        <thead><tr><th scope="col">Shop</th><th scope="col">Owner</th><th scope="col">Proof</th><th scope="col">Status</th><th scope="col">Submitted</th><th scope="col"><span class="sr-only">Open</span></th></tr></thead>
        <tbody>
        <?php foreach ($places as $p): ?>
            <tr>
                <td class="cell-name"><?= e($p['name']) ?><br><span class="muted"><?= e($p['category_name']) ?></span></td>
                <td><?= e($p['owner_name'] ?? 'None') ?><br><span class="muted"><?= e($p['owner_email'] ?? '') ?></span></td>
                <td><?= (int) $p['document_count'] ?> file(s)</td>
                <td><span class="status status-<?= e($p['status']) ?>"><?= e(Place::STATUSES[$p['status']] ?? $p['status']) ?></span></td>
                <td class="muted cell-nowrap"><?= $p['submitted_at'] ? e(date('M j, Y', strtotime($p['submitted_at']))) : '—' ?></td>
                <td><a href="<?= e(url('/admin/shops/' . $p['id'])) ?>"><?= $p['status'] === 'pending' ? 'Check' : 'Open' ?></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
