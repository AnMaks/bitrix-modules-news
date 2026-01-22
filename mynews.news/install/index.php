<?php
/**
 * Установщик модуля mynews.news.
 *
 * Данный файл отвечает за установку и удаление модуля.
 *
 * При установке:
 *  - регистрируется модуль в системе;
 *  - создаётся Highload-блок новостей;
 *  - добавляются тестовые новости (8 штук);
 *  - копируется компонент в /local/components;
 *  - создаётся публичная страница /news/.
 *
 * При удалении:
 *  - удаляется страница /news/;
 *  - удаляется компонент;
 *  - удаляется Highload-блок;
 *  - модуль снимается с регистрации.
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;

use MyNews\HL\Installer;

Loc::loadMessages(__FILE__);

// Проверяем, не был ли модуль уже подключён
if (class_exists('mynews_news')) {
    return;
}

class mynews_news extends CModule
{
    public $MODULE_ID = 'mynews.news';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arVersion['VERSION'] ?? '1.0.0';
        $this->MODULE_VERSION_DATE = $arVersion['VERSION_DATE'] ?? date('Y-m-d');

        $this->MODULE_NAME = 'Лента новостей (mynews)';
        $this->MODULE_DESCRIPTION =
            'HL-блок новостей + компонент + AJAX через контроллер модуля + /news/';
    }

    /**
     * Установка модуля
     */
    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        // После регистрации можно подключать модуль
        Loader::includeModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallFiles();
    }

    /**
     * Удаление модуля
     */
    public function DoUninstall()
    {
        // Подключаем модуль, чтобы работала автозагрузка классов
        Loader::includeModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallDB();

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Создание Highload-блока и тестовых данных
     */
    public function InstallDB()
    {
        $hlId = Installer::ensureHighloadBlock();
        Installer::fillTestData($hlId, 8);

        return true;
    }

    /**
     * Удаление Highload-блока
     */
    public function UnInstallDB()
    {
        Installer::removeHighloadBlock();
        return true;
    }

    /**
     * Копирование файлов модуля
     */
    public function InstallFiles()
    {
        // Копируем компонент
        CopyDirFiles(
            __DIR__ . '/components',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components',
            true,
            true
        );

        // Создаём публичную страницу /news/
        CopyDirFiles(
            __DIR__ . '/public/news',
            $_SERVER['DOCUMENT_ROOT'] . '/news',
            true,
            true
        );

        return true;
    }

    /**
     * Удаление файлов модуля
     */
    public function UnInstallFiles()
    {
        // Удаляем страницу, которую создавали при установке
        DeleteDirFilesEx('/news');

        // Удаляем компонент
        DeleteDirFilesEx('/local/components/mynews/news.list');

        return true;
    }
}
