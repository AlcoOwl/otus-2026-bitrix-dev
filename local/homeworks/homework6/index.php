<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #6: Разработка модуля для расширения стандартного модуля CRM");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2" open>
    <summary><strong>Цель</strong></summary>
    <div class="mt-3">
        <ul>
            <li>создать собственный модуль;</li>
            <li>научиться работать с внешними таблицами;</li>
            <li>сделать собственный компонент как элемент модуля;</li>
            <li>научиться работать со стандартными GRID-компонентами.</li>
        </ul>
    </div>
</details>

<details class="mb-2" open>
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <p>
            Необходимо написать модуль, который добавит вкладку в карточку CRM
            и выведет в ней данные из произвольной таблицы базы данных в виде грида.
        </p>
    </div>
</details>

<details class="mb-4" open>
    <summary><strong>Пошаговая инструкция</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Добавить таблицу из дампа базы данных или создать собственную аналогичную таблицу.</li>
            <li>Написать компонент, который будет работать с этой таблицей.</li>
            <li>
                Создать модуль, который обработает событие
                <code>onEntityDetailsTabsInitialized</code> и вернёт вкладку с созданным компонентом.
            </li>
            <li>Вывести данные таблицы с помощью стандартного GRID-компонента.</li>
        </ol>
    </div>
</details>

<h4 class="mb-2">Статус выполнения</h4>
<p class="text-warning-emphasis">Работа в процессе.</p>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
