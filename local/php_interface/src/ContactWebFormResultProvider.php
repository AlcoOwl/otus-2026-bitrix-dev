<?php

namespace Otus;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Otus\Models\LinkResultFormTable;

class ContactWebFormResultProvider
{
    /**
     * @throws LoaderException
     * @throws SystemException
     */
    public function getByContactId(int $contactId): array
    {
        $results = $this->loadContactResults([$contactId]);

        return $results[$contactId] ?? [];
    }

    /**
     * @throws LoaderException
     * @throws SystemException
     */
    public function getByContactIds(array $contactIds): array
    {
        $contactIds = $this->normalizeContactIds($contactIds);
        if (empty($contactIds)) {
            return [];
        }

        return $this->loadContactResults($contactIds);
    }

    /**
     * @throws LoaderException
     * @throws SystemException
     */
    private function loadContactResults(array $contactIds): array
    {
        if (!Loader::includeModule('crm')) {
            throw new SystemException('Module crm is not available.');
        }

        $result = [];
        foreach ($contactIds as $contactId) {
            $result[$contactId] = [];
        }

        $query = LinkResultFormTable::query();
        $query->setSelect([
            'RESULT_ID',
            'CONTACT_ID',
            'FORM_ID',
            'ACTIVITY_ID',
            'RESULT_DATE_INSERT' => 'RESULT.DATE_INSERT',
            'ACTIVITY_PROVIDER_PARAMS' => 'ACTIVITY.PROVIDER_PARAMS',
        ]);
        $query->setFilter([
            '@CONTACT_ID' => $contactIds,
        ]);
        $query->setOrder([
            'RESULT_ID' => 'DESC'
        ]);

        $rows = $query->fetchAll();

        foreach ($rows as $row) {
            $contactId = (int)$row['CONTACT_ID'];
            $fields = $this->parsePayload($row['ACTIVITY_PROVIDER_PARAMS']);
            $date = $row['RESULT_DATE_INSERT'];

            $result[$contactId][] = [
                'result_id' => (int)$row['RESULT_ID'],
                'form_id' => (int)$row['FORM_ID'],
                'contact_id' => $contactId,
                'date_insert' => $date->format('Y-m-d H:i:s'),
                'activity_id' => (int)$row['ACTIVITY_ID'],
                'fields' => $fields,
            ];
        }

        return $result;
    }

    private function normalizeContactIds(array $contactIds): array
    {
        $contactIds = array_map('intval', $contactIds);
        $contactIds = array_filter($contactIds, static fn (int $contactId): bool => $contactId > 0);

        return array_values(array_unique($contactIds));
    }

    private function parsePayload(array $payload): array
    {
        $result = [];

        foreach ($payload['FIELDS'] ?? [] as $field) {
            $code = (string)($field['caption'] ?? $field['code'] ?? '');
            $value = $field['value'] ?? [];

            if ($code === '') {
                continue;
            }

            $value = is_array($value) ? array_values($value) : [$value];
            $result[$code] = count($value) === 1 ? $value[0] : $value;
        }

        return $result;
    }
}
