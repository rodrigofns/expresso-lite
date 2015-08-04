<?php
/**
 * Tine Tunnel
 * Superclass of all Tine Tunnel Exceptions
 *
 * @package   ExpressoLite\TineTunnel/Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\TineTunnel\Exception;

use \Exception;

class TineTunnelException extends Exception
{
    public function __construct ($message)
    {
        parent::__construct($message, 0, null);
    }
}
