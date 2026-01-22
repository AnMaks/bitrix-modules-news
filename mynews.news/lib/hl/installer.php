<?php
/**
 * Класс Installer для работы с Highload-блоком новостей.
 *
 * Это часть установки модуля.
 * Тут мы делаем 3 основные вещи:
 *
 * 1) ensureHighloadBlock()
 *    - проверяет, есть ли HL-блок с таблицей mynews_news
 *    - если нет — создаёт HL-блок и добавляет поля UF_TITLE, UF_TEXT, UF_DATE, UF_SORT
 *
 * 2) fillTestData()
 *    - добавляет тестовые новости (по умолчанию 8 штук)
 *    - но если записи уже есть — повторно не добавляет (чтобы не было дублей)
 *
 * 3) removeHighloadBlock()
 *    - при удалении модуля удаляет поля UF_* и сам HL-блок
 *
 * Важно: чтобы это работало, должен быть установлен модуль highloadblock.
 * Поэтому в начале методов мы делаем Loader::includeModule('highloadblock').
 */

namespace MyNews\HL;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Highloadblock\HighloadBlockTable;

class Installer
{
    public const HL_NAME = 'MyNewsNews';
    public const HL_TABLE = 'mynews_news';

    /**
     * Создаёт HL-блок и поля, если их ещё нет.
     * Возвращает HL_BLOCK_ID.
     */
    public static function ensureHighloadBlock(): int
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Module highloadblock is not installed');
        }

        // 1) Проверим, не создан ли уже блоки
        $existing = HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::HL_TABLE],
            'select' => ['ID', 'NAME', 'TABLE_NAME']
        ])->fetch();

        if ($existing) {
            return (int)$existing['ID'];
        }

        // 2) Создаём HL-блок
        $addRes = HighloadBlockTable::add([
            'NAME' => self::HL_NAME,
            'TABLE_NAME' => self::HL_TABLE
        ]);

        if (!$addRes->isSuccess()) {
            throw new SystemException('Cannot create HL block: ' . implode('; ', $addRes->getErrorMessages()));
        }

        $hlId = (int)$addRes->getId();

        // 3) Создаём пользовательские поля UF_*
        self::addUserField($hlId, 'UF_TITLE', 'string', 'Заголовок', true);
        self::addUserField($hlId, 'UF_TEXT', 'string', 'Текст', true, [
            'SIZE' => 80,
            'ROWS' => 8,
        ]);

        self::addUserField($hlId, 'UF_DATE', 'datetime', 'Дата', true);
        self::addUserField($hlId, 'UF_SORT', 'integer', 'Сортировка', false);

        return $hlId;
    }

    public static function fillTestData(int $hlId, int $count = 8): void
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Module highloadblock is not installed');
        }

        $entity = HighloadBlockTable::compileEntity($hlId);
        $dataClass = $entity->getDataClass();

        // Если уже есть данные — не дублируем
        $hasAny = $dataClass::getList(['select' => ['ID'], 'limit' => 1])->fetch();
        if ($hasAny) {
            return;
        }

        $now = new \Bitrix\Main\Type\DateTime();

        // Добавляем тестовые записи
        for ($i = 1; $i <= $count; $i++) {
            $date = (clone $now)->add("-{$i} days");

            $add = $dataClass::add([
                'UF_TITLE' => "Тестовая новость №{$i}",
                'UF_TEXT'  => "Это тестовый текст новости №{$i}. Здесь может быть краткое описание.",
                'UF_DATE'  => $date,
                'UF_SORT'  => 100 + $i,
            ]);

            if (!$add->isSuccess()) {
                throw new SystemException('Cannot add test data: ' . implode('; ', $add->getErrorMessages()));
            }
        }
    }

    public static function removeHighloadBlock(): void
    {
        if (!Loader::includeModule('highloadblock')) {
            return;
        }

        $existing = HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::HL_TABLE],
            'select' => ['ID']
        ])->fetch();

        if (!$existing) {
            return;
        }

        $hlId = (int)$existing['ID'];

        // Удалим UF-поля
        self::deleteUserField($hlId, 'UF_TITLE');
        self::deleteUserField($hlId, 'UF_TEXT');
        self::deleteUserField($hlId, 'UF_DATE');
        self::deleteUserField($hlId, 'UF_SORT');

        // Удалим HL-блок
        HighloadBlockTable::delete($hlId);
    }

    private static function addUserField(
        int $hlId,
        string $fieldName,
        string $type,
        string $label,
        bool $mandatory,
        array $settings = []
    ): void
    {
        $entityId = 'HLBLOCK_' . $hlId;

        $userField = new \CUserTypeEntity();

        // если поле уже есть — пропускаем
        $existing = \Bitrix\Main\UserFieldTable::getList([
            'filter' => ['=ENTITY_ID' => $entityId, '=FIELD_NAME' => $fieldName],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        if ($existing) {
            return;
        }

        $id = $userField->Add([
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName,
            'USER_TYPE_ID' => $type,
            'XML_ID' => $fieldName,
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => $mandatory ? 'Y' : 'N',
            'SETTINGS' => $settings,
            'EDIT_FORM_LABEL' => ['ru' => $label, 'en' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label, 'en' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label, 'en' => $label],
        ]);

        if (!$id) {
            global $APPLICATION;
            $ex = $APPLICATION->GetException();
            throw new \Bitrix\Main\SystemException(
                'Cannot add userfield ' . $fieldName . ': ' . ($ex ? $ex->GetString() : 'unknown error')
            );
        }
    }

    private static function deleteUserField(int $hlId, string $fieldName): void
    {
        $entityId = 'HLBLOCK_' . $hlId;

        $row = \Bitrix\Main\UserFieldTable::getList([
            'filter' => ['=ENTITY_ID' => $entityId, '=FIELD_NAME' => $fieldName],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();

        if ($row) {
            $userField = new \CUserTypeEntity();
            $userField->Delete((int)$row['ID']);
        }
    }
}
