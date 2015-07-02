<?php
/**
 * Expresso Lite
 * Exception thrown when a LiteRequest is invoked without estabilishing
 * a TineSession first. However, this exception is not thrown if
 * LiteRequest->allowAccessWithoutSession() returns true.
 *
 *
 * @package   ExpressoLite\Backend\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Exception;

class NoTineSessionException extends LiteException {

    /**
     * Creates a new <tt>NoTineSessionException</tt>.
     * It defaults parent class httpCode to 401.
     *
     * @param string $message
     *            The exception message.
     * @param int $code
     *            The exception code used for logging.
     */
    public function __construct($message, $code = 0) {
        parent::__construct ( $message, $code, 401 );
    }
}
