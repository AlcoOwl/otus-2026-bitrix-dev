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
        Для выполнения задания был написан абстрактный D7-класс <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/AbstractIblockPropertyValuesTable.php">/local/php_interface/src/models/AbstractIblockPropertyValuesTable.php</a><br>
        Он формирует карту полей, поддерживает одиночные и множественные свойства и позволяет описывать связи через ORM.<br>
        На его основе созданы модели <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/DoctorsPropertiesTable.php">/local/php_interface/src/models/DoctorsPropertiesTable.php</a> и
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/php_interface/src/Models/ProceduresPropertiesTable.php">/local/php_interface/src/models/ProceduresPropertiesTable.php</a><br>
        в которых описаны поля врачей (ФИО, стаж, привязка к процедурам) и процедур (цена).<br>
        В <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/tests/custom_model.php">/local/tests/custom_model.php</a> и
        <a href="https://github.com/AlcoOwl/otus-2026-bitrix-dev/blob/main/local/tests/multi.php">/local/tests/multi.php</a> проверены выборки связанных полей, runtime-связи и работа
        с множественными свойствами.<br>
        В самом разделе ДЗ реализованы страница со списком врачей, карточка врача с перечнем
        его процедур, а также формы добавления врачей, процедур и связей между ними. <br>
    </p>
</div>

<div class="mb-4">
    <h2><a href="/local/homeworks/homework3/doctors.php">Страница с демонстрацией функционала &rarr;</a></h2>
</div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
