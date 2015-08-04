<?php
/**
 * Tine Tunnel
 * Exception thrown when Tine returns a response with an error
 *
 * @package   ExpressoLite\TineTunnel/Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\TineTunnel\Exception;

use ExpressoLite\TineTunnel\Exception\TineTunnelException;

class TineErrorException extends TineTunnelException
{
    private $tineError;

    public function __construct($tineError)
    {
        $code = isset($tineError->code) ? $tineError->code : '<undefined>';
        $message = isset($tineError->message) ? $tineError->message: '<undefined>';

        parent::__construct("Tine returned an error. Code: $code; Message: $message");
        $this->tineError = $tineError;
    }

    public function getTineError()
    {
        return $this->tineError;
    }
}
