<?php
/**
 * Expresso Lite
 * Handler for Login calls. This handler connects the current TineSession
 * to the Tine server, allowing it to perform authenticated calls from
 * then on.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\TineTunnel\Exception\CaptchaRequiredException;

class Login extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        if (! $this->isParamSet('user') || ! $this->isParamSet('pwd')) {
            $this->httpError(400, 'É necessário informar login e senha.');
        }

        try {
            $result = $this->tineSession->login(
                $this->param('user'),
                $this->param('pwd'),
                $this->isParamSet('captcha') ? $this->param('captcha') : null
            );
        } catch (PasswordExpiredException $pe) {
            return (object) array(
                'success' => false,
                'expired' => true
            );
        } catch (CaptchaRequiredException $cre) {
            return (object) array(
                    'success' => false,
                    'captcha' => $cre->getCaptcha()
            );
        }

        return (object) array(
            'success' => $result,
            'userInfo' => (object) array (
                'mailAddress' => $this->tineSession->getAttribute('Expressomail.email'),
                'mailSignature' => $this->tineSession->getAttribute('Expressomail.signature'),
                'mailBatch' => MAIL_BATCH,
            )
        );
    }

    /**
     * Allow this request to be called even without a previously estabilished
     * TineSession (after all, THIS is the request that estabilishes the TineSession!)
     *
     * @return true
     */
    public function allowAccessWithoutSession()
    {
        return true;
    }
}
