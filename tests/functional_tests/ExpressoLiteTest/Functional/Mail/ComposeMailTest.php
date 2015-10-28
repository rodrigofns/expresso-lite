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
     * Input data:
     *
     * - mail.recipient: a valid e-mail address to be used as recipient
     * - mail.subject: subject for the test e-mail (will be suffixed with the test id)
     * - mail.content: content for the test e-mail (will be suffixed with the test id)
     */
    public function testSendMail()
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

        $this->assertFalse($widgetCompose->isDisplayed(), 'Compose Window should have been closed, but it is still visible');

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertNotNull($headlinesEntry, "A mail with subject $MAIL_SUBJECT was sent, but it could not be found on Sent folder");

        $headlinesEntry->click();
        $this->waitForAjaxToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $this->assertEquals($MAIL_SUBJECT, $widgetMessages->getHeader(), 'The header in the right body header does not match the expected mail subject: ' . $MAIL_SUBJECT);

        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $this->assertContains($MAIL_CONTENT, $messageUnit->getContent(), 'The message content differs from the expected');
    }

    /**
     * During e-mail composition, checks if the badges generated for the recipients are correct.
     * Also checks if the list of recipients match what was originally typed
     *
     * Input data:
     *
     * - mail.recipients: several valid e-mail addresses to be used as recipients
     * - badges: expected texts for the badges generated for each recipient
     * - mail.subject: subject for the test e-mail (will be suffixed with the test id)
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

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($MAIL_RECIPIENTS, $messageUnit->getToAddresses(), 'Could not find one of the recipients in the message');
    }

    /**
     * Checks if an e-mail marked with the "Important" flag is being sent and displayed correctly
     *
     * Input data:
     *
     * - mail.recipient: valid e-mail address to be used as recipient
     * - mail.subject: subject for the test e-mail (will be suffixed with the test id)
     */
    public function testSendImportant()
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
        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);

        $this->assertTrue($headlinesEntry->hasImportantIcon(), 'Headline should have been listed as important, but it was not');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertTrue($messageUnit->hasImportantIcon(), 'The message details should show the important icon, but did not');
    }

    /**
     * Checks if the system is validating correctly an attempt to send an e-mail without recipients
     *
     * Input data:
     *
     * - mail.subject: subject for the test e-mail (will be suffixed with the test id)
     */
    public function testNoRecipient()
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
     * Input data:
     *
     * - mail.recipient: valid e-mail address to be used as recipient
     */
    public function testNoSubject()
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
     * Input data:
     *
     * - initial.mail.recipients: initial list of valid e-mails to be used as recipients (the last one will be edited)
     * - initial.badges: list with the expected texts for the badges generated for each one of the initial recipients, BEFORE edition
     * - extra.recipient: an additional valid e-mail address that will replace the last e-mail of initial.mail.recipients
     * - final.badges: ist with the expected texts for the badges generated for each one of the recipients AFTER edition of the last
     * - final.mail.recipients: list of the expected e-mail AFTER the edition of the last
     * - mail.subject: subject for the test e-mail (will be suffixed with the test id)
     */
    public function testEditBadge()
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

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($FINAL_MAIL_RECIPIENTS, $messageUnit->getToAddresses(), 'Could not find one of the recipients in the message');
    }

    /**
     * - During e-mail composition, checks the screen behavior while deleting on of its recipients by clicking on the badge
     *
     * Input data:
     *
     * - initial.mail.recipients: initial list of valid e-mails to be used as recipients (all, except the last, will be removed)
     * - initial.badges: list with the expected texts for the badges generated for each one of the initial recipients, BEFORE the deletions
     * - final.badge: Expected text for the only displayed badge AFTER the deletions
     * - final.mail.recipient: Expected e-mail address to be used as recipient AFTER the deletions
     * - mail.subject: subject for the test e-mail (will be suffixed with the test id)
     */
    public function testDeleteBadge()
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

        $mailPage->clickOnFolderByName('Enviados');

        $headlinesEntry = $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);
        $this->waitForAjaxToComplete();

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals(array($FINAL_MAIL_RECIPIENT), $messageUnit->getToAddresses(), 'Recipients in mail do not match what was expected');
    }
}

