<?php
/**
 * Expresso Lite
 * Test case that verifies the behavior of the login screen.
 *
 * @package ExpressoLiteTest\Functional\Login
 * @license http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLiteTest\Functional\Login;

use ExpressoLiteTest\Functional\Generic\ExpressoLiteTest;

class LoginTest extends ExpressoLiteTest
{
    /**
     * Checks a valid login attempt
     *
     * Input data:
     *
     * - valid.user: a valid user login
     * - valid.password: valid password for the user
     */
    public function testValidLogin()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_USER = $this->getTestValue('valid.user');
        $VALID_PASSWORD = $this->getTestValue('valid.password');

        $loginPage->typeUser($VALID_USER);
        $loginPage->typePassword($VALID_PASSWORD);

        $loginPage->clickLogin();
        $this->waitForUrl(LITE_URL . '/mail/');

        $this->assertElementPresent('#headlinesArea', 'Headlines listing was not available after successful login');
    }

    /**
     * Checks a login attempt with an invalid user
     *
     * Input data:
     *
     * - invalid.user: an invalid user login
     * - valid.password: some valid password
     */
    public function testInvalidUser()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $INVALID_USER = $this->getTestValue('invalid.user');
        $VALID_PASSWORD = $this->getTestValue('valid.password');

        $loginPage->typeUser($INVALID_USER);
        $loginPage->typePassword($VALID_PASSWORD);

        $loginPage->clickLogin();

        $this->assertAlertTextEquals(
                "Não foi possível efetuar login.\nO usuário ou a senha estão incorretos.",
                'Problems with incorrect user message');
        $this->dismissAlert();
    }

    /**
     * Checks a login attempt with a valid user, but with the wrong password
     *
     * Input data:
     *
     * - valid.user: a valid user login
     * - invalid.password: wrong password for the user
     */
    public function testInvalidPassword()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_USER = $this->getTestValue('valid.user');
        $INVALID_PASSWORD = $this->getTestValue('invalid.password');

        $loginPage->typeUser($VALID_USER);
        $loginPage->typePassword($INVALID_PASSWORD);

        $loginPage->clickLogin();

        $this->assertAlertTextEquals(
                "Não foi possível efetuar login.\nO usuário ou a senha estão incorretos.",
                'Problems with incorrect password message');
        $this->dismissAlert();
    }

    /**
     * Checks a login attempt where only the password is typed,
     * but not the user login
     *
     * Input data:
     *
     * - valid.password: some random password
     */
    public function testNoUser()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_PASSWORD = $this->getTestValue('valid.password');

        $loginPage->typePassword($VALID_PASSWORD);

        $loginPage->clickLogin();
        $this->assertAlertTextEquals(
                "Por favor, digite seu nome de usuário.",
                'Problems with user not informed message');
        $this->dismissAlert();
    }

    /**
     * Checks a login attempt where only the user login is typed,
     * but not the password
     *
     * Input data:
     *
     * - valid.user: a valid user login
     */
    public function testNoPassword()
    {
        $loginPage = new LoginPage($this);

        //load test data
        $VALID_USER = $this->getTestValue('valid.user');

        $loginPage->typeUser($VALID_USER);

        $loginPage->clickLogin();
        $this->assertAlertTextEquals(
                "Por favor, digite sua senha.",
                'Problems with password not informed message');
        $this->dismissAlert();
    }
}
