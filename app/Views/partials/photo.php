<?php
/**
 * One venue photo, shown whole (never cropped or stretched, so source
 * watermarks stay visible) inside a 3:2 frame. A blurred copy of the same
 * image fills the empty space; it is decorative and hidden from screen readers.
 * @var array|null $photo  row from place_images
 * @var string $name       place name, for the placeholder label
 * @var bool $lazy
 */
$lazy = $lazy ?? true;
?>
<?php if ($photo): $src = asset($photo['path']); ?>
    <div class="photo-frame">
        <img class="photo-fill" src="<?= e($src) ?>" alt="" aria-hidden="true" width="<?= e($photo['width']) ?>" height="<?= e($photo['height']) ?>"<?= $lazy ? ' loading="lazy"' : '' ?> decoding="async">
        <img class="photo-main" src="<?= e($src) ?>" alt="<?= e($photo['alt']) ?>" width="<?= e($photo['width']) ?>" height="<?= e($photo['height']) ?>"<?= $lazy ? ' loading="lazy"' : '' ?> decoding="async">
    </div>
<?php else: ?>
    <div class="photo-frame photo-placeholder" role="img" aria-label="No photo yet for <?= e($name) ?>"><span>Photo coming soon</span></div>
<?php endif; ?>
