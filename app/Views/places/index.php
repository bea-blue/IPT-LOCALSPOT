<?php
use SpotCore\View;
/** @var App\Support\PlaceFilters $filters */
$count = count($places);
$totalPublished = array_sum(array_map(fn ($t) => (int) $t['place_count'], $tiles));
// Category shortcuts keep the other filters; empty categories are not shown.
$chipUrl = function (?int $categoryId) use ($filters): string {
    $q = array_filter([
        'q' => $filters->keyword,
        'category' => $categoryId,
        'max_budget' => $filters->maxBudget !== null ? (string) (float) $filters->maxBudget : null,
    ], fn ($v) => $v !== null && $v !== '');
    return url('/' . ($q ? '?' . http_build_query($q) : ''));
};
?>
<section class="intro">
    <div class="container">
        <p class="eyebrow">Angeles City, Pampanga</p>
        <h1>Discover your next local spot.</h1>
        <p class="intro-text">Explore cafés and food spots around Angeles City.</p>

        <form class="search" method="get" action="<?= e(url('/')) ?>" role="search" aria-label="Find places">
            <div class="field search-q">
                <label for="q">Search</label>
                <input id="q" type="search" name="q" maxlength="100" value="<?= e($filters->keyword) ?>" placeholder="Place name or address">
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= e($c['id']) ?>" <?= $filters->categoryId === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="max_budget">Max starting budget (₱)</label>
                <input id="max_budget" type="number" name="max_budget" min="0" max="100000" step="1" inputmode="numeric" value="<?= e($filters->maxBudget !== null ? (float) $filters->maxBudget : '') ?>" placeholder="Any" aria-describedby="budget-help">
            </div>
            <div class="search-actions filter-actions">
                <button class="btn btn-primary" type="submit">Find places</button>
                <?php if ($filters->isActive()): ?><a class="btn btn-secondary" href="<?= e(url('/')) ?>">Clear</a><?php endif; ?>
            </div>
            <p class="search-help" id="budget-help">A place matches when the low end of its budget fits yours. Budgets marked Est. are planning estimates per person, not quotes. Places without a budget figure are hidden while a budget is set.</p>
        </form>
    </div>
</section>

<div class="container">
    <?= View::partial('partials/flash') ?>
    <?php foreach ($filters->errors as $err): ?>
        <div class="flash flash-error" role="alert"><?= e($err) ?></div>
    <?php endforeach; ?>

    <nav class="chips" aria-label="Categories">
        <a class="chip" href="<?= e($chipUrl(null)) ?>" <?= $filters->categoryId === null ? 'aria-current="page"' : '' ?>>All <span class="chip-count"><?= $totalPublished ?></span></a>
        <?php foreach ($tiles as $t): if ((int) $t['place_count'] === 0) { continue; } ?>
            <a class="chip" href="<?= e($chipUrl((int) $t['id'])) ?>" <?= $filters->categoryId === (int) $t['id'] ? 'aria-current="page"' : '' ?>><?= e($t['name']) ?> <span class="chip-count"><?= (int) $t['place_count'] ?></span></a>
        <?php endforeach; ?>
    </nav>

    <section class="results" aria-labelledby="results-title">
        <div class="results-head">
            <h2 id="results-title"><?= $filters->isActive() ? 'Matching places' : 'All places' ?></h2>
            <p class="result-count" aria-live="polite"><?= $count ?> <?= $count === 1 ? 'place' : 'places' ?> found</p>
        </div>
        <?php if ($filters->maxBudget !== null): ?>
            <p class="results-note">Showing places whose budget starts at <?= e(peso($filters->maxBudget)) ?> or less (estimates included). Places whose budget is not confirmed yet are hidden.</p>
        <?php endif; ?>

        <?php if (!$places): ?>
            <div class="empty">
                <h3>No places match these filters</h3>
                <p><?= $filters->maxBudget !== null
                    ? 'Most listings do not have a confirmed price yet, so a budget filter hides them. Remove the budget or try another search.'
                    : 'Try another word or a different category.' ?></p>
                <a class="btn btn-secondary" href="<?= e(url('/')) ?>">Show all places</a>
            </div>
        <?php else: ?>
            <ul class="cards">
                <?php foreach ($places as $p): ?>
                    <?= View::partial('partials/place-card', ['p' => $p]) ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
