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
if(!Tine::isLogged()) header('location: ../');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1"/>
    <link rel="stylesheet" href="../inc/general.css"/>
    <link rel="stylesheet" href="mail.css"/>
    <title>Expresso Lite</title>
    <script src="../inc/UrlStack.js"></script>
    <script src="../inc/DateFormat.js"></script>
    <script src="../inc/sprintf.min.js"></script>
    <script src="../inc/jquery-2.1.1.min.js"></script>
    <script src="../inc/jquery-serialize.js"></script>
    <script src="../inc/jquery-dropdownMenu.js"></script>
    <script src="../inc/jquery-modelessDialog.js"></script>
    <script src="../inc/jquery-uploadFile.js"></script>
    <script src="Contacts.js"></script>
    <script src="ThreadMail.js"></script>
    <script src="WidgetFolders.js"></script>
    <script src="WidgetHeadlines.js"></script>
    <script src="WidgetMessages.js"></script>
    <script src="WidgetAttacher.js"></script>
    <script src="WidgetSearchAddr.js"></script>
    <script src="WidgetCompose.js"></script>
    <script src="mail.js"></script>
</head>
<body>
    <header id="bigheader">
        <div id="headerLogo"><img src="../img/logo-lite-165.png"/></div>
        <div id="headerMenu">&laquo; <span id="headerMenuFolderName"></span>
            <span id="headerFolderCounter" class="folders_counter"></span></div>
        <div id="headerCloseMessage">&laquo; <span>voltar &nbsp;</span></div>
        <div id="headerCompose"><input type="button" class="compose" value="escrever" title="Escrever um novo email"/></div>
        <div id="headerLogoff"><div class="userAddr"><?=$_SESSION['user_email']?></div> &nbsp;
            <input type="button" class="logoff" value="logoff"/></div>
        <div id="headerHeadlinesMenu"><input id="headerHeadlinesDropdown" type="button" value="menu"/></div>
    </header>
    <section id="bigbody">
        <aside id="leftColumn">
            <input type="button" id="updateFolders" value="atualizar" title="Atualizar lista de pastas"/>
            <span id="updateFoldersWait">... &nbsp;</span>
            <span id="composeFoldersSlot"><input type="button" class="compose" value="escrever" title="Escrever um novo email"/></span>
            <div id="foldersArea"></div>
            <div id="logoffFoldersSlot"><span class="userAddr"><?=$_SESSION['user_email']?></span><br/><br/>
                <input type="button" class="logoff" value="logoff" title="Sair do Expresso Lite"/></div>
        </aside>
        <section id="centerColumn">
            <div id="headlinesArea"></div>
            <footer id="headlinesFooter">
                <span id="loadedCount"></span> &nbsp;
                <input type="button" id="loadMore" value="carregar mais"/>
            </footer>
        </section>
        <section id="rightColumn">
            <div id="subjectTop">
                <div id="subjectText"></div>
                <div id="closeButton"><input type="button" id="closeMessages" value="fechar"/></div>
            </div>
            <div id="messagesArea"></div>
        </section>
    </section>
    <section id="templates">
        <div class="headlines_entry"><!-- template for each headline -->
            <div class="headlines_check"><div class="icoCheck0"></div></div>
            <div class="headlines_sender"></div>
            <div class="headlines_highlight"></div>
            <div class="headlines_subject"></div>
            <div class="headlines_icons"></div>
            <div class="headlines_when"></div>
        </div>
        <div class="messages_unit"><!-- template for each message -->
            <div class="messages_top1">
                <div class="messages_mugshot"><img src=""/></div>
                <div class="messages_from">
                    <div class="messages_fromName"></div>
                    <div class="messages_fromMail"></div>
                </div>
                <div class="messages_icons"></div>
                <div class="messages_when"></div>
            </div>
            <div class="messages_top2">
                <input class="messages_dropdown" type="button" value="menu"/>
                <div class="messages_people">
                    <div class="messages_addr"><b>Para:</b> <span class="messages_addrTo"></span></div>
                    <div class="messages_addr"><b>Cc:</b> <span class="messages_addrCc"></span></div>
                    <div class="messages_addr"><b>Bcc:</b> <span class="messages_addrBcc"></span></div>
                </div>
            </div>
            <div class="messages_attachs"></div>
            <div class="messages_content">
                <div class="messages_body"></div>
                <input class="messages_showQuote" type="button" value="citações" title="Ver citações encaminhadas na mensagem"/>
                <div class="messages_quote"></div>
            </div>
        </div>
        <span class="messages_addrPerson"><!-- template for an address in message grey top header -->
            <span class="messages_addrName"></span>
            <span class="messages_addrDomain"></span>
        </span>
    </section>
    <section id="composePanel"><!-- will be shown into a non-modal window -->
        <div><input type="text" id="composePanel_subject" placeholder="Assunto..."/></div>
        <div id="composePanel_toggs">
            <a id="composePanel_toBtn" href="#" title="Adicionar destinatários do email">Para...</a> &nbsp;
            <a id="composePanel_ccBtn" href="#" title="Adicionar destinatários a receber cópia do email">Cc...</a> &nbsp;
            <a id="composePanel_bccBtn" href="#" title="Adicionar destinatários a receber cópia oculta do email">Bcc...</a>
        </div>
        <textarea id="composePanel_to" placeholder="Para..."></textarea>
        <textarea id="composePanel_cc" placeholder="Cc..."></textarea>
        <textarea id="composePanel_bcc" placeholder="Bcc..."></textarea>
        <div contentEditable="true" id="composePanel_body"></div>
        <div id="composePanel_attacher"></div>
        <div>
            <input type="button" id="composePanel_send" value="&nbsp; enviar &nbsp;" title="Enviar este email"/> &nbsp;
            <input type="button" id="composePanel_draft" value="salvar rascunho" title="Salvar na pasta Rascunhos"/> &nbsp;
            <input type="button" id="composePanel_attachNew" title="Anexar arquivo" value="anexar..."/> &nbsp; &nbsp;
            <label title="Marcar esta mensagem como importante"><input type="checkbox" id="composePanel_important"/><div
                class="icoImportant" title="Marcar esta mensagem como importante"></div></label>
        </div>
    </section>
    <div id="icons"><!-- icon templates -->
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
    </div>
    <div id="info">
        <input type="hidden" id="mailBatch" value="<?=MAIL_BATCH?>"/>
        <input type="hidden" id="mailAddress" value="<?=$_SESSION['user_email']?>"/>
        <input type="hidden" id="mailSignature" value="<?=$_SESSION['user_signature']?>"/>
    </div>
</body>
</html>
