<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Displays an email message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/OpenMessageTemplate.css" />
    <title>Leitura de mensagem - ExpressoBr Acessível</title>
</head>
<body>

<div id="anchors">
    <div id="logomark"></div>
    <div id="anchors_area" name="anchors_area" class="menu">
        <nav id="anchors_nav" name="anchors_nav">
            <ul>
            <li><a href="#email_header" accesskey="1">Ir para o cabeçalho do email [1]</a></li>
            <li><a href="#email_content" accesskey="2">Ir para o corpo da mensagem [2]</a></li>
            <li><a href="#email_actions" accesskey="3">Ir para ações de email [3]</a></li>
            </ul>
        </nav>
    </div>
</div>

<!-- EMAIL HEADER -->
<div id="email_header" name="email_header">
    <h2 class="anchors-title">Cabeçalho</h2>

    <div><span class="fieldName">Assunto:</span> <?= $VIEW->message->subject ?></div>

    <div><span class="fieldName">Remetente:</span> <?= $VIEW->message->from_name ?> (<?= $VIEW->message->from_email ?>)</div>

    <div><span class="fieldName">Data:</span> <?= $VIEW->formattedDate ?></div>

    <?php IF (!EMPTY($VIEW->message->to[0])) : ?>
        <div><span class="fieldName">Destinatário:</span> <?= implode(', ', $VIEW->message->to) ?></div>
    <?php ENDIF; ?>

    <?php IF (!EMPTY($VIEW->message->cc[0])) : ?>
        <div><span class="fieldName">Com cópia para:</span> <?= implode(', ', $VIEW->message->cc) ?></div>
    <?php ENDIF; ?>

    <?php IF (!EMPTY($VIEW->message->bcc[0])) : ?>
        <div><span class="fieldName">Com cópia oculta para:</span> <?= implode(', ', $VIEW->message->bcc) ?></div>
    <?php ENDIF; ?>

    <?php IF ($VIEW->message->importance) : ?>
        <div><span class="fieldName">Observação:</span> Esta mensagem foi marcada como importante.</div>
    <?php ENDIF; ?>

    <?php IF ($VIEW->message->has_attachment) : ?>
        <div class="menu">
            <span class="fieldName">Anexos:</span>
            <ul id="attachments">
            <?php FOREACH ($VIEW->message->attachments as $ATTACH) : ?>
                <li>
                    <a title="<?= $ATTACH->filename ?>" href="<?= $ATTACH->lnkDownload ?>">
                        Abrir anexo <span class="attachName"><?= $ATTACH->filename ?></span>
                    </a>
                </li>
            <? ENDFOREACH; ?>
            </ul>
        </div>
    <?php ENDIF; ?>
</div>

<!-- CONTAINER CONTENT -->
<div id="email_content" name="email_content">
<h2 class="anchors-title">Mensagem</h2>
    <div id="composePanel_body" name="composePanel_body">
        <?= $VIEW->message->body->message ?>
        <br/><br/>
        <blockquote><?= $VIEW->message->body->quoted ?></blockquote>
    </div>
</div>

<br />

<!-- EMAIL ACTIONS -->
<div id="email_actions" name="email_actions">
<h2 class="anchors-title">Ações</h2>
    <ul>
        <li><a href="<?= $VIEW->lnkReply ?>">Responder</a></li>
        <li><a href="<?= $VIEW->lnkReplyAll ?>">Responder a todos</a></li>
        <li><a href="<?= $VIEW->lnkForward ?>">Encaminhar</a></li>
        <li><a href="<?= $VIEW->lnkMark ?>">Marcar como não lida</a></li>
        <li><a href="<?= $VIEW->lnkDelete ?>">Apagar</a></li>
        <li><a href="<?= $VIEW->lnkBack ?>" accesskey="v">Voltar para <?= $VIEW->folderName?> [v]</a></li>
    </ul>
</div>

</body>
</html>
