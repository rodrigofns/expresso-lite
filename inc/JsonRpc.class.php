<?php
/*!
 * Expresso Lite
 * Low-level JSON-RPC abstraction.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

require(dirname(__FILE__).'/Request.class.php');

class JsonRpc extends Request
{
	private $rpcMethod = '';      // Object.method
	private $rpcParams = array(); // associative array with key/value

	public function rpcMethod($method) { $this->rpcMethod = $method; }
	public function rpcParams($params) { $this->rpcParams = $params; }

	public function send()
	{
		$this->postFields(json_encode(array(
			'id'      => 1,
			'jsonrpc' => '2.0',
			'method'  => $this->rpcMethod,
			'params'  => $this->rpcParams
		)));
		return parent::send(parent::POST);
	}
}