<?php

defined('B_PROLOG_INCLUDED') || die();
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <p><?= htmlspecialcharsbx(\Bitrix\Main\Localization\Loc::getMessage('AIRECOGN_UNINSTALL_COMPLETE')) ?></p>
    <input type="submit" value="<?= htmlspecialcharsbx(\Bitrix\Main\Localization\Loc::getMessage('MOD_BACK')) ?>">
</form>

