<?php
/**
 * Expresso Lite
 * Represents a generic exception thrown by a ExpressoLite feature
 *
 * @package   ExpressoLite\Backend\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Exception;

use \Exception;

class LiteException extends Exception
{

    /**
     * @var int $httpCode Http code returned to the frontend when this exception is
     * thrown during a call done by AjaxProcessor
     */
    private $httpCode;

    /**
     * Creates a new <tt>LiteException</tt>
     *
     * @param string $message The exception message.
     * @param int $code The exception code used for logging.
     * @param int $httCode If the exception occurs during a call done by AjaxProcessor,
     *    this is the http code returned to the frontend
     */
    public function __construct($message, $code = 0, $httpCode=500)
    {
        parent::__construct($message, $code);
        $this->setHttpCode($httpCode);
    }

    /**
     * Creates a string representation of the exception
     *
     */
    public function __toString()
    {
        return get_called_class() . ": [{$this->code}]: {$this->message} (HTTP $this->httpCode)\n";
    }

    /**
     * Gets httpCode
     *
     */
    public function getHttpCode() {
        return $this->httpCode;
    }

    /**
     * Sets httpCode
     *
     * @param int $httCode The HTTP code to be set for this exception
     */
    public function setHttpCode($httpCode) {
        $this->httpCode = $httpCode;
    }

}
