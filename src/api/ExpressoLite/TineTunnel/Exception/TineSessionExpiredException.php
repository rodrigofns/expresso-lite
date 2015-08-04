<?php
/**
 * Tine Tunnel
 * This exception is thrown when a TineJsonRpc call is made while
 * Tine is not auhtenticated.
 *
 * @package   ExpressoLite\TineTunnel/Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\TineTunnel\Exception;

use ExpressoLite\TineTunnel\Exception\TineTunnelException;

class TineSessionExpiredException extends TineTunnelException
{
    public function __construct()
    {
        parent::__construct('User session on Tine is likely to be expired');
    }
}

