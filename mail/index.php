<?php
/*!
 * Expresso Lite
 * Entry index page for mail module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

@session_start();
require_once(dirname(__FILE__).'/../conf.php');
require_once(dirname(__FILE__).'/../inc/Ajax.class.php');
if (!Tine::isLogged()) header('location: ../');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1"/>
    <link type="text/css" rel="stylesheet" href="../inc/general.css"/>
    <link type="text/css" rel="stylesheet" href="mail.css"/>
    <title>Expresso Lite</title>
    <script src="../inc/jquery-2.1.3.min.js"></script>
    <script src="../inc/LoadCss.js"></script>
    <script src="../inc/DateFormat.js"></script>
    <script src="../inc/UrlStack.js"></script>
    <script src="../inc/ContextMenu.js"></script>
    <script src="../inc/Layout.js"></script>
    <script src="../inc/UploadFile.js"></script>
    <script src="../inc/ModelessDialog.js"></script>
    <script src="Contacts.js"></script>
    <script src="ThreadMail.js"></script>
    <script src="WidgetFolders.js"></script>
    <script src="WidgetHeadlines.js"></script>
    <script src="WidgetMessages.js"></script>
    <script src="WidgetAttacher.js"></script>
    <script src="WidgetSearchAddr.js"></script>
    <script src="WidgetCompose.js"></script>
    <script src="mail.js"></script>
</html>
<body>
    <section id="info"><!-- exposed server constants -->
        <input type="hidden" id="mailBatch" value="<?=MAIL_BATCH?>"/>
        <input type="hidden" id="mailAddress" value="<?=$_SESSION['user_email']?>"/>
        <input type="hidden" id="mailSignature" value="<?=$_SESSION['user_signature']?>"/>
    </section>
    <section id="icons"><!-- icon templates -->
        <img class="throbber" src="../img/chromiumthrobber.svg"/>
        <div class="icoReplied" title="Você respondeu este email"></div>
        <div class="icoConfirm" title="O remetente deste email receberá uma confirmação de leitura"></div>
        <div class="icoImportant" title="O remetente deste email o marcou como importante"></div>
        <div class="icoAttach" title="Este email contém anexo(s)"></div>
        <div class="icoForwarded" title="Você já encaminhou este email"></div>
        <div class="icoSigned" title="Esta mensagem foi assinada digitalmente"></div>
        <div class="icoHigh0" title="Mensagem não destacada"></div>
        <div class="icoHigh1" title="Mensagem destacada"></div>
        <div class="icoCheck0" title="Selecionar mensagem"></div>
        <div class="icoCheck1" title="Desselecionar mensagem"></div>
    </section>
    <aside id="leftColumn">
        <input type="button" id="btnUpdateFolders" value="atualizar" title="Atualizar lista de pastas"/>
        <div id="txtUpdateFolders">atualizando... <img class="throbber" src="../img/chromiumthrobber.svg"/></div>
        <input type="button" id="btnCompose" value="escrever" title="Escrever um novo email"/>
        <div id="foldersArea"><!-- folder tree renders here --></div>
    </aside>
    <section id="bigBody">
        <section id="middleBody">
            <div id="headlinesArea"><!-- headlines list renders here --></div>
            <footer id="headlinesFooter">
                <span id="loadedCount"></span> &nbsp;
                <input type="button" id="loadMore" value="carregar mais"/>
            </footer>
        </section>
        <section id="rightBody">
            <header id="subject">
                <div id="subjectText"><!-- thread subject goes here --></div>
            </header>
            <div id="messagesArea"><!-- messages list renders here --></div>
        </section>
    </section>
</body>
</html>
