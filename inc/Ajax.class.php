<?php
/*!
 * Expresso Lite
 * Hub to handle all AJAX requests made by JS front-end.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

require_once(dirname(__FILE__).'/../conf.php');
require_once(dirname(__FILE__).'/Tine.class.php');

class Ajax {
	private static $tine = null;

	public static function InitTine() {
		// This method is called at the end of this script.
		self::$tine = new Tine(BACKEND_URL);
	}

	public static function ProcessRequest() {
		// Yes, we could have one method to each request, but on an editor
		// with code folding, this is actually faster to work with.
		if($_REQUEST['r'] === 'session')
		{
			self::_EchoJson($_SESSION); // for debug purposes
		}
		else if($_REQUEST['r'] === 'isLogged')
		{
			self::_EchoJson(Tine::IsLogged());
		}
		else if($_REQUEST['r'] === 'login')
		{
			if(!isset($_REQUEST['user']) || !isset($_REQUEST['pwd']))
				self::_HttpError(400, 'É necessário informar login e senha.');

			try {
				$logged = self::$tine->login($_REQUEST['user'], $_REQUEST['pwd']);
			} catch(Exception $e) {
				self::_HttpError(400, $e->getMessage());
			}

			self::_EchoJson($logged);
		}
		else if($_REQUEST['r'] === 'setLocale')
		{
			try {
				$res = self::$tine->setLocale('pt_BR');
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($res);
		}
		else if($_REQUEST['r'] === 'getAllRegistryData')
		{
			try {
				$ud = self::$tine->getAllRegistryData($_REQUEST['validateLogin'] == 1);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			if($_REQUEST['validateLogin'] == 1) {
				$_SESSION['user_email']     = $ud->email;
				$_SESSION['user_login']     = $ud->user; // cpf
				$_SESSION['user_name']      = $ud->from;
				$_SESSION['user_org']       = $ud->organization;
				$_SESSION['user_signature'] = htmlspecialchars($ud->signature);
			}
			self::_EchoJson($ud);
		}
		else if($_REQUEST['r'] === 'logoff')
		{
			try {
				$res = self::$tine->logoff();
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			unset($_SESSION['user_org']);
			unset($_SESSION['user_email']);
			unset($_SESSION['user_login']);
			unset($_SESSION['user_name']);
			unset($_SESSION['user_signature']);
			self::_EchoJson($res);
		}
		else if($_REQUEST['r'] === 'getPersonalContacts')
		{
			try {
				$personalId = ltrim(substr($_SESSION['user_login'], 0, 9), '0'); // cpf without last two digits
				$contacts = self::$tine->getPersonalContacts($personalId);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($contacts);
		}
		else if($_REQUEST['r'] === 'searchFolders')
		{
			try {
				$fldr = self::$tine->searchFolders(
					isset($_REQUEST['parentFolder']) ? $_REQUEST['parentFolder'] : '',
					isset($_REQUEST['recursive']) ? $_REQUEST['recursive'] == 1 : false);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($fldr);
		}
		else if($_REQUEST['r'] === 'updateMessageCache')
		{
			try {
				$folderStatus = self::$tine->updateMessageCache($_REQUEST['folderId']);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($folderStatus);
		}
		else if($_REQUEST['r'] === 'getFolderStatus')
		{
			try {
				$folders = self::$tine->getFolderStatus(explode(',', $_REQUEST['ids'])); // comma-separated into array
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($folders);
		}
		else if($_REQUEST['r'] === 'getFolderHeadlines')
		{
			try {
				$headlines = self::$tine->getFolderHeadlines($_REQUEST['folderId'],
					(int)$_REQUEST['start'], (int)$_REQUEST['limit']);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($headlines);
		}
		else if($_REQUEST['r'] === 'getMessage')
		{
			try {
				$msg = self::$tine->getMessage($_REQUEST['id']);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($msg);
		}
		else if($_REQUEST['r'] === 'searchMessages')
		{
			try {
				$headlines = self::$tine->searchMessages($_REQUEST['what'],
					explode(',', $_REQUEST['folderIds']), // comma-separated into array
					(int)$_REQUEST['start'], (int)$_REQUEST['limit']);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($headlines);
		}
		else if($_REQUEST['r'] === 'markAsHighlighted')
		{
			try {
				$status = self::$tine->markMessageHighlighted(
					$_REQUEST['asHighlighted'] === '1',
					explode(',', $_REQUEST['ids']) ); // comma-separated into array
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
		else if($_REQUEST['r'] === 'markAsRead')
		{
			try {
				$status = self::$tine->markMessageRead(
					$_REQUEST['asRead'] === '1',
					explode(',', $_REQUEST['ids']) ); // comma-separated into array
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
		else if($_REQUEST['r'] === 'deleteMessages')
		{
			try {
				$status = self::$tine->deleteMessages(
					explode(',', $_REQUEST['messages']), // comma-separated into array
					isset($_REQUEST['forever']) && $_REQUEST['forever'] === '1');
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
		else if($_REQUEST['r'] === 'moveMessages')
		{
			try {
				$status = self::$tine->moveMessages(
					explode(',', $_REQUEST['messages']), // comma-separated into array
					$_REQUEST['folder']);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
		else if($_REQUEST['r'] === 'downloadAttachment')
		{
			try {
				self::$tine->downloadAttachment($_REQUEST['fileName'], $_REQUEST['messageId'],
					$_REQUEST['partId']); // this method will directly output the binary stream to client
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
		}
		else if($_REQUEST['r'] === 'searchContactsByToken')
		{
			try {
				$contacts = self::$tine->searchContactsByToken($_REQUEST['token']);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($contacts);
		}
		else if($_REQUEST['r'] === 'searchContactsByEmail')
		{
			try {
				$contacts = self::$tine->searchContactsByEmail( explode(',', $_REQUEST['emails']) ); // comma-separated into array
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($contacts);
		}
		else if($_REQUEST['r'] === 'saveContacts')
		{
			try {
				$personalId = ltrim(substr($_SESSION['user_login'], 0, 9), '0'); // cpf without last two digits
				$containers = self::$tine->getNewContactsContainer($personalId);
				$good = false;
				foreach($containers as $container) {
					if($container->name === 'Contatos Coletados' && $container->type === 'personal') {
						self::$tine->saveContact($container->id,
							explode(',', $_REQUEST['emails']), // comma-separated into array
							explode(',', $_REQUEST['surnames']),
							explode(',', $_REQUEST['names']),
							explode(',', $_REQUEST['orgs']) );
						$good = true;
						break;
					}
				}
				if(!$good) self::_HttpError(500, 'Erro ao salvar novo contato: '.
					'container de contatos coletados não encontrado.');
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
		}
		else if($_REQUEST['r'] === 'uploadTempFile')
		{
			if(isset($_SERVER['HTTP_X_FILE_NAME']) && isset($_SERVER['HTTP_X_FILE_TYPE'])) {
				try {
					// http://stackoverflow.com/questions/9553168/undefined-variable-http-raw-post-data
					$status = self::$tine->uploadTempFile(file_get_contents('php://input'),
						$_SERVER['HTTP_X_FILE_NAME'], $_SERVER['HTTP_X_FILE_TYPE']);
				} catch(Exception $e) {
					self::_HttpError(500, $e->getMessage());
				}
				self::_EchoJson($status);
			} else {
				self::_HttpError(400, 'Nenhum arquivo a ser carregado.');
			}
		}
		else if($_REQUEST['r'] === 'joinTempFiles')
		{
			try {
				$status = self::$tine->joinTempFiles(json_decode($_REQUEST['tempFiles']));
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
		else if($_REQUEST['r'] === 'saveMessage')
		{
			try {
				$status = self::$tine->saveMessage($_REQUEST['subject'], $_REQUEST['body'],
					($_REQUEST['to'] != '') ? explode(',', $_REQUEST['to']) : array(),
					($_REQUEST['cc'] != '') ? explode(',', $_REQUEST['cc']) : array(),
					($_REQUEST['bcc'] != '') ? explode(', ', $_REQUEST['bcc']) : array(),
					($_REQUEST['isImportant'] == '1'),
					($_REQUEST['replyToId'] != '') ? $_REQUEST['replyToId'] : null,
					($_REQUEST['forwardFromId'] != '') ? $_REQUEST['forwardFromId'] : null,
					($_REQUEST['attachs'] != '') ? json_decode($_REQUEST['attachs']) : array() // array of tempFile objects
				);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
		else if($_REQUEST['r'] === 'saveMessageDraft')
		{
			try {
				$status = self::$tine->saveMessageDraft($_REQUEST['subject'], $_REQUEST['body'],
					($_REQUEST['to'] != '') ? explode(',', $_REQUEST['to']) : array(),
					($_REQUEST['cc'] != '') ? explode(',', $_REQUEST['cc']) : array(),
					($_REQUEST['bcc'] != '') ? explode(', ', $_REQUEST['bcc']) : array(),
					($_REQUEST['isImportant'] == '1'),
					($_REQUEST['replyToId'] != '') ? $_REQUEST['replyToId'] : null,
					($_REQUEST['forwardFromId'] != '') ? $_REQUEST['forwardFromId'] : null,
					($_REQUEST['attachs'] != '') ? json_decode($_REQUEST['attachs']) : array() // array of tempFile objects
				);
			} catch(Exception $e) {
				self::_HttpError(500, $e->getMessage());
			}
			self::_EchoJson($status);
		}
	}

	private static function _EchoJson($obj) {
		header('Content-Type: application/json');
		echo json_encode($obj);
	}

	private static function _HttpError($code, $msg) {
		$httpErr = array(
			400 => 'Bad Request',
			401 => 'Unauthorized',
			500 => 'Internal Server Error'
		);
		header('Content-type:text/html; charset=UTF-8');
		header(sprintf('HTTP/1.1 %d %s', $code, $httpErr[$code]));
		die($msg); // note: a standard HTML object will be sent, see Firebug output
	}
}

Ajax::InitTine();