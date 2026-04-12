<?php

use Bitrix\Main\Page\Asset;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

global $APPLICATION;

$APPLICATION->SetTitle('Карточка врача');

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

use Otus\Models\DoctorsPropertiesTable as Doctors;

$doctorId = (int)($_GET['id'] ?? 0);
$doctorData = null;
$errorMessage = '';

if ($doctorId <= 0) {
    $errorMessage = 'Некорректный идентификатор врача.';
} else {
    try {
        $doctor = Doctors::query()
            ->setSelect([
                'IBLOCK_ELEMENT_ID',
                'ELEMENT',
                'FIO',
                'EXPERIENCE',
                'PROC_ID.ELEMENT.NAME',
            ])
            ->where('IBLOCK_ELEMENT_ID', $doctorId)
            ->fetchObject();

        if (!$doctor) {
            $errorMessage = 'Врач не найден.';
        } else {
            $procedureNames = [];
            $procedureCollection = $doctor->get('PROC_ID');

            foreach ($procedureCollection?->getAll() ?? [] as $procedure) {
                $procedureName = $procedure->get('ELEMENT')?->get('NAME');

                if ($procedureName !== null) {
                    $procedureNames[] = $procedureName;
                }
            }

            $doctorData = [
                'IBLOCK_ELEMENT_ID' => $doctor->get('IBLOCK_ELEMENT_ID'),
                'DOCTOR' => $doctor->get('ELEMENT')?->get('NAME'),
                'FIO' => $doctor->get('FIO'),
                'EXPERIENCE' => $doctor->get('EXPERIENCE'),
                'PROCEDURES' => $procedureNames,
            ];
        }
    } catch (Throwable $e) {
        $errorMessage = $e->getMessage();
    }
}
?>

<div class="container py-4">
    <div class="mb-4">
        <a class="text-decoration-none" href="doctors.php">&larr; Назад к списку врачей</a>
    </div>

    <?php if ($errorMessage !== ''): ?>
        <div class="alert alert-danger mb-0" role="alert">
            <?= htmlspecialcharsbx($errorMessage) ?>
        </div>
    <?php elseif ($doctorData !== null): ?>
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h1 class="h3 mb-1"><?= htmlspecialcharsbx((string)$doctorData['FIO']) ?></h1>
                <p class="text-muted mb-1"><?= htmlspecialcharsbx((string)$doctorData['DOCTOR']) ?></p>
                <p class="mb-4">
                    <strong>Стаж:</strong>
                    <?= (int)$doctorData['EXPERIENCE'] ?> лет
                </p>

                <h2 class="h4 mb-1">Список процедур</h2>

                <?php if (!empty($doctorData['PROCEDURES'])): ?>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($doctorData['PROCEDURES'] as $procedureName): ?>
                            <li><?= htmlspecialcharsbx($procedureName) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted mb-0">Процедуры не указаны.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
