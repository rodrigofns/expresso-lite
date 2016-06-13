<?php
/**
 * Expresso Lite
 * Checks the list of events in the month or in the week
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Calendar;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Mail\MailPage;
use ExpressoLiteTest\Functional\Login\LoginPage;
use ExpressoLiteTest\Functional\Addressbook\AddressbookPage;

class ListEventTest extends ExpressoLiteTest
{
    /*
     * Check if access in the other modules are possible
     *
     * CTV3-1117
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1117;
     */
    public function test_CTV3_1117_Access_other_modules()
    {
        $SENDER_LOGIN = $this->getGlobalValue('user.1.login');
        $SENDER_PASSWORD = $this->getGlobalValue('user.1.password');

        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->clickCalendar();

        $calendar = new CalendarPage($this);

        $this->assertTrue($calendar->hasCalendarScreenListed(), 'The calendar screen is visible, but it is not');
        $calendar->clickEmail();

        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $this->assertTrue($addressbookPage->hasAddressbookScreenListed(), 'The Addressbook screen is visible, but it is not');
        $calendar->clickEmail();

        $this->assertTrue($mailPage->hasEmailScreenListed(), 'The Email screen is visible, but it is not');

        $mailPage->clickLogout();
    }
}