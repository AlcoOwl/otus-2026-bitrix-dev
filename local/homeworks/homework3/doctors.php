<?php

use Bitrix\Main\Page\Asset;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Вывод связанных полей');

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

use Otus\Models\DoctorsPropertiesTable as Doctors;
use Otus\Models\ProceduresPropertiesTable as Procedures;

try {
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
