<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentDescription = [
    "NAME" => "Лента новостей (mynews.news)",
    "DESCRIPTION" => "Показывает по 2 новости из Highload-блока + листание AJAX по кольцу",
    "CACHE_PATH" => "Y",
    "SORT" => 100,
    "PATH" => [
        "ID" => "mynews",
        "NAME" => "MyNews",
    ],
];
