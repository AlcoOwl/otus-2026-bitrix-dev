<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #8: Модификация интерфейса на стороне клиента");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2">
    <summary><strong>Цель</strong></summary>
    <div class="mt-3">
        <ul>
            <li>закрепить способы подключения произвольного JavaScript без редактирования шаблона;</li>
            <li>научиться отслеживать системные JavaScript-события и реагировать на них.</li>
        </ul>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <p>Необходимо реализовать модальное окно с подтверждением начала рабочего дня.</p>
    </div>
</details>

<details class="mb-2">
    <summary><strong>Пошаговая инструкция</strong></summary>
    <div class="mt-3">
        <ol>
            <li>
                При нажатии на кнопку «Начать рабочий день» или «Продолжить», расположенную в правом верхнем углу,
                открыть модальное окно с произвольным текстом.
            </li>
            <li>Добавить в модальное окно кнопку подтверждения.</li>
            <li>Начинать или продолжать рабочий день только после нажатия на эту кнопку.</li>
            <li>При закрытии модального окна отменять действие.</li>
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

<details class="mb-4">
    <summary><strong>Формат сдачи</strong></summary>
    <div class="mt-3">
        <p>Ссылка на GitHub и доступы к учебному порталу: URL, логин и пароль администратора.</p>
    </div>
</details>

<h4 class="mb-2">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        Для выполнения задания создано
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/tree/main/local/js/otus/timeman-confirm">JS-расширение</a>,
        которое подключается через
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/events.php">events.php</a>
        без изменения шаблона сайта. Тексты модального окна и кнопок вынесены в языковые фразы.
    </p>

    <p>
        При поиске подходящей точки подключения были проверены разные системные события. Событие
        <code>onTimeManDayOpen</code> вызывается уже после отправки запроса на начало рабочего дня, а Pull-события
        приходят после изменения состояния. Поэтому для подтверждения они не подходят. В итоговой реализации
        используется <code>onTimemanInit</code>: после инициализации тайм-менеджера стандартные действия
        <code>OPEN</code> и <code>REOPEN</code> заменяются обёртками с подтверждением.
    </p>

    <p>
        На лекции рассматривался <code>BX.PopupWindowManager</code>, но в задании требуется простое окно
        подтверждения без нестандартной разметки. Поэтому использован готовый стандартный
        <code>BX.UI.Dialogs.MessageBox</code>. Он предоставляет нужные кнопки и поведение модального окна
        с меньшим количеством собственного кода.
    </p>

    <p>
        При подтверждении вызывается исходное действие Bitrix и рабочий день начинается или продолжается.
        При отмене или закрытии окна исходное действие не выполняется.
    </p>
</div>

<div class="mb-4">
    <h2>Демонстрация</h2>
    <p>
        Необходимо открыть окно тайм-менеджера в правом верхнем углу и нажать
        «Начать рабочий день» или «Продолжить».
    </p>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
