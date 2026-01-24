<?php
/**
 * Компонент для вывода новостей на странице.
 *
 * Этот компонент нужен только для показа новостей.
 * Он получает данные из Highload-блока через модуль mynews.news.
 *
 * При открытии страницы:
 *  - подключается модуль;
 *  - загружается первая страница новостей (2 штуки);
 *  - данные передаются в шаблон компонента.
 *
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class MynewsNewsListComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        if (!Loader::includeModule('mynews.news')) {
            ShowError('Модуль mynews.news не установлен');
            return;
        }

        $perPage = (int)($this->arParams['PER_PAGE'] ?? 2);
        if ($perPage <= 0) $perPage = 2;

        $repo = new \Mynews\News\Service\NewsRepository();
        $data = $repo->getPage(0, $perPage);

        $this->arResult = [
            'ITEMS'    => $data['items'],
            'PAGE'     => $data['page'],
            'PAGES'    => $data['pages'],
            'PER_PAGE' => $data['perPage'],
        ];

        $this->includeComponentTemplate();
    }
}
