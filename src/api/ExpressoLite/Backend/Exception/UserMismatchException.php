<?php
/**
 * Expresso Lite
 * Thrown when user ID accidentally changes during an ordinary session.
 *
 * @package   ExpressoLite\Backend\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Exception;

class UserMismatchException extends LiteException
{
    /**
     * Creates a new <tt>UserMismatchException</tt>.
     * It defaults parent class httpCode to 401.
     *
     * @param string $message
     *            The exception message.
     * @param int $code
     *            The exception code used for logging.
     */
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code, 401);
    }
}
