<?php
/*!
 * Expresso Lite
 * Entry index page for mail module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

require_once (dirname(__FILE__) .'/../inc/bootstrap.php');

$tineSession = ExpressoLite\Backend\TineSessionRepository::getTineSession();

if(!$tineSession->isLoggedIn()) {
    header('location: ../');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1"/>
    <link type="text/css" rel="stylesheet" href="../inc/general.css"/>
    <link type="text/css" rel="stylesheet" href="mail.css"/>
    <title>Expresso Lite</title>
    <script src="../inc/require.min.js" data-main="mail.js"></script>
</head>
<body>
    <section id="info"><!-- exposed server constants -->
        <input type="hidden" id="mailBatch" value="<?=MAIL_BATCH?>"/>
        <input type="hidden" id="mailAddress" value="<?=$tineSession->getAttribute('Expressomail.email') ?>"/>
        <input type="hidden" id="mailSignature" value="<?=$tineSession->getAttribute('Expressomail.signature') ?>"/>
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
    <section id="sections">
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
    </section>
</body>
</html>
