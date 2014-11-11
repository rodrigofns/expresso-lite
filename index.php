<?php
/*!
 * Expresso Lite
 * Main index login page.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

@session_start();
require_once(dirname(__FILE__).'/conf.php');

define('EXPRESSOLITE_PACKAGE_STRING', 'lite_development');

if(!function_exists('curl_init')) die('PHP cURL (php5-curl) library not installed.');
if(isset($_REQUEST['r'])) { // that's an AJAX request
    require('inc/Ajax.class.php');
    Ajax::ProcessRequest();
    exit; // nothing is outputted here
}
require('inc/Tine.class.php');
if(Tine::IsLogged())
    header('location: ./mail');
$lastLogin = isset($_COOKIE['TINE20LASTUSERID']) ? $_COOKIE['TINE20LASTUSERID'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1"/>
    <link type="text/css" rel="stylesheet" href="inc/general.css"/>
    <link type="text/css" rel="stylesheet" href="login.css"/>
    <title>Expresso Lite</title>
    <script src="inc/jquery-2.1.1.min.js"></script>
    <script src="inc/jquery-serialize.js"></script>
    <script src="inc/LoadCss.js"></script>
    <script src="login.js"></script>
</head>
<body>
    <div id="topgray"></div>
    <div id="thebg"></div>
    <div id="blue"></div>
    <div id="credent">
        <img id="logo" src="img/logo-lite-165.png"/>
        <form id="frmLogin">
            <input type="text" id="user" placeholder="usuário" value="<?php echo $lastLogin;?>"/>
            <input type="password" id="pwd" placeholder="senha"/>
            <input type="submit" id="btnLogin" value="login"/>
            <div class="throbber"><span></span> &nbsp; <img src="img/chromiumthrobber.svg"/></div>
        </form>
    </div>
    <section id="templates">
        <form id="frmChangePwd">
            <input type="password" id="cpOldPwd" placeholder="senha antiga"/>
            <input type="password" id="cpNewPwd" placeholder="nova senha"/>
            <input type="password" id="cpNewPwd2" placeholder="repetir nova senha"/>
            <input type="submit" id="btnNewPwd" value="trocar"/>
            <div class="throbber"><span></span> &nbsp; <img src="img/chromiumthrobber.svg"/></div>
        </form>
    </section>
    <div id="links">
        <?php if(ANDROID_URL != '') { ?>
        <a href="<?php echo ANDROID_URL?>" title="Baixe na Google Play™"><img src="img/store-play.png"/></a>
        <!-- Google Play é uma marca registrada da Google Inc. -->
        <?php } ?>
        <?php if(IOS_URL != '') { ?>
        <a href="<?php echo IOS_URL?>" title="Baixe na Apple Store™"><img src="img/store-apple.png"/></a>
        <!-- Apple Store é uma marca registrada da Apple Inc. -->
        <?php } ?>
        <br/>
        <a href="<?php echo CLASSIC_URL;?>">Ir para a versão clássica do Expresso</a>
    </div>
    <div id="versionInfo">
        Informações da Versão<br />
        <?php echo EXPRESSOLITE_PACKAGE_STRING;?><br />
    </div>
</body>
</html>
