<?php

defined('B_PROLOG_INCLUDED') || die();
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="alcoowl.airecogn">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="2">
    <p><?= htmlspecialcharsbx(\Bitrix\Main\Localization\Loc::getMessage('AIRECOGN_INSTALL_COMPLETE')) ?></p>
    <input type="submit" value="<?= htmlspecialcharsbx(\Bitrix\Main\Localization\Loc::getMessage('MOD_BACK')) ?>">
</form>
