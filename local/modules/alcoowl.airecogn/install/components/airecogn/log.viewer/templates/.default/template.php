<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}
?>
<div class="airecogn-log-viewer">
    <?php if (empty($arResult['RECORDS'])): ?>
        <p>Для активити этого контакта записей в логах пока нет.</p>
    <?php else: ?>
        <?php foreach ($arResult['RECORDS'] as $record): ?>
            <details style="margin-bottom:8px">
                <summary>
                    <?= htmlspecialcharsbx((string)($record['timestamp'] ?? '')) ?>
                    — <?= htmlspecialcharsbx((string)($record['channel'] ?? '')) ?>
                    — активити #<?= (int)($record['activity_id'] ?? 0) ?>
                    — <?= htmlspecialcharsbx((string)($record['message'] ?? $record['TYPE'] ?? $record['type'] ?? 'record')) ?>
                </summary>
                <pre style="white-space:pre-wrap"><?= htmlspecialcharsbx(json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
            </details>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
