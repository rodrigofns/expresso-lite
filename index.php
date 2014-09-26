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
	<link rel="stylesheet" href="inc/general.css"/>
	<title>Expresso Lite</title>
	<script src="inc/jquery-2.1.0.min.js"></script>
	<script src="inc/jquery-serialize.js"></script>
	<script src="login.js"></script>
	<style>
	#topgray { position:absolute; top:0; height:70%; width:100%; background-color:#DADADA; }
	#thebg { position:absolute; top:30%; height:40%; width:100%;
		background-image:url("img/fondo.jpg"); background-position:50% 70%; background-size:100%;
		border-top:1px solid #CDCDCD; }
	#blue { position:absolute; top:70%; width:100%; height:8px; background:#005A97; }
	#credent { max-width:240px; background-color:rgba(255,255,255,.88); text-align:center;
		border:1px solid #E2E2E2; padding:10px; box-shadow:2px 2px 6px #999; }
	#btnLogin { margin:3px 0; }
	#throbber { display:none; margin:6px 0; }
	#throbber > span { font-style:italic; color:#666; -moz-user-select:none; -webkit-user-select:none; }
	@media (max-width:767px) { /* phones */
		#credent { margin:10% auto; }
		#topgray,#thebg,#blue,#versionInfo { z-index:-1; }
		#links { position:absolute; bottom:80px; left:12px; width:90%; font-size:90%; text-align:center; }
	}
	@media (min-width:768px) { /* everyone else */
		#credent { position:absolute; right:20%; top:17%; }
		#links { position:absolute; bottom:20px; left:12px; font-size:90%; }
	}
	input[type=text],input[type=password] { height:2.2em; margin:6px; width:180px; }
	#versionInfo { position:absolute; bottom:10px; right:8px; font-size:80%; text-align:right; color:#AAA; }
	input[type=text]:focus, input[type=password]:focus { box-shadow:0 0 6px #6EA2DE; border:1px solid #6EA2DE; }
	</style>
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
			<div id="throbber"><span></span> &nbsp; <img src="img/chromiumthrobber.svg"/></div>
		</form>
	</div>
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
	<div id="versionInfo"></div>
</body>
</html>