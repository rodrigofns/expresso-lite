<?php
/**
 * Tine Tunnel
 * Exception thrown when Tine fails a login because it requires the user
 * to inform a CAPTCHA.
 *
 * @package   ExpressoLite\TineTunnel/Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\TineTunnel\Exception;

class CaptchaRequiredException extends TineTunnelException
{
    private $captcha;

    public function __construct($captcha)
    {
        $this->captcha = $captcha;
    }

    public function getCaptcha()
    {
        return $this->captcha;
    }
}
