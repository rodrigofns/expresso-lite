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
    <title><?= $VIEW->actionText ?> mensagem - Expresso Lite Acessível</title>
    <title>Seleção de pasta - Expresso Lite Accessível</title>
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
<form action="<?= $VIEW->lnkSendMessageAction ?>" method="post">
    <fieldset class="Dialog_email">
        <input type="hidden" name="folderId" value="<?= $VIEW->folderId ?>" />
        <input type="hidden" name="folderName" value="<?= $VIEW->folderName ?>" />
        <input type="hidden" name="page" value="<?= $VIEW->page ?>" />
        <input type="hidden" name="replyToId" value="<?= $VIEW->replyToId ?>" />
        <input type="hidden" name="forwardFromId" value="<?= $VIEW->forwardFromId ?>" />
        <div class="Dialog_field">
            <label for="subject">Assunto:</label><br />
            <input type="text" name="subject" required="required" value="<?= $VIEW->subject ?>" />
        </div>
        <div class="Dialog_field">
            <label for="addrTo">Destinatário:</label><br />
            <input type="email" multiple="multiple" name="addrTo" required="required" value="<?= $VIEW->to ?>"/>
        </div>
        <div class="Dialog_field">
            <label for="addrCc">Destinatário em cópia:</label><br />
            <input type="email" multiple="multiple" name="addrCc" />
        </div>
        <div class="Dialog_field">
            <label for="addrBcc">Destinatário em cópia oculta:</label><br />
            <input type="email" multiple="multiple" name="addrBcc" />
        </div >
        <div class="Dialog_message">
            <label for="messageBody">Mensagem:</label><br />
            <textarea name="messageBody" required="required"></textarea>
            <?php IF ($VIEW->signature !== '') : ?>
            <div class="compose_sign"><?= $VIEW->signature ?></div>
            <?php ENDIF; ?>
        </div>

        <?php IF ($VIEW->quotedBody !== '') : ?>
            <span class="quoted_area">Mensagem citada:</span>
            <div class="message_text" name="quotedBody"><?= $VIEW->quotedBody ?></div>
        <?php ENDIF; ?>
        <div class="compose_footer">
            <div>
                <label for="important">Esta mensagem é importante</label>
                <input type="checkbox" name="important" title="Marcar essa mensagem como importante" />
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
