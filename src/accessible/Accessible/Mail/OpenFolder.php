<?php
/**
 * Expresso Lite Accessible
 * Shows all folders for the user to choose one.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Edgar de Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\TineSessionRepository;
use ExpressoLite\Backend\LiteRequestProcessor;

class OpenFolder extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $response = $liteRequestProcessor->executeRequest('SearchFolders', (object) array(
            'recursive' => true
        ));

        $folders = $this->flatFolderTree($response);
        TineSessionRepository::getTineSession()->setAttribute('folders', $folders);

        $this->showTemplate('OpenFolderTemplate', (object) array(
            'folders' => $folders,
            'folderId' => $params->folderId,
            'folderName' => $params->curFolderName,
            'lnkRefreshFolder' => $this->makeUrl('Mail.Main', array(
                'folderName' => $params->curFolderName,
                'folderId' =>  $params->folderId,
                'page' => $params->page
            )),
        ));
    }

    /**
     * Flat folder tree
     *
     * @param array $arrFolders
     * @param string $parents
     * @return array of folder tree
     */
    private function flatFolderTree($arrFolders, $parents = '')
    {
        $retFolders = array();
        foreach ($arrFolders as $fol) {
            $writtenParents = ($parents === '') ? '' : $parents . ' / ';

            $retFolders[] = (object) array(
                'id' => $fol->id,
                'lnkOpenFolder' => $this->makeUrl('Mail.Main', array(
                    'folderId' => $fol->id,
                )),
                'title' => 'Abrir pasta ' . $fol->localName .', contém ' . $fol->totalMails . ' emails' . ' e ' . $fol->unreadMails . ' emails não lido',
                'localName' => $writtenParents . $fol->localName,
                'globalName' => $fol->globalName,
                'totalMails' => $fol->totalMails,
                'unreadMail' => $fol->unreadMails
            );
            if (count($fol->subfolders) > 0) {
                $retFolders = array_merge($retFolders, $this->flatFolderTree($fol->subfolders, $writtenParents . $fol->localName));
            }
        }
        return $retFolders;
    }
}
