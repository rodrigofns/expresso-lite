<?php
/**
 * Expresso Lite Accessible
 * Event calendar routines.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Core;

use ExpressoLite\Backend\LiteRequestProcessor;
use Accessible\Core\DateUtils;
use \DateTime;

class EventUtils
{
    /**
     * @var EVENTS_CALENDAR_NAME.
     */
    const EVENTS_CALENDAR_NAME = 'Calendário de eventos';

    /**
     * @var EVENTS_NO_SCHEDULED.
     */
    const EVENTS_NO_SCHEDULED = 'Não existem eventos agendados';

    /**
     * @var EVENTS_ONE_SCHEDULED.
     */
    const EVENTS_ONE_SCHEDULED = 'Existe 1 evento agendado';

    /**
     * @var EVENTS_MANY_SCHEDULED.
     */
    const EVENTS_MANY_SCHEDULED = 'eventos agendados';

    /**
     * @var EVENTS_ONE_NOT_OCCURRED.
     */
    const EVENTS_ONE_NOT_OCCURRED = '1 não ocorreu';

    /**
     * @var EVENTS_MANY_NOT_OCCURRED.
     */
    const EVENTS_MANY_NOT_OCCURRED = 'não ocorreram';

    /**
     * @var EVENTS_ONE_NOT_STARTED.
     */
    const EVENTS_ONE_NOT_STARTED = '1 não começou';

    /**
     * @var EVENTS_MANY_NOT_STARTED.
     */
    const EVENTS_MANY_NOT_STARTED = 'não começaram';

    /**
     * @var EVENTS_WITHOUT_SUMMARY.
     */
    const EVENTS_WITHOUT_SUMMARY = 'Não foi definido um assunto deste evento';

    /**
     * @var EVENTS_WITHOUT_SUMMARY.
     */
    const EVENTS_WITHOUT_DESCRIPTION = 'Não foi definida uma descrição deste evento';

    /**
     * @var EVENTS_WITHOUT_SUMMARY.
     */
    const EVENTS_WITHOUT_LOCATION = 'Não foi definido um local para este evento';

    /**
     * This method standardize events date range commonly used in all events
     * calendar routines that needs date range information. Date representation
     * is like in the following format '2016-03-01 00:00'.
     *
     * @param StdClass $dateRange Formatted date range with the month number
     *                            (->monthVal) and year value (->yearVal)
     * @return StdClass An event date range object with 'from' date value (->from)
     *                  and 'until' date value (->until)
     */
    public static function prepareEventsDateRange($dateRange = null)
    {
        if (is_null($dateRange) || (!isset($dateRange->monthVal) && !isset($dateRange->yearVal))){
            // No parameters provided, current year and month as the date range
            $fromVal =  DateUtils::getFirstDayOfThisMonth();
            $untilVal = DateUtils::getLastDayOfThisMonth();
        } else {
            $fromVal =  DateUtils::getFirstDayOfMonth($dateRange->monthVal, $dateRange->yearVal);
            $untilVal = DateUtils::getLastDayOfMonth($dateRange->monthVal, $dateRange->yearVal);
        }
        return (object) array(
            'from' => $fromVal,
            'until' => $untilVal
        );
    }

    /**
     * Given an event listing, formats a string message according to the total
     * count of scheduled events, like 'there are 2 scheduled events' or
     * 'no scheduled events'.
     *
     * @param StdClass $eventListing Event listing
     * @return string Formatted message with the total count of scheduled events
     */
    private static function formatTotalEventScheduled($eventListing)
    {
        $countScheduledEvents = abs(count((array) $eventListing));
        if ($countScheduledEvents === 0) {
            return self::EVENTS_NO_SCHEDULED;
        } else {
           return $countScheduledEvents === 1 ?
               self::EVENTS_ONE_SCHEDULED :
               'Existem ' . $countScheduledEvents .' ' .  self::EVENTS_MANY_SCHEDULED;
        }
    }

    /**
     * Given an event listing, formats a message according the total count of
     * scheduled events that have not occurred yet.
     *
     * @param StdClass $eventListing Event listing
     * @return string Formatted Message with the total count of scheduled events
     *                that have not occurred yet
     */
    private static function formatEventScheduledNotOccurred($eventListing)
    {
        $countEventsNotYetOccurred = 0;
        if (!is_null($eventListing) && count($eventListing) > 0) {
            foreach ($eventListing as $event) {
                if (DateUtils::compareToCurrentDate($event->from)) {
                    $countEventsNotYetOccurred++;
                }
            }
        }

        if ($countEventsNotYetOccurred != 0) {
            return $countEventsNotYetOccurred === 1 ?
                ', sendo que ' . self::EVENTS_ONE_NOT_OCCURRED :
                ', sendo que ' . $countEventsNotYetOccurred . ' ' . self::EVENTS_MANY_NOT_OCCURRED;
        } else {
            return ''; // Any event to occur
        }
    }

    /**
     * Creates a summary of events date range. The summary of entire event listing
     * is like: 'There are seven events scheduled for January 2016 and that 1 did
     * not happen'.
     *
     * @param StdClass $eventListing Event listing
     * @param StdClass $dateRange Event listing
     * @return string The summary of a event date range
     */
    public static function setEventsDateRangeSummary($eventListing, $dateRange)
    {
        return
            self::formatTotalEventScheduled($eventListing) . ' para '
            . DateUtils::getMonthName($dateRange->monthVal) . ' de ' . $dateRange->yearVal
            . self::formatEventScheduledNotOccurred($eventListing) . '.';
    }

    /**
     * Creates a summary of events date range. The summary of today listing is like:
     * 'There are no scheduled events for today, Wednesday, January 20, 2016'.
     *
     * @param StdClass $todayEventListing Event listing
     * @return string The summary of today events date range
     */
    public static function setTodayEventsDateRangeSummary($todayEventListing)
    {
        return
            self::formatTotalEventScheduled($todayEventListing) . ' para ' . DateUtils::TODAY . ', '
            . DateUtils::getCurrentWeekdayName() . ', '
            . DateUtils::getCurrentDay() . ' de '
            . DateUtils::getCurrentMonthName() . ' de '
            . DateUtils::getCurrentYear()
            . self::formatEventScheduledNotOccurred($todayEventListing) . '.';
    }

    /**
     * Check if the event is scheduled to current date.
     *
     * @param string $strTime Information about date and time in the following
     *                        format '2015-12-24 23:59'
     * @return boolean True if event's day, month and year are equals to
     *                 current's day, month and year; false otherwise
     */
    public static function isEventScheduledForToday($strTime)
    {
        $dtEvent = DateUtils::getInfomationAboutDate($strTime);
        return
            $dtEvent->dayVal ===   DateUtils::getCurrentDay() &&
            $dtEvent->monthVal === DateUtils::getCurrentMonthNumber() &&
            $dtEvent->yearVal ===  DateUtils::getCurrentYear();
    }
}
