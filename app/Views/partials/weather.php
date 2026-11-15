<?php /** @var array $weather */ $d = $weather['data']; ?>
<section class="panel weather weather-<?= e($weather['state']) ?>" aria-labelledby="weather-title">
    <h2 id="weather-title"><?= e($weather['label']) ?></h2>
    <?php if ($weather['state'] === 'unavailable'): ?>
        <p class="weather-msg"><?= e($weather['message']) ?></p>
        <p class="weather-help">The place details still apply. Check again in a few minutes.</p>
    <?php else: ?>
        <?php if ($weather['state'] === 'stale'): ?>
            <p class="weather-stale">Older reading: the weather service could not be reached just now.</p>
        <?php endif; ?>
        <p class="weather-main">
            <span class="weather-temp"><?= e(rtrim(rtrim(number_format($d['temperature'], 1), '0'), '.')) ?><?= e($d['temperature_unit']) ?></span>
            <span class="weather-cond"><?= e($d['condition']) ?></span>
        </p>
        <dl class="weather-facts">
            <div><dt>Humidity</dt><dd><?= e((int) $d['humidity']) ?><?= e($d['humidity_unit']) ?></dd></div>
            <div><dt>Data time</dt><dd><?= e(date('M j, g:i A', strtotime($d['time']))) ?></dd></div>
            <div><dt>Fetched</dt><dd><?= e(date('M j, g:i A', (int) $weather['fetched_at'])) ?></dd></div>
        </dl>
    <?php endif; ?>
    <p class="weather-note">Area weather for the whole city, not measured at this venue. Model data, not an official advisory.
        <a href="https://open-meteo.com/" rel="noopener" target="_blank">Weather data by Open-Meteo.com</a>
        (<a href="https://creativecommons.org/licenses/by/4.0/" rel="noopener" target="_blank">CC BY 4.0</a>)</p>
</section>
