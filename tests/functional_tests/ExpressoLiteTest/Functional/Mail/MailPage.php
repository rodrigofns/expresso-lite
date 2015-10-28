<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite mail module main screen
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class MailPage extends GenericPage
{
    /**
     * Clicks on the Write email button on the left of the screen
     * and waits for the compose window to be displayed
     */
    public function clickWriteEmailButton()
    {
        $this->byCssSelector('#btnCompose')->click();
        sleep(1); //TODO: this avoids problems with testNoRecipients, but a more elegant solution should be used
        $this->testCase->waitForAjaxToComplete();
    }

    /**
     * Clicks on a folder contained on the folder tree based on its name
     *
     * @param string $folderName The name of the folder to be clicked
     */
    public function clickOnFolderByName($folderName)
    {
        $folderNames = $this->byCssSelectorMultiple('#foldersArea .Folders_folderName');
        foreach ($folderNames as $folderNameDiv) {
            if ($folderNameDiv->text() == $folderName) {
                $folderNameDiv->click();
                break;
            }
        }
        $this->testCase->waitForAjaxToComplete();
    }

    /**
     * Returns a HeadlineEntry that represents the item of the headlines list that
     * contains a specific subject. If there are no e-mails with the specified subject,
     * it returns nulll .
     *
     * @param string $subject The subject to be searched for
     *
     * @return HeadlinesEntry The headline that contains the specified subject, null if
     * no e-mail with the specified subject was found
     */
    public function getHeadlinesEntryBySubject($subject)
    {
        foreach($this->byCssSelectorMultiple('#headlinesArea > .Headlines_entry') as $headlinesEntryDiv) {
            $entry = new HeadlinesEntry($this, $headlinesEntryDiv);
            if ($entry->getSubject() == $subject) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Clicks on the headline in the headlines list that contains a specified subject
     *
     * @param string $subject The subject to be searched for
     *
     * @throws \Exception If no headlines entry was found
     */
    public function clickOnHeadlineBySubject($subject)
    {
        $headline = $this->getHeadlinesEntryBySubject($subject);
        if ($headline == null) {
            throw new \Exception('Could not find a headline with subject ' . $subject);
        } else {
            $headline->click();
            $this->testCase->waitForAjaxToComplete();
        }
    }

    /**
     * Performs all the steps involved in sending a simple e-mail to one or more recipients.
     * This involves the following steps: 1 - click the Write button, 2 - For each recipient,
     * type its name followed by an ENTER key, 3 - Write the subject, 4 - Write the content
     * of the e-mail, 5 - Click the Send button
     *
     * @param array $recipients An array of strings containing the recipients of the e-mail
     * @param string $subject The subject of the e-mail
     * @param string $content The content to be written in the e-mail
     */
    public function sendMail($recipients, $subject, $content)
    {
        $this->clickWriteEmailButton();
        $widgetCompose = $this->getWidgetCompose();
        $widgetCompose->clickOnRecipientField();

        foreach ($recipients as $recipient) {
            $widgetCompose->type($recipient);
            $widgetCompose->typeEnter();
        }

        $widgetCompose->typeSubject($subject);

        $widgetCompose->typeMessageBodyBeforeSignature($content);
        $widgetCompose->clickSendMailButton();
    }

    /**
     * Clicks the Logout button and wait for the login screen to be displayed
     */
    public function clickLogout()
    {
        $this->byCssSelector('#Layout_logoff')->click();
        $this->testCase->waitForUrl(LITE_URL . '/');
        $this->testCase->waitForAjaxToComplete();
    }

    /**
     * Clicks the Back ( <-- ) button in the top of the screen
     */
    public function clickLayoutBackButton()
    {
        $this->byCssSelector('#Layout_arrowLeft')->click();
    }

    /**
     * Returns a WidgetCompose Page Object that represents the
     * compose window currently being displayed by the mail module
     *
     * @return WidgetCompose
     */
    public function getWidgetCompose()
    {
        return new WidgetCompose(
                $this,
                $this->byCssSelector('body > .Dialog_box')); //'body >' will filter templates out
    }

    /**
     * Returns the WidgetMessages Page Object that represents the message details
     * currently being displayed in the mail module
     *
     * @return WidgetMessages
     */
    public function getWidgetMessages()
    {
        return new WidgetMessages($this, $this->byCssSelector('#rightBody'));
    }
}
