<?php
/**
 * Expresso Lite
 * This tests verify the contact list in contact's module
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Addressbook;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Login\LoginPage;
use ExpressoLiteTest\Functional\Mail\MailPage;

class ContactListTest extends ExpressoLiteTest
{
    /*
     * Check if personal catalog has Letter Separator and entry cards with
     * Names and email address;
     */
    public function test_Ctv3_905_List_Personal()
    {
        //load test data
        $SENDER_LOGIN = $this->getTestValue('login');
        $SENDER_PASSWORD = $this->getTestValue('password');
        $USER_2_EMAIL = $this->getTestValue('user.2.mail');
        $USER_2_NAME = $this->getTestValue('user.2.name');
        $USER_3_EMAIL = $this->getTestValue('user.3.mail');
        $USER_3_NAME = $this->getTestValue('user.3.name');
        $LETTER3 = $this->getTestValue('letter.3');
        $LETTER2 = $this->getTestValue('letter.2');

        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($USER_2_EMAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->sendMail(array($USER_3_EMAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $addressbookPage->clickPersonalCatalog();
        $this->waitForAjaxAndAnimations();
        $this->assertTrue($addressbookPage->hasLetterSeparator($LETTER2), "Letter separator '$LETTER2' should have been displayed, but it was not");
        $this->assertTrue($addressbookPage->hasLetterSeparator($LETTER3), "Letter separator '$LETTER3' should have been displayed, but it was not");

        $contactListItem = $addressbookPage->getCatalogEntryByName($USER_2_NAME);

        $this->assertEquals($USER_2_NAME, $contactListItem->getNameFromContact(),
                "There is not entry catalog name '$USER_2_NAME', the recipient name is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByName($USER_3_NAME);

        $this->assertEquals($USER_3_NAME, $contactListItem->getNameFromContact(),
                "There is not entry catalog name '$USER_3_NAME', the recipient name is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByEmail($USER_2_EMAIL);

        $this->assertEquals($USER_2_EMAIL, $contactListItem->getEmailFromContact(),
                "There is not entry catalog email '$USER_2_EMAIL', the recipient email is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByEmail($USER_3_EMAIL);

        $this->assertEquals($USER_3_EMAIL, $contactListItem->getEmailFromContact(),
                "There is not entry catalog email '$USER_3_EMAIL', the recipient email is not created in personal catalog ");
    }
}