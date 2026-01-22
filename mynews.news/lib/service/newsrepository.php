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

namespace MyNews\Service;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;
use MyNews\HL\Installer;

class NewsRepository
{
    private const CACHE_TTL = 3600; // кеш на 1 час
    private const CACHE_DIR = '/mynews/news';

    /**
     * Считаем сколько всего новостей в HL-блоке.
     */
    public function getTotalCount(): int
    {
        $result = $this->getEntityDataClass()::getList([
            'select' => ['ID'],
        ]);

        return $result->getSelectedRowsCount();
    }

    /**
     * Получение новостей по страницам.
     *
     * Возвращает массив вида:
     * [
     *   'items'   => [ ... новости ... ],
     *   'page'    => текущая страница (с 0),
     *   'pages'   => сколько всего страниц,
     *   'perPage' => сколько новостей на странице,
     *   'total'   => сколько всего новостей
     * ]
     */
    public function getPage(int $page, int $perPage = 2): array
    {
        // Защита от плохих значений
        $page = max(0, $page);
        $perPage = max(1, $perPage);

        // Ключ кеша зависит от номера страницы и количества на странице
        $cacheKey = "page={$page};per={$perPage}";
        $cache = Cache::createInstance();

        // Если кеш есть — просто возвращаем его
        if ($cache->initCache(self::CACHE_TTL, $cacheKey, self::CACHE_DIR)) {
            return $cache->getVars();
        }

        // Если кеша нет — делаем запрос в HL и сохраняем результат
        if ($cache->startDataCache()) {
            $dataClass = $this->getEntityDataClass();

            $total = $this->getTotalCount();
            $pages = max(1, (int)ceil($total / $perPage));

            // "кольцо": приводим страницу к диапазону 0..pages-1
            $page = ($pages > 0) ? ($page % $pages) : 0;

            $offset = $page * $perPage;

            // Берём нужный кусок новостей
            $rows = $dataClass::getList([
                'select' => ['ID', 'UF_TITLE', 'UF_TEXT', 'UF_DATE', 'UF_SORT'],
                'order' => ['UF_SORT' => 'ASC', 'ID' => 'ASC'],
                'limit' => $perPage,
                'offset' => $offset,
            ])->fetchAll();

            // Приводим поля к удобному виду
            $items = array_map(static function ($r) {
                return [
                    'ID' => (int)$r['ID'],
                    'TITLE' => (string)$r['UF_TITLE'],
                    'TEXT' => (string)$r['UF_TEXT'],
                    'DATE' => $r['UF_DATE'] ? $r['UF_DATE']->toString() : '',
                ];
            }, $rows);

            $payload = [
                'items' => $items,
                'page' => $page,
                'pages' => $pages,
                'perPage' => $perPage,
                'total' => $total,
            ];

            // Сохраняем в кеш и возвращаем
            $cache->endDataCache($payload);
            return $payload;
        }

        // Фолбэк (если кеш по какой-то причине не отработал)
        return [
            'items' => [],
            'page' => 0,
            'pages' => 1,
            'perPage' => $perPage,
            'total' => 0
        ];
    }

    /**
     * Получаем DataClass HL-блока, чтобы через него делать запросы.
     */
    private function getEntityDataClass(): string
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
