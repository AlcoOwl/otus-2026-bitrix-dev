<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #5: Разработка собственного компонента");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2">
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <p>
            Необходимо создать собственный компонент Bitrix, который получает валюту из параметров компонента
            и выводит на странице текущий курс выбранной валюты.
        </p>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Требования</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Компонент должен иметь один параметр: выпадающий список выбора валюты.</li>
            <li>Список валют должен формироваться из справочника валют, доступного по адресу <code>/bitrix/admin/currencies.php</code>.</li>
            <li>Компонент должен передавать в шаблон текущий курс выбранной валюты из этого же справочника.</li>
            <li>Компонент необходимо разместить на странице <code>/otus/currencies.php</code>.</li>
        </ol>
    </div>
</details>

<h4 class="mb-2">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        В рамках задания создан компонент
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/tree/main/local/components/otus/currency_check">/local/components/otus/currency_check</a>.
        Описание компонента находится в файле <code>.description.php</code>, параметры выбора валюты описаны в
        <code>.parameters.php</code>, а основная логика получения курса вынесена в <code>component.php</code>.
    </p>

    <p>
        В параметрах компонента используется тип <code>LIST</code>. Значения списка собираются из модуля валют
        через <code>CCurrency::GetList()</code>, поэтому администратор может выбрать любую валюту из справочника
        <code>/bitrix/admin/currencies.php</code>.
    </p>

    <p>
        Шаблон компонента <code>templates/.default/template.php</code> получает подготовленные данные через
        <code>$arResult</code> и выводит код валюты, полное название, курс, курс за одну единицу и признак базовой валюты.
    </p>
</div>

<div class="mb-4">
    <h2><a href="/otus/currencies.php">Страница с демонстрацией функционала &rarr;</a></h2>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
