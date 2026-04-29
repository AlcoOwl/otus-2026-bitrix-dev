<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #4: Создание своих таблиц БД и написание модели данных к ним");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2">
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <ol>
            <li>
                Создать таблицу в базе данных со следующими типами данных: числовой, строковый, связываемый;
            </li>
            <li>
                Создать 2-3 инфоблока, описать для них модели данных и связать позиции по первичному ключу с созданной таблицей в БД;
            </li>
            <li>
                Создать тестовую страницу для выборки и вывода данных в виде списка. При выборке из таблицы БД, выбрать
                также свойства из элементов инфоблоков.
            </li>
            <li>
                Использовать в запросах registerRuntimeField;
            </li>
        </ol>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Требования</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Для каждого метода описывать PHPDoc (alt+enter на названии метода в PHPStorm -> Generate PHPDoc).</li>
            <li>Использовать языковые фразы для написания текста в коде.</li>
            <li>
                При разработке стоит придерживаться базовых рекомендаций оформления кода от битрикс
                <a href="https://dev.1c-bitrix.ru/docs/articles/develop/277171/">https://dev.1c-bitrix.ru/docs/articles
                    /develop/277171/</a>.
            </li>
        </ol>
    </div>
</details>

<details class="mb-4">
    <summary><strong>Критерии оценки</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Созданы модели данных для таблиц и инфоблоков.
            </li>
            <li>Модель БД связана с моделями инфоблоков.
            </li>
            <li>Нет ошибок при выводе данных.
            </li>
        </ol>
    </div>
</details>

<h4 class="mb-2">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        В рамках задания создана таблица <code>link_form_result</code>, которая хранит индексы результатов заполнения
        CRM-форм: <code>CONTACT_ID</code>, <code>RESULT_ID</code>, <code>ACTIVITY_ID</code>, <code>FORM_ID</code>,
        <code>CREATED_AT</code>.<br>
        Для таблицы описана ORM-модель
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/LinkResultFormTable.php">/local/php_interface/src/Models/LinkResultFormTable.php</a>.<br>
        В модели настроены связи через <code>Reference</code> с контактом, результатом CRM-формы, активити и самой CRM-формой.
    </p>

    <p>
        Вместо 2-3 тестовых инфоблоков использованы стандартные ORM-сущности CRM. Сделано это было для того, чтобы наработки не пропали даром, а пошли в дело.
        Давно хотел такой инструмент, с помощью которого можно было бы в удобном виде смотреть все заполнения форм, которые привязаны к контакту.
        Помимо этого только для учебных целей через эту же кастомную таблицу связал контакты с формами, чтобы была связь many-to-many
        Для этого расширил стандартные модели:
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/ContactWebFormTable.php">ContactWebFormTable</a>
        и
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/CrmWebFormTable.php">CrmWebFormTable</a>.<br>
        В них показаны связи <code>OneToMany</code> и <code>ManyToMany</code> через таблицу <code>link_form_result</code>.
    </p>

    <p>
        В процессе работы также нашелся нюанс в стандартной ORM-модели
        <code>Bitrix\Crm\WebForm\Internals\ResultTable</code>: поле <code>DATE_INSERT</code> в базе данных имеет тип
        <code>datetime</code>, но в ORM-карте описано как <code>date</code>. Из-за этого при выборке через стандартную
        модель терялось время и дата приходила с <code>00:00:00</code>.<br>
        Чтобы не менять ядро Bitrix, была создана расширенная модель
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/CrmWebFormResultTable.php">CrmWebFormResultTable</a>,
        которая использует ту же таблицу <code>b_crm_webform_result</code>, но описывает <code>DATE_INSERT</code> как
        <code>DatetimeField</code>.
    </p>

    <p>
        На странице демонстрации выборка начинается от расширенной ORM-модели контакта. В URL параметре нужно передать <code>contact_id</code>.
        По нему выводятся все результаты заполнения CRM-форм, данные результата, активити в таймлайне контакта и заполненные поля формы.<br>
        Ниже через связь <code>ManyToMany</code> показывается список форм, которые заполнял контакт. Если передать
        <code>form_id</code> или нажать рядом на кнопочку "Контакты формы", дополнительно выводятся контакты, которые заполняли выбранную форму.
    </p>

    <p>
        Для автоматического наполнения таблицы <code>link_form_result</code> добавлен обработчик события сохранения
        записи в кастомную линковую таблицу. Регистрация обработчика находится в
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/events.php">/local/php_interface/events.php</a>,
        а сама логика вынесена в
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/WebFormResultSync.php">WebFormResultSync</a>.<br>
        Так как активити в таймлайне может появиться не одновременно с результатом формы, обработчик ставит агент с задержкой.
        Агент проверяет наличие активити несколько раз, и если оно так и не найдено, записывает в <code>ACTIVITY_ID</code>
        значение <code>-1</code> как технический признак незавершенной связки.
    </p>
</div>

<div class="mb-4">
    <h2><a href="/local/homeworks/homework4/webform_result_provider.php?contact_id=8">Страница с демонстрацией функционала &rarr;</a></h2>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
