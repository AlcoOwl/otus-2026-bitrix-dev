<?php

use Airecogn\Model\RecognitionResultTable;
use Bitrix\Crm\ActivityBindingTable;
use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\PageNavigation;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
    die();
}

class AirecognRecognitionGridComponent extends CBitrixComponent
{
    private const GRID_ID = 'AIRECOGN_RECOGNITION_GRID';

    public function executeComponent(): void
    {
        if (!Loader::includeModule('alcoowl.airecogn') || !Loader::includeModule('crm'))
        {
            ShowError('Не удалось подключить модули alcoowl.airecogn и crm.');
            return;
        }

        $contactId = (int)($this->arParams['CONTACT_ID'] ?? 0);
        if ($contactId <= 0)
        {
            ShowError('Не указан контакт.');
            return;
        }

        $gridOptions = new GridOptions(self::GRID_ID . '_' . $contactId);
        $navigationParams = $gridOptions->getNavParams(['nPageSize' => 20]);
        $navigation = new PageNavigation(self::GRID_ID . '_NAV_' . $contactId);
        $navigation->allowAllRecords(false)
            ->setPageSize((int)$navigationParams['nPageSize'])
            ->initFromUri();

        [$items, $total] = $this->loadItems($contactId, $navigation->getOffset(), $navigation->getLimit());
        $navigation->setRecordCount($total);

        $this->arResult = [
            'GRID_ID' => self::GRID_ID . '_' . $contactId,
            'COLUMNS' => $this->getColumns(),
            'ROWS' => $this->prepareRows($items, $contactId),
            'NAVIGATION' => $navigation,
            'TOTAL' => $total,
            'AJAX_MODE' => $this->arParams['AJAX_MODE'] ?? 'N',
            'AJAX_ID' => $this->arParams['AJAX_ID'] ?? '',
            'AJAX_OPTION_JUMP' => $this->arParams['AJAX_OPTION_JUMP'] ?? 'N',
            'AJAX_OPTION_HISTORY' => $this->arParams['AJAX_OPTION_HISTORY'] ?? 'N',
            'AJAX_LOADER' => $this->arParams['AJAX_LOADER'] ?? null,
        ];

        $this->includeComponentTemplate();
    }

    private function loadItems(int $contactId, int $offset, int $limit): array
    {
        $result = RecognitionResultTable::getList([
            'select' => ['ID', 'ACTIVITY_ID', 'STATUS', 'SUMMARY', 'CREATED_AT', 'UPDATED_AT'],
            'runtime' => [
                new ReferenceField(
                    'ACTIVITY_BINDING',
                    ActivityBindingTable::class,
                    Join::on('this.ACTIVITY_ID', 'ref.ACTIVITY_ID'),
                    ['join_type' => 'INNER']
                ),
            ],
            'filter' => [
                '=ACTIVITY_BINDING.OWNER_TYPE_ID' => CCrmOwnerType::Contact,
                '=ACTIVITY_BINDING.OWNER_ID' => $contactId,
            ],
            'order' => ['UPDATED_AT' => 'DESC'],
            'offset' => $offset,
            'limit' => $limit,
            'count_total' => true,
        ]);

        return [$result->fetchAll(), $result->getCount()];
    }

    private function getColumns(): array
    {
        return [
            ['id' => 'ACTIVITY_ID', 'name' => 'ID активити', 'default' => true],
            ['id' => 'CONTACT', 'name' => 'Контакт', 'default' => true],
            ['id' => 'STATUS', 'name' => 'Статус', 'default' => true],
            ['id' => 'SUMMARY', 'name' => 'Краткое содержание', 'default' => true],
            ['id' => 'CREATED_AT', 'name' => 'Создано', 'default' => true],
            ['id' => 'UPDATED_AT', 'name' => 'Обновлено', 'default' => true],
        ];
    }

    private function prepareRows(array $items, int $contactId): array
    {
        $rows = [];
        $contactUrl = $contactId > 0 ? '/crm/contact/details/' . $contactId . '/' : '';
        $contactTitle = $contactId > 0 ? CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $contactId) : '';

        foreach ($items as $item)
        {
            $rows[] = [
                'id' => (int)$item['ID'],
                'data' => $item,
                'columns' => [
                    'ACTIVITY_ID' => (int)$item['ACTIVITY_ID'],
                    'CONTACT' => $contactUrl !== ''
                        ? '<a href="' . htmlspecialcharsbx($contactUrl) . '">' . htmlspecialcharsbx($contactTitle ?: ('Контакт #' . $contactId)) . '</a>'
                        : '',
                    'STATUS' => $this->renderStatus((string)$item['STATUS']),
                    'SUMMARY' => nl2br(htmlspecialcharsbx((string)$item['SUMMARY'])),
                    'CREATED_AT' => htmlspecialcharsbx((string)$item['CREATED_AT']),
                    'UPDATED_AT' => htmlspecialcharsbx((string)$item['UPDATED_AT']),
                ],
            ];
        }

        return $rows;
    }

    private function renderStatus(string $status): string
    {
        $colors = ['pending' => '#d57b11', 'success' => '#2f9b45', 'error' => '#d93025'];
        $color = $colors[$status] ?? '#777';

        return '<strong style="color:' . $color . '">' . htmlspecialcharsbx($status) . '</strong>';
    }
}
