<?php
/**
 * Expresso Lite
 * Handler for getFolderHeadlines calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetFolderHeadlines extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $folderId = $this->param('folderId');
        $start = (int) $this->param('start');
        $limit = (int) $this->param('limit');

        $accountId = $this->getSessionAttribute('Expressomail.accountId');

        $folderIds = array(
            "/$accountId/$folderId"
        );

        $tineMessages = $this->getTineMessages('', $folderIds, $start, $limit);
        return $this->convertTineMessagesToLiteHeadlines($tineMessages);
    }

    /**
     * Connects to tine to return an array of messages that match a specified criteria.
     *
     * @param string $what A text that is part of the message
     * @param array $folderIds The ids of the folders in which the message should be searched
     * @param int @start The index of the first result to be returned (used for paging purposes)
     * @param int @limit The max number of results to be returned (used for paging purposes)
     *
     * @return array of messages as returned by Tine
     */
    private function getTineMessages($what, array $folderIds, $start, $limit)
    {
        $response = $this->jsonRpc('Expressomail.searchMessages', (object) array(
            'filter' => array(
                (object) array(
                    'condition' => 'AND',
                    'filters' => array(
                        (object) array(
                            'field' => 'query',
                            'operator' => 'contains',
                            'value' => $what
                        ),
                        (object) array(
                            'field' => 'path',
                            'operator' => 'in',
                            'value' => $folderIds
                        )
                    )
                )
            ),
            'paging' => (object) array(
                'sort' => 'received',
                'dir' => 'DESC',
                'start' => $start,
                'limit' => $limit
            )
        ));
        return $response->result->results;
    }

    /**
     * Converts an array of messages (as returned by Tine) to the format expected by Lite.
     *
     * @param array $tineMessages Then array of messages as returned by Tine
     *
     * @return array of messages as expected by Lite
     */
    private function convertTineMessagesToLiteHeadlines($tineMessages) {
        $headlines = array();
        foreach ($tineMessages as $mail) {
            $headlines[] = (object) array(
                'id' => $mail->id,
                'subject' => $mail->subject !== null ? $mail->subject : '',
                'to' => $mail->to !== null ? $mail->to : array(),
                'cc' => $mail->cc !== null ? $mail->cc : array(),
                'bcc' => $mail->bcc !== null ? $mail->bcc : array(), // brings only 1 email
                'from' => (object) array(
                    'name' => $mail->from_name,
                    'email' => $mail->from_email
                ),
                'unread' => ! in_array("\\Seen", $mail->flags),
                'draft' => in_array("\\Draft", $mail->flags),
                'flagged' => in_array("\\Flagged", $mail->flags),
                'replied' => in_array("\\Answered", $mail->flags),
                'forwarded' => in_array("Passed", $mail->flags),
                'important' => $mail->importance,
                'signed' => $mail->structure->contentType === 'multipart/signed',
                'wantConfirm' => $mail->reading_conf,
                'received' => strtotime($mail->received), // timestamp
                'size' => (int) $mail->size, // bytes
                'hasAttachment' => $mail->has_attachment,
                'attachments' => null, // not populated here
                'body' => null
            );
        }
        return $headlines;
    }
}
