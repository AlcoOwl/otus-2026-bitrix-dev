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

<h4 class="mb-2">Реализация</h4>
<div class="mb-4">
    <p>
        Для задания разрабатывается устанавливаемый модуль <code>airecogn</code>, который обрабатывает
        внутреннее событие завершения звонка <code>voximplant:onCallEnd</code> и входящий результат
        AI-распознавания. Модуль создаёт таблицу
        <code>b_airecogn_result</code> и хранит в ней ID CRM-активити, статус
        <code>pending</code>, <code>success</code> или <code>erorr</code>, а также краткое содержание звонка.
    </p>
    <p>
        Обработчик события <code>onEntityDetailsTabsInitialized</code> добавляет в карточку контакта
        вкладку со стандартным <code>main.ui.grid</code> и отдельную административную вкладку просмотра логов.
        Связь результата с контактом определяется через привязки CRM-активити.
    </p>
    <ul>
        <li><code>/local/modules/airecogn</code> — исходный код модуля;</li>
        <li><code>Airecogn\Integration\VoximplantEventHandler</code> — обработчик завершения звонка;</li>
        <li><code>/local/handler/airecogn/inbound/index.php</code> — устанавливаемая точка результата распознавания.</li>
    </ul>
</div>

<h4 class="mb-2">Статус выполнения</h4>
<p class="text-warning-emphasis">Работа в процессе.</p>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
