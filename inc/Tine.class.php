<?php
/*!
 * Expresso Lite
 * Tine abstraction layer.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

require(dirname(__FILE__).'/JsonRpc.class.php');

// Session variables used by this class:
//  ourtine_url, ourtine_id, ourtine_jsonkey
// Don't forget to call session_start() on the invoking page.

class Tine
{
    const MAILMODULE = 'Expressomail'; // Felamimail dropped in Kristina

    private function _jsonRpc($method, $params=array(), $ignoreErrors=false)
    {
        if (!isset($_SESSION['ourtine_url']))
            throw new Exception('No JSON-RPC requests before log in, dude.');
        $tid = sha1(mt_rand().microtime());
        $jsonRpc = new JsonRpc();
        $jsonRpc->url($_SESSION['ourtine_url'].'?transactionid='.$tid);
        $jsonRpc->cookies(true);
        $jsonRpc->rpcMethod($method);
        $jsonRpc->rpcParams($params);
        $jsonRpc->headers(array(
            'Content-Type: application/json; charset=UTF-8',
            'Connection: Keep-Alive',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT'],
            'DNT: 1',
            'X-Requested-With: XMLHttpRequest',
            'X-Tine20-JsonKey: '.(isset($_SESSION['ourtine_jsonkey']) ? $_SESSION['ourtine_jsonkey'] : 'undefined'),
            'X-Tine20-Request-Type: JSON',
            'X-Tine20-TransactionId: '.$tid
        ));
        $jreq = $jsonRpc->send();
        if (!$ignoreErrors && isset($jreq->error))
            throw new Exception($jreq->error->code.', '.$jreq->error->message);
        return $jreq;
    }

    public static function IsLogged()
    {
        return isset($_SESSION['ourtine_id']);
    }

    function __construct($url)
    {
        $_SESSION['ourtine_url'] = $url; // ex.: https://expressov3.serpro.gov.br/index.php
    }

    public function login($user, $pwd)
    {
        if ($this->isLogged())
            throw new Exception('Already logged in.');
        try {
            $jreq = $this->_jsonRpc('Tinebase.login', (object)array(
                'username'     => $user,
                'password'     => $pwd,
                'securitycode' => ''
            ));
        } catch (Exception $e) {
            unset($_SESSION['ourtine_url']);
            throw new Exception('Tinebase.login: '.$e->getMessage());
        }

        $res = $jreq->result;
        if ($res->success === false && strpos($res->errorMessage, 'Your password has expired. You must change it.') === 0) {
            $res->expired = true;
        } else if (isset($res->success) && $res->success === false)
            throw new Exception('Tinebase.login: '.$res->errorMessage);
        $_SESSION['ourtine_jsonkey'] = $res->jsonKey;
        // For some weird reason, calling getAllRegistryData() here will return an incomplete
        //  object. It demands another $.post() from the page in order to work.
        return $res;
    }

    public function logoff()
    {
        if (!$this->isLogged()) return false;
        try {
            $jreq = $this->_jsonRpc('Tinebase.logout');
        } catch (Exception $e) {
            throw new Exception('Tinebase.logout: '.$e->getMessage());
        }
        unset($_SESSION['ourtine_url']);
        unset($_SESSION['ourtine_id']);
        unset($_SESSION['ourtine_jsonkey']);
        return $jreq->result;
    }

    public function setLocale($locale)
    {
        try {
            $jreq = $this->_jsonRpc('Tinebase.setLocale', (object)array(
                'localeString'     => $locale,
                'saveaspreference' => true,
                'setcookie'        => true
            ));
        } catch (Exception $e) {
            throw new Exception('Tinebase.setLocale: '.$e->getMessage());
        }
        return $jreq->result;
    }

    public function getAllRegistryData($validateLogin)
    {
        try {
            $jreq = $this->_jsonRpc('Tinebase.getAllRegistryData');
        } catch (Exception $e) {
            throw new Exception('Tinebase.getAllRegistryData: '.$e->getMessage());
        }
        if ($validateLogin) {
            if (!isset($jreq->result->{self::MAILMODULE}))
                throw new Exception('Tinebase.getAllRegistryData: Mail info not returned.');
            $_SESSION['ourtine_id'] = $jreq->result->{self::MAILMODULE}->accounts->results[0]->id;
            //~ $_SESSION['ourtine_jsonkey'] = $jreq->result->Tinebase->jsonKey; // now set by login()
            return (object)array( // AJAX getAllRegistryData() will save some of these in session
                'id'           => $jreq->result->{self::MAILMODULE}->accounts->results[0]->id,
                'email'        => $jreq->result->{self::MAILMODULE}->accounts->results[0]->email,
                'organization' => $jreq->result->{self::MAILMODULE}->accounts->results[0]->organization,
                'from'         => $jreq->result->{self::MAILMODULE}->accounts->results[0]->from,
                'signature'    => $jreq->result->{self::MAILMODULE}->accounts->results[0]->signature,
                'user'         => $jreq->result->Tinebase->currentAccount->accountLoginName,
                'addrCatalog'  => '/personal/'.$jreq->result->Tinebase->currentAccount->accountId,
                'lastLogin'    => $jreq->result->Tinebase->currentAccount->accountLastLogin,
                'jsonKey'      => $jreq->result->Tinebase->jsonKey
            );
        } else {
            return $jreq->result;
        }
    }

    public function changeExpiredPassword($userName, $oldPassword, $newPassword)
    {
        try {
            $jreq = $this->_jsonRpc('Tinebase.changeExpiredPassword', (object)array(
                'userName'    => $userName,
                'oldPassword' => $oldPassword,
                'newPassword' => $newPassword
            ));
        } catch (Exception $e) {
            throw new Exception('Tinebase.changeExpiredPassword: '.$e->getMessage());
        }
        if ($jreq->result->success === false)
            throw new Exception($jreq->result->errorMessage);
        return $jreq->result;
    }

    public function getPersonalContacts($addrCatalog)
    {
        try {
            $jreq = $this->_jsonRpc('Addressbook.searchEmailAddresss', (object)array(
                'filter' => array(
                    (object)array(
                        'field'     => 'query',
                        'label'     => 'Pesquisa rápida',
                        'operators' => array('contains')
                    ),
                    (object)array(
                        'field'    => 'email_query',
                        'operator' => 'contains',
                        'value'    => '@'
                    ),
                    (object)array(
                        'field'    => 'container_id',
                        'operator' => 'equals',
                        'value'    => $addrCatalog
                    )
                ),
                'paging' => (object)array(
                    'dir'  => 'ASC',
                    'sort' => 'email'
                )
            ));
        } catch (Exception $e) {
            throw new Exception('Addressbook.searchEmailAddresss: '.$e->getMessage());
        }
        $contacts = array();
        foreach ($jreq->result->results as $cont) {
            $contactsEmail = null;
            if (isset($cont->emails)) { // this contact is actually a group with many emails
                $contactsEmail = array();
                $addrs = explode(',', $cont->emails);
                foreach ($addrs as $addr) {
                    if (strpos($addr, '<') !== false) {
                        $contactsEmail[] = substr($addr,
                            strpos($addr, '<') + 1,
                            strrpos($addr, '>') - strpos($addr, '<') - 1 );
                    }
                }
            } else {
                $contactsEmail = array($cont->email); // ordinary contact, 1 address
            }
            $contacts[] = (object)array(
                'name'   => $cont->n_fn,
                'emails' => $contactsEmail
            );
        }
        return $contacts; // both collected and personal contacts, merged
    }

    public function searchFolders($parentFolder='', $recursive=false)
    {
        try {
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.searchFolders', (object)array(
                'filter' => array(
                    (object)array(
                        'field'    => 'account_id',
                        'operator' => 'equals',
                        'value'    => $_SESSION['ourtine_id']
                    ),
                    (object)array(
                        'field'    => 'globalname',
                        'operator' => 'equals',
                        'value'    => $parentFolder
                    )
                )
            ));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.searchFolders: '.$e->getMessage());
        }
        $fldrs = array();
        foreach ($jreq->result->results as $result) {
            $enTrans = array('INBOX', 'Drafts', 'Sent', 'Templates', 'Trash');
            $ptTrans = array('Inbox', 'Rascunhos', 'Enviados', 'Modelos', 'Lixeira');
            for ($i = 0; $i < count($enTrans); ++$i)
                if ($result->localname == $enTrans[$i])
                    $result->localname = $ptTrans[$i];
            $fldrs[] = (object)array(
                'id'            => $result->id,
                'globalName'    => $result->globalname,
                'localName'     => $result->localname,
                'hasSubfolders' => $result->has_children,
                'subfolders'    => $recursive && $result->has_children ? self::searchFolders($result->globalname, true) : array(),
                'totalMails'    => isset($result->cache_totalcount) ? $result->cache_totalcount : 0,
                'unreadMails'   => isset($result->cache_unreadcount) ? $result->cache_unreadcount : 0,
                'recentMails'   => isset($result->cache_recentcount) ? $result->cache_recentcount : 0,
                'quotaLimit'    => isset($result->quota_limit) ? (int)$result->quota_limit : 0,
                'quotaUsage'    => isset($result->quota_usage) ? (int)$result->quota_usage : 0,
                'systemFolder'  => $result->system_folder,
                'messages'      => array(), // not populated here
                'threads'       => array()  // not populated here
            );
        }
        return $fldrs;
    }

    public function updateMessageCache($folderId)
    {
        try {
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.updateMessageCache', (object)array(
                'folderId' => $folderId,
                'time'     => 10 // minutes?
            ));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.updateMessageCache: '.$e->getMessage());
        }
        return (object)array(
            'id'          => $jreq->result->id,
            'totalMails'  => isset($jreq->result->cache_totalcount) ? $jreq->result->cache_totalcount : 0,
            'unreadMails' => isset($jreq->result->cache_unreadcount) ? $jreq->result->cache_unreadcount : 0,
            'recentMails' => isset($jreq->result->cache_recentcount) ? $jreq->result->cache_recentcount : 0
        );
    }

    public function getFolderStatus_UNUSED(array $folderIds)
    {
        // *** Don't use: Tine returns WRONG results for this method. ***
        try {
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.getFolderStatus', array(
                array(
                    (object)array(
                        'field'    => 'id',
                        'operator' => 'in',
                        'value'    => $folderIds
                    )
                )
            ));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.getFolderStatus: '.$e->getMessage());
        }
        $folders = array();
        foreach ($jreq->result as $f) {
            $folders[] = (object)array(
                'id'            => $f->id,
                'totalMails'    => isset($f->cache_totalcount) ? $f->cache_totalcount : 0,
                'unreadMails'   => isset($f->cache_unreadcount) ? $f->cache_unreadcount : 0,
                'recentMails'   => isset($f->cache_recentcount) ? $f->cache_recentcount : 0
            );
        }
        return $folders;
    }

    public function getFolderHeadlines($folderId, $start, $limit)
    {
        return $this->_searchHeadlines('', array('/'.$_SESSION['ourtine_id'].'/'.$folderId), $start, $limit);
    }

    public function getMessage($id)
    {
        try {
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.getMessage', (object)array('id' => $id));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.getMessage: '.$e->getMessage());
        }
        $mailsTo = (isset($jreq->result->headers->to) && $jreq->result->headers->to !== null) ?
            explode(',', $jreq->result->headers->to) : array();
        for ($i = 0, $countMailsTo = count($mailsTo); $i < $countMailsTo; ++$i)
            $mailsTo[$i] = htmlspecialchars(trim($mailsTo[$i]));
        return (object)array(
            //~ 'id'          => $jreq->result->id,
            //~ 'subject'     => $jreq->result->subject,
            'body'        => self::_BreakQuotedMessage($jreq->result->body),
            'attachments' => $jreq->result->attachments,
            //~ 'to'          => $mailsTo,
            //~ 'cc'          => (isset($jreq->result->cc) && $jreq->result->cc !== null) ? $jreq->result->cc : array(),
            //~ 'bcc'         => (isset($jreq->result->bcc) && $jreq->result->bcc !== null) ? $jreq->result->bcc : array(),
            //~ 'from'        => (object)array(
                //~ 'name'  => $jreq->result->from_name,
                //~ 'email' => $jreq->result->from_email
            //~ ),
            //~ 'flags'       => $jreq->result->flags,
            //~ 'unread'      => !in_array("\\Seen", $jreq->result->flags),
            //~ 'draft'       => in_array("\\Draft", $jreq->result->flags),
            //~ 'flagged'     => in_array("\\Flagged", $jreq->result->flags),
            //~ 'replied'     => in_array("\\Answered", $jreq->result->flags),
            //~ 'forwarded'   => in_array("Passed", $jreq->result->flags),
            //~ 'important'   => $jreq->result->importance,
            //~ 'received'    => strtotime($jreq->result->received) // timestamp
        );
    }

    private static function _BreakQuotedMessage($message)
    {
        $patterns = array(
            '/--+\s?((Mensagem encaminhada)|(Mensagem original)|(Original message)|(Originalnachricht))\s?--+/',
            '/(<((div)|(font))(.{1,50})>)?Em \d+(\/|\.|-)\d+(\/|\.|-)\d+,? (a|à)(s|\(s\)) \d+:\d+( horas)?, (.{1,256})escreveu:/',
            '/(<((div)|(font))(.{1,50})>)?Em \d+(\/|\.|-)\d+(\/|\.|-)\d+ \d+:\d+(:\d+)?, (.{1,256})escreveu:/',
            '/(<((div)|(font))(.{1,50})>)?Em \d+ de (.{1,9}) de \d+ \d+:\d+(:\d+)?, (.{1,256})escreveu:/',
            '/((On)|(Am)) \d{1,2}(\/|\.|-)\d{1,2}(\/|\.|-)\d{4} \d\d:\d\d(:\d\d)?, (.{1,256})((wrote)|(schrieb)):?/'
        );
        $idx = array();
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches, PREG_OFFSET_CAPTURE))
                $idx[] = $matches[0][1]; // append index of 1st occurrence of regex match
        }
        if (!empty($idx)) {
            $idx = min($idx); // isolate index of earliest occurrence
            $topMsg = self::_FixInlineAttachmentImages( self::_TrimLinebreaks(substr($message, 0, $idx)) );
            $quotedMsg = self::_FixInlineAttachmentImages( self::_TrimLinebreaks(substr($message, $idx)) );
            return (object)array('message' => $topMsg,
                'quoted' => ($quotedMsg != '') ? self::_FixBrokenDiv($quotedMsg) : null);
        } else {
            return (object)array('message' => self::_FixInlineAttachmentImages(self::_TrimLinebreaks($message)),
                'quoted' => null); // no quotes found
        }
    }

    private static function _TrimLinebreaks($text)
    {
        $text = trim($text);
        if ($text != '') {
            $text = preg_replace('/^(<(BR|br)\s?\/?>)+/', '', $text); // ltrim
            $text = preg_replace('/(<(BR|br)\s?\/?>)+$/', '', $text); // rtrim
        }
        return $text;
    }

    private static function _FixInlineAttachmentImages($text)
    {
        //~ return preg_replace('/<img alt="(.{1,128})" src="index\.php\?method='.'Expressomail'.'\.downloadAttachment&amp;messageId=(.{1,160})&amp;partId=(.{1,8})"\/>/',
            //~ '<img src="?r=downloadAttachment&messageId=$2&partId=$3&fileName=$1"/>', $text);
        return preg_replace('/src="index\.php\?method='.self::MAILMODULE.'\.downloadAttachment/',
            'src="../?r=downloadAttachment&fileName=inlineAttachment', $text);
    }

    private static function _FixBrokenDiv($text)
    {
        $firstClose = stripos($text, '</div>'); // an enclosing div before an opening one
        $firstOpen = stripos($text, '<div');
        return ($firstClose !== false && $firstOpen !== false && $firstClose < $firstOpen) ||
            ($firstClose !== false && $firstOpen === false) ?
            preg_replace('/<\/div>/i', '', $text, 1) : $text;
    }

    public function searchMessages($what, array $folderIds, $start, $limit)
    {
        $folderCompoundIds = array();
        foreach ($folderIds as $folderId)
            $folderCompoundIds[] = '/'.$_SESSION['ourtine_id'].'/'.$folderId;
        $headlines = $this->_searchHeadlines($what, $folderCompoundIds, $start, $limit);
        $unreadCount = 0;
        foreach ($headlines as $headline)
            if ($headline->unread) ++$unreadCount;
        return (object)array( // return an artificial folder
            'id'            => null,
            'globalName'    => 'search',
            'localName'     => 'Resultados de buscas',
            'hasSubfolders' => true,
            'subfolders'    => array((object)array( // unique subfolder with search results
                'id'            => null,
                'globalName'    => null,
                'localName'     => $what,
                'hasSubfolders' => false,
                'subfolders'    => array(),
                'totalMails'    => count($headlines),
                'unreadMails'   => 0,//$unreadCount,
                'recentMails'   => 0,
                'quotaLimit'    => 0,
                'quotaUsage'    => 0,
                'systemFolder'  => false,
                'messages'      => $headlines,
                'threads'       => array()  // not populated here
            )),
            'totalMails'    => 0,
            'unreadMails'   => 0,
            'recentMails'   => 0,
            'quotaLimit'    => 0,
            'quotaUsage'    => 0,
            'systemFolder'  => false,
            'messages'      => array(),
            'threads'       => array()
        );
    }

    private function _searchHeadlines($what, array $folderIds, $start, $limit)
    {
        try {
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.searchMessages', (object)array(
                'filter' => array(
                    (object)array(
                        'condition' => 'AND',
                        'filters'   => array(
                            (object)array(
                                'field'    => 'query',
                                'operator' => 'contains',
                                'value'    => $what
                            ),
                            (object)array(
                                'field'    => 'path',
                                'operator' => 'in',
                                'value'    => $folderIds
                            )
                        )
                    )
                ),
                'paging' => (object)array(
                    'sort'  => 'received',
                    'dir'   => 'DESC',
                    'start' => $start,
                    'limit' => $limit
                )
            ));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.searchMessages: '.$e->getMessage());
        }
        $headlines = array();
        foreach ($jreq->result->results as $mail) {
            $headlines[] = (object)array(
                'id'            => $mail->id,
                'subject'       => $mail->subject !== null ? $mail->subject : '',
                'to'            => $mail->to !== null ? $mail->to : array(),
                'cc'            => $mail->cc !== null ? $mail->cc : array(),
                'bcc'           => $mail->bcc !== null ? $mail->bcc : array(), // brings only 1 email
                'from'          => (object)array(
                    'name'  => $mail->from_name,
                    'email' => $mail->from_email
                ),
                //~ 'flags'         => $mail->flags,
                'unread'        => !in_array("\\Seen", $mail->flags),
                'draft'         => in_array("\\Draft", $mail->flags),
                'flagged'       => in_array("\\Flagged", $mail->flags),
                'replied'       => in_array("\\Answered", $mail->flags),
                'forwarded'     => in_array("Passed", $mail->flags),
                'important'     => $mail->importance,
                'signed'        => $mail->structure->contentType === 'multipart/signed',
                'wantConfirm'   => $mail->reading_conf,
                'received'      => strtotime($mail->received), // timestamp
                'size'          => (int)$mail->size, // bytes
                'hasAttachment' => $mail->has_attachment,
                'attachments'   => null, // not populated here
                'body'          => null  // not populated here
            );
        }
        return $headlines;
    }

    public function markMessageHighlighted($asHighlighted, array $msgIds)
    {
        return $this->_addOrClearFlag($msgIds, "\\Flagged", $asHighlighted);
    }

    public function markMessageRead($asRead, array $msgIds)
    {
        return $this->_addOrClearFlag($msgIds, "\\Seen", $asRead);
    }

    public function deleteMessages(array $msgIds, $forever=false)
    {
        return $forever ? $this->_addOrClearFlag($msgIds, "\\Deleted", true) : // use when message is already on trash folder
            $this->moveMessages($msgIds, '_trash_'); // just move into trash folder
    }

    private function _addOrClearFlag(array $msgIds, $flag, $doAdd)
    {
        $what = $doAdd ? '.addFlags' : '.clearFlags';
        try {
            if (count($msgIds) === 1) {
                // Only 1 message to be processed.
                $jreq = $this->_jsonRpc(self::MAILMODULE.$what, (object)array(
                    'filterData' => $msgIds[0],
                    'flags'      => $flag
                ));
            } else {
                // Many messages to be processed.
                $jreq = $this->_jsonRpc(self::MAILMODULE.$what, (object)array(
                    'filterData' => array(
                        (object)array(
                            'field'    => 'id',
                            'operator' => 'in',
                            'value'    => $msgIds
                        ),
                        (object)array(
                            'field'    => 'container_id',
                            'operator' => 'in',
                            'value'    => array()
                        )
                    ),
                    'flags' => $flag
                ));
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('%s.%s: %s',
                self::MAILMODULE,
                $doAdd ? 'addFlags' : 'clearFlags',
                $e->getMessage() ));
        }
        return $jreq->result;
    }

    public function moveMessages(array $msgIds, $folderId)
    {
        try {
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.moveMessages', (object)array(
                'filterData' => array(
                    (object)array(
                        'field'    => 'id',
                        'operator' => 'in',
                        'value'    => $msgIds // simple array
                    ),
                    (object)array(
                        'field'    => 'container_id',
                        'operator' => 'in',
                        'value'    => array()
                    )
                ),
                'targetFolderId' => $folderId
            ));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.moveMessage: '.$e->getMessage());
        }
        return $jreq->result;
    }

    public function searchContactsByToken($token)
    {
        try {
            $jreq = $this->_jsonRpc('Addressbook.searchContacts', (object)array(
                'filter' => array(
                    (object)array(
                        'condition' => 'OR',
                        'filters'   => array(
                            (object)array(
                                'condition' => 'AND',
                                'filters'   => array(
                                    (object)array(
                                        'field'    => 'query',
                                        'id'       => 'ext-record-5',
                                        'operator' => 'contains',
                                        'value'    => $token
                                    ),
                                    (object)array(
                                        'field'    => 'container_id',
                                        'id'       => 'ext-record-6',
                                        'operator' => 'in',
                                        'value'    => array('48480')
                                    )
                                ),
                                'id'    => 'ext-comp-1023',
                                'label' => 'Contatos'
                            )
                        )
                    )
                ),
                'paging' => (object)array(
                    'dir'   => 'ASC',
                    'limit' => 50,
                    'sort'  => 'n_fileas',
                    'start' => 0
                )
            ));
        } catch (Exception $e) {
            throw new Exception('Addressbook.searchContacts: '.$e->getMessage());
        }
        $contacts = array();
        foreach ($jreq->result->results as $contact) {
            $contacts[] = (object)array(
                'cpf'       => $contact->id, // yes, returned object is inconsistent, see searchContactsByEmail()
                'email'     => $contact->email,
                'name'      => $contact->n_fn,
                'isDeleted' => $contact->is_deleted !== '',
                'org'       => $contact->org_name
                //'orgUnit'   => $contact->org_unit
            );
        }
        return $contacts;
    }

    public function searchContactsByEmail(array $emails)
    {
        $filters = array();
        foreach ($emails as $email) {
            $filters[] = (object)array(
                'field'    => 'email_query',
                'operator' => 'contains',
                'value'    => $email
            );
        }
        try {
            $jreq = $this->_jsonRpc('Addressbook.searchContacts', (object)array(
                'filter' => array(
                    (object)array(
                        'condition' => 'OR',
                        'filters'   => array(
                            (object)array(
                                'condition' => 'OR',
                                'filters'   => $filters,
                                'label'     => 'Contacts'
                            )
                        )
                    )
                ),
                'paging' => (object)array(
                    'sort'  => 'n_fn',
                    'dir'   => 'ASC',
                    'start' => 0,
                    'limit' => 50 //count($emails)
                )
            ));
        } catch (Exception $e) {
            throw new Exception('Addressbook.searchContacts: '.$e->getMessage());
        }
        $contacts = array();
        foreach ($jreq->result->results as $contact) {
            $contacts[] = (object)array(
                'id'        => $contact->id,
                'isDeleted' => $contact->is_deleted !== '0',
                'created'   => strtotime($contact->creation_time),
                'email'     => $contact->email,
                'mugshot'   => $this->getContactPicture($contact->id, strtotime($contact->creation_time), 90, 113),
                'name'      => $contact->n_fn,
                'phone'     => $contact->tel_work,
                'cpf'       => sprintf('%011d', (int)$contact->account_id),
                //~ 'matricula' => $contact->employee_number,
                'org'       => $contact->org_name,
                'orgUnit'   => $contact->org_unit
            );
        }
        return self::_RemoveDuplicatedContacts($contacts);
    }

    private static function _RemoveDuplicatedContacts(array $contacts)
    {
        $ret = array();
        foreach ($contacts as $contact) {
            $willAdd = true;
            for ($i = 0, $tot = count($ret); $i < $tot; ++$i) {
                if ($contact->email === $ret[$i]->email) { // duplicated contact
                    if ($ret[$i]->mugshot == '' || $ret[$i]->mugshot === null) { // our current contact has no mugshot
                        if ($contact->mugshot != '' && $contact->mugshot !== null)
                            $ret[$i] = $contact; // replace
                    }
                    $willAdd = false;
                    break;
                }
            }
            if ($willAdd) $ret[] = $contact;
        }
        return $ret;
    }

    public function getContactPicture($contactId, $creationTime, $cx, $cy)
    {
        header('Content-Type: image/jpeg');
        $req = new Request();
        $req->binaryOutput(false); // do not directly output the binary stream to client
        $req->cookies(true);
        $req->url($_SESSION['ourtine_url'].'?method=Tinebase.getImage&application=Addressbook'.
            "&location=&id={$contactId}&width={$cx}&height={$cy}&ratiomode=0&mtime={$creationTime}000");
        $req->headers(array(
            'Connection: keep-alive',
            'DNT: 1',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT'],
            'Pragma: no-cache',
            'Cache-Control: no-cache'
        ));
        $mugshot = $req->send(Request::GET);
        return ($mugshot !== null) ? $mugshot : ''; // dummy if binaryOutput(true)
    }

    public function getNewContactsContainer($personalId)
    {
        try {
            $jreq = $this->_jsonRpc('Tinebase_Container.getContainer', (object)array(
                'application'    => 'Addressbook',
                'containerType'  => 'personal',
                'owner'          => $personalId,
                'requiredGrants' => ''
            ));
        } catch (Exception $e) {
            throw new Exception('Tinebase_Container.getContainer: '.$e->getMessage());
        }
        $containers = array();
        foreach ($jreq->result as $cont) {
            $containers[] = (object)array(
                'id'        => $cont->id,
                'name'      => $cont->name,
                'type'      => $cont->type,
                'path'      => $cont->path,
                'isDeleted' => $cont->is_deleted
            );
        }
        return $containers; // used when saving new collected contacts
    }

    public function saveContact($idContainer, array $mails, array $surnames, array $names, array $orgs)
    {
        try {
            for ($i = 0, $tot = count($mails); $i < $tot; ++$i) { // one request to each new contact
                $this->_jsonRpc('Addressbook.saveContact', array(
                    (object)array(
                        'container_id' => $idContainer,
                        'email'        => $mails[$i],
                        'n_family'     => $surnames[$i],
                        'n_given'      => $names[$i],
                        'org_name'     => $orgs[$i]
                    ),
                    false
                ), true); // always returns an error, ignore it
            }
        } catch (Exception $e) {
            throw new Exception('Addressbook.saveContact: '.$e->getMessage());
        }
        return count($mails);
    }

    public function downloadAttachment($fileName, $messageId, $partId)
    {
        // This method will directly output the binary stream to client.
        // Intended to be called on a "_blank" window.
        $mimeTypes = array(
            '.txt'  => 'text/plain',
            '.pdf'  => 'application/pdf',
            '.png'  => 'image/png',
            '.jpe'  => 'image/jpeg',
            '.jpeg' => 'image/jpeg',
            '.jpg'  => 'image/jpeg',
            '.gif'  => 'image/gif',
            '.bmp'  => 'image/bmp',
            '.ico'  => 'image/vnd.microsoft.icon',
            '.tiff' => 'image/tiff',
            '.tif'  => 'image/tiff',
            '.svg'  => 'image/svg+xml',
            '.svgz' => 'image/svg+xml'
        ); // these file extensions will be opened in browser, not downloaded

        $dotPos = strrpos($fileName, '.');
        $ext = ($dotPos === false) ? '' : substr($fileName, $dotPos);
        if (array_key_exists($ext, $mimeTypes)) {
            header('Content-Type: '.$mimeTypes[$ext]); // will be opened in browser
        } else {
            header("Content-Disposition: attachment; filename=\"$fileName\""); // will be downloaded
            header('Content-Type: application/octet-stream');
            header('Content-Transfer-Encoding: binary');
        }

        $req = new Request();
        $req->url($_SESSION['ourtine_url']);
        $req->cookies(true);
        $req->binaryOutput(true); // directly output binary stream to client
        $req->postFields('requestType=HTTP&method='.self::MAILMODULE.'.downloadAttachment&'.
            "messageId=$messageId&partId=$partId&getAsJson=false" );
        $req->headers(array(
            'Connection: keep-alive',
            'DNT: 1',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT'],
            'Pragma: no-cache',
            'Cache-Control: no-cache'
        ));
        $req->send(Request::POST);
    }

    public function uploadTempFile($rawData, $fileDisplayName, $mimeType)
    {
        $req = new Request();
        $req->url($_SESSION['ourtine_url'].'?method=Tinebase.uploadTempFile');
        $req->cookies(true);
        $req->postFields($rawData);
        $req->headers(array(
            'DNT: 1',
            'User-Agent: '.$_SERVER['HTTP_USER_AGENT'],
            'X-File-Name: '.$fileDisplayName,
            'X-File-Size: 0',
            'X-File-Type: '.$mimeType,
            'X-Requested-With: XMLHttpRequest',
            'X-Tine20-Request-Type: HTTP'
        ));
        return $req->send(REQUEST::POST);
    }

    public function joinTempFiles(array $tempFilesData)
    {
        try {
            $jreq = $this->_jsonRpc('Tinebase.joinTempFiles',
                (object)array('tempFilesData' => $tempFilesData));
        } catch (Exception $e) {
            throw new Exception('Tinebase.joinTempFiles: '.$e->getMessage());
        }
        return $jreq->result;
    }

    public function saveMessage($subject, $body, array $to, array $cc, array $bcc,
        $isImportant=false, $replyToId=null, $forwardFromId=null, $origDraftId=null, array $attachs=array() )
    {
        try { // this method sends the email
            $jreq = $this->_jsonRpc(self::MAILMODULE.'.saveMessage', (object)array(
                'recordData' => $this->_buildMessageForSaving($subject, $body, $to, $cc, $bcc,
                    $isImportant, $replyToId, $forwardFromId, $origDraftId, $attachs)
            ));
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.saveMessage: '.$e->getMessage());
        }
        return $jreq->result;
    }

    public function saveMessageDraft($draftFolderId, $subject, $body, array $to, array $cc, array $bcc,
        $isImportant=false, $replyToId=null, $forwardFromId=null, $origDraftId=null, array $attachs=array() )
    {
        try {
            $this->_jsonRpc(self::MAILMODULE.'.saveMessageInFolder', (object)array(
                'folderName' => 'INBOX/Drafts',
                'recordData' => $this->_buildMessageForSaving($subject, $body, $to, $cc, $bcc,
                    $isImportant, $replyToId, $forwardFromId, $origDraftId, $attachs)
            ));
            $draftMsg = $this->getFolderHeadlines($draftFolderId, 0, 1); // newest draft
            return $this->markMessageRead(true, array($draftMsg[0]->id)); // because Tine saves new draft as unread
        } catch (Exception $e) {
            throw new Exception(self::MAILMODULE.'.saveMessageInFolder: '.$e->getMessage());
        }
        return (object)array('DraftSaved' => 'ok', 'origDraftId' => $origDraftId);
    }

    private function _buildMessageForSaving($subject, $body, array $to, array $cc, array $bcc,
        $isImportant=false, $replyToId=null, $forwardFromId=null, $origDraftId=null, array $attachs=array() )
    {
        $recordData = (object)array(
            'note'            => '0',
            'content_type'    => 'text/html',
            'account_id'      => $_SESSION['ourtine_id'],
            'to'              => $to,
            'cc'              => $cc,
            'bcc'             => $bcc,
            'subject'         => $subject,
            'body'            => $body,
            'attachments'     => $attachs,
            'embedded_images' => array(),
            'from_email'      => $_SESSION['user_email'], // set by getAllRegistryData()
            'from_name'       => $_SESSION['user_name'],
            'customfields'    => (object)array()
        );
        if ($isImportant) $recordData->importance = true;
        if ($replyToId !== null) { // this email is a reply
            $recordData->original_id = $replyToId;
            $recordData->flags = "\\Answered";
        } else if ($forwardFromId !== null) { // this email is being forwarded
            $recordData->original_id = $forwardFromId;
            $recordData->flags = 'Passed';
        }
        if ($origDraftId !== null) {
            $recordData->original_id = $origDraftId; // editing an existing draft
        }
        return $recordData;
    }
}
