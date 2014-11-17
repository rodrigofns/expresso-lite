<?php
/**
 * Expresso Lite
 * Utility class that provides tine mail message facilities that are
 * shared by several lite request handlers.
 * These methods were originally avaible in Tine.class
 * (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request\Utils;

use ExpressoLite\TineTunnel\TineSession;

class MessageUtils
{

    /**
     * Adds or clear a flag (read, forwarded, etc...) for all msgs with the informed.
     *
     * @param TineSession $tineSession The TineSession used to communicate with Tine.
     * @param array $msgIds An array with all the messages ids to have the flag set or unset.
     * @param string $flag The flag to be set or unset.
     * @param boolean $doAdd Tells if the flags need to be set (true) or unset(false).
     *
     * @return The JSON RPC call response given by Tine
     */
    public static function addOrClearFlag(TineSession $tineSession, array $msgIds, $flag, $doAdd)
    {
        $operation = $doAdd ? 'Expressomail.addFlags' : 'Expressomail.clearFlags';

        if (count($msgIds) === 1) {
            $filterData = (object) array(
                'filterData' => $msgIds[0],
                'flags' => $flag
            );
        } else {
            $filterData = (object) array(
                'filterData' => array(
                    (object) array(
                        'field' => 'id',
                        'operator' => 'in',
                        'value' => $msgIds
                    ),
                    (object) array(
                        'field' => 'container_id',
                        'operator' => 'in',
                        'value' => array()
                    )
                ),
                'flags' => $flag
            );
        }

        $response = $tineSession->jsonRpc($operation, $filterData);

        return $response->result;
    }

    /**
     * Creates an stdClass object with all attributes that Tine needs to save a message.
     *
     * @param TineSession $tineSession The TineSession used to communicate with Tine.
     * @param $subject
     * @param $body
     * @param array $to Array with all the emails in the "To" field
     * @param array $cc Array with all the emails in the "Cc" field
     * @param array $bcc Array with all the emails in the "Bcc" field
     * @param boolean $isImportant Indicates whether the message should be sent with the Important flag
     * @param $replyToId
     * @param $forwardFromId
     * @param $origDraftId
     * @param array $attachs
     *
     * @return stdClass object with all attributes that Tine needs to save a message
     */
    public static function buildMessageForSaving(TineSession $tineSession, $subject, $body, array $to, array $cc, array $bcc, $isImportant = false, $replyToId = null, $forwardFromId = null, $origDraftId = null, array $attachs = array())
    {
        $recordData = (object) array(
            'note' => '0',
            'content_type' => 'text/html',
            'account_id' => $tineSession->getAttribute('Expressomail.accountId'),
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $subject,
            'body' => $body,
            'attachments' => $attachs,
            'embedded_images' => array(),
            'from_email' => $tineSession->getAttribute('Expressomail.email'),
            'from_name' => $tineSession->getAttribute('Expressomail.from'),
            'customfields' => (object) array()
        );
        if ($isImportant)
            $recordData->importance = true;
        if ($replyToId !== null) { // this email is a reply
            $recordData->original_id = $replyToId;
            $recordData->flags = "\\Answered";
        } else
            if ($forwardFromId !== null) { // this email is being forwarded
                $recordData->original_id = $forwardFromId;
                $recordData->flags = 'Passed';
            }
        if ($origDraftId !== null) {
            $recordData->original_id = $origDraftId; // editing an existing draft
        }
        return $recordData;
    }

    /**
     * Moves messages to a different folder
     *
     * @param TineSession $tineSession The TineSession used to communicate with Tine.
     * @param array $msgIds An array with all the messages ids to be moved.
     * @param $folderId The destination folder id
     *
     * @return The JSON RPC call response given by Tine
     */
    public static function moveMessages(TineSession $tineSession, array $msgIds, $folderId)
    {
        $response = $tineSession->jsonRpc('Expressomail.moveMessages', (object) array(
            'filterData' => array(
                (object) array(
                    'field' => 'id',
                    'operator' => 'in',
                    'value' => $msgIds // simple array
                ),
                (object) array(
                    'field' => 'container_id',
                    'operator' => 'in',
                    'value' => array()
                )
            ),
            'targetFolderId' => $folderId
        ));
        return $response->result;
    }
}
