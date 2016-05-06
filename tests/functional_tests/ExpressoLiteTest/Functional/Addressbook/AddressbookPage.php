<?php
/**
 * Expresso Lite
 * A Page Object that represents Expresso Lite addressbook module main screen
 *
 * @package ExpressoLiteTest\Functional\Mail
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Fatima Tonon <fatima.tonon@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLiteTest\Functional\Addressbook;

use ExpressoLiteTest\Functional\Generic\GenericPage;

class AddressbookPage extends GenericPage
{
    /**
     * Clicks on the Catalogo Pessoal
     */
    public function clickPersonalCatalog()
    {
        $this->clickOnMenuCatalog('Catálogo Pessoal');
    }

    /**
     * Clicks on the Catalogo Pessoal
     */
    public function clickCorporateCatalog()
    {
        $this->clickOnMenuCatalog('Catálogo Corporativo');
    }

    /**
     * Clicks on a menu item within the context menu
     *
     * @param string $itemText The text of the item to be clicked
     */
    private function clickOnMenuCatalog($itemText)
    {
        foreach ($this->getContextMenuItems() as $menuItem) {
            if (trim($menuItem->text()) == $itemText) {
                $menuItem->click();
                return;
            }
        }
        throw new \Exception("Menu item with text $itemText was not found");
    }

    /**
     * Returns an array of <li> elements within the context menu
     *
     * @returns array Array of <li> elements within the context menu
     */
    private function getContextMenuItems()
    {
        return $this->byCssSelectorMultiple('.SimpleMenu_list span');
    }

    /**
     * Returns an array containing all catalog entries being displayed on the screen
     *
     * @return array Array of ContactListItem objects
     */
    public function getArrayOfCatalogEntries()
    {
        $entries = array();
        foreach($this->byCssSelectorMultiple('#WidgetContactList_mainSection > .WidgetContactList_item') as $contactListItemDiv) {
            $entries[] = new ContactListItem($this, $contactListItemDiv);
        }
        return $entries;
    }

    /**
     * Returns a CatalogEntry that represents the item of the Catalog list that,
     * contains a specific name. If there are noone with the specified name,
     * it returns null
     *
     * @param string $name The name to be searched for
     *
     * @return CatalogEntry The Catalog that contains the specified name, null if
     * noone with the specified name was found
     */
    public function getCatalogEntryByName($name)
    {
        foreach($this->byCssSelectorMultiple('#WidgetContactList_mainSection > .WidgetContactList_item') as $contactListItemDiv) {
            $entry = new ContactListItem($this, $contactListItemDiv);
            if ($entry->getNameFromContact() == $name) {
                return $entry;
            }
        }
        return null;
    }
}
