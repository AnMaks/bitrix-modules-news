<?php
/**
 * Файл автозагрузки классов модуля.
 *
 * Здесь регистрируются все классы модуля mynews.news,
 * чтобы код мог автоматически подключать их при использовании.
 *
 */

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'mynews.news',
    [
        // Класс установки HL-блока
        '\MyNews\HL\Installer' => 'lib/hl/installer.php',

        // Репозиторий для работы с новостями
        '\MyNews\Service\NewsRepository' => 'lib/service/newsrepository.php',

        // Контроллер модуля для AJAX-запросов
        '\MyNews\Controller\News' => 'lib/controller/newscontroller.php',
    ]
);
