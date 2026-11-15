<?php use App\Support\Roles; use SpotCore\View; ?>
<div class="page-head">
    <div>
        <h1>Staff</h1>
        <p class="muted">Developers and managers see this page. Add someone by the email they signed up with. Managers cannot create, change or remove a Developer. Nobody can change their own role, and LocalSpot always keeps at least one developer.</p>
    </div>
</div>
<section class="panel">
    <h2>Add staff or change a role</h2>
    <form method="post" action="<?= e(url('/admin/staff')) ?>" class="form upload-form">
        <?= csrf_field() ?>
        <div class="field field-grow"><label for="staff-email">Account email</label><input id="staff-email" type="email" name="email" required value="<?= e(old('email')) ?>"></div>
        <div class="field"><label for="staff-role">Role</label>
            <select id="staff-role" name="role" required>
                <?php foreach (Roles::STAFF as $key => $label): if (!Roles::canManage($actor, null, $key)) continue; ?><option value="<?= e($key) ?>" <?= old('role') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
            </select></div>
        <button class="btn btn-primary" type="submit">Save role</button>
    </form>
</section>
<div class="table-wrap">
    <table class="table">
        <caption class="sr-only">Staff accounts</caption>
        <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        <?php foreach ($staff as $s): ?>
            <tr>
                <td><?= e($s['name']) ?><?= (int) $s['id'] === $me ? ' <span class="muted">(you)</span>' : '' ?></td>
                <td><?= e($s['email']) ?></td>
                <td><?= e(Roles::label($s['role'])) ?></td>
                <td><?php if ((int) $s['id'] !== $me && Roles::canManage($actor, $s['role'], 'member')): ?>
                    <form method="post" action="<?= e(url('/admin/staff/' . $s['id'] . '/remove')) ?>" data-confirm="Remove <?= e($s['name']) ?> from staff? They keep their account as a member.">
                        <?= csrf_field() ?><button class="link-button danger" type="submit">Remove from staff</button>
                    </form>
                <?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<section class="panel admin-rights">
    <h2>Who can do what</h2>
    <?= View::partial('staff/roles-table') ?>
</section>
