<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #7: Создание собственного типа поля для элементов инфоблока");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2">
    <summary><strong>Цель</strong></summary>
    <div class="mt-3">
        <ul>
            <li>подключение кастомных скриптов к проекту;</li>
            <li>написание кастомного свойства;</li>
            <li>работа с библиотекой Bitrix.UI.</li>
        </ul>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <p>
            Необходимо написать собственный тип свойства для элементов инфоблока и подключить для его работы
            кастомный JavaScript.
        </p>
        <p>
            <a
                href="https://rutube.ru/video/private/ab9ac83ff6c3fdde0c5792480ea177da/?p=neLtxUkhcrOe3WACsqJK3w"
                target="_blank"
                rel="noopener noreferrer"
            >Демонстрация ожидаемой работы</a>.
        </p>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Пошаговая инструкция</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Создать список «Бронирование» с полями «ФИО пациента», «Время записи» и «Процедура».</li>
            <li>
                Добавить кастомное свойство и вывести в нём все процедуры, которые связаны с выбранным врачом.
            </li>
            <li>По клику на процедуру открыть окно через <code>BX.PopupWindowManager</code>.</li>
            <li>После заполнения всех обязательных полей создавать бронирование.</li>
            <li>
                Дополнительно, на усмотрение студента, проверить занятость выбранного времени и вывести ошибку,
                если на него уже запланирована процедура.
            </li>
        </ol>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Требования</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Для каждого метода описывать PHPDoc.</li>
            <li>Использовать языковые фразы для текста в коде.</li>
            <li>
                Придерживаться
                <a
                    href="https://dev.1c-bitrix.ru/docs/php_recommendation.php"
                    target="_blank"
                    rel="noopener noreferrer"
                >рекомендаций Bitrix по оформлению PHP-кода</a>.
            </li>
        </ol>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Критерии оценки</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Создан список «Бронирование».</li>
            <li>Создано кастомное поле со списком процедур для врача.</li>
            <li>Реализовано выпадающее окно с информацией о пациенте.</li>
            <li>По результатам заполнения окна создаётся бронирование.</li>
        </ol>
        <p>Статус «Принято» ставится при выполнении всех критериев.</p>
    </div>
</details>

<details class="mb-4">
    <summary><strong>Формат сдачи</strong></summary>
    <div class="mt-3">
        <p>Ссылка на GitHub и доступы к учебному порталу: URL, логин и пароль администратора.</p>
    </div>
</details>

<h4 class="mb-2">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        Для выполнения задания созданы списки врачей, процедур и бронирований. В списке бронирований хранятся
        ФИО пациента, время записи, выбранная процедура и врач. Для процедур дополнительно указана длительность в часах.
    </p>

    <p>
        Пользовательский тип свойства реализован в классе
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Iblock/Property/BookingProperty.php">BookingProperty</a>
        и зарегистрирован через событие <code>OnIBlockPropertyBuildList</code> в
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/events.php">events.php</a>.
        В карточке врача свойство выводит связанные с ним процедуры.
    </p>

    <p>
        По клику на процедуру подключённое
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/tree/main/local/js/otus/booking">JS-расширение</a>
        открывает <code>BX.PopupWindowManager</code> с формой бронирования. Создание элемента выполняет
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/ajax/homework7/create_booking.php">AJAX-обработчик</a>.
        Перед сохранением он проверяет занятость врача с учётом времени начала и длительности процедур.
        Для записи доступны целые часы с 11:00 до 17:00.
    </p>
</div>

<div class="mb-4">
    <h2>
        <a href="/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=20&amp;type=lists&amp;lang=ru&amp;find_section_section=0">
            Открыть список врачей для демонстрации &rarr;
        </a>
    </h2>
    <p>
        <a href="/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=22&amp;type=lists&amp;lang=ru&amp;find_section_section=0">
            Открыть созданные бронирования
        </a>
    </p>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
