<?php
/**
 * Expresso Lite Accessible
 * Changes flags of message.
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

class MarkMessageAsUnread extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('MarkAsRead', (object) array(
            'ids' => $params->messageId,
            'asRead' => '0'
        ));

        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'message' => 'Mensagem marcada como não lida com sucesso.',
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array(
                    'folderId' => $params->folderId
                )
            )
        ));
    }
}
