<?php
/**
 * Expresso Lite
 * Handler for searchEvent calls.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Fabiano Kuss <fabiano.kuss@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Backend\Request;
use \DateTime;
use \DateTimeZone;

class SearchEvents extends LiteRequest
{
    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $start = $this->isParamSet('start') ? $this->param('start') : 0; // pagination
        $limit = $this->isParamSet('limit') ? $this->param('limit') : 50;

        $from = $this->param('from');
        $until = $this->param('until');

        $response = $this->jsonRpc('Calendar.searchEvents', (object) array(
            'filter' => array(
                (object) array( // search on all catalogs
                    'field' => 'period',
                    'operator' => 'within',
                    'value' => array(
                        'from' => $from,
                        'until' => $until
                    )
                )
            ),
            'paging' => (object) array(
                'dir' => 'ASC',
                'limit' => $limit,
                'start' => $start
            )
        ));
        return (object) array(
            'totalCount' => $response->result->totalcount,
            'events' => $this->formatEvents($response->result->results)
        );
    }

    /**
     * Formats an array of Tine raw events stripping away the garbage.
     *
     * @param array $events Raw Tine events.
     *
     * @return array Array of formatted events.
     */
    private function formatEvents(array $events)
    {
        $ret = array();
        foreach ($events as $e) {
            $ret[] = (object) array(
                'id' => $e->id,
                'from' => $this->parseTimeZone($e->dtstart, $e->originator_tz),
                'until' => $this->parseTimeZone($e->dtend, $e->originator_tz),
                'summary' => $e->summary,
                'description' => $e->description,
                'location' => $e->location,
                'color' => $this->getEventColor($e)
            );
        }
        return $ret;
    }

    /**
     * Given a datetime string, formats it according to the given timezone.
     *
     * @param string $strTime String like '2015-08-05 13:50:00'.
     * @param string $strZone PHP DateTimeZone string, like 'America_SaoPaulo'.
     *
     * @return string String with datetime converted to user timezone, like '2015-08-05 13:50:00'.
     */
    private function parseTimeZone($strTime, $strZone)
    {
        $d = new DateTime($strTime, new DateTimeZone($strZone));
        return $d->format('Y-m-d H:i:s');
    }

    /**
     * Get the hexadecimal color code for the given event.
     *
     * @param stdClass $event The event object to retrieve the color.
     *
     * @return string Hexadecimal color code.
     */
    private function getEventColor($event)
    {
        // For some odd reason, "container_id" may be a full object with all
        // information, or just a number without anything useful.
        return is_object($event->container_id) ?
            $event->container_id->color :
            $this->tineSession->getAttribute('Calendar.defaultEventColor');
    }
}
