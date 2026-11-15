<?php
use App\Support\Roles;
$labels = [
    'view_submissions' => 'See submitted shops', 'view_documents' => 'Open proof (permit, shop photo)',
    'add_check_note' => 'Add a check note', 'decide_submission' => 'Approve or reject a shop (with a reason)',
    'suspend_place' => 'Suspend or reinstate a shop (with a reason)', 'view_reports' => 'See review reports',
    'resolve_reports' => 'Hide a reported review or dismiss the report', 'manage_staff' => 'Add, change or remove staff (managers: not Developers)',
];
?>
<div class="table-wrap">
    <table class="table roles-table">
        <caption class="sr-only">Staff permissions by role</caption>
        <thead><tr><th scope="col">Action</th><?php foreach (Roles::STAFF as $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php foreach (Roles::matrix() as $ability => $roles): ?>
            <tr>
                <th scope="row"><?= e($labels[$ability] ?? $ability) ?></th>
                <?php foreach (array_keys(Roles::STAFF) as $r): ?>
                    <td class="cell-center"><?= in_array($r, $roles, true) ? '<span class="yes">Yes</span>' : '<span class="no">No</span>' ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
