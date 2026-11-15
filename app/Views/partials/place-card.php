<?php
use App\Support\PlaceFormat;
use SpotCore\View;
/** @var array $p  one place row (with category_name) */
$budget = PlaceFormat::budget($p);
$setting = PlaceFormat::setting($p['setting'] ?? null);
$titleId = 'place-' . (int) $p['id'];
?>
<li class="card">
    <article aria-labelledby="<?= e($titleId) ?>">
        <div class="card-media">
            <?= View::partial('partials/photo', ['photo' => $p['cover'] ?? null, 'name' => $p['name']]) ?>
        </div>
        <div class="card-body">
            <p class="card-cat"><?= e($p['category_name']) ?></p>
            <h3 class="card-title" id="<?= e($titleId) ?>"><a href="<?= e(url('/places/' . $p['id'])) ?>"><?= e($p['name']) ?></a></h3>
            <p class="card-location"><?= e($p['address']) ?>, <?= e($p['city']) ?></p>
            <p class="card-desc"><?= e($p['description']) ?></p>
            <?php if (!empty($p['rating'])): ?>
                <p class="card-rating"><span class="stars" aria-hidden="true">★</span> <?= e(number_format($p['rating']['avg'], 1)) ?> <span class="muted">(<?= (int) $p['rating']['count'] ?> <?= $p['rating']['count'] === 1 ? 'review' : 'reviews' ?>)</span><span class="sr-only"> average rating out of 5</span></p>
            <?php endif; ?>
            <div class="card-foot">
                <?php if ($budget): ?>
                    <p class="price"><strong><?php if ($budget['estimate']): ?><abbr class="est" title="Estimated budget">Est.</abbr> <?php endif; ?><?= e($budget['amount']) ?></strong><?php if ($budget['unit']): ?><span class="price-unit"><?= e($budget['unit']) ?></span><?php endif; ?></p>
                <?php else: ?>
                    <p class="price price-unknown"><?= e(PlaceFormat::noBudgetLabel($p)) ?></p>
                <?php endif; ?>
                <?php if ($setting || !empty($p['has_menu'])): ?>
                    <div class="card-extra">
                        <?php if ($setting): ?><p class="card-setting"><?= e($setting) ?></p><?php endif; ?>
                        <?php if (!empty($p['has_menu'])): ?><a class="card-menu" href="<?= e(url('/places/' . $p['id'] . '#menu')) ?>">View menu<span class="sr-only"> for <?= e($p['name']) ?></span></a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </article>
</li>
