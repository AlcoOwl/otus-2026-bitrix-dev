<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #2: Отладка и логирование");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2">
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
                Написать и подключить собственный класс системного логгера, который будет переопределять форматирование
                строк лога — добавлять слово OTUS в каждую строку.
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
            <li>Созданы файлы с кодом.</li>
            <li>В логах отображается необходимая информация.</li>
        </ol>
    </div>
</details>

<h4 class="mb-2">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        Был клонирован предлагаемый преподавателем удаленный репозиторий.
        Директории, относящиеся к домашним заданиям, помещены в
        <a href="/local/homeworks">/local/homeworks</a>.<br>
        Следуя примеру с прошлого занятия, в <a href="/local/classes.php">/local/php_interface/src</a>
        были созданы классы <a href="/local/classes.php#debug">Debug</a>,
        <a href="/local/classes.php#otus-exception-log">OtusExceptionLog</a>, которые также, как и класс
        <a href="/local/classes.php#helper">Helper</a>, подключаются через composer в пространство имен <b>Otus</b>.
        Ниже приведены ссылки на скрипты для выполнения и проверки пунктов 2 и 3 ДЗ
    </p>
</div>

<h4 class="mb-2">Часть 1 - Logger</h4>
<ul class="list-group mb-4">
    <li class="list-group-item">
        <a href="/local/logs/homework2.log">Просмотр log файла п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="writelog.php">Запись в лог из п2 ДЗ текущей даты и времени</a>
    </li>
    <li class="list-group-item">
        <a href="clearlog.php">Очистка лога из п2 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FDebug.php&full_src=Y
        &site=s1&lang=ru&&filter=Y&set_filter=Y">Файл с классом для выполнения п2 ДЗ</a>
    </li>
</ul>

<h4 class="mb-2">Часть 2 - Exception</h4>
<ul class="list-group mb-4">
    <li class="list-group-item">
        <a href="/local/logs/otus_exceptions.log">Просмотр log исключений п3 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="writeexception.php">Ручной вызов исключения (деление на 0)</a>
    </li>
    <li class="list-group-item">
        <a href="clearexception.php">Очистить лог исключений из п3 ДЗ</a>
    </li>
    <li class="list-group-item">
        <a href="/bitrix/admin/fileman_file_edit.php?path=%2Flocal%2Fphp_interface%2Fsrc%2FOtusExceptionLog.php
        &full_src=Y&site=s1&lang=ru&&filter=Y&set_filter=Y">Файл с классом для выполнения п3 ДЗ</a>
    </li>
</ul>

<h4 class="mb-2">Примечания</h4>
<div>
    <p>
        По ходу выполнения ДЗ было недопонимание 3 пункта. Исходя из того, о чем говорилось на лекции и репозитория с ДЗ,
        возникает ощущение, что нужно подключить собственный класс для логирования системных исключений. Надеюсь, что
        понял правильно). Потому что в противном случае, не совсем понимаю, что делать.
    </p>
    <p>
        Чтобы можно было использовать значение "level", из .settings была дополнительно переопределена функция
        initialize, Т.к. в родительском классе level — это private.
    </p>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
