<?php
/**
 * Expresso Lite
 * A Page Object that represents a single message unit displayed
 * in the current conversation of a WidgetMessages
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class MessageUnit extends GenericPage
{
    /**
     * @var MailPage The mail page to which this object belongs
     */
    private $mailPage;

    /**
     * Creates a new MessageUnit object
     *
     * @param MailPage $mailPage The mail page to which this object belongs
     * @param unknown $div The main div that contains the message unit
     */
    public function __construct(MailPage $mailPage, $div)
    {
        parent::__construct($mailPage->getTestCase(), $div);
        $this->mailPage = $mailPage;
    }

    /**
     * Returns the text of the message content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->byCssSelector('.Messages_body')->text();
    }

    /**
     * Returns an array of strings with all addresses in the To field
     *
     *  @return array
     */
    public function getToAddresses()
    {
        return explode(', ', $this->byCssSelector('.Messages_addrTo')->text());
    }

    /**
     * Checks if the Important icon is displayed in this message unit
     *
     * @return boolean
     */
    public function hasImportantIcon()
    {
        return $this->isElementPresent('.icoImportant');
    }

    /**
     * Returns the name of the sender contained in the From field
     *
     * @return string
     */
    public function getFromName()
    {
        return $this->byCssSelector('.Messages_fromName')->text();
    }

    /**
     * Returns the e-mail of the sender contained in the From field
     *
     * @return string
     */
    public function getFromMail()
    {
        return $this->byCssSelector('.Messages_fromMail')->text();
    }

    /**
     * Moves the mouse over the message unit dropdown menu to make it show
     * the available options
     */
    public function moveMouseToDropdownMenu()
    {
        $this->testCase->moveto($this->byCssSelector('.Messages_dropdown'));
    }

    /**
     * Returns an array of DOM elements that represent each option available
     * in the currently visible context menu
     *
     *  @return array
     */
    private function getContextMenuItems()
    {
        return $this->mailPage->byCssSelectorMultiple('.ContextMenu_liOption');
    }

    /**
     * Opens the dropdown context menu and click in one the options inside it
     *
     * @param string $itemText The text of the item to be clicked
     *
     * @throws \Exception If there is no option with the specified text
     */
    private function clickOnMenuItemByText($itemText)
    {
        $this->moveMouseToDropdownMenu();
        foreach ($this->getContextMenuItems() as $menuItem) {
            if ($menuItem->text() == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("No menu item with text $itemText was found");
    }

    /**
     * Opens the dropdown context menu and clicks in the Answer option
     */
    public function clickMenuOptionReply()
    {
        $this->clickOnMenuItemByText("Responder");
        usleep(500000); //TODO: FIX this
    }

    /**
     * Checks if the message unit displays a Show Quote button
     *
     * @return boolean
     */
    public function hasShowQuoteButton()
    {
        return $this->isElementPresent('.Messages_showQuote');
    }

    /**
     * Clicks on the Show Quote button contained in the message unit
     */
    public function clickShowQuoteButton()
    {
        $this->byCssSelector('.Messages_showQuote')->click();
        usleep(500000); //TODO: Fix this
    }

    /**
     * Returns the text of the quoted message
     *
     * @return string
     */
    public function getQuoteText()
    {
        return $this->byCssSelector('.Messages_quote')->text();
    }
}
