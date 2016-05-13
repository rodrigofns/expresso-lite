<?php
/**
 * Expresso Lite
 * Test case that checks the behavior of e-mail composition. In these tests,
 * we focus on the compose window, but we do not check if the mail is
 * really arriving its destination.
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\SingleLoginTest;

class ComposeMailTest extends SingleLoginTest
{
    /**
     * Overwrites superclass getTestUrl to indicate that this module should
     * always redirect to the main mail module before each test.
     *
     * @see \ExpressoLiteTest\Functional\Generic\SingleLoginTest::getTestUrl()
     */
    public function getTestUrl()
    {
        return LITE_URL . '/mail';
    }

    /**
     * Tests sending a simple e-mail, checking if the e-mail composition screen opens and
     * close as expected. Also checks if the sent e-mail data match what was originally typed
     *
     *   CTV3-753
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-753
     */
    public function test_CTV3_753_Send_Mail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getTestValue('mail.recipient');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();
        $this->assertTrue($widgetCompose->isDisplayed(), 'Compose Window should be displayed, but it is not');

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($MAIL_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($MAIL_SUBJECT);

        $widgetCompose->typeMessageBodyBeforeSignature($MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry, "A mail with subject $MAIL_SUBJECT was sent, but it could not be found on Sent folder");

        $headlinesEntry->click();
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $this->assertEquals($MAIL_SUBJECT, $widgetMessages->getHeader(), 'The header in the right body header does not match the expected mail subject: ' . $MAIL_SUBJECT);

        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $this->assertContains($MAIL_CONTENT, $messageUnit->getContent(), 'The message content differs from the expected');
    }

    /**
     * During e-mail composition, checks if the badges generated for the recipients are correct.
     * Also checks if the list of recipients match what was originally typed
     */
    public function testBadges()
    {
        $MAIL_RECIPIENTS = $this->getTestValue('mail.recipients');
        $BADGES = $this->getTestValue('badges');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        $mailPage = new MailPage($this);

        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        foreach ($MAIL_RECIPIENTS as $recipient) {
            $widgetCompose->type($recipient);
            $widgetCompose->typeEnter();
        }

        $this->assertEquals($BADGES, $widgetCompose->getArrayOfCurrentBadges(), 'The displayed badges do not match what was expected');

        $widgetCompose->typeSubject($MAIL_SUBJECT);
        $widgetCompose->typeMessageBodyBeforeSignature('placeholder');

        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($MAIL_RECIPIENTS, $messageUnit->getToAddresses(), 'Could not find one of the recipients in the message');
    }

    /**
     * Tests sending a simple e-mail, checking if the e-mail composition screen opens and
     * close as expected. Also checks the search in the catalog.
     *
     *   CTV3-759
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-759
     */
    public function test_CTV3_759_Search_Recipient()
    {
        $mailPage = new MailPage($this);
        //load test data
        $MAIL_RECIPIENT = $this->getTestValue('mail.recipient');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');
        $PART_NAME_SEARCH = $this->getTestValue('part.name.search');
        $NAME_SEARCH = $this->getTestValue('name.search');
        $NAME2_SEARCH = $this->getTestValue('name.2.search');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();
        $this->assertTrue($widgetCompose->isDisplayed(), 'Compose Window should be displayed, but it is not');

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($PART_NAME_SEARCH);
        $this->waitForAjaxAndAnimationsToComplete();

        $contactsAutoComplete = $mailPage->getContactsAutoComplete();
        $this->assertTrue($contactsAutoComplete->hasContactsListed(), 'The contacts list is visible, but it is not');
        $contactsAutoComplete->clickOnContactsListByName($NAME_SEARCH);

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($PART_NAME_SEARCH);
        $this->waitForAjaxAndAnimationsToComplete();
        $contactsAutoComplete->clickMoreResults();

        $this->assertTrue($contactsAutoComplete->hasContactscounted(), 'The counter is visible, but it is not');

        $contactsAutoComplete->clickOnContactsListByName($NAME2_SEARCH);
        $widgetCompose->typeSubject($MAIL_SUBJECT);
        $widgetCompose->typeMessageBodyBeforeSignature($MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry, "A mail with subject $MAIL_SUBJECT was sent, but it could not be found on Sent folder");

        $headlinesEntry->click();
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $this->assertEquals($MAIL_SUBJECT, $widgetMessages->getHeader(), 'The header in the right body header does not match the expected mail subject: ' . $MAIL_SUBJECT);

        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $this->assertContains($MAIL_CONTENT, $messageUnit->getContent(), 'The message content differs from the expected');
    }

    /**
     * Checks if an e-mail marked with the "Important" flag is being sent and displayed correctly
     *
     * CTV3-890
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-890
     */
    public function test_CTV3_890_Send_Important()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getTestValue('mail.recipient');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($MAIL_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($MAIL_SUBJECT);
        $widgetCompose->typeMessageBodyBeforeSignature('placeholder');

        $widgetCompose->clickImportantRadio();
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();
        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertTrue($headlinesEntry->hasImportantIcon(), 'Headline should have been listed as important, but it was not');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertTrue($messageUnit->hasImportantIcon(), 'The message details should show the important icon, but did not');
    }

    /**
     * Checks if the system is validating correctly an attempt to send an e-mail without recipients
     *
     *  CTV3-1053
     *  http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1053
     */
    public function test_CTV3_1053_No_Recipient()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->typeSubject($MAIL_SUBJECT);
        $widgetCompose->typeMessageBodyBeforeSignature('placeholder');
        $widgetCompose->clickSendMailButton();
        $this->assertAlertTextEquals(
                'Não há destinatários para o email.',
                'System did not show message indicating missing recipients');

        $this->dismissAlert();
        $mailPage->clickOnFolderByName('Enviados');

        $headline = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertNull($headline, 'There should have been no headlines with this subject, as mail was not sent due to missing recipients');
    }

    /**
     * Checks if the system is validating correctly an attempt to send an e-mail without a subject
     *
     * CTV3-903
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-903
     */
    public function test_CTV3_903_No_Subject()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_RECIPIENT = $this->getTestValue('mail.recipient');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        $widgetCompose->type($MAIL_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeMessageBodyBeforeSignature('placeholder');
        $widgetCompose->clickSendMailButton();

        $this->assertAlertTextEquals(
                'O email está sem assunto.',
                'System did not show the expected message for an e-mail without subject');

        $this->dismissAlert();
    }

    /**
     * During e-mail composition, checks the screen behavior while editing one of the recipients using the BACKSPACE key
     *
     *  CTV3-975
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-975
     */
    public function test_CTV3_975_Edit_Badge()
    {
        $INITAL_MAIL_RECIPIENTS = $this->getTestValue('initial.mail.recipients');
        $INITIAL_BADGES = $this->getTestValue('initial.badges');
        $EXTRA_RECIPIENT = $this->getTestValue('extra.recipient');
        $FINAL_BADGES = $this->getTestValue('final.badges');
        $FINAL_MAIL_RECIPIENTS = $this->getTestValue('final.mail.recipients');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        $mailPage = new MailPage($this);

        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        foreach ($INITAL_MAIL_RECIPIENTS as $recipient) {
            $widgetCompose->type($recipient);
            $widgetCompose->typeEnter();
        }

        $this->assertEquals($INITIAL_BADGES, $widgetCompose->getArrayOfCurrentBadges(), 'The displayed badges do not match what was expected (before backspace)');
        $widgetCompose->typeBackspace();

        $lastExpectedRecipient = array_pop($INITAL_MAIL_RECIPIENTS);

        $this->assertEquals($lastExpectedRecipient, $widgetCompose->getRecipientFieldValue(), 'Pressing backspace should have made recipient field edit the last typed e-mail, but it did not');

        $widgetCompose->clearRecipientField();
        $widgetCompose->type($EXTRA_RECIPIENT);
        $widgetCompose->typeEnter();

        $this->assertEquals($FINAL_BADGES, $widgetCompose->getArrayOfCurrentBadges(), 'The displayed badges do not match what was expected (after backspace)');

        $widgetCompose->typeSubject($MAIL_SUBJECT);
        $widgetCompose->typeMessageBodyBeforeSignature('placeholder');

        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($FINAL_MAIL_RECIPIENTS, $messageUnit->getToAddresses(), 'Could not find one of the recipients in the message');
    }

    /**
     * - During e-mail composition, checks the screen behavior while deleting on of its recipients by clicking on the badge
     *
     *   CTV3-977
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-977
     */
    public function test_CTV3_977_Delete_Badge()
    {
        $INITAL_MAIL_RECIPIENTS = $this->getTestValue('initial.mail.recipients');
        $INITIAL_BADGES = $this->getTestValue('initial.badges');
        $DELETED_BADGES = $this->getTestValue('deleted.badges');
        $FINAL_BADGE = $this->getTestValue('final.badge');
        $FINAL_MAIL_RECIPIENT = $this->getTestValue('final.mail.recipient');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');

        $mailPage = new MailPage($this);

        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();

        $widgetCompose->clickOnRecipientField();
        foreach ($INITAL_MAIL_RECIPIENTS as $recipient) {
            $widgetCompose->type($recipient);
            $widgetCompose->typeEnter();
        }

        $this->assertEquals($INITIAL_BADGES, $widgetCompose->getArrayOfCurrentBadges(), 'The displayed badges do not match what was expected (before deletions)');

        foreach ($DELETED_BADGES as $deletedBadge) {
            $widgetCompose->clickOnBadgeByName($deletedBadge);
        }

        $this->assertEquals(array($FINAL_BADGE), $widgetCompose->getArrayOfCurrentBadges(), 'The displayed badges do not match what was expected (after deletions)');

        $widgetCompose->typeSubject($MAIL_SUBJECT);
        $widgetCompose->typeMessageBodyBeforeSignature('placeholder');

        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals(array($FINAL_MAIL_RECIPIENT), $messageUnit->getToAddresses(), 'Recipients in mail do not match what was expected');
    }

    /**
     * Tests sending a simple e-mail,with bcc and bcc cecking if the e-mail composition
     * screen opens and close as expected. Also checks if the recipients copy receives
     * the e-mail.
     *
     *   CTV3-1051
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1051
     */
    public function test_CTV3_1051_Send_Cc_Mail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_CC_RECIPIENT = $this->getTestValue('mail.cc.recipient');
        $MAIL_SENDER = $this->getTestValue('mail.sender');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();
        $this->assertTrue($widgetCompose->isDisplayed(), 'Compose Window should be displayed, but it is not');

        $widgetCompose->clickOnCcToggleButton();
        $this->waitForAjaxAndAnimationsToComplete();
        $widgetCompose->type($MAIL_CC_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($MAIL_SUBJECT);

        $widgetCompose->typeMessageBodyBeforeSignature($MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');
        $this->waitForAjaxAndAnimationsToComplete();

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry, "A mail with subject $MAIL_SUBJECT was sent, but it could not be found on Sent folder");

        $headlinesEntry->click();
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $this->assertContains('(ninguém)', $messageUnit->getToAddresses(), 'The to address content differs from the expected');
        $this->assertEquals("($MAIL_SENDER)", $messageUnit->getFromMail(), 'Message sender mail does not match');
        $this->assertContains("$MAIL_SENDER", $messageUnit->getCcAddresses(), 'Message cc recipient mail does not match');
    }

    /**
     * Tests sending a simple e-mail,with cc and checking if the e-mail composition
     * screen opens and close as expected. Also checks if the recipients copy receives
     * the e-mail.
     *
     *   CTV3-1052
     *   http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-1052
     */
    public function test_CTV3_1052_Send_Bcc_Mail()
    {
        $mailPage = new MailPage($this);

        //load test data
        $MAIL_BCC_RECIPIENT = $this->getTestValue('mail.bcc.recipient');
        $MAIL_SENDER = $this->getTestValue('mail.sender');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $mailPage->clickWriteEmailButton();

        $widgetCompose = $mailPage->getWidgetCompose();
        $this->assertTrue($widgetCompose->isDisplayed(), 'Compose Window should be displayed, but it is not');

        $widgetCompose->clickOnBccToggleButton();
        $this->waitForAjaxAndAnimationsToComplete();
        $widgetCompose->type($MAIL_BCC_RECIPIENT);
        $widgetCompose->typeEnter();

        $widgetCompose->typeSubject($MAIL_SUBJECT);

        $widgetCompose->typeMessageBodyBeforeSignature($MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');

        $mailPage->clickRefreshButton();
        $this->waitForAjaxAndAnimationsToComplete();
        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertContains('(ninguém)', $messageUnit->getToAddresses(), 'The to address content differs from the expected');
        $this->assertFalse($messageUnit->isBccAddressesDisplayed(), 'Bcc tag should not have been displayed, but it was');

        $mailPage->clickLayoutBackButton();
        $this->waitForAjaxAndAnimationsToComplete();
        $mailPage->clickOnFolderByName('Enviados');
        $this->waitForAjaxAndAnimationsToComplete();

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry, "A mail with subject $MAIL_SUBJECT was sent, but it could not be found on Sent folder");

        $headlinesEntry->click();
        $this->waitForAjaxAndAnimationsToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertContains('(ninguém)', $messageUnit->getToAddresses(), 'The to address content differs from the expected');
        $this->assertEquals("($MAIL_SENDER)", $messageUnit->getFromMail(), 'Message sender mail does not match');
        $this->assertContains("$MAIL_SENDER", $messageUnit->getBccAddresses(), 'Message bcc recipient mail does not match');
    }
}
