<?php use SpotCore\View; ?>
<div class="container auth">
    <section class="auth-panel panel auth-panel-wide">
        <h1>Edit your review</h1>
        <p class="muted">For <?= e($place['name'] ?? 'this place') ?></p>
        <?= View::partial('partials/flash') ?>
        <form method="post" action="<?= e(url('/reviews/' . $review['id'] . '/update')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <?= View::partial('partials/review-fields', [
                'rating'  => old('rating', (string) $review['rating']),
                'comment' => old('comment', $review['comment']),
            ]) ?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save changes</button>
                <a class="btn btn-secondary" href="<?= e(url('/places/' . $review['place_id'] . '#reviews')) ?>">Cancel</a>
            </div>
        </form>
    </section>
</div>
