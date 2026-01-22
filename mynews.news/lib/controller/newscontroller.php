<?php
/**
 * Контроллер модуля для AJAX-запросов.
 *
 * Этот класс нужен, чтобы фронт (JavaScript) мог получать новости без перезагрузки страницы.
 * JS отправляет запрос через BX.ajax.runAction(...) и попадает сюда.
 *
 * Здесь есть метод getPageAction():
 *  - принимает номер страницы (page) и сколько новостей выводить (perPage)
 *  - обращается к NewsRepository, который читает данные из Highload-блока
 *  - возвращает массив с новостями, страницами и т.д. (это уйдёт обратно в JS)
 *
 * Также тут включены фильтры безопасности:
 *  - разрешаем только POST запросы
 *  - проверяем CSRF (sessid), чтобы запрос был “с сайта”, а не снаружи
 */

namespace MyNews\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use MyNews\Service\NewsRepository;

class News extends Controller
{
    public function configureActions(): array
    {
        return [
            'getPage' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    public function getPageAction(int $page = 0, int $perPage = 2): array
    {
        $repo = new NewsRepository();
        return $repo->getPage($page, $perPage);
    }
}
