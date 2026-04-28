<?php

namespace Otus;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Otus\Models\ContactWebFormTable;
use Otus\Models\CrmWebFormTable;

class ContactWebFormResultProvider
{
    /**
     * @param int $contactId
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getByContactId(int $contactId): array
    {
        $result = [];

        $rows = ContactWebFormTable::query()
            ->setSelect([
            'ID',
            'RESULT_ID' => 'LINK_RESULTS.RESULT_ID',
            'FORM_ID' => 'LINK_RESULTS.FORM_ID',
            'ACTIVITY_ID' => 'LINK_RESULTS.ACTIVITY_ID',
            'RESULT_DATE_INSERT' => 'LINK_RESULTS.RESULT.DATE_INSERT',
            'ACTIVITY_PROVIDER_PARAMS' => 'LINK_RESULTS.ACTIVITY.PROVIDER_PARAMS',
            ])
            ->setFilter([
                '=ID' => $contactId,
            ])
            ->setOrder([
                'LINK_RESULTS.RESULT_ID' => 'DESC',
            ])
            ->fetchAll();

        foreach ($rows as $row) {
            $fields = $this->parsePayload($row['ACTIVITY_PROVIDER_PARAMS']);
            $date = $row['RESULT_DATE_INSERT'];

            $result[] = [
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

    /**
     * @param int $contactId
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getFormsByContactId(int $contactId): array
    {
        $rows = ContactWebFormTable::query()
            ->setSelect([
                'ID',
                'FORM_ID' => 'FORMS.ID',
                'FORM_NAME' => 'FORMS.NAME'
            ])
            ->setFilter([
                '=ID' => $contactId,
            ])
            ->setOrder([
                'FORMS.ID' => 'ASC'
            ])
            ->fetchAll();

        $result = [];

        foreach ($rows as $row) {
            $formId = (int)$row['FORM_ID'];
            if ($formId <= 0 || isset($result[$formId])) {
                continue;
            }

            $result[$formId] = [
                'form_id' => $formId,
                'name' => (string)($row['FORM_NAME']),
            ];
        }

        return array_values($result);
    }

    /**
     * @param int $formId
     * @return array
     * @throws ArgumentException
     * @throws SystemException
     */
    public function getContactsByFormId(int $formId): array
    {
        $rows = CrmWebFormTable::query()
            ->setSelect([
                'ID',
                'NAME',
                'CONTACT_ID' => 'CONTACTS.ID',
                'CONTACT_FULL_NAME' => 'CONTACTS.FULL_NAME',
            ])
            ->setFilter([
                '=ID' => $formId,
            ])
            ->setOrder([
                'CONTACTS.FULL_NAME' => 'ASC',
                'CONTACTS.ID' => 'ASC',
            ])
            ->fetchAll();

        $result = [];

        foreach ($rows as $row) {
            $contactId = (int)$row['CONTACT_ID'];
            if ($contactId <= 0 || isset($result[$contactId])) {
                continue;
            }

            $result[$contactId] = [
                'contact_id' => $contactId,
                'full_name' => (string)$row['CONTACT_FULL_NAME'],
            ];
        }

        return array_values($result);
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
