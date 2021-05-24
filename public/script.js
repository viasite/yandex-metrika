const reportsLinks = {
  'Источники': 'https://metrika.yandex.ru/stat/sources?group=week&chart_type=stacked-chart&period={{year}}&id={{counter_id}}',
  'Поисковые системы': 'https://metrika.yandex.ru/stat/search_engines?group=week&chart_type=stacked-chart&period={{year}}&id={{counter_id}}',
  'Глубина просмотра': 'https://metrika.yandex.ru/stat/5c1418041709fe9d50c6229d?group=week&period={{year}}&id={{counter_id}}',
  'Время на сайте': 'https://metrika.yandex.ru/stat/deepness_time?chart_type=stacked-chart?group=week&period={{year}}&id={{counter_id}}',
  'Цели по источникам': 'https://metrika.yandex.ru/stat/5c1a8bbfad22f472b5a401f3?group=week&period={{year}}&id={{counter_id}}',
  'Цели по устройствам': 'https://metrika.yandex.ru/stat/5c6a5c7d46d5ca89d0e42b7c?group=week&period={{year}}&id={{counter_id}}',
  'Рекламный трафик': 'https://metrika.yandex.ru/stat/5c767c120cea2fe2e870a45f/compare?period=month&secondary_period=month&id={{counter_id}}',
  'Страницы входа с поиска': 'https://metrika.yandex.ru/stat/5ce256cfc48e83846300929f?group=week&period={{year}}&id={{counter_id}}',
  'Популярное': 'https://metrika.yandex.ru/stat/popular?dimension_mode=list&group=week&chart_type=stacked-chart&period={{year}}&attribution=Last&id={{counter_id}}',
  'География: области': 'https://metrika.yandex.ru/stat/5ee0e2c2b5f282527ab07b1b?group=week&period={{year}}&id={{counter_id}}',
  'Возраст': 'https://metrika.yandex.ru/stat/demography_age?group=week&chart_type=bar-chart&period={{year}}&attribution=Last&id={{counter_id}}',
  'Пол': 'https://metrika.yandex.ru/stat/demography_structure?group=week&chart_type=pie&period={{year}}&attribution=Last&id={{counter_id}}',
  'Устройства': 'https://metrika.yandex.ru/stat/tech_devices?group=week&chart_type=stacked-chart&period={{year}}&attribution=Last&id={{counter_id}}',
  'Эл. коммерция': 'https://metrika.yandex.ru/stat/purchase?group=week&chart_type=stacked-chart&period={{year}}&attribution=Last&id={{counter_id}}',
  'Вебвизор': 'https://metrika.yandex.ru/stat/visor?period={{year}}&id={{counter_id}}',
  'Вебмастер: Качество': 'https://webmaster.yandex.ru/site/{{url_webmaster}}/quality-tools/quality/',
  'Вебмастер: Запросы': 'https://webmaster.yandex.ru/site/{{url_webmaster}}/search/statistics/',
  'Вебмастер: Страницы': 'https://webmaster.yandex.ru/site/{{url_webmaster}}/search/urls/',
  'Вебмастер: Индексирование': 'https://webmaster.yandex.ru/site/{{url_webmaster}}/indexing/indexing/',
  'Google: Поиск': 'https://search.google.com/search-console/performance/search-analytics?resource_id={{gsc_resource_id}}',
  'Google: Скорость': 'https://search.google.com/search-console/core-web-vitals?resource_id={{gsc_resource_id}}',
  'Google: Скорость: Мобильные': 'https://search.google.com/search-console/core-web-vitals/summary?resource_id={{gsc_resource_id}}&device=2',
  'Google: Удобство для мобильных': 'https://search.google.com/search-console/mobile-usability?resource_id={{gsc_resource_id}}',
  'Google: AMP': 'https://search.google.com/search-console/amp?resource_id={{gsc_resource_id}}',
}

$(function() {
  const select = $('#counter_id');
  let counterId = select.val();
  updateReports(counterId);
  drawBookmarks();

  select.on('change', function() {
    counterId = select.val();
    updateReports(counterId);
  });
});

function updateReports(counterId) {
  let siteBrief = '';
  const reports = $('<ul id="metrika-reports"></ul>');
  if(counterId != 0) {
    const select = $('#counter_id');
    const counterName = select.find('option:selected').text();

    let from = Date.today().add(-365).day();
    if (from.getDay() != 1) from = from.previous().monday();
    from = from.toString('yyyy-MM-dd');

    const to = Date.today().previous().sunday().toString('yyyy-MM-dd');

    const yearAligned = `${from}%3A${to}`;

    for (let name in reportsLinks) {
      let href = reportsLinks[name].
          replace('{{counter_id}}', counterId).
          replace('{{url_webmaster}}', `https:${counterName}:443`).
          replace('{{year}}', yearAligned).
          replace('{{gsc_resource_id}}', `https://${counterName}/`);
      let hrefMonth = href.
          replace(yearAligned, 'month').
          replace('group=week&', '');
      let hrefWeek = href.
          replace(yearAligned, 'week').
          replace('group=week&', '');

      const li = $('<li></li>');
      li.append(`<a href="${href}">${name}</a>`);

      // ссылки на разные периоды для метрики
      if (reportsLinks[name].includes('metrika.yandex.ru')) {
        li.append(`<a class="link_secondary" href="${href}">год</a>`);
        li.append(`<a class="link_secondary" href="${hrefMonth}">месяц</a>`);
        li.append(`<a class="link_secondary" href="${hrefWeek}">неделя</a>`);
      }

      reports.append(li);
    }

    siteBrief = `${counterName}, ${counterId}, <a target="_blank" href="https://${counterName}">link</a>`;
  }
  $('#metrika-reports').replaceWith(reports);

  $('#current-site-brief').html(siteBrief);

  // bookmarks
  if(counterId != 0) {
    const fav = $('<a class="bookmark__toggle" href="javascript:"></a>');
    fav.text(isBookmarked(counterId) ? 'remove bookmark' : 'add bookmark');
    $('#current-site-brief').append(fav);

    fav.on('click', function() {
      const isAdd = !isBookmarked(counterId);
      addToBookmarks(counterId, isAdd);
      fav.text(isBookmarked(counterId) ? 'remove bookmark' : 'add bookmark');
    });
  }
}

function addToBookmarks(counterId, isAdd = true) {
  const bookmarks = getBookmarks();

  // add
  if (isAdd && !bookmarks[counterId]) bookmarks[counterId] = true;

  // remove
  if (!isAdd && bookmarks[counterId]) delete(bookmarks[counterId]);

  setBookmarks(bookmarks);
  drawBookmarks();
}

function isBookmarked(counterId) {
  return !!getBookmarks()[counterId];
}

function getBookmarks() {
  return JSON.parse(window.localStorage.bookmarks || '{}');
}

function setBookmarks(bookmarks) {
  window.localStorage.bookmarks = JSON.stringify(bookmarks);
}

// рисуются только те, что есть в списке
function drawBookmarks() {
  const ul = $('<ul></ul>');
  $('#counter_id option').each(function() {
    const counterId = $(this).val();
    const text = $(this).text();
    if (isBookmarked(counterId)) {
      const a = $(`<a href="javascript:" data-counter-id="${counterId}" class="bookmark__link">${text}</a>`);
      a.on('click', function() {
        const counterId = $(this).data('counterId');
        $('#counter_id').val(counterId).trigger('change');
      })
      ul.append($('<li></li>').append(a));
    }
  });
  $('#bookmarks').html(ul);
}