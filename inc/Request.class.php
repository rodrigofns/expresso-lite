<?php
/*!
 * Expresso Lite
 * Low-level HTTP request abstraction.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

class Request
{
	private $url          = '';      // for a GET request, pass the whole URL with params after '?'
	private $headers      = array(); // simple array of 'Key: value' strings, not an associative array with key/value
	private $cookies      = false;   // transparent cookie handling?
	private $binaryOutput = false;   // useful when downloading files; streams direct to client, instead of put into a variable
	private $postFields   = '';      // http://stackoverflow.com/questions/5224790/curl-post-format-for-curlopt-postfields

	public function url($url)           { $this->url          = $url;     }
	public function headers($headers)   { $this->headers      = $headers; }
	public function cookies($cookies)   { $this->cookies      = $cookies; }
	public function binaryOutput($use)  { $this->binaryOutput = $use;     }
	public function postFields($fields) { $this->postFields   = $fields;  }

	const GET  = 'GET';
	const POST = 'POST';

	public function send($method=self::GET)
	{
		$curl = curl_init($this->url);
		curl_setopt_array($curl, array(
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_ENCODING       => 'gzip',
			CURLOPT_RETURNTRANSFER => !$this->binaryOutput,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_CONNECTTIMEOUT => 60,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER         => !$this->binaryOutput,
			CURLOPT_HTTPHEADER     => $this->headers,
			CURLOPT_COOKIE         => $this->cookies ? $this->_currentCookies() : '',
			CURLOPT_POSTFIELDS     => $this->postFields,
			CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4
		));
		$errMsg = '';
		$req = curl_exec($curl);
		if($req === false)
			$errMsg = sprintf('cURL failed. #%d: %s', curl_errno($curl), curl_error($curl));
		curl_close($curl);
		if($errMsg != '')
			throw new Exception($errMsg);

		if($this->binaryOutput) {
			return true;
		} else if($this->_isImage($req)) {
			return bin2hex(base64_encode( substr($req, strpos($req, "\r\n\r\n") + 4) ));
		} else {
			return $this->_crackResponse($req);
		}
	}

	private function _currentCookies()
	{
		$vals = array();
		foreach($_COOKIE as $name => $val)
			if(strcmp($name, 'PHPSESSID') !== 0)
				$vals[] = ($name.'='.$val);
		return implode('; ', $vals);
	}

	private function _isImage($res)
	{
		$lines = explode("\r\n", $res);
		$ctts = array(
			'Content-Type: image/gif',
			'Content-Type: image/jpeg',
			'Content-Type: image/png',
			'Content-Type: image/tiff'
		);
		foreach($lines as $line)
			if(in_array($line, $ctts))
				return true;
		return false;
	}

	private function _crackResponse($res)
	{
		$lines = explode("\r\n", $res);
		foreach($lines as $line) {
			if($this->cookies && strncasecmp($line, 'Set-Cookie: ', strlen('Set-Cookie: ')) === 0) {
				$coo = (object)array(
					'name'     => '',
					'value'    => '',
					'expires'  => 0,
					'path'     => '',
					'domain'   => '',
					'secure'   => false,
					'httpOnly' => false
				);
				$cookieParts = explode(';', substr($line, strlen('Set-Cookie: ')));
				foreach($cookieParts as $cookiePart) {
					$cookiePart = trim($cookiePart);
					$pair = explode('=', $cookiePart);
					switch($pair[0]) {
					case 'path':     $coo->path = $pair[1]; break;
					case 'HttpOnly': $coo->httpOnly = true; break;
					case 'secure':   $coo->secure = true;   break;
					case 'expires':
						$dtt = DateTime::createFromFormat('D, d-M-Y H:i:s T', $pair[1]);
						$coo->expires = $dtt->getTimestamp();
						break;
					default:
						$coo->name  = $pair[0];
						$coo->value = $pair[1];
					}
				}
				setcookie($coo->name, $coo->value, $coo->expires); // ignore other cookie information
			}
		}
		return json_decode($lines[count($lines) - 1]);
	}
}