<?php use SpotCore\View; ?>
<div class="container auth">
    <section class="auth-panel panel">
        <h1>Log in</h1>
        <p class="muted">Log in to write and manage your reviews. Browsing places never needs an account.</p>
        <?= View::partial('partials/flash') ?>
        <form method="post" action="<?= e(url('/login')) ?>" class="form" novalidate>
            <?= csrf_field() ?>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" autocomplete="username" required value="<?= e(old('email')) ?>">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Log in</button>
        </form>
        <p class="auth-switch">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
        <a class="back" href="<?= e(url('/')) ?>">Back to Explore</a>
    </section>
</div>
