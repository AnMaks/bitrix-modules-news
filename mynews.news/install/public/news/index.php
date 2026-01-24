<?php
/**
 * Публичная страница новостей.
 *
 * На этой странице подключается компонент mynews:news.list,
 * который выводит список новостей и позволяет листать их.
 * Все данные загружаются из Highload-блока через модуль mynews.news.
 */
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Новости");

// Подключаем компонент вывода новостей
$APPLICATION->IncludeComponent(
    "mynews:news.list",
    ".default",
    [
        "PER_PAGE" => 2 // количество новостей на странице
    ]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
