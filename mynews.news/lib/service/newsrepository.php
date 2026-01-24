<?php
/**
 * Репозиторий новостей (NewsRepository).
 *
 * Этот класс нужен для того, чтобы в одном месте хранить всю работу с новостями.
 * Компонент и контроллер не должны напрямую лазить в Highload-блок, поэтому они
 * просто вызывают методы репозитория.
 *
 * Что делает этот класс:
 *  - берёт данные из Highload-блока (таблица mynews_news)
 *  - отдаёт новости постранично (по умолчанию по 2 штуки)
 *  - считает сколько всего новостей и сколько страниц
 *  - использует кеш (на 1 час), чтобы каждый раз не ходить в базу
 *
 * Важно:
 *  - Highload-блок создаётся при установке модуля (Installer::ensureHighloadBlock()).
 *  - Метод getPage() возвращает массив, который удобно отдавать в AJAX и в компонент.
 */

namespace Mynews\News\Service;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use Mynews\News\HL\Installer;

class NewsRepository
{
    private const CACHE_TTL = 3600; // кеш на 1 час
    private const CACHE_DIR = '/mynews/news';

    public function getPage(int $page, int $perPage = 2): array
    {
        // Защита от ненужных значений
        $page    = max(0, $page);
        $perPage = max(1, $perPage);

        // Ключ кеша зависит от номера страницы и количества на странице
        $cacheKey = "p={$page};per={$perPage}";
        $cache = Cache::createInstance();

        // Если кеш есть — просто возвращаем его
        if ($cache->initCache(self::CACHE_TTL, $cacheKey, self::CACHE_DIR)) {
            return $cache->getVars();
        }

        // Если кеша нет — делаем запрос в HL и сохраняем результат
        if (!$cache->startDataCache()) {
            return ['items' => [], 'page' => 0, 'pages' => 1, 'perPage' => $perPage, 'total' => 0];
        }

        $dataClass = $this->getDataClass();

        $totalRes = $dataClass::getList(['select' => ['ID']]);
        $total = $totalRes->getSelectedRowsCount();

        // "кольцо": приводим страницу к диапазону 0..pages-1
        $pages = max(1, (int)ceil($total / $perPage));
        $page  = ($pages > 0) ? ($page % $pages) : 0;

        $rows = $dataClass::getList([
            'select' => ['ID', 'UF_TITLE', 'UF_TEXT', 'UF_DATE', 'UF_SORT'],
            'order'  => ['UF_SORT' => 'ASC', 'ID' => 'ASC'],
            'limit'  => $perPage,
            'offset' => $page * $perPage,
        ])->fetchAll();

        // Берём нужный кусок новостей
        $items = array_map(static function ($r) {
            return [
                'ID'    => (int)$r['ID'],
                'TITLE' => (string)$r['UF_TITLE'],
                'TEXT'  => (string)$r['UF_TEXT'],
                'DATE'  => $r['UF_DATE'] ? $r['UF_DATE']->toString() : '',
            ];
        }, $rows);

        $payload = [
            'items'   => $items,
            'page'    => $page,
            'pages'   => $pages,
            'perPage' => $perPage,
            'total'   => $total,
        ];

        // Сохраняем в кеш и возвращаем
        $cache->endDataCache($payload);
        return $payload;
    }

    /**
     * Получаем DataClass HL-блока, чтобы через него делать запросы.
     */
    private function getDataClass(): string
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new \RuntimeException('highloadblock module not installed');
        }
        // HL-блок должен быть создан при установке модуля
        $hlId = Installer::ensureHighloadBlock();

        $entity = HighloadBlockTable::compileEntity($hlId);
        return $entity->getDataClass();
    }
}
