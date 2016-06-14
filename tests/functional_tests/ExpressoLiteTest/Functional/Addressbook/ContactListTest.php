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
        $USER_LOGIN = $this->getGlobalValue('user.1.login');
        $USER_PASSWORD = $this->getGlobalValue('user.1.password');
        $CONTACT_1_EMAIL = $this->getTestValue('contact.1.mail');
        $CONTACT_1_NAME = $this->getTestValue('contact.1.name');
        $CONTACT_2_EMAIL = $this->getTestValue('contact.2.mail');
        $CONTACT_2_NAME = $this->getTestValue('contact.2.name');
        $LETTER_1 = $this->getTestValue('letter.1');
        $LETTER_2 = $this->getTestValue('letter.2');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($USER_LOGIN, $USER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($CONTACT_1_EMAIL, $CONTACT_2_EMAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickAddressbook();
        $addressbookPage = new AddressbookPage($this);

        $addressbookPage->clickPersonalCatalog();
        $this->waitForAjaxAndAnimations();
        $this->assertTrue($addressbookPage->hasLetterSeparator($LETTER_1), "Letter separator '$LETTER_1' should have been displayed, but it was not");
        $this->assertTrue($addressbookPage->hasLetterSeparator($LETTER_2), "Letter separator '$LETTER_2' should have been displayed, but it was not");

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_1_NAME);

        $this->assertEquals($CONTACT_1_NAME, $contactListItem->getNameFromContact(),
                "There is not entry catalog name '$CONTACT_1_NAME', the recipient name is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByName($CONTACT_2_NAME);

        $this->assertEquals($CONTACT_2_NAME, $contactListItem->getNameFromContact(),
                "There is not entry catalog name '$CONTACT_2_NAME', the recipient name is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByEmail($CONTACT_1_EMAIL);

        $this->assertEquals($CONTACT_1_EMAIL, $contactListItem->getEmailFromContact(),
                "There is not entry catalog email '$CONTACT_1_EMAIL', the recipient email is not created in personal catalog ");

        $contactListItem = $addressbookPage->getCatalogEntryByEmail($CONTACT_2_EMAIL);

        $this->assertEquals($CONTACT_2_EMAIL, $contactListItem->getEmailFromContact(),
                "There is not entry catalog email '$CONTACT_2_EMAIL', the recipient email is not created in personal catalog ");
    }
}
