<?php
/**
 * Expresso Lite Accessible
 * Sends a message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Dispatcher;
use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;

class SendMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $lrp = new LiteRequestProcessor();

        // Retrieve original message, if replying or forwarding.
        if (!empty($params->replyToId) || !empty($params->forwardFromId)) {
            $msg = $lrp->executeRequest('GetMessage', (object) array(
                'id' => !empty($params->replyToId) ?
                    $params->replyToId : $params->forwardFromId
            ));
        } else {
            $msg = null; // compose new mail, not reply/forward
        }
        $lrp->executeRequest('SaveMessage', (object) array(
            'subject' => $params->subject,
            'body' => $this->prepareQuotedMessage($params, $msg),
            'to' => $params->addrTo,
            'cc' => $params->addrCc,
            'bcc' => $params->addrBcc,
            'isImportant' => isset($params->important) ? '1' : '0',
            'attachs' => '',
            'replyToId' => $params->replyToId,
            'forwardFromId' => $params->forwardFromId,
            'origDraftId' => ''
        ));

        Dispatcher::processRequest('Core.ShowFeedback', (object) array(
            'message' => 'Mensagem enviada com sucesso.',
            'destinationText' => 'Voltar para ' . $params->folderName,
            'destinationUrl' => (object) array(
                'action' => 'Mail.Main',
                'params' => array(
                   'folderId' => $params->folderId,
                   'page' => $params->page
                )
            )
        ));
    }

    /**
     * Formats replied/forwarded message body texts.
     *
     * @param  stdClass $params Request parameters.
     * @param  stdClass $msg    Message object, if replied or forwarded.
     * @return string           Formatted body text.
     */
    private function prepareQuotedMessage($params, $msg = null)
    {
        $formattedDate = date('d/m/Y H:i', strtotime($msg->received));
        if (!empty($params->replyToId) && $msg !== null) {
            $out = '<br />Em ' . $formattedDate . ', ' .
                $msg->from_name . ' escreveu:' .
                '<blockquote>' . $msg->body->message . '<br />' .
                ($msg->body->quoted !== null ? $msg->body->quoted : '') .
                '</blockquote>';
        } else if (!empty($params->forwardFromId) && $msg !== null) {
            $out = '<br />-----Mensagem original-----<br />' .
                '<b>Assunto:</b> ' . $msg->subject . '<br />' .
                '<b>Remetente:</b> "' . $msg->from_name . '" &lt;' . $msg->from_email . '&gt;<br />' .
                '<b>Para:</b> ' . implode(', ', $msg->to) . '<br />' .
                (!empty($msg->cc) ? '<b>Cc:</b> ' . implode(', ', $msg->cc) . '<br />' : '') .
                '<b>Data:</b> ' . $formattedDate . '<br /><br />' .
                $msg->body->message . '<br />' .
                ($msg->body->quoted !== null ? $msg->body->quoted : '');
        } else {
            $out = '';
        }
        return nl2br($params->messageBody) . '<br /><br />' .
            TineSessionRepository::getTineSession()->getAttribute('Expressomail.signature') .
            '<br />' . $out;
    }
}
