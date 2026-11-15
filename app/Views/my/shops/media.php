<?php
use App\Models\PlaceDocument;
$dis = $locked ? 'disabled' : '';
$base = '/my/shops/' . $p['id'];
$money = fn($v) => $v === null ? '' : '₱' . rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
?>
<section class="panel owner-section" id="documents" aria-labelledby="docs-title">
    <h2 id="docs-title">Proof for approval <span class="private-tag">Private</span></h2>
    <p class="muted">Only LocalSpot staff and you can open these. Required: a <strong>business permit</strong> and a <strong>photo of the shop</strong>. JPG, PNG, WEBP or PDF, up to 5 MB.</p>
    <?php if ($documents): ?>
        <ul class="file-list">
            <?php foreach ($documents as $d): ?>
                <li>
                    <span class="file-kind"><?= e(PlaceDocument::KINDS[$d['kind']] ?? $d['kind']) ?></span>
                    <a href="<?= e(url('/documents/' . $d['id'])) ?>" target="_blank" rel="noopener"><?= e($d['original_name']) ?></a>
                    <span class="muted"><?= e(strtoupper($d['file_type'])) ?>, <?= e(round($d['size'] / 1024)) ?> KB</span>
                    <?php if (!$locked): ?>
                        <form method="post" action="<?= e(url('/my/documents/' . $d['id'] . '/delete')) ?>" data-confirm="Remove this document?">
                            <?= csrf_field() ?><button class="link-button danger" type="submit">Remove</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="hint">No documents yet.</p>
    <?php endif; ?>
    <?php if (!$locked): ?>
        <form class="form upload-form" method="post" action="<?= e(url($base . '/documents')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field">
                <label for="doc-kind">Document</label>
                <select id="doc-kind" name="kind" required>
                    <?php foreach (PlaceDocument::KINDS as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="field field-grow">
                <label for="doc-file">File</label>
                <input id="doc-file" type="file" name="document" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required>
            </div>
            <button class="btn btn-secondary" type="submit">Upload</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel owner-section" id="photos" aria-labelledby="photos-title">
    <h2 id="photos-title">Photos on your listing</h2>
    <p class="muted">The first photo is used on the result card. Up to 6 photos, JPG, PNG or WEBP, 5 MB each.</p>
    <?php if ($photos): ?>
        <ul class="admin-photos">
            <?php foreach ($photos as $ph): ?>
                <li>
                    <img src="<?= e(asset($ph['path'])) ?>" alt="<?= e($ph['alt']) ?>" width="<?= e($ph['width']) ?>" height="<?= e($ph['height']) ?>" loading="lazy">
                    <span><?= e($ph['alt']) ?></span>
                    <?php if (!$locked && str_starts_with($ph['path'], 'uploads/')): ?>
                        <form method="post" action="<?= e(url('/my/photos/' . $ph['id'] . '/delete')) ?>" data-confirm="Remove this photo?">
                            <?= csrf_field() ?><button class="link-button danger" type="submit">Remove</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (!$locked): ?>
        <form class="form upload-form" method="post" action="<?= e(url($base . '/photos')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field field-grow">
                <label for="photo-file">Photo</label>
                <input id="photo-file" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
            </div>
            <div class="field field-grow">
                <label for="photo-alt">Describe the photo</label>
                <input id="photo-alt" name="alt" maxlength="200" placeholder="e.g. Front of the shop with outdoor seating" required>
            </div>
            <button class="btn btn-secondary" type="submit">Add photo</button>
        </form>
    <?php endif; ?>
</section>

<section class="panel owner-section" id="menu" aria-labelledby="menu-title">
    <h2 id="menu-title">Menu</h2>
    <?php if ($menuItems): ?>
        <table class="table menu-table">
            <caption class="sr-only">Menu items</caption>
            <thead><tr><th scope="col">Section</th><th scope="col">Item</th><th scope="col">Price</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
            <?php foreach ($menuItems as $it): ?>
                <tr>
                    <td><?= e($it['section']) ?></td><td><?= e($it['name']) ?></td><td><?= e($money($it['price'])) ?></td>
                    <td><?php if (!$locked): ?><form method="post" action="<?= e(url('/my/menu-items/' . $it['id'] . '/delete')) ?>"><?= csrf_field() ?><button class="link-button danger" type="submit">Remove</button></form><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php if (!$locked): ?>
        <form class="form upload-form" method="post" action="<?= e(url($base . '/menu-items')) ?>">
            <?= csrf_field() ?>
            <div class="field"><label for="mi-section">Section</label><input id="mi-section" name="section" maxlength="80" placeholder="e.g. Drinks" required></div>
            <div class="field field-grow"><label for="mi-name">Item</label><input id="mi-name" name="name" maxlength="150" required></div>
            <div class="field"><label for="mi-price">Price (₱)</label><input id="mi-price" name="price" type="number" min="0" step="0.01"></div>
            <button class="btn btn-secondary" type="submit">Add item</button>
        </form>
    <?php endif; ?>

    <?php if ($menus): ?>
        <ul class="file-list">
            <?php foreach ($menus as $m): ?>
                <li>
                    <span class="file-kind"><?= $m['kind'] === 'image' ? 'Menu photo' : 'Menu link' ?></span>
                    <a href="<?= e($m['kind'] === 'image' ? asset($m['path']) : $m['url']) ?>" target="_blank" rel="noopener"><?= e($m['label']) ?></a>
                    <?php if (!$locked && ($m['kind'] === 'link' || str_starts_with((string) $m['path'], 'uploads/'))): ?>
                        <form method="post" action="<?= e(url('/my/menus/' . $m['id'] . '/delete')) ?>" data-confirm="Remove this from the menu?">
                            <?= csrf_field() ?><button class="link-button danger" type="submit">Remove</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if (!$locked): ?>
        <form class="form upload-form" method="post" action="<?= e(url($base . '/menu-photos')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="field field-grow"><label for="mp-file">Menu photo</label><input id="mp-file" type="file" name="menu_photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required></div>
            <div class="field field-grow"><label for="mp-label">Label</label><input id="mp-label" name="label" maxlength="150" placeholder="e.g. Drinks menu" required></div>
            <button class="btn btn-secondary" type="submit">Add menu photo</button>
        </form>
        <form class="form upload-form" method="post" action="<?= e(url($base . '/menu-links')) ?>">
            <?= csrf_field() ?>
            <div class="field field-grow"><label for="ml-url">Online menu link</label><input id="ml-url" name="url" type="url" maxlength="255" placeholder="https://" required></div>
            <div class="field field-grow"><label for="ml-label">Label</label><input id="ml-label" name="label" maxlength="150" placeholder="e.g. Order on Foodpanda" required></div>
            <button class="btn btn-secondary" type="submit">Add link</button>
        </form>
    <?php endif; ?>
</section>
