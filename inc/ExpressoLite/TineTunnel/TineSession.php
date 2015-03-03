<?php
/**
 * Tine Tunnel
 * Represents an estabilished session between a user and Tine.
 * Provides facilities for login, caches useful login info (account, registry
 * data) and handles cookies.
 *
 * @package   ExpressoLite\TineTunnel
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\TineTunnel;

use ExpressoLite\TineTunnel\CookieHandler;
use ExpressoLite\TineTunnel\TineJsonRpc;
use ExpressoLite\TineTunnel\Exception\TineException;
use ExpressoLite\TineTunnel\Exception\PasswordExpiredException;

class TineSession implements CookieHandler
{

    /**
     * @var string $tineUrl Tine URL address
     */
    private $tineUrl;

    /**
     * @var string $jsonKey Tine's jsonKey estabilished during login
     */
    private $jsonKey = null;

    /**
     * @var array $cookies An indexed array with all cookie names and values
     * associated with the TineSession
     */
    private $cookies = array();

    /**
     * @var array $attributes An indexed array with revelant attribute names
     * and values associated to the current session. These are usually stored
     * during the initial login process.
     */
    private $attributes = array();

    /**
     * @var boolean $activateTineXDebug if this is true, it will append
     * GET parameters to the call that will activate XDebug in the Tine server.
     * Should be used only for debug purposes
     */
    private $activateTineXDebug = false;

    /**
     * @var string $locale Locale to be used in Tine
     */
    private $locale = null;

    /**
     * @var boolean $isLocaleSet Indicates whether this session has already set
     * Tine's locale with 'Tinebase.setLocale'
     */
    private $isLocaleSet = false;

    /**
     * Creates a new TineSession that will be associated to a target Tine url.
     *
     * @param $tineUrl The address in which Tine is located
     */
    public function __construct($tineUrl)
    {
        $this->tineUrl = $tineUrl;
    }

    /**
     * @return the URL of the Tine server to which this session is connected to
     */
    public function getTineUrl()
    {
        return $this->tineUrl;
    }

    /**
     * @return the JSON key this session got during login
     *
     */
    public function getJsonKey()
    {
        return $this->jsonKey;
    }

    /**
     * Sets Tine locale to be used for this session. This will
     * impact in the language of messages in future responses from Tine.
     *
     */
    public function setLocale($locale)
    {
        return $this->locale = $locale;
    }

    /**
     * Public method that executes a JSON RPC call to tine. If no locale
     * has been set for this session yet, it first sets Tine locale, so that
     * the response comes in the expected language.
     *
     * @param string $method The RPC method to be executed
     * @param array $params The params of the RPC
     * @param boolean $acceptErrors Indicates if exception throwing should
     *     be supreressed when Tine returns a response with an error
     *
     * @return The JSON RPC call response given by Tine
     */
    public function jsonRpc($method, $params = array(), $acceptErrors = false)
    {
        if ($this->locale !== null && ! $this->isLocaleSet) {
            $this->sendJsonRpc('Tinebase.setLocale', (object) array(
                'localeString' => $this->locale,
                'saveaspreference' => 'true',
                'setcookie' => 'true'
            ));
            $this->isLocaleSet = true;
        }

        return $this->sendJsonRpc($method, $params, $acceptErrors);
    }


    /**
     * Private method that executes a JSON RPC call to tine
     * (without worrying about locale).
     *
     * @param string $method The RPC method to be executed
     * @param array $params The params of the RPC
     * @param boolean $acceptErrors Indicates if exception throwing should
     *     be supreressed when Tine returns a response with an error
     *
     * @return The JSON RPC call response given by Tine
     */
    private function sendJsonRpc($method, $params = array(), $acceptErrors = false)
    {
        $tineJsonRpc = new TineJsonRpc();

        $tineJsonRpc->setTineUrl($this->tineUrl);
        $tineJsonRpc->setActivateTineXDebug($this->activateTineXDebug);
        $tineJsonRpc->setCookieHandler($this);
        $tineJsonRpc->setRpcMethod($method);
        $tineJsonRpc->setRpcParams($params);
        $tineJsonRpc->setJsonKey($this->jsonKey);
        return $tineJsonRpc->send();
    }


    /**
     * Executes Tinebase.login in Tine and returns its result.
     *
     * @param string $user User login
     * @param string $password User password
     *
     * @return The result of the login attempt as returned by Tine
     *
     */
    private function getLoginInfo($user, $password)
    {
        try {
            return $this->jsonRpc('Tinebase.login', (object) array(
                'username' => $user,
                'password' => $password,
                'securitycode' => ''
            ));
        } catch (Exception $e) {
            throw new TineException('Tinebase.login: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Executes Tinebase.getAllRegistryData in Tine and returns its result.
     *
     * @return The result of Tinebase.getAllRegistryData as returned by Tine
     *
     */
    private function getAllRegistryData()
    {
        return $this->jsonRpc('Tinebase.getAllRegistryData');
    }


    /**
     * Logins the tine session in Tine, registering the jsonKey to
     * perform authenticated calls in the future. It also caches relevant
     * attributes from login and registry data for future reference.
     *
     * @param string $user User login
     * @param string $password User password
     *
     * @return True if the login was successful, false otherwise
     *
     */
    public function login($user, $password)
    {
        $loginInfo = $this->getLoginInfo($user, $password);

        if ($loginInfo->result->success === false && strpos($loginInfo->result->errorMessage, 'Your password has expired. You must change it.') === 0) {
            throw new PasswordExpiredException();
        }

        if ($loginInfo->result->success) {
            $registryData = $this->getAllRegistryData();

            $this->jsonKey = $loginInfo->result->jsonKey;
            $this->saveRelevantAttributes($loginInfo, $registryData);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Caches relevant values from login info and registry data
     * for quick reference in the future
     *
     * @param $loginInfo The result of Tinebase.login
     * @param $registryData The result of Tinebase.getAllRegistryData
     *
     */
    private function saveRelevantAttributes($loginInfo, $registryData)
    {
        $this->setAttribute('Expressomail.accountId', $registryData->result->Expressomail->accounts->results[0]->id);
        $this->setAttribute('Expressomail.email', $registryData->result->Expressomail->accounts->results[0]->email);
        $this->setAttribute('Tinebase.accountLoginName', $registryData->result->Tinebase->currentAccount->accountLoginName);
        $this->setAttribute('Expressomail.from', $registryData->result->Expressomail->accounts->results[0]->from);
        $this->setAttribute('Expressomail.organization', $registryData->result->Expressomail->accounts->results[0]->organization);
        $this->setAttribute('Expressomail.signature', htmlspecialchars($registryData->result->Expressomail->accounts->results[0]->signature));
        $this->setAttribute('Tinebase.accountId', $registryData->result->Tinebase->currentAccount->accountId);
    }

    /**
     * @return true if this Tine session has already performed a successful
     * login in Tine, false otherwise
     *
     */
    public function isLoggedIn()
    {
        return $this->jsonKey != null;
    }

    /**
     * Logs out from Tine
     *
     */
    public function logout()
    {
        try {
            $response = $this->jsonRpc('Tinebase.logout');
            $this->jsonKey = null;
        } catch (Exception $e) {
            throw new TineException('Tinebase.logout: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Stores a cookie that will be used for future requests to Tine.
     *
     * @param $cookie An object with two fields: $cookie->name and $cookie->value
     *
     */
    public function storeCookie($cookie)
    {
        $this->cookies[$cookie->name] = $cookie;

        if (strcmp($cookie->name, 'PHPSESSID') !== 0 && ! defined('TEST_ENVIRONMENT')) {
            setcookie($cookie->name, $cookie->value, $cookie->expires);
        }
        // TODO: remove this if above after refactoring is complete
    }

    /**
     * Returns an array with all the stored cookies
     *
     * @return $cookie Array with all the stored cookies
     *
     */
    public function getCookies()
    {
        $result = array();
        foreach ($this->cookies as $name => $cookie) {
            $result[] = $cookie;
        }
        return $result;
    }

    /**
     * Returns the $cookie with a specific name
     *
     * @param string $cookieName The cookie name
     *
     * @return An object with two fields: $cookie->name and $cookie->value
     *
     */
    public function getCookie($cookieName)
    {
        return $this->cookies[$cookieName];
    }

    /**
     * Sets a generic attribute in this TineSession
     *
     * @param string $attrName The attribute name
     * @param string $attrValue The attribute value
     *
     */
    public function setAttribute($attrName, $attrValue)
    {
        $this->attributes[$attrName] = $attrValue;
    }

    /**
     * Gets a generic attribute value
     *
     * @param string $attrName The attribute name

     * @return The attribute value
     *
     */
    public function getAttribute($attrName)
    {
        return $this->attributes[$attrName];
    }

    /**
     * Sets $activateTineXDebug. When this attribute is true, all JSON RPC calls
     * made to Tine will activate XDebug in the Tine server
     * (if the server is configured for XDebug)
     *
     * @param booelan $activateTineXDebug

     */
    public function setActivateTineXDebug($activateTineXDebug)
    {
        $this->activateTineXDebug = $activateTineXDebug;
    }
}
