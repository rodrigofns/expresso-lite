<?php
/**
 * Expresso Lite
 * Handler for uploadTempFile calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\TineTunnel\Request;

class UploadTempFile extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $fileType = isset($_SERVER['HTTP_X_FILE_TYPE']) ?
            $_SERVER['HTTP_X_FILE_TYPE'] : 'text/plain';

        if (isset($_SERVER['HTTP_X_FILE_NAME']) && isset($_SERVER['HTTP_X_FILE_TYPE'])) {
            return $this->uploadTempFile(
                file_get_contents('php://input'),
                $_SERVER['HTTP_X_FILE_NAME'],
                $fileType
            );
        } else {
            return $this->httpError(400, 'Nenhum arquivo a ser carregado.');
        }
    }

    /**
     * Uploads raw data to Tine as a temp file that may be used as a message attachment
     *
     * @param $rawData The file raw content
     * @param $fileDisplayName The file name
     * @param $mimeType The file's mimetype
     *
     * @return The temp file information.
     */
    public function uploadTempFile($rawData, $fileDisplayName, $mimeType)
    {
        $req = new Request();
        $req->setUrl($this->tineSession->getTineUrl() . '?method=Tinebase.uploadTempFile&eid='.sha1(mt_rand().microtime()));
        $req->setCookieHandler($this->tineSession); //tineSession has the necessary cookies
        $req->setPostFields($rawData);
        $req->setHeaders(array(
            'DNT: 1',
            'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'],
            'X-File-Name: ' . $fileDisplayName,
            'X-File-Size: 0',
            'X-File-Type: ' . $mimeType,
            'X-Requested-With: XMLHttpRequest',
            'X-Tine20-Request-Type: HTTP'
        ));
        return $req->send(REQUEST::POST);
    }
}
