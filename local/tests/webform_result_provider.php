<?php

use Bitrix\Main\Page\Asset;
use Otus\ContactWebFormResultProvider;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Тест ContactWebFormResultProvider');

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Asset::getInstance()->addJs('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');

$contactId = (int)($_REQUEST['contact_id'] ?? 0);
$formId = (int)($_REQUEST['form_id'] ?? 0);

if ($contactId <= 0) {
    ?>
    <div class="container py-4">
        <div class="alert alert-warning mb-0">
            contact_id is required. Example:
            <code>/local/tests/webform_result_provider.php?contact_id=123</code>
        </div>
    </div>
    <?php

    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
    return;
}

$provider = new ContactWebFormResultProvider();
$results = $provider->getByContactId($contactId);
$forms = $provider->getFormsByContactId($contactId);
$formContacts = $formId > 0 ? $provider->getContactsByFormId($formId) : [];
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
        <div>
            <h1 class="h4 mb-1">Результаты CRM-форм</h1>
            <div class="text-muted small">Контакт #<?= $contactId ?>, результатов: <?= count($results) ?></div>
        </div>
    </div>

    <div class="accordion" id="resultsAccordion">
        <?php foreach ($results as $index => $result): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-<?= $index ?>">
                    <button
                        class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#collapse-<?= $index ?>"
                        aria-expanded="false"
                        aria-controls="collapse-<?= $index ?>"
                    >
                        <span class="me-3">#<?= (int)($result['result_id'] ?? 0) ?></span>
                        <span class="text-muted small me-3">Форма: <?= (int)($result['form_id'] ?? 0) ?></span>
                        <span class="text-muted small me-3">Активити: <?= (int)($result['activity_id'] ?? 0) ?></span>
                        <span class="text-muted small"><?= htmlspecialcharsbx((string)($result['date_insert'] ?? '-')) ?></span>
                    </button>
                </h2>
                <div
                    id="collapse-<?= $index ?>"
                    class="accordion-collapse collapse"
                    aria-labelledby="heading-<?= $index ?>"
                    data-bs-parent="#resultsAccordion"
                >
                    <div class="accordion-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <tbody>
                                <?php foreach (($result['fields'] ?? []) as $code => $value): ?>
                                    <tr>
                                        <th class="text-muted fw-semibold" style="width: 25%;">
                                            <?= htmlspecialcharsbx((string)$code) ?>
                                        </th>
                                        <td>
                                            <?php if (is_array($value)): ?>
                                                <?php if ($value === []): ?>
                                                    <span class="text-muted">[]</span>
                                                <?php else: ?>
                                                    <?= htmlspecialcharsbx(implode(', ', array_map('strval', $value))) ?>
                                                <?php endif; ?>
                                            <?php elseif ($value === null || $value === ''): ?>
                                                <span class="text-muted">-</span>
                                            <?php elseif (is_bool($value)): ?>
                                                <?= $value ? 'true' : 'false' ?>
                                            <?php else: ?>
                                                <?= nl2br(htmlspecialcharsbx((string)$value)) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <h2 class="h5 mb-3">Формы, которые заполнял контакт</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th style="width: 25%;">ID формы</th>
                    <th style="width: 50%;">Название</th>
                    <th style="width: 25%;"></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($forms as $form): ?>
                    <tr>
                        <td><?= (int)$form['form_id'] ?></td>
                        <td><?= htmlspecialcharsbx((string)$form['name']) ?></td>
                        <td class="text-end">
                            <a href="?contact_id=<?= $contactId ?>&form_id=<?= (int)$form['form_id'] ?>">Контакты формы</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($formId > 0): ?>
        <div class="mt-4">
            <h2 class="h5 mb-3">Контакты, которые заполняли форму #<?= $formId ?></h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width: 25%;">ID контакта</th>
                        <th style="width: 75%;">Имя</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($formContacts as $item): ?>
                        <tr>
                            <td><?= (int)$item['contact_id'] ?></td>
                            <td><?= htmlspecialcharsbx((string)$item['full_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
