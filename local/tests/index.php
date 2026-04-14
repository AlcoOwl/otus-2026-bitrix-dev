<?php

use Bitrix\Main\Page\Asset;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Тестовые страницы');

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

$testPages = [
    [
        'title' => 'TEST DEBUG',
        'description' => 'Проверка логирования и замеров времени выполнения.',
        'href' => '/local/tests/test_debug.php',
    ],
    [
        'title' => 'CRUD CRM Entity',
        'description' => 'Пример чтения CRM-сущностей через фабрику.',
        'href' => '/local/tests/crud_entity.php',
    ],
    [
        'title' => 'Custom ORM Model',
        'description' => 'Вывод связанных полей и runtime reference.',
        'href' => '/local/tests/custom_model.php',
    ],
    [
        'title' => 'Relations And Multi',
        'description' => 'Тест множественных связей и выборок инфоблока.',
        'href' => '/local/tests/multi.php',
    ],
];
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="d-flex flex-column gap-2 mb-4">
                <span class="text-uppercase text-secondary small">local/tests</span>
                <h1 class="mb-0"><?php $APPLICATION->ShowTitle(); ?></h1>
                <p class="text-secondary mb-0">Навигация по тестовым страницам проекта.</p>
            </div>

            <div class="row g-3">
                <?php foreach ($testPages as $page): ?>
                    <div class="col-12 col-md-6">
                        <a class="card h-100 text-decoration-none shadow-sm border-0" href="<?= htmlspecialcharsbx($page['href']) ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div>
                                        <h2 class="h5 text-dark mb-2"><?= htmlspecialcharsbx($page['title']) ?></h2>
                                        <p class="text-secondary mb-0"><?= htmlspecialcharsbx($page['description']) ?></p>
                                    </div>
                                    <span class="btn btn-outline-primary btn-sm disabled">Открыть</span>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
