<?php
use App\Support\PlaceFormat;
use SpotCore\Auth;
use SpotCore\View;
$me = Auth::id();
$myReview = null;
foreach ($reviews as $r) { if ($me !== null && (int) ($r['user_id'] ?? 0) === $me) { $myReview = $r; break; } }
$mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($place['name'] . ', ' . $place['address'] . ', ' . $place['city']);
$budget = PlaceFormat::budget($place);
$setting = PlaceFormat::setting($place['setting'] ?? null);
$checked = PlaceFormat::checkedOn($place['checked_on'] ?? null);
$host = PlaceFormat::sourceHost($place['source_url'] ?? null);
$cover = $photos[0] ?? null;
// The slideshow starts with the second photo (the first is already the backdrop), then cycles through all.
$slides = count($photos) > 1 ? array_merge(array_slice($photos, 1), array_slice($photos, 0, 1)) : $photos;
$stars = fn (float $r) => str_repeat('★', (int) round($r)) . str_repeat('☆', 5 - (int) round($r));
$credit = fn (array $ph) => !empty($ph['source_url'])
    ? '<a href="' . e($ph['source_url']) . '" rel="noopener" target="_blank">Photo: ' . e($ph['credit']) . '</a>'
    : 'Photo: ' . e($ph['credit']);
$isOwnerHere = $me !== null && (int) ($place['owner_id'] ?? 0) === $me;
$isDemoOwner = ($place['owner_name'] ?? '') === 'Demo Owner (test account)';
$reasons = \App\Models\ReviewReport::REASONS;
?>
<?php if (!empty($preview)): ?>
    <div class="preview-bar" role="status">Preview only: this listing is <strong><?= e(strtolower(\App\Models\Place::STATUSES[$place['status']] ?? $place['status'])) ?></strong> and visitors cannot see it.</div>
<?php endif; ?>
<section class="place-hero<?= $cover ? ' has-photo' : '' ?>">
    <?php if ($cover): ?>
        <div class="place-hero-media" aria-hidden="true">
            <img src="<?= e(asset($cover['path'])) ?>" alt="" width="<?= e($cover['width']) ?>" height="<?= e($cover['height']) ?>" decoding="async">
        </div>
    <?php endif; ?>
    <div class="container place-hero-inner">
        <a class="back" href="<?= e(url('/')) ?>">Back to all places</a>
        <p class="card-cat"><?= e($place['category_name']) ?></p>
        <h1><?= e($place['name']) ?></h1>
        <p class="detail-location"><?= e($place['address']) ?>, <?= e($place['city']) ?></p>
        <?php if ($rating): ?>
            <p class="hero-rating"><span class="stars" aria-hidden="true"><?= e($stars($rating['avg'])) ?></span>
                <?= e(number_format($rating['avg'], 1)) ?> out of 5 from <?= (int) $rating['count'] ?> <?= $rating['count'] === 1 ? 'review' : 'reviews' ?></p>
        <?php endif; ?>
        <?php if ($menus): ?>
            <p class="hero-actions"><a class="btn btn-primary" href="#menu">View menu</a></p>
        <?php endif; ?>
        <?php if ($cover): ?>
            <p class="hero-credit">Background: <?= $credit($cover) ?></p>
        <?php endif; ?>
    </div>
</section>

