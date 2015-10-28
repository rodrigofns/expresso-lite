<?php
/**
 * Expresso Lite
 * Represents a Expresso Lite test case that will use a single login
 * for all tests. This speeds the execution of the tests considerably.
 *
 * @package ExpressoLiteTest\Functional\Generic
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Generic;

use ExpressoLiteTest\Functional\Login\LoginPage;

class SingleLoginTest extends ExpressoLiteTest
{
    /**
     * @var boolean Indicates if the login was already performed in this test case
     */
    protected static $isLoggedIn = false;

    /**
     * Overrides ExpressoLiteTest::setUpPage. This method will perform login into
     * Expresso Lite, but only if it was not already done in this test case.
     *
     * @see \ExpressoLiteFunctionalTests\Generic\ExpressoLiteTest::setUpPage()
     */
    public function setUpPage()
    {
        $this->setClearSessionDataBetweenTests(false);
        if (!self::$isLoggedIn) {
            parent::setUpPage();
            $this->doLogin();
        } else {
            parent::setUpPage($this->getTestUrl());
            $this->waitForAjaxToComplete();
        }
    }

    /**
     * Performs the login steps in the current window. The user and password to be
     * used will be searched in a section named [login] in the test data file.
     */
    private function doLogin()
    {
        $user = $this->testData->getTestValue('login', 'user');
        $password = $this->testData->getTestValue('login', 'password');

        $loginPage = new LoginPage($this);
        $loginPage->doLogin($user, $password);
        self::$isLoggedIn = true;
    }

    /**
     * Overrides PHPUnit onNotSuccessfulTest. As failed tests reset the selenium session,
     * this methods will also reset $isLoggedIn value to ensure a new login is made in
     * Expresso Lite on the next test to be executed.
     *
     * @param unknown $e The exception that made the test fail.
     */
    public function onNotSuccessfulTest($e)
    {
        self::$isLoggedIn = false;
        parent::onNotSuccessfulTest($e);
    }

    /**
     * Returns the URL to be used when the user starts a new test after the login
     * was made. This is meant to be overwritten in subclasses
     *
     * @return string The URL to be used in the begining of each test
     */
    public function getTestUrl()
    {
        return LITE_URL;
    }
}
