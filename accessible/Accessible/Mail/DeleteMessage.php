<?php
/**
 * Expresso Lite Accessible
 * Delete an email message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use Accessible\Dispatcher;

class DeleteMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */

    /**
     * @var TRASH_FOLDER Global name of trash folder.
     */
    const TRASH_FOLDER = 'INBOX/Trash';

    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('DeleteMessages', (object) array(
            'messages' => $params->messageId,
            'forever' => $params->isTrashFolder
        ));

        $outMsg = ($params->isTrashFolder && $params->globalName === self::TRASH_FOLDER) ?
            'Mensagem apagada com sucesso.' :
            'Mensagem movida para lixeira.';

        Dispatcher::processRequest('Core.ShowFeedback', (object) array (
            'message' => $outMsg,
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array (
                   'folderId' => $params->folderId
                )
            )
        ));
    }
}
