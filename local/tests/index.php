<?php

use Bitrix\Main\Page\Asset;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Тестовые страницы');

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

$pageMeta = [
    'contacts_bindings.php' => [
        'title' => 'Contacts Bindings',
        'description' => 'Проверка привязки нескольких контактов к элементу СП через factory.',
    ],
    'crud_entity.php' => [
        'title' => 'CRUD CRM Entity',
        'description' => 'Пример чтения CRM-сущностей через фабрику.',
    ],
    'custom_model.php' => [
        'title' => 'Custom ORM Model',
        'description' => 'Вывод связанных полей и runtime reference.',
    ],
    'multi.php' => [
        'title' => 'Relations And Multi',
        'description' => 'Тест множественных связей и выборок инфоблока.',
    ],
    'phone_parser.php' => [
        'title' => 'Phone Parser',
        'description' => 'Проверка парсинга и нормализации телефонного номера.',
    ],
    'test_debug.php' => [
        'title' => 'TEST DEBUG',
        'description' => 'Проверка логирования и замеров времени выполнения.',
    ],
];

$testPages = [];

foreach (glob(__DIR__ . '/*.php') ?: [] as $filePath) {
    $fileName = basename($filePath);

    if ($fileName === 'index.php') {
        continue;
    }

    $meta = $pageMeta[$fileName] ?? [
        'title' => ucwords(str_replace('_', ' ', pathinfo($fileName, PATHINFO_FILENAME))),
        'description' => 'Тестовая страница проекта.',
    ];

    $testPages[] = [
        'title' => $meta['title'],
        'description' => $meta['description'],
        'href' => '/local/tests/' . $fileName,
    ];
}

usort(
    $testPages,
    static fn(array $left, array $right): int => strcmp($left['title'], $right['title'])
);
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
