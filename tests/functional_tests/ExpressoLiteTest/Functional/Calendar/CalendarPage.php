<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite Calendar module main screen
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Calendar;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class CalendarPage extends GenericPage
{
    /**
     * Clicks on the Email link on the left of the screen
     * and waits for the Email window to be displayed
     */
    public function clickEmail()
    {
        $this->byCssSelector('.Layout_iconEmail')->click();
        $this->testCase->waitForAjaxAndAnimations();
    }

    /**
     * Checks if the Calendar container was displayed
     *
     * @return boolean
     */
    public function hasCalendarScreenListed()
    {
        return $this->isElementPresent('.Month_container');
    }
}
