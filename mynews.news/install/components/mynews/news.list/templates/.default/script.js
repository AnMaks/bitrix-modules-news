/**
 * Скрипт для переключения новостей по стрелкам.
 *
 * На странице показывается по 2 новости.
 * При клике на < или > мы отправляем AJAX в контроллер модуля
 * (mynews:news.api.news.getPage) и перерисовываем список новостей.
 *
 * Тут хранится текущая страница в data-page и общее кол-во страниц в data-pages,
 * чтобы можно было листать по кругу.
 */

(function () {
    function render(list, items) {
        list.innerHTML = '';
        items.forEach(function (item) {
            var el = document.createElement('div');
            el.className = 'mynews-item';
            el.innerHTML =
                '<div class="mynews-item-title"></div>' +
                '<div class="mynews-item-date"></div>' +
                '<div class="mynews-item-text"></div>';

            el.querySelector('.mynews-item-title').textContent = item.TITLE || '';
            el.querySelector('.mynews-item-date').textContent = item.DATE || '';
            el.querySelector('.mynews-item-text').textContent = item.TEXT || '';
            list.appendChild(el);
        });
    }

    function init(root) {
        var list = root.querySelector('[data-role="list"]');
        var btnPrev = root.querySelector('[data-dir="prev"]');
        var btnNext = root.querySelector('[data-dir="next"]');

        function loadPage(page) {
            var perPage = parseInt(root.dataset.perPage || '2', 10);

            BX.ajax.runAction('mynews:news.api.news.getPage', {
                data: { page: page, perPage: perPage, sessid: BX.bitrix_sessid() }
            }).then(function (res) {
                var data = res.data || {};
                render(list, data.items || []);

                root.dataset.page = data.page;
                root.dataset.pages = data.pages;
            });
        }

        function step(dir) {
            var page = parseInt(root.dataset.page || '0', 10);
            var pages = parseInt(root.dataset.pages || '1', 10);
            if (pages <= 0) pages = 1;

            if (dir === 'next') page = (page + 1) % pages;
            if (dir === 'prev') page = (page - 1 + pages) % pages;

            loadPage(page);
        }

        btnPrev.addEventListener('click', function () { step('prev'); });
        btnNext.addEventListener('click', function () { step('next'); });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mynews-wrap').forEach(init);
    });
})();
