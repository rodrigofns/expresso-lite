<?php
/**
 * Expresso Lite
 * A Page Object that represents a single entry in the headlines list area
 * of the mail module
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Mail;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class HeadlinesEntry extends GenericPage
{
    /**
     * @var MailPage The mail page to which this entry belongs
     */
    private $mailPage;

    /**
     * Creates a new HeadlinesEntry object
     *
     * @param MailPage $mailPage The mail page to which this entry belongs
     * @param unknown $headlinesEntryDiv A reference to the main div of this headline
     */
    public function __construct(MailPage $mailPage, $headlinesEntryDiv)
    {
        parent::__construct($mailPage->getTestCase(), $headlinesEntryDiv);
        $this->mailPage = $mailPage;
    }

    /**
     * Clicks on the headline entry
     */
    public function click()
    {
        $this->rootContext->click();
    }

    /**
     * Returns the subject displyed on this entry
     *
     * @return string The entry subject
     */
    public function getSubject()
    {
        return $this->byCssSelector('.Headlines_subject')->text();
    }

    /**
     * Checks if this HeadlinesEntry displays the important icon
     *
     * @return boolean True if the important icon is displayed in this entry, false otherwise
     */
    public function hasImportantIcon()
    {
        return $this->isElementPresent('.icoImportant');
    }

    /**
     * Returns the sender name displyed on this entry
     *
     * @return string The sender name
     */
    public function getSender()
    {
        return $this->byCssSelector('.Headlines_sender')->text();
    }
}