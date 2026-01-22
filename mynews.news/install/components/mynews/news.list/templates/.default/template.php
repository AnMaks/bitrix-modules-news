<?php
/**
 * Шаблон компонента новостей.
 *
 * Здесь выводится список новостей и кнопки переключения.
 * Данные приходят из компонента, а переключение страниц
 * происходит через JavaScript и AJAX.
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\UI\Extension::load("ajax");

$this->addExternalCss($templateFolder . "/style.css");
$this->addExternalJs($templateFolder . "/script.js");
?>

<div class="mynews-wrap"
     data-page="<?= (int)$arResult['PAGE'] ?>"
     data-pages="<?= (int)$arResult['PAGES'] ?>"
     data-per-page="<?= (int)$arResult['PER_PAGE'] ?>">

    <div class="mynews-header">
        <div class="mynews-title">Новости</div>
    </div>

    <div class="mynews-list" data-role="list">
        <?php foreach ($arResult['ITEMS'] as $item): ?>
            <div class="mynews-item">
                <div class="mynews-item-title"><?= htmlspecialcharsbx($item['TITLE']) ?></div>
                <div class="mynews-item-date"><?= htmlspecialcharsbx($item['DATE']) ?></div>
                <div class="mynews-item-text"><?= htmlspecialcharsbx($item['TEXT']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>


    <div class="mynews-meta">
        <button class="mynews-btn" data-dir="prev">&lt;</button>
        <button class="mynews-btn" data-dir="next">&gt;</button>
    </div>

</div>
