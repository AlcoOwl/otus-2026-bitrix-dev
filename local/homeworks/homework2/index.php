<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-3">
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <ol>
            <li>
                Создать файлы для ДЗ согласно репозиторию
                <a href="https://github.com/OtusTeam/bitrix24">https://github.com/OtusTeam/bitrix24</a>.
            </li>
            <li>
                В нем написать код, который, при обращении к нему по HTTP, будет записывать в файл текущие дату и время.
            </li>
            <li>
                Написать и подключить собственный класс системного логгера, который будет переопределять форматирование строк лога - добавлять слово OTUS в каждую строку.
            </li>
        </ol>
    </div>
</details>

<details class="mb-3">
    <summary><strong>Требования</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Для каждого метода описывать PHPDoc (alt+enter на названии метода в PHPStorm -> Generate PHPDoc).</li>
            <li>Использовать языковые фразы для написания текста в коде.</li>
            <li>
                При разработке стоит придерживаться базовых рекомендаций оформления кода от битрикс
                <a href="https://dev.1c-bitrix.ru/docs/articles/develop/277171/">https://dev.1c-bitrix.ru/docs/articles/develop/277171/</a>.
            </li>
        </ol>
    </div>
</details>

<details class="mb-4">
    <summary><strong>Критерии оценки</strong></summary>
    <div class="mt-3">
        <ol>
            <li>Созданы файлы с кодом.</li>
            <li>В логах отображается необходимая информация.</li>
        </ol>
    </div>
</details>

<h4 class="mb-3">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        Был клонирован предлагаемый преподавателем удаленный репозиторий.
        Директории, относящиеся к домашним заданиям, помещены в
        <a href="/local/homeworks">/local/homeworks</a>.
    </p>
    <p>
        Следуя примеру с прошлого занятия, в
        <a href="/local/classes.php">/local/php_interface/src</a>
        были созданы классы <a href="/local/classes.php#debug">Debug</a>,
        <a href="/local/classes.php#otus-exception-log">OtusExceptionLog</a>, которые
        также, как и класс <a href="/local/classes.php#helper">Helper</a>,
        подключаются через composer в пространство имен <b>Otus</b>.
    </p>
</div>

<h4 class="mb-3">Часть 1 - Logger</h4>
<ul class="list-group mb-5">
    <li class="list-group-item">
        <a href="/local/logs/homework2.log">Файл лога из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="writelog.php">Добавление в лог из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="clearlog.php">Очистить лог из п1 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FDebug.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Файл с классом кастомного логгера</a>
    </li>
</ul>

<h4 class="mb-3">Часть 2 - Exception</h4>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/logs/otus_exceptions.log">Файл лога из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="writeexception.php">Ручной вызов исключения</a>
    </li>
    <li class="list-group-item">
        <a href="clearexception.php">Очистить лог из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtusExceptionLog.php&full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Файл с классом системного исключений</a>
    </li>
</ul>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
