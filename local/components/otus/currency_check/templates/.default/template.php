<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>

<div class="otus-currency-rate">
    <?php if ($arResult['ERROR'] !== ''): ?>
        <div class="otus-currency-rate__error">
            <?= htmlspecialcharsbx($arResult['ERROR']) ?>
        </div>
    <?php else: ?>
        <dl class="otus-currency-rate__list">
            <div class="otus-currency-rate__row">
                <dt>Валюта</dt>
                <dd>
                    <?= htmlspecialcharsbx($arResult['CURRENCY']) ?>
                    <?php if ($arResult['FULL_NAME'] !== ''): ?>
                        <span><?= htmlspecialcharsbx($arResult['FULL_NAME']) ?></span>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="otus-currency-rate__row">
                <dt>Курс</dt>
                <dd>
                    <?= htmlspecialcharsbx($arResult['AMOUNT_CNT']) ?>
                    <?= htmlspecialcharsbx($arResult['CURRENCY']) ?>
                    =
                    <?= htmlspecialcharsbx(number_format((float)$arResult['AMOUNT'], 4, '.', ' ')) ?>
                    <?php if ($arResult['BASE'] === 'Y'): ?>
                        <span>базовая валюта</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="otus-currency-rate__row">
                <dt>Курс за 1 единицу</dt>
                <dd><?= htmlspecialcharsbx(number_format((float)$arResult['UNIT_RATE'], 4, '.', ' ')) ?></dd>
            </div>
        </dl>
    <?php endif; ?>
</div>
