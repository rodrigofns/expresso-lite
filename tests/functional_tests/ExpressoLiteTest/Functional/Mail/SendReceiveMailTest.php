<?php
/**
 * Expresso Lite
 * Test case that checks the behavior sending and receiving e-mails.
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;
use ExpressoLiteTest\Functional\Login\LoginPage;

class SendReceiveMailTest extends ExpressoLiteTest
{
    /**
     * Tests sending and receiving a simple e-mail, checking if the
     * data received by the recipient matches the originally sent data
     *
     * Input data:
     *
     * - sender.login: sender's login
     * - sender.password: sender's password
     * - sender.mail: sender's e-mail address
     * - sender.name: sender's exhibition name
     * - recipient.login: recipient's login
     * - recipient.password: recipient's password
     * - recipient.mail: recipient's e-mail address
     * - mail.subject: e-mail subject (will be suffixed with the test id)
     * - mail.content: e-mail content (will be suffixed with the test id)
     */
    public function testSendReceiveSimpleMail()
    {
        //load test data
        $SENDER_LOGIN = $this->getTestValue('sender.login');
        $SENDER_PASSWORD = $this->getTestValue('sender.password');
        $SENDER_MAIL = $this->getTestValue('sender.mail');
        $SENDER_NAME = $this->getTestValue('sender.name');
        $RECIPIENT_LOGIN = $this->getTestValue('recipient.login');
        $RECIPIENT_PASSWORD = $this->getTestValue('recipient.password');
        $RECIPIENT_MAIL = $this->getTestValue('recipient.mail');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $MAIL_CONTENT = $this->getTestValue('mail.content');

        //testStart
        $loginPage = new LoginPage($this);
        $loginPage->doLogin($SENDER_LOGIN, $SENDER_PASSWORD);

        $mailPage = new MailPage($this);
        $mailPage->sendMail(array($RECIPIENT_MAIL), $MAIL_SUBJECT, $MAIL_CONTENT);
        $mailPage->clickLogout();

        $loginPage->doLogin($RECIPIENT_LOGIN, $RECIPIENT_PASSWORD);
        $mailPage = new MailPage($this);

        $headlinesEntry = $mailPage->getHeadlinesEntryBySubject($MAIL_SUBJECT);
        $this->assertEquals($SENDER_NAME, $headlinesEntry->getSender() , 'Headline sender name does not match what was expected');

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $this->assertEquals($MAIL_SUBJECT, $widgetMessages->getHeader(), 'The header in the right body header does not match the expected mail subject: ' . $MAIL_SUBJECT);

        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertEquals($SENDER_NAME, $messageUnit->getFromName(), 'Message sender name does not match');
        $this->assertEquals("($SENDER_MAIL)", $messageUnit->getFromMail(), 'Message sender mail does not match');
        $this->assertEquals(array($RECIPIENT_MAIL), $messageUnit->getToAddresses(), 'Message recipient does not match');
        $this->assertContains($MAIL_CONTENT, $messageUnit->getContent(), 'The message content differs from the expected');
    }

    /**
     * Checks the reply e-mail feature. In this test, user 1 sends an e-mail
     * to user 2, who replies it back to user 1
     *
     * CTV3-757
     * http://comunidadeexpresso.serpro.gov.br/testlink/linkto.php?tprojectPrefix=CTV3&item=testcase&id=CTV3-757
     *
     * - user.1.login: user 1's login
     * - user.1.password: user 1's password
     * - user.1.mail: user 1's e-mail address
     * - user.1.name: user 1's display name
     * - user.2.login: user 2's login
     * - user.2.password: user 2's password
     * - user.2.mail: user 2's e-mail address
     * - mail.subject: e-mail subject (will be suffixed with the test id)
     * - original.mail.content: content of the first e-mail (will be suffixed with the test id)
     * - reply.mail.content: content added to the reply (will be suffixed with the test id)
     */
    public function test_CTV3_757_SendReceiveReplySimpleMail()
    {
        //load test data
        $USER_1_LOGIN = $this->getTestValue('user.1.login');
        $USER_1_PASSWORD = $this->getTestValue('user.1.password');
        $USER_1_MAIL = $this->getTestValue('user.1.mail');
        $USER_1_NAME = $this->getTestValue('user.1.name');
        $USER_2_LOGIN = $this->getTestValue('user.2.login');
        $USER_2_PASSWORD = $this->getTestValue('user.2.password');
        $USER_2_MAIL = $this->getTestValue('user.2.mail');
        $MAIL_SUBJECT = $this->getTestValue('mail.subject');
        $ORIGINAL_MAIL_CONTENT = $this->getTestValue('original.mail.content');
        $REPLY_MAIL_CONTENT = $this->getTestValue('reply.mail.content');

        //testStart
        $loginPage = new LoginPage($this);

        $loginPage->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);
        $mailPage = new MailPage($this);

        $mailPage->sendMail(array($USER_2_MAIL), $MAIL_SUBJECT, $ORIGINAL_MAIL_CONTENT);
        $mailPage->clickLogout();

        $loginPage->doLogin($USER_2_LOGIN, $USER_2_PASSWORD);

        $mailPage->clickOnHeadlineBySubject($MAIL_SUBJECT);

        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();
        $messageUnit->clickMenuOptionReply();

        $widgetCompose = $mailPage->getWidgetCompose();

        $REPLY_SUBJECT = 'Re: ' . $MAIL_SUBJECT;

        $this->assertEquals(array($USER_1_NAME), $widgetCompose->getArrayOfCurrentBadges(), 'Reply window did not show the expected recipient');
        $this->assertEquals($REPLY_SUBJECT, $widgetCompose->getSubject() , 'Reply window did not the expected subject');
        $this->assertContains($ORIGINAL_MAIL_CONTENT, $widgetCompose->getMessageBodyText());

        $widgetCompose->typeMessageBodyBeforeSignature($REPLY_MAIL_CONTENT);
        $widgetCompose->clickSendMailButton();
        $this->waitForAjaxAndAnimationsToComplete();
        $mailPage->clickLayoutBackButton();
        $this->waitForAjaxAndAnimationsToComplete();

        $mailPage->clickLogout();
        $loginPage->doLogin($USER_1_LOGIN, $USER_1_PASSWORD);

        $mailPage->clickOnHeadlineBySubject($REPLY_SUBJECT);
        $widgetMessages = $mailPage->getWidgetMessages();
        $messageUnit = $widgetMessages->getSingleMessageUnitInConversation();

        $this->assertContains($REPLY_MAIL_CONTENT, $messageUnit->getContent(), 'The replied message content was not found in the reply body');
        $this->assertTrue($messageUnit->hasShowQuoteButton(), 'The replied message did not show the Show Quote button');

        $messageUnit->clickShowQuoteButton();

        $this->assertContains($ORIGINAL_MAIL_CONTENT, $messageUnit->getQuoteText(), 'The original message content was not found in the mail quote section');
    }
}
