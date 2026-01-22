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
    function init(root) {
        var listNode = root.querySelector('[data-role="list"]');
        var pageNode = root.querySelector('[data-role="page"]');

        function draw(items) {
            listNode.innerHTML = '';

            items.forEach(function (item) {
                listNode.insertAdjacentHTML(
                    'beforeend',
                    '<div class="mynews-item">' +
                    '<div class="mynews-item-title"></div>' +
                    '<div class="mynews-item-date"></div>' +
                    '<div class="mynews-item-text"></div>' +
                    '</div>'
                );

                var el = listNode.lastElementChild;
                el.querySelector('.mynews-item-title').textContent = item.TITLE || '';
                el.querySelector('.mynews-item-date').textContent = item.DATE || '';
                el.querySelector('.mynews-item-text').textContent = item.TEXT || '';
            });
        }

        function load(page) {
            var perPage = parseInt(root.dataset.perPage || '2', 10);

            BX.ajax.runAction('mynews:news.api.news.getPage', {
                data: {
                    page: page,
                    perPage: perPage,
                    sessid: BX.bitrix_sessid()
                }
            }).then(function (res) {
                var data = res.data || {};

                draw(data.items || []);

                root.dataset.page = data.page;
                root.dataset.pages = data.pages;

                if (pageNode) pageNode.textContent = (parseInt(data.page, 10) + 1);
            }, function (err) {
                console.error(err);
            });
        }

        // один обработчик на весь блок
        root.addEventListener('click', function (e) {
            var btn = e.target.closest('.mynews-btn');
            if (!btn) return;

            var dir = btn.getAttribute('data-dir'); // prev или next
            var page = parseInt(root.dataset.page || '0', 10);
            var pages = parseInt(root.dataset.pages || '1', 10);
            if (pages <= 0) pages = 1;

            if (dir === 'next') page = (page + 1) % pages;
            if (dir === 'prev') page = (page - 1 + pages) % pages;

            load(page);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.mynews-wrap').forEach(init);
    });
})();
