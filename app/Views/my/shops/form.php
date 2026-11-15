<?php
use App\Models\Place;
use App\Models\PlaceDocument;
use SpotCore\View;
$p = $place;
$v = fn(string $key, $fallback = '') => old($key, $p[$key] ?? $fallback);
$action = $p ? url('/my/shops/' . $p['id'] . '/update') : url('/my/shops');
$err = fn(string $f) => View::partial('partials/field_error', ['field' => $f]);
$inv = fn(string $f) => error_for($f) ? 'aria-invalid="true" aria-describedby="' . e($f) . '-error"' : '';
$locked = $p && $p['status'] === 'suspended';
?>
<div class="container owner-page">
    <p><a class="back" href="<?= e(url('/my/shops')) ?>">Back to My shops</a></p>
    <div class="page-head">
        <div>
            <h1><?= $p ? e($p['name']) : 'List your shop' ?></h1>
            <?php if ($p): ?>
                <p><span class="status status-<?= e($p['status']) ?>"><?= e(Place::STATUSES[$p['status']] ?? $p['status']) ?></span></p>
            <?php else: ?>
                <p class="muted">Start with the details. You will upload your business permit and a photo of the shop on the next page.</p>
            <?php endif; ?>
        </div>
        <?php if ($p): ?><a class="btn btn-secondary" href="<?= e(url('/places/' . $p['id'])) ?>"><?= $p['status'] === 'published' ? 'View public page' : 'Preview' ?></a><?php endif; ?>
    </div>
    <?= View::partial('partials/flash') ?>

    <?php if ($p): ?>
        <?php if ($p['status'] === 'rejected' || $p['status'] === 'suspended'): ?>
            <div class="callout callout-warn"><strong><?= $p['status'] === 'rejected' ? 'Rejected by LocalSpot staff' : 'Suspended by LocalSpot staff' ?>:</strong> <?= e($p['review_note'] ?? '') ?>
                <?php if ($p['status'] === 'rejected'): ?><br>Fix the problem, then submit again.<?php else: ?><br>The listing is locked until staff reinstate it.<?php endif; ?></div>
        <?php endif; ?>
        <?php if (in_array($p['status'], ['draft', 'rejected'], true)): ?>
            <div class="callout submit-box">
                <div>
                    <strong>Ready to go live?</strong>
                    <?php if ($missing): ?>
                        First upload your <?= e(implode(' and ', array_map(fn($k) => strtolower(PlaceDocument::KINDS[$k]), $missing))) ?> in <a href="#documents">Proof for approval</a> below. The button unlocks once both are uploaded.
                    <?php else: ?>
                        Staff will check your documents and approve or reject the listing.
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= e(url('/my/shops/' . $p['id'] . '/submit')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary" type="submit" <?= $missing ? 'disabled' : '' ?>>Submit for approval</button>
                </form>
            </div>
        <?php elseif ($p['status'] === 'pending'): ?>
            <div class="callout">Waiting for approval. You can keep editing while staff check your documents.</div>
        <?php elseif ($p['status'] === 'published'): ?>
            <div class="callout">Approved and live. Changes you save here appear right away. Changing your business permit sends the shop back for approval.</div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="post" action="<?= e($action) ?>" class="form place-form" novalidate>
        <?= csrf_field() ?>
        <fieldset class="group" <?= $locked ? 'disabled' : '' ?>>
            <legend>Shop details</legend>
            <div class="field">
                <label for="name">Shop name</label>
                <input id="name" name="name" maxlength="150" required value="<?= e($v('name')) ?>" <?= $inv('name') ?>>
                <?= $err('name') ?>
            </div>
            <div class="field-row">
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required <?= $inv('category_id') ?>>
                        <option value="">Choose a category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= (string) $v('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('category_id') ?>
                </div>
                <div class="field">
                    <label for="setting">Setting</label>
                    <select id="setting" name="setting" <?= $inv('setting') ?>>
                        <option value="">Not specified</option>
                        <?php foreach (Place::SETTINGS as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= (string) $v('setting') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('setting') ?>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required <?= $inv('description') ?>><?= e($v('description')) ?></textarea>
                <?= $err('description') ?>
            </div>
            <div class="field-row">
                <div class="field field-grow">
                    <label for="address">Address</label>
                    <input id="address" name="address" maxlength="255" required value="<?= e($v('address')) ?>" <?= $inv('address') ?>>
                    <?= $err('address') ?>
                </div>
                <div class="field">
                    <label for="city">City</label>
                    <input id="city" name="city" maxlength="100" required value="<?= e($v('city', 'Angeles City')) ?>" <?= $inv('city') ?>>
                    <?= $err('city') ?>
                </div>
            </div>
        </fieldset>

        <fieldset class="group" <?= $locked ? 'disabled' : '' ?>>
            <legend>Prices, hours and payment</legend>
            <p class="hint">Leave the prices empty if you prefer not to show them; visitors will see "Budget not confirmed". Use 0 for free.</p>
            <div class="field-row">
                <div class="field">
                    <label for="budget_min">Lowest price (₱)</label>
                    <input id="budget_min" name="budget_min" type="number" min="0" step="0.01" value="<?= e($v('budget_min')) ?>" <?= $inv('budget_min') ?>>
                    <?= $err('budget_min') ?>
                </div>
                <div class="field">
                    <label for="budget_max">Highest price (₱)</label>
                    <input id="budget_max" name="budget_max" type="number" min="0" step="0.01" value="<?= e($v('budget_max')) ?>" <?= $inv('budget_max') ?>>
                    <?= $err('budget_max') ?>
                </div>
                <div class="field">
                    <label for="price_unit">Price is</label>
                    <select id="price_unit" name="price_unit" <?= $inv('price_unit') ?>>
                        <option value="">Choose a unit</option>
                        <?php foreach (Place::PRICE_UNITS as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= (string) $v('price_unit') === $key ? 'selected' : '' ?>><?= e(ucfirst($label)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('price_unit') ?>
                </div>
            </div>
            <div class="field-row">
                <label class="check"><input type="checkbox" name="budget_is_estimate" value="1" <?= (string) $v('budget_is_estimate') === '1' ? 'checked' : '' ?>> This is an estimate (shown with "Est.")</label>
                <div class="field field-grow">
                    <label for="budget_note">Budget note (optional)</label>
                    <input id="budget_note" name="budget_note" maxlength="255" value="<?= e($v('budget_note')) ?>" placeholder="e.g. Meal and a drink" <?= $inv('budget_note') ?>>
                    <?= $err('budget_note') ?>
                </div>
            </div>
            <div class="field">
                <label for="hours_note">Opening hours (optional)</label>
                <input id="hours_note" name="hours_note" maxlength="255" value="<?= e($v('hours_note')) ?>" placeholder="e.g. Mon–Sat 8:00 AM – 9:00 PM, closed Sunday" <?= $inv('hours_note') ?>>
                <?= $err('hours_note') ?>
            </div>
            <div class="field-row">
                <div class="field field-grow">
                    <label for="payment_options">Payment options (optional)</label>
                    <input id="payment_options" name="payment_options" maxlength="255" value="<?= e($v('payment_options')) ?>" placeholder="e.g. Cash, GCash, Maya, bank transfer" <?= $inv('payment_options') ?>>
                    <?= $err('payment_options') ?>
                </div>
                <div class="field field-grow">
                    <label for="payment_note">Payment note (optional)</label>
                    <input id="payment_note" name="payment_note" maxlength="255" value="<?= e($v('payment_note')) ?>" placeholder="e.g. Online payment for pick-up orders only" <?= $inv('payment_note') ?>>
                    <?= $err('payment_note') ?>
                </div>
            </div>
            <div class="field">
                <label for="source_url">Website or Facebook page (optional)</label>
                <input id="source_url" name="source_url" type="url" maxlength="255" value="<?= e($v('source_url')) ?>" placeholder="https://" <?= $inv('source_url') ?>>
                <?= $err('source_url') ?>
            </div>
        </fieldset>
        <?php if (!$locked): ?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit"><?= $p ? 'Save details' : 'Save and continue' ?></button>
                <a class="btn btn-secondary" href="<?= e(url('/my/shops')) ?>">Cancel</a>
            </div>
        <?php endif; ?>
    </form>

    <?php if ($p): ?>
        <?= View::partial('my/shops/media', ['p' => $p, 'documents' => $documents, 'photos' => $photos, 'menus' => $menus, 'menuItems' => $menuItems, 'locked' => $locked]) ?>

        <section class="panel danger-zone">
            <h2>Delete this shop</h2>
            <p class="muted">Removes the listing, its photos, menu, documents and reviews. This cannot be undone.</p>
            <form method="post" action="<?= e(url('/my/shops/' . $p['id'] . '/delete')) ?>" data-confirm="Delete &quot;<?= e($p['name']) ?>&quot; and everything in it? This cannot be undone.">
                <?= csrf_field() ?>
                <button class="btn btn-danger" type="submit">Delete shop</button>
            </form>
        </section>
    <?php endif; ?>
</div>
