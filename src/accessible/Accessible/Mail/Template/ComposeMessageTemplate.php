<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Template for email composing.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/ComposeMessageTemplate.css" />
    <title><?= $VIEW->actionText ?> mensagem - ExpressoBr Acessível</title>
    <title>Seleção de pasta - ExpressoBr Acessível</title>
</head>
<body>

<div id="anchors">
    <div id="logomark"></div>
    <div id="anchors_area" name="anchors_area" class="menu">
        <nav id="anchors_nav" name="anchors_nav">
            <ul>
                <li><a href="<?= $VIEW->lnkBackUrl ?>" accesskey="v">Voltar para <?= $VIEW->lnkBackText ?> [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<br/>

<h2 class="anchors-title"><?= $VIEW->actionText ?> mensagem</h2>
<form action="<?= $VIEW->lnkSendMessageAction ?>" method="post" enctype="multipart/form-data">
    <fieldset class="Dialog_email">
        <input type="hidden" name="folderId" value="<?= $VIEW->folderId ?>" />
        <input type="hidden" name="folderName" value="<?= $VIEW->folderName ?>" />
        <input type="hidden" name="page" value="<?= $VIEW->page ?>" />
        <input type="hidden" name="replyToId" value="<?= $VIEW->replyToId ?>" />
        <input type="hidden" name="forwardFromId" value="<?= $VIEW->forwardFromId ?>" />
        <div class="Dialog_field">
            <label for="subject">Assunto:</label><br />
            <input type="text" name="subject" id="subject" required="required" value="<?= $VIEW->subject ?>" />
        </div>
        <div class="Dialog_field">
            <label for="addrTo">Destinatário:</label><br />
            <input type="email" multiple="multiple" name="addrTo" id="addrTo" required="required" value="<?= $VIEW->to ?>"/>
        </div>
        <div class="Dialog_field">
            <label for="addrCc">Destinatário em cópia:</label><br />
            <input type="email" multiple="multiple" name="addrCc" id="addrCc" value="<?= $VIEW->cc ?>" />
        </div>
        <div class="Dialog_field">
            <label for="addrBcc">Destinatário em cópia oculta:</label><br />
            <input type="email" multiple="multiple" name="addrBcc" id="addrBcc" />
        </div >
        <div class="Dialog_message">
            <label for="messageBody">Mensagem:</label><br />
            <textarea name="messageBody" id="messageBody" required="required"></textarea>
            <?php IF ($VIEW->signature !== '') : ?>
            <div class="compose_sign"><?= $VIEW->signature ?></div>
            <?php ENDIF; ?>
        </div>

        <?php IF ($VIEW->quotedBody !== '') : ?>
            <label class="quoted_area" for="quotedBody">Mensagem citada:</label>
            <div class="message_text" name="quotedBody" id="quotedBody"><?= $VIEW->quotedBody ?></div>
        <?php ENDIF; ?>

        <div class="Dialog_field">
            <p class="Attach">
                <label for="attach0">Anexar 1º arquivo:</label>&nbsp;
                <input type="file" name="attach0" id="attach0" />
            </p>
            <p class="Attach">
                <label for="attach1">Anexar 2º arquivo:</label>&nbsp;
                <input type="file" name="attach1" id="attach1" />
            </p>
            <p class="Attach">
                <label for="attach2">Anexar 3º arquivo:</label>&nbsp;
                <input type="file" name="attach2" id="attach2" />
            </p>
        </div>

        <div class="compose_footer">
            <div>
                <label for="important">Esta mensagem é importante</label>
                <input type="checkbox" name="important" id="important" title="Marcar essa mensagem como importante" />
            </div>
            <br/>
            <div >
                <input type="submit" value="Enviar" />
            </div>
        </div>
    </fieldset>
</form>

</body>
</html>
