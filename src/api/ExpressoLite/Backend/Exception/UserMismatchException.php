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
     * It sets parent class constructor params with default values
     */
    public function __construct()
    {
        parent::__construct('UserMismatchException', 0, 500);
    }
}