<div class="container detail">
    <div class="detail-grid">
        <div class="detail-main">
            <section class="detail-about" aria-labelledby="about-title">
                <h2 id="about-title">About</h2>
                <p class="detail-desc"><?= e($place['description']) ?></p>
            </section>

            <?php if ($slides): ?>
                <section class="carousel" data-carousel data-interval="5000" aria-roledescription="carousel" aria-labelledby="photos-title">
                    <h2 id="photos-title">Photos</h2>
                    <div class="carousel-viewport">
                        <ul class="carousel-track" aria-live="off">
                            <?php foreach ($slides as $i => $ph): ?>
                                <li class="carousel-slide<?= $i === 0 ? ' is-active' : '' ?>" role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> of <?= count($slides) ?>">
                                    <figure class="gallery-item">
                                        <?= View::partial('partials/photo', ['photo' => $ph, 'name' => $place['name'], 'lazy' => $i > 0]) ?>
                                        <figcaption><?= e($ph['alt']) ?>. <?= $credit($ph) ?></figcaption>
                                    </figure>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php if (count($slides) > 1): ?>
                        <div class="carousel-bar" hidden>
                            <div class="carousel-thumbs">
                                <?php foreach ($slides as $i => $ph): ?>
                                    <button type="button" class="carousel-thumb" data-slide="<?= $i ?>" aria-label="Show photo <?= $i + 1 ?>: <?= e($ph['alt']) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
                                        <img src="<?= e(asset($ph['path'])) ?>" alt="" width="<?= e($ph['width']) ?>" height="<?= e($ph['height']) ?>" loading="lazy">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-buttons">
                                <button type="button" class="btn btn-secondary carousel-prev" aria-label="Previous photo">&lsaquo;</button>
                                <button type="button" class="btn btn-secondary carousel-toggle">Pause</button>
                                <button type="button" class="btn btn-secondary carousel-next" aria-label="Next photo">&rsaquo;</button>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>
            <?php else: ?>
                <figure class="gallery-item gallery-single">
                    <?= View::partial('partials/photo', ['photo' => null, 'name' => $place['name']]) ?>
                </figure>
            <?php endif; ?>

            <section class="reviews" id="reviews" aria-labelledby="reviews-title">
                <div class="reviews-head">
                    <h2 id="reviews-title">Reviews</h2>
                    <?php if ($rating): ?>
                        <p class="reviews-summary"><span class="reviews-avg"><?= e(number_format($rating['avg'], 1)) ?></span>
                            <span class="stars" aria-hidden="true"><?= e($stars($rating['avg'])) ?></span>
                            <span class="muted"><?= (int) $rating['count'] ?> <?= $rating['count'] === 1 ? 'review' : 'reviews' ?></span></p>
                    <?php endif; ?>
                </div>
                <p class="note">Reviews are users' own opinions and are not checked by LocalSpot.</p>
                <?= View::partial('partials/flash') ?>

                <?php if (!$reviews): ?>
                    <p class="reviews-empty">No reviews yet. Been here? Share how your visit went.</p>
                <?php else: ?>
                    <ul class="review-list">
                        <?php foreach ($reviews as $r): $mine = $me !== null && (int) ($r['user_id'] ?? 0) === $me; ?>
                            <li class="review<?= $mine ? ' review-mine' : '' ?>" id="review-<?= (int) $r['id'] ?>">
                                <div class="review-head">
                                    <strong><?= e($r['author_name']) ?></strong><?php if ($mine): ?> <span class="review-you">(you)</span><?php endif; ?>
                                    <span class="stars" aria-hidden="true"><?= e($stars((float) $r['rating'])) ?></span>
                                    <span class="sr-only">Rated <?= (int) $r['rating'] ?> out of 5</span>
                                    <time class="muted" datetime="<?= e(date('Y-m-d', strtotime($r['created_at']))) ?>"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></time>
                                </div>
                                <p><?= e($r['comment']) ?></p>
                                <?php if ($mine && (int) $r['is_hidden'] === 1): ?>
                                    <p class="note">Hidden by LocalSpot staff after a report. Only you can see it.</p>
                                <?php endif; ?>
                                <?php if (!$mine && $me !== null && empty($preview)): ?>
                                    <details class="report">
                                        <summary>Report</summary>
                                        <form method="post" action="<?= e(url('/reviews/' . $r['id'] . '/report')) ?>" class="report-form">
                                            <?= csrf_field() ?>
                                            <label for="reason-<?= (int) $r['id'] ?>">Why are you reporting this review?</label>
                                            <select id="reason-<?= (int) $r['id'] ?>" name="reason" required>
                                                <?php foreach ($reasons as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?>
                                            </select>
                                            <input name="note" maxlength="500" placeholder="Details (optional)" aria-label="Details (optional)">
                                            <button class="btn btn-secondary btn-small" type="submit">Send report</button>
                                        </form>
                                    </details>
                                <?php endif; ?>
                                <?php if ($mine): ?>
                                    <div class="review-actions">
                                        <a class="btn btn-secondary btn-small" href="<?= e(url('/reviews/' . $r['id'] . '/edit')) ?>">Edit</a>
                                        <form method="post" action="<?= e(url('/reviews/' . $r['id'] . '/delete')) ?>" data-confirm="Delete your review? This cannot be undone.">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-danger btn-small" type="submit">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if ($me === null): ?>
                    <div class="panel review-login">
                        <h3>Write a review</h3>
                        <p>Log in or create a free account to share how your visit went. You can edit or delete your review later.</p>
                        <p class="review-login-actions">
                            <a class="btn btn-primary" href="<?= e(url('/login')) ?>">Log in</a>
                            <a class="btn btn-secondary" href="<?= e(url('/register')) ?>">Create an account</a>
                        </p>
                    </div>
                <?php elseif ($isOwnerHere): ?>
                    <p class="note">This is your shop, so you cannot review it. You can report a review that is spam, abusive or off-topic.</p>
                <?php elseif (!empty($preview)): ?>
                <?php elseif ($myReview): ?>
                    <p class="note">You already reviewed this place. Use Edit on your review to change it.</p>
                <?php else: ?>
                    <form class="form panel review-form" id="write-review" method="post" action="<?= e(url('/places/' . $place['id'] . '/reviews')) ?>" novalidate>
                        <h3>Write a review</h3>
                        <p class="muted">Posting as <strong><?= e(Auth::user()['name']) ?></strong></p>
                        <?= csrf_field() ?>
                        <?= View::partial('partials/review-fields', ['rating' => old('rating'), 'comment' => old('comment')]) ?>
                        <div class="hp-field" aria-hidden="true">
                            <label for="website">Leave this empty</label>
                            <input id="website" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <p class="hint">Your name and review are shown publicly right away. Keep it about your visit.</p>
                        <div><button class="btn btn-primary" type="submit">Post review</button></div>
                    </form>
                <?php endif; ?>
            </section>
        </div>

        <aside class="detail-side">
            <section class="panel plan" aria-labelledby="visit-title">
                <h2 id="visit-title">Visit details</h2>
                <dl class="facts">
                    <div>
                        <dt>Budget</dt>
                        <dd><?php if ($budget): ?><?php if ($budget['estimate']): ?><abbr class="est" title="Estimated budget">Est.</abbr> <?php endif; ?><?= e($budget['amount']) ?><?php if ($budget['unit']): ?> <span class="muted"><?= e($budget['unit']) ?></span><?php endif; ?><?php else: ?><span class="muted"><?= e(PlaceFormat::noBudgetLabel($place)) ?></span><?php endif; ?>
                            <?php if (!empty($place['budget_note'])): ?><span class="budget-note"><?= e($place['budget_note']) ?></span><?php endif; ?></dd>
                    </div>
                    <div>
                        <dt>Setting</dt>
                        <dd><?= $setting ? e($setting) : '<span class="muted">Not confirmed</span>' ?></dd>
                    </div>
                    <div>
                        <dt>Hours</dt>
                        <dd><?= $place['hours_note'] ? e($place['hours_note']) : '<span class="muted">Not confirmed</span>' ?></dd>
                    </div>
                    <div>
                        <dt>Payment</dt>
                        <dd><?= !empty($place['payment_options']) ? e($place['payment_options']) : '<span class="muted">Not confirmed</span>' ?>
                            <?php if (!empty($place['payment_note'])): ?><span class="budget-note"><?= e($place['payment_note']) ?></span><?php endif; ?></dd>
                    </div>
                    <div>
                        <dt>Address</dt>
                        <dd><?= e($place['address']) ?>, <?= e($place['city']) ?></dd>
                    </div>
                </dl>
                <p><a class="btn btn-secondary btn-block" href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener">Open in Google Maps</a></p>
                <?php if ($isDemoOwner): ?>
                    <p class="note">Opening hours and current operation are not confirmed by LocalSpot. Check with the venue before you go.</p>
                    <p class="source">Demo listing from public sources, managed by a test owner account.
                        <?php if ($host): ?>Source: <a href="<?= e($place['source_url']) ?>" rel="noopener" target="_blank"><?= e($host) ?></a><?php endif; ?>
                        <?php if ($checked): ?><span class="source-date">Last checked <?= e($checked) ?></span><?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="note">Details are provided by the shop owner. LocalSpot staff approved the listing after checking the business permit.</p>
                    <p class="source">
                        <?php if ($host): ?>Website: <a href="<?= e($place['source_url']) ?>" rel="noopener" target="_blank"><?= e($host) ?></a><?php endif; ?>
                        <?php if ($checked): ?><span class="source-date">Updated by the owner <?= e($checked) ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>
            </section>

            <?php if ($menus || $menuItems):
                $menuPhotos = array_values(array_filter($menus, fn ($m) => $m['kind'] === 'image' && $m['path']));
                $menuLinks = array_values(array_filter($menus, fn ($m) => $m['kind'] === 'link' && $m['url']));
            ?>
                <section class="panel menu-panel" id="menu" aria-labelledby="menu-title">
                    <h2 id="menu-title">Menu</h2>
                    <?php if ($menuItems):
                        $sections = [];
                        foreach ($menuItems as $it) { $sections[$it['section']][] = $it; }
                        $first = array_key_first($sections);
                        $restCount = count($menuItems) - count($sections[$first]);
                        $itemList = function (array $items): string {
                            $html = '<ul class="menu-items-list">';
                            foreach ($items as $it) {
                                $html .= '<li><span class="menu-item-name">' . e($it['name']) . '</span><span class="menu-item-dots" aria-hidden="true"></span>'
                                    . '<span class="menu-item-price">' . ($it['price'] !== null ? e(peso($it['price'])) : '<span class="muted">Price unknown</span>') . '</span></li>';
                            }
                            return $html . '</ul>';
                        };
                    ?>
                        <div class="menu-items">
                            <h3 class="menu-section-title"><?= e($first) ?></h3>
                            <?= $itemList($sections[$first]) ?>
                            <?php if ($restCount > 0): ?>
                                <details class="menu-more">
                                    <summary><span class="more-label">See more (<?= $restCount ?> items)</span><span class="less-label">See less</span></summary>
                                    <?php foreach ($sections as $title => $items): if ($title === $first) { continue; } ?>
                                        <h3 class="menu-section-title"><?= e($title) ?></h3>
                                        <?= $itemList($items) ?>
                                    <?php endforeach; ?>
                                </details>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($menuPhotos as $m): ?>
                        <figure class="menu-photo">
                            <a class="menu-photo-link" href="<?= e(asset($m['path'])) ?>" target="_blank" rel="noopener" data-lightbox
                               data-caption="<?= e($m['label']) ?>">
                                <img src="<?= e(asset($m['path'])) ?>" alt="<?= e($m['label']) ?> for <?= e($place['name']) ?>" width="<?= e($m['width']) ?>" height="<?= e($m['height']) ?>" loading="lazy">
                                <span class="menu-photo-hint">Click to enlarge</span>
                            </a>
                            <figcaption>
                                <?php if ($m['credit'] || $m['source_url']): ?><span class="note">Photo: <?php if ($m['source_url']): ?><a href="<?= e($m['source_url']) ?>" rel="noopener" target="_blank"><?= e($m['credit'] ?: PlaceFormat::sourceHost($m['source_url'])) ?></a><?php else: ?><?= e($m['credit']) ?><?php endif; ?></span><?php endif; ?>
                            </figcaption>
                        </figure>
                    <?php endforeach; ?>
                    <?php if ($menuLinks): ?>
                        <ul class="menu-links">
                            <?php foreach ($menuLinks as $m): ?>
                                <li>
                                    <a class="btn btn-secondary btn-block" href="<?= e($m['url']) ?>" target="_blank" rel="noopener"><?= e($m['label']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?= View::partial('partials/weather', ['weather' => $weather]) ?>
        </aside>
    </div>
</div>

<dialog class="lightbox" aria-label="Menu photo">
    <div class="lightbox-bar">
        <p class="lightbox-caption"></p>
        <a class="btn btn-secondary lightbox-open" href="#" target="_blank" rel="noopener">Open full size</a>
        <button type="button" class="btn btn-primary lightbox-close">Close</button>
    </div>
    <div class="lightbox-body"><img alt=""></div>
</dialog>
