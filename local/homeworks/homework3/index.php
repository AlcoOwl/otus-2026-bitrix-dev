<?php

use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

global $APPLICATION;

$APPLICATION->SetTitle("ДЗ #3: Связывание моделей");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-4"><?php $APPLICATION->ShowTitle() ?></h1>

<details class="mb-2">
    <summary><strong>Описание</strong></summary>
    <div class="mt-3">
        <ol>
            <li>
                Создать 2 списка с врачами и процедурами, которые они выполняют.
            </li>
            <li>
                Привязать процедуры к врачам;
            </li>
            <li>
                Создать пустую страницу, где мы кликаем по врачу и видим процедуры, которые он делает.
                Использовать абстрактный класс из предыдущих занятий и D7;
            </li>
            <li>
                Реализовать возможность добавления врача, процедуры, связи между ними.
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
            <li>На отдельной странице будет представлен перечень специалистов, а при выборе конкретного врача будет
                отображаться перечень доступных услуг. Для этого не требуется использование Ajax или SSR — достаточно
                выполнить GET/POST-запросы к серверу.
            </li>
            <li>Реализован функционал на D7.
            </li>
        </ol>
    </div>
</details>

<h4 class="mb-2">Пояснительная записка</h4>
<div class="mb-4">
    <p>
        Для выполнения задания был написан абстрактный D7-класс для работы со свойствами инфоблоков: он формирует карту полей,
        поддерживает одиночные и множественные свойства и позволяет описывать связи через ORM.
        На его основе созданы модели <b>DoctorsPropertiesTable</b> и <b>ProceduresPropertiesTable</b>, в которых описаны
        поля врачей (ФИО, стаж, привязка к процедурам) и процедур (цена). В
        <a href="/local/tests/custom_model.php">/local/tests/custom_model.php</a> и
        <a href="/local/tests/multi.php">/local/tests/multi.php</a> проверены выборки связанных полей, runtime-связи и работа
        с множественными свойствами. В самом разделе ДЗ реализованы страница со списком врачей, карточка врача с перечнем
        его процедур, а также формы добавления врачей, процедур и связей между ними.

        <a href="/local/homeworks/homework3/doctors.php">Страница с демонстрацией функционала &rarr;</a>
    </p>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
