<?php

use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Вывод связанных полей');

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
Asset::getInstance()->addJs('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');

use Otus\Models\DoctorsPropertiesTable as Doctors;
use Otus\Models\ProceduresPropertiesTable as Procedures;

$procedureFormData = [
    'NAME' => '',
    'PRICE' => '',
];
$procedureFormErrors = [];
$procedureSuccessMessage = '';
$shouldOpenProcedureModal = false;

try {
    Loader::includeModule('iblock');

    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && ($_POST['action'] ?? '') === 'add_procedure'
    ) {
        $procedureFormData['NAME'] = trim((string)($_POST['procedure_name'] ?? ''));
        $procedureFormData['PRICE'] = trim((string)($_POST['procedure_price'] ?? ''));

        if (!check_bitrix_sessid()) {
            $procedureFormErrors[] = 'Сессия истекла. Обновите страницу и попробуйте снова.';
        }

        if ($procedureFormData['NAME'] === '') {
            $procedureFormErrors[] = 'Введите название процедуры.';
        }

        if ($procedureFormData['PRICE'] === '') {
            $procedureFormErrors[] = 'Введите цену процедуры.';
        } elseif (!is_numeric($procedureFormData['PRICE']) || (int)$procedureFormData['PRICE'] < 0) {
            $procedureFormErrors[] = 'Цена процедуры должна быть неотрицательным числом.';
        }

        if (empty($procedureFormErrors)) {
            $procedureEntity = IblockTable::compileEntity(Procedures::API_CODE);

            if (!$procedureEntity) {
                $procedureFormErrors[] = 'Не удалось собрать ORM-сущность инфоблока процедур.';
            } else {
                $procedureClass = $procedureEntity->getDataClass();
                $addResult = $procedureClass::add([
                    'NAME' => $procedureFormData['NAME'],
                    'ACTIVE' => 'Y',
                    'PRICE' => (int)$procedureFormData['PRICE'],
                ]);

                if ($addResult->isSuccess()) {
                    LocalRedirect($APPLICATION->GetCurPageParam('procedure_added=Y', ['procedure_added']));
                }

                $procedureFormErrors = array_merge($procedureFormErrors, $addResult->getErrorMessages());
            }
        }

        if (!empty($procedureFormErrors)) {
            $shouldOpenProcedureModal = true;
        }
    }

    if (($_GET['procedure_added'] ?? '') === 'Y') {
        $procedureSuccessMessage = 'Процедура успешно добавлена.';
    }

    $doctorsCollection = Doctors::query()
        ->setSelect([
            'IBLOCK_ELEMENT_ID',
            'ELEMENT',
            'FIO',
            'EXPERIENCE',
            'PROC_ID.ELEMENT.NAME',
        ])
        ->setOrder(['IBLOCK_ELEMENT_ID' => 'desc'])
        ->fetchCollection();

    $collectionData = [];

    foreach ($doctorsCollection as $doctor) {
        $doctorId = $doctor->get('IBLOCK_ELEMENT_ID');
        $procedureNames = [];
        $procedureCollection = $doctor->get('PROC_ID');

        foreach ($procedureCollection?->getAll() ?? [] as $procedure) {
            $procedureName = $procedure->get('ELEMENT')?->get('NAME');

            if ($procedureName !== null) {
                $procedureNames[] = $procedureName;
            }
        }

        $collectionData[] = [
            'IBLOCK_ELEMENT_ID' => $doctorId,
            'DOCTOR' => $doctor->get('ELEMENT')?->get('NAME'),
            'FIO' => $doctor->get('FIO'),
            'PROCEDURES' => $procedureNames,
        ];
    }

    $proceduresCollection = Procedures::query()
        ->setSelect([
            'IBLOCK_ELEMENT_ID',
            'ELEMENT',
            'PRICE',
        ])
        ->setOrder(['IBLOCK_ELEMENT_ID' => 'desc'])
        ->fetchCollection();

    $proceduresData = [];

    foreach ($proceduresCollection as $procedure) {
        $proceduresData[] = [
            'IBLOCK_ELEMENT_ID' => $procedure->get('IBLOCK_ELEMENT_ID'),
            'NAME' => $procedure->get('ELEMENT')?->get('NAME'),
            'PRICE' => $procedure->get('PRICE'),
        ];
    }
} catch (Throwable $e) {
    print_r($e->getMessage());
}
?>

<div class="container py-4">
    <?php if ($procedureSuccessMessage !== ''): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialcharsbx($procedureSuccessMessage) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($procedureFormErrors)): ?>
        <div class="alert alert-danger" role="alert">
            <?php foreach ($procedureFormErrors as $error): ?>
                <div><?= htmlspecialcharsbx($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center mb-4">
        <h1 class="h3 mb-0">Список врачей</h1>
        <button
            type="button"
            class="btn btn-primary d-inline-flex align-items-center justify-content-center ms-4"
            style="width: 44px; height: 44px; border-radius: 4px; font-size: 28px; line-height: 3;"
            aria-label="Добавить врача"
        > <b>+</b> </button>
    </div>

    <?php if (!empty($collectionData)): ?>
        <div class="row g-4">
            <?php foreach ($collectionData as $doctor): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5 card-title mb-2">
                                <?= htmlspecialcharsbx((string)$doctor['FIO']) ?>
                            </h2>

                            <p class="card-text text-muted mb-4">
                                <?= htmlspecialcharsbx((string)$doctor['DOCTOR']) ?>
                            </p>

                            <a
                                class="mt-auto text-decoration-none d-inline-flex align-items-center gap-2"
                                href="doctor.php?id=<?= (int)$doctor['IBLOCK_ELEMENT_ID'] ?>"
                            >
                                <span>Подробнее</span>
                                <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary mb-0" role="alert">
            Врачи не найдены.
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center mt-5 mb-4">
        <h2 class="h3 mb-0">Список процедур</h2>
        <button
            type="button"
            class="btn btn-primary d-inline-flex align-items-center justify-content-center ms-4"
            style="width: 44px; height: 44px; border-radius: 4px; font-size: 28px; line-height: 3;"
            aria-label="Добавить процедуру"
            data-bs-toggle="modal"
            data-bs-target="#addProcedureModal"
        > <b>+</b> </button>
    </div>

    <?php if (!empty($proceduresData)): ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Название</th>
                        <th scope="col">Цена</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proceduresData as $procedure): ?>
                        <tr>
                            <td><?= htmlspecialcharsbx((string)$procedure['NAME']) ?></td>
                            <td><?= (int)$procedure['PRICE'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary mt-0 mb-0" role="alert">
            Процедуры не найдены.
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="addProcedureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <?= bitrix_sessid_post() ?>
                <input type="hidden" name="action" value="add_procedure">

                <div class="modal-header">
                    <h2 class="modal-title fs-5 mb-0">Добавление процедуры</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="procedure-name">Название процедуры</label>
                        <input
                            id="procedure-name"
                            type="text"
                            name="procedure_name"
                            class="form-control"
                            value="<?= htmlspecialcharsbx($procedureFormData['NAME']) ?>"
                            required
                        >
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="procedure-price">Цена процедуры</label>
                        <input
                            id="procedure-price"
                            type="number"
                            name="procedure_price"
                            class="form-control"
                            min="0"
                            step="1"
                            value="<?= htmlspecialcharsbx($procedureFormData['PRICE']) ?>"
                            required
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Добавить</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($shouldOpenProcedureModal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modalElement = document.getElementById('addProcedureModal');

            if (!modalElement) {
                return;
            }

            var modal = new bootstrap.Modal(modalElement);
            modal.show();
        });
    </script>
<?php endif; ?>
