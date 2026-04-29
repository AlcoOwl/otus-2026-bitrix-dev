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
        В рамках задания создана таблица <code>link_form_result</code>, которая хранит индекс результатов заполнения
        CRM-форм: <code>CONTACT_ID</code>, <code>RESULT_ID</code>, <code>ACTIVITY_ID</code>, <code>FORM_ID</code>,
        <code>CREATED_AT</code>.<br>
        Для таблицы описана ORM-модель
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/LinkResultFormTable.php">/local/php_interface/src/Models/LinkResultFormTable.php</a>.<br>
        В модели настроены связи через <code>Reference</code> с контактом, результатом CRM-формы, активити и самой CRM-формой.
    </p>

    <p>
        Вместо учебных инфоблоков использованы стандартные ORM-сущности CRM. Сделано это было для того, чтобы наработки не пропали даром, а пошли в дело.
        Давно хотел такой инструмент, с помощью которого можно было бы в удобном виде смотреть все заполнения форм, которые привязаны к контакту.
        Помимо этого только для учебных целей через эту же кастомную модель связал контакты с формами, чтобы была связь many-to-many
        Для этого расширил стандартные модели:
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/ContactWebFormTable.php">ContactWebFormTable</a>
        и
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/CrmWebFormTable.php">CrmWebFormTable</a>.<br>
        В них показаны связи <code>OneToMany</code> и <code>ManyToMany</code> через таблицу <code>link_form_result</code>.
    </p>

    <p>
        На странице демонстрации выборка начинается от расширенной ORM-модели контакта. В URL параметре нужно передать <code>contact_id</code>.
        По нему выводятся все результаты заполнения CRM-форм, данные результата, активити в таймлайне контакта и заполненные поля формы.<br>
        Ниже через связь <code>ManyToMany</code> показывается список форм, которые заполнял контакт. Если передать
        <code>form_id</code> или нажать рядом на кнопочку "Контакты формы", дополнительно выводятся контакты, которые заполняли выбранную форму.
    </p>
</div>

<div class="mb-4">
    <h2><a href="/local/homeworks/homework4/webform_result_provider.php">Страница с демонстрацией функционала &rarr;</a></h2>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
