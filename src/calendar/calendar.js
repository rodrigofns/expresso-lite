/*!
 * Expresso Lite
 * Main script of calendar module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

require.config({
    baseUrl: '..',
    paths: { jquery: 'common-js/jquery.min' }
});

require(['jquery',
    'common-js/App',
    'common-js/UrlStack',
    'common-js/Layout',
    'calendar/DateCalc',
    'calendar/Events',
    'calendar/WidgetMonth',
    'calendar/WidgetWeek'
],
function($, App, UrlStack, Layout, DateCalc, Events, WidgetMonth, WidgetWeek) {
window.Cache = {
    events: null, // Events object
    viewMonth: null, // WidgetMonth object
    viewWeek: null // WidgetWeek object
};

$(document).ready(function() {
    // Initialize page objects.
    Cache.layout = new Layout({
        userMail: App.GetUserInfo('mailAddress'),
        $menu: $('#leftColumn'),
        $content: $('#bigBody')
    });
    Cache.events = new Events();
    Cache.viewMonth = new WidgetMonth({ events:Cache.events, $elem: $('#middleBody') });
    Cache.viewWeek = new WidgetWeek({ events:Cache.events, $elem: $('#middleBody') });

    // Some initial work.
    UrlStack.keepClean();

    // Load templates of widgets.
    $.when(
        Cache.layout.load(),
        Cache.viewMonth.load(),
        Cache.viewWeek.load()
    ).done(function() {
        Cache.layout.setLeftMenuVisibleOnPhone(true).done(function() {
            $('#renderMonth').trigger('click'); // full month is selected by default
        });

        // Setup events.
        Cache.layout
            .onKeepAlive(function() { })
            .onSearch(function() { }); // when user performs a search
        Cache.viewMonth.onMonthChanged(UpdateCurrentMonthName);
        Cache.viewWeek.onWeekChanged(UpdateCurrentWeekName);
        $('#renderOptions li').on('click', ChangeRenderOption);
    });
});

function UpdateCurrentMonthName() {
    var curMonth = Cache.viewMonth.getCurMonth();
    Cache.layout.setTitle(
        DateCalc.monthName(curMonth.getMonth()) + ', ' +
        curMonth.getFullYear()
    );
}

function UpdateCurrentWeekName() {
    var curWeek = Cache.viewWeek.getCurWeek();
    var saturday = DateCalc.saturdayOfWeek(curWeek);
    if (curWeek.getMonth() === saturday.getMonth()) {
        Cache.layout.setTitle(
            curWeek.getDate() + ' - ' +
            saturday.getDate() + ' ' +
            DateCalc.monthName(curWeek.getMonth()) + ', ' +
            saturday.getFullYear()
        );
    } else {
        Cache.layout.setTitle(
            curWeek.getDate() + ' ' +
            DateCalc.monthName(curWeek.getMonth()).substr(0, 3) + ' - ' +
            saturday.getDate() + ' ' +
            DateCalc.monthName(saturday.getMonth()).substr(0, 3) + ', ' +
            saturday.getFullYear()
        );
    }
}

function ChangeRenderOption() {
    var $li = $(this);
    $('#renderOptions li').removeClass('renderOptionCurrent'); // remove from all LI
    $li.addClass('renderOptionCurrent');
    Cache.layout.setLeftMenuVisibleOnPhone(false).done(function() {
        if ($li.attr('id') === 'renderMonth') {
            Cache.viewWeek.hide();
            Cache.viewMonth.show(Cache.viewWeek.getCurWeek()).done(function() {
                UpdateCurrentMonthName();
            });
        } else if ($li.attr('id') === 'renderWeek') {
            var curMonth = Cache.viewMonth.getCurMonth();
            if (DateCalc.isSameMonth(curMonth, DateCalc.today())) {
                curMonth = DateCalc.today();
            }
            Cache.viewMonth.hide();
            Cache.viewWeek.show(curMonth).done(function() {
                UpdateCurrentWeekName();
            });
        }
    });
}
});