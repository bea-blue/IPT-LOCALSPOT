<?php use SpotCore\View; ?>
<div class="container auth">
    <section class="auth-panel panel">
        <h1>Create an account</h1>
        <p class="muted">A free account lets you write, edit and delete your own reviews.</p>
        <?= View::partial('partials/flash') ?>
        <form method="post" action="<?= e(url('/register')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="name">Name shown on your reviews</label>
                <input id="name" name="name" maxlength="60" autocomplete="nickname" required value="<?= e(old('name')) ?>" <?= error_for('name') ? 'aria-invalid="true" aria-describedby="name-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['field' => 'name']) ?>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" maxlength="191" autocomplete="email" required value="<?= e(old('email')) ?>" <?= error_for('email') ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['field' => 'email']) ?>
            </div>
            <div class="field">
                <label for="password">Password (at least 8 characters)</label>
                <input id="password" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required <?= error_for('password') ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['field' => 'password']) ?>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" maxlength="72" autocomplete="new-password" required <?= error_for('password_confirmation') ? 'aria-invalid="true" aria-describedby="password_confirmation-error"' : '' ?>>
                <?= View::partial('partials/field_error', ['field' => 'password_confirmation']) ?>
            </div>
            <div class="hp-field" aria-hidden="true">
                <label for="website">Leave this empty</label>
                <input id="website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <button class="btn btn-primary btn-block" type="submit">Create account</button>
        </form>
        <p class="auth-switch">Already have an account? <a href="<?= e(url('/login')) ?>">Log in</a></p>
        <a class="back" href="<?= e(url('/')) ?>">Back to Explore</a>
    </section>
</div>
