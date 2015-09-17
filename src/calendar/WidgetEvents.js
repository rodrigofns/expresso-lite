/*!
 * Expresso Lite
 * Widget to render a sequence of events with details.
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
function($, App, DateCalc) {
App.LoadCss('calendar/WidgetEvents.css');
return function(options) {
    var userOpts = $.extend({
        events: null, // Events cache object
        $elem: null // jQuery object for the target DIV
    }, options);

    var THIS   = this;
    var $panel = null;

    THIS.load = function() {
        return $('#Events_template').length ? // load once
            $.Deferred().resolve().promise() :
            App.LoadTemplate('WidgetEvents.html');
    };

    THIS.clear = function() {
        $panel.find('.Events_dayTitle').text('');
        $panel.find('.Events_canvas .Events_unit').remove();
        return THIS;
    };

    THIS.render = function(events) {
        var defer = $.Deferred();
        if ($panel === null) {
            $panel = $('#Events_template .Events_panel').clone();
            $panel.appendTo(userOpts.$elem);
            _SetEvents();
        }
        THIS.clear();
        _FormatHeader(events);
        var $units = [];
        for (var i = 0; i < events.length; ++i) {
            $units.push(_BuildUnit(events[i]));
        }
        $panel.find('.Events_canvas')
            .append($units)
            .hide()
            .fadeIn(250, function() { defer.resolve(); });
        return defer.promise();
    };

    function _SetEvents() {
        $panel.on('click', '.Events_showPeople', function() {
            $(this).parent().nextAll('.Events_peopleList').slideToggle(200);
        });
    }

    function _FormatHeader(events) {
        var evCount = events.length > 1 ? ' ('+events.length+')' : '';
        $panel.find('.Events_dayTitle').text(
            DateCalc.makeWeekDayMonthYearStr(events[0].from) + evCount
        );
    }

    function _BuildUnit(event) {
        var $unit = $('#Events_template .Events_unit').clone();
        $unit.find('.Events_summary').css('color', event.color);
        $unit.find('.Events_summary .Events_time').text(DateCalc.makeHourMinuteStr(event.from));
        $unit.find('.Events_summary .Events_text').text(event.summary);
        $unit.find('.Events_summary .Events_userFlag').append(_BuildStatusIcon(event));

        $unit.find('.Events_description .Events_text').html(event.description.replace(/\n/g, '<br />'));
        $unit.find('.Events_when .Events_text').text(
            DateCalc.makeHourMinuteStr(event.from) +
            ' - ' +
            DateCalc.makeHourMinuteStr(event.until)
        );
        $unit.find('.Events_location .Events_text').text(event.location);
        $unit.find('.Events_organizer .Events_text').text(event.organizer.name);
        $unit.find('.Events_personOrg').text(_FormatUserOrg(event.organizer));

        var $btnShowPeople = $unit.find('.Events_people .Events_showPeople');
        $btnShowPeople.val($btnShowPeople.val() +' ('+event.attendees.length+')');

        $unit.find('.Events_people .Events_peopleList').append(_BuildPeople(event)).hide();
        return $unit;
    }

    function _BuildPeople(event) {
        var $pps = [];
        for (var i = 0; i < event.attendees.length; ++i) {
            $pp = $('#Events_template .Events_person').clone();
            $pp.find('.Events_personFlag').append(_BuildStatusIcon(event.attendees[i]));
            $pp.find('.Events_personName').text(event.attendees[i].name);
            $pp.find('.Events_personOrg').text(_FormatUserOrg(event.attendees[i]));
            $pps.push($pp);
        }
        return $pps;
    }

    function _BuildStatusIcon(attendee) {
        var icoSt = null;

        switch (attendee.status) {
            case 'NEEDS-ACTION': icoSt = '.Events_icoWaiting'; break;
            case 'ACCEPTED':     icoSt = '.Events_icoAccepted'; break;
            case 'DECLINED':     icoSt = '.Events_icoDeclined';
        }

        return $('#Events_template '+icoSt).clone();
    }

    function _FormatUserOrg(user) {
        if (!user.orgUnit && !user.region) {
            return '';
        } else if (!user.orgUnit) {
            return user.region;
        } else if (!user.region) {
            return user.orgUnit;
        } else {
            return user.orgUnit+', '+user.region;
        }
    }
};
});
