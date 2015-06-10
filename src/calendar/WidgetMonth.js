/*!
 * Expresso Lite
 * Widget to render a full month.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

define(['jquery',
    'common-js/App',
    'calendar/DateCalc'
],
function($, App, DateCalc, Events) {
App.LoadCss('calendar/WidgetMonth.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null, // jQuery object for the target DIV
        animationTime: 250
    }, options);

    var THIS             = this;
    var $templateView    = null; // jQuery object with our HTML template
    var curDate          = DateCalc.firstOfMonth(DateCalc.today()); // month currently displayed
    var onMonthChangedCB = null; // user callback

    THIS.load = function() {
        return $('#Month_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.LoadTemplate('WidgetMonth.html');
    };

    THIS.hide = function() {
        if ($templateView !== null) {
            $templateView.hide();
        }
        return THIS;
    };

    THIS.show = function(when) {
        curDate = DateCalc.firstOfMonth(when);
        if ($templateView === null) {
            $templateView = $('#Month_template .Month_container').clone();
            $templateView.appendTo(userOpts.$elem).hide();
            _SetWidgetEvents();
        }
        return _LoadEventsOfCurMonth();
    };

    THIS.getCurMonth = function() {
        return curDate; // 1st day of month, at 12:00:00
    };

    THIS.onMonthChanged = function(callback) {
        onMonthChangedCB = callback; // onMonthChanged()
        return THIS;
    };

    function _SetWidgetEvents() {
        $templateView.find('.Month_prev').on('click', function() {
            $(this).blur();
            curDate = DateCalc.prevMonth(curDate);
            _LoadEventsOfCurMonth().done(function() {
                if (onMonthChangedCB !== null) {
                    onMonthChangedCB(); // invoke user callback
                }
            });
        });

        $templateView.find('.Month_next').on('click', function() {
            $(this).blur();
            curDate = DateCalc.nextMonth(curDate);
            _LoadEventsOfCurMonth().done(function() {
                if (onMonthChangedCB !== null) {
                    onMonthChangedCB(); // invoke user callback
                }
            });
        });
    }

    function _LoadEventsOfCurMonth() {
        var defer = $.Deferred();
        var $loading = $('#Month_template .Month_loading').clone();
        $templateView.hide().after($loading);
        userOpts.events.loadMonth(curDate).done(function() {
            _RenderCells();
            $loading.remove();
            $templateView.fadeIn(userOpts.animationTime, function() {
                defer.resolve();
            });
        }).fail(function() {
            defer.reject();
        });
        return defer.promise();
    }

    function _RenderCells() {
        var $divMonthCanvas = $templateView.find('.Month_canvas');
        var numWeeks = DateCalc.weeksInMonth(curDate);
        var runDate = DateCalc.sundayOfWeek(curDate);
        var dateToday = DateCalc.today();
        $divMonthCanvas.empty();

        for (var w = 0; w < numWeeks; ++w) {
            var $week = $('#Month_template .Month_week').clone();
            for (var d = 0; d < 7; ++d) {
                var $day = $('#Month_template .Month_day').clone();
                if (runDate.getMonth() !== curDate.getMonth()) {
                    $day.addClass('Month_dayOutside');
                }
                if (DateCalc.isSameDay(runDate, dateToday)) {
                    $day.addClass('Month_dayToday');
                }
                $day.find('.Month_dayDisplay').text(runDate.getDate());
                $day.data('date', DateCalc.clone(runDate)); // store a date object for this day
                _FillEvents(runDate, $day);
                runDate.setDate(runDate.getDate() + 1); // advance 1 day
                $day.appendTo($week);
            }
            $week.addClass('Month_'+numWeeks+'weeks'); // specific CSS style to define row height
            $divMonthCanvas.append($week);
        }
    }

    function _FillEvents(when, $day) {
        var events = userOpts.events.inDay(when);
        for (var i = 0; i < events.length; ++i) {
            var $box = $('#Month_template .Month_event').clone();
            $box.css('color', events[i].color);
            $box.find('.Month_eventHour').text(DateCalc.makeHourMinuteStr(events[i].from));
            $box.find('.Month_eventName').text(events[i].summary);
            $box.attr('title', events[i].summary);
            $day.find('.Month_dayContent').append($box);
        }
        $day.data('events', events); // store events array for this day
    }
};
});
