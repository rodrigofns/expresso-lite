<?php
/*!
 * Expresso Lite
 * Global configuration file.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

define('BACKEND_URL', 'https://expressobr.serpro.gov.br/index.php');
define('CLASSIC_URL', 'https://expressobr.serpro.gov.br');
define('ANDROID_URL', '');
define('IOS_URL', ''); // app download links

define('MAIL_BATCH', 50); // how many email entries loaded by default

/*
 * WARNING: The following values are only meant to be used in the
 * development environment for debug purposes. DO NOT USE THEM IN A
 * PRODUCTION ENVIRONMENT!!!
 */


/*
 * It is possible to make ExpressoLite activate PHP XDebug in the target
 * Tine backend server. To do this, configure a constant named
 * ACTIVATE_TINE_XDEBUG with the value true, like:
 * -------------
 * define('ACTIVATE_TINE_XDEBUG', true);
 * -------------
 */


/*
 * Expresso Lite has a special debugger module with several tools that are
 * useful during development. To make it available, set the following
 * constant with true
 * -------------
 * define('SHOW_DEBUGGER_MODULE', true);
 * -------------
 */


/*
 * It is possible to make Expresso Lite client to trace every AJAX call
 * that has not yet received a response. This is specially useful when
 * running Selenium tests, as this will provide more information when a
 * timeout occurs. To enable tracing, set the following constant with true
 * -------------
 * define('TRACE_PENDING_AJAX_CALLS', true);
 * -------------
 */
