<?php
/**
 * Expresso Lite Accessible
 * Opens an email message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Core\DateUtils;

class OpenMessage extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $message = $liteRequestProcessor->executeRequest('GetMessage', (object) array(
            'id' => $params->messageId
        ));

        $markAsRead = $liteRequestProcessor->executeRequest('MarkAsRead', (object) array(
            'ids' => $params->messageId,
            'asRead' => '1'
        ));

        if ($message->subject == '') {
            $message->subject = '(sem assunto)';
        }

        $this->createAttachmentsLinks($message->attachments, $params->messageId);
        $this->showTemplate('OpenMessageTemplate', (object) array(
            'folderName' => $params->folderName,
            'message' => $message,
            'formattedDate' => DateUtils::getFormattedDate(strtotime($message->received), true),
            'page' => $params->page,
            'lnkBack' => $this->makeUrl('Mail.Main', array(
                'folderId' => $params->folderId,
                'page' => $params->page
            )),
            'lnkDelete' => $this->makeUrl('Mail.DeleteMessage', array(
                'folderName' => $params->folderName,
                'messageId' => $params->messageId,
                'folderId' => $params->folderId
            )),
            'lnkMark' => $this->makeUrl('Mail.MarkMessageAsUnread', array(
                'folderName' => $params->folderName,
                'messageId' => $params->messageId,
                'folderId' => $params->folderId
            )),
            'lnkReply' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageId' => $params->messageId,
                'reply' => 'yes'
            )),
            'lnkReplyAll' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageId' => $params->messageId,
                'replyAll' => 'yes'
            )),
            'lnkForward' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $params->folderId,
                'folderName' => $params->folderName,
                'page' => $params->page,
                'messageId' => $params->messageId,
                'forward' => 'yes'
            ))
        ));
    }

    /**
     * In-place update of attachments array by adding the download links.
     *
     * @param array  $attachments Attachments array from message object.
     * @param string $msgId       ID of current message.
     */
    private function createAttachmentsLinks(array $attachments, $msgId)
    {
        foreach ($attachments as $attach) {
            $attach->accessibleExtension = strrchr($attach->filename, '.');
            $attach->accessibleFileName = basename ($attach->filename, $attach->accessibleExtension);
            $attach->accessibleFileSize = $this->sizeFilter($attach->size);

            $attach->lnkDownload = '../api/ajax.php?' .
                'r=downloadAttachment&' .
                'forceDownload&' .
                'fileName=' . urlencode($attach->filename) . '&' .
                'messageId=' . $msgId . '&' .
                'partId=' . $attach->partId;
        }
    }

    /**
     * Filters file size in Bytes, KBytes, MBytes, GBytes, TBytes.
     *
     * @param string $fileSizeBytes       represents size file in bytes.
     */
    public function sizeFilter($fileSizeBytes)
    {
        $base = log($fileSizeBytes, 1024);
        $suffixes = array('bytes', 'KB', 'MB', 'GB', 'TB');

        return round(pow(1024, $base - floor($base)), 0) . ' ' . $suffixes[floor($base)];
    }
}
