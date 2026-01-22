<?php
/**
 * Настройки контроллеров модуля.
 *
 * Этот файл нужен для того, чтобы Bitrix понимал,
 * где находятся AJAX-контроллеры модуля.
 *
 * Здесь мы указываем namespace MyNews\Controller
 * и задаём для него алиас "api".
 *
 * Благодаря этому JavaScript может вызывать методы контроллера так:
 * BX.ajax.runAction('mynews:news.api.news.getPage')
 */

return [
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\\MyNews\\Controller' => 'api',
            ],
        ],
        'readonly' => true,
    ],
];
