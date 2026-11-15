<?php use SpotCore\View; ?>
<fieldset class="rating-field" <?= error_for('rating') ? 'aria-describedby="rating-error"' : '' ?>>
    <legend>Your rating</legend>
    <div class="rating-options">
        <?php foreach ([5 => 'Excellent', 4 => 'Good', 3 => 'Okay', 2 => 'Poor', 1 => 'Bad'] as $n => $label): ?>
            <label class="rating-option">
                <input type="radio" name="rating" value="<?= $n ?>" <?= (string) $rating === (string) $n ? 'checked' : '' ?> required>
                <span><span aria-hidden="true"><?= str_repeat('★', $n) ?></span> <?= $n ?> <?= e($label) ?></span>
            </label>
        <?php endforeach; ?>
    </div>
    <?= View::partial('partials/field_error', ['field' => 'rating']) ?>
</fieldset>
<div class="field">
    <label for="comment">Your review</label>
    <textarea id="comment" name="comment" rows="4" maxlength="1000" required <?= error_for('comment') ? 'aria-invalid="true" aria-describedby="comment-error"' : '' ?>><?= e($comment) ?></textarea>
    <?= View::partial('partials/field_error', ['field' => 'comment']) ?>
</div>
