<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry index page for mail module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/MainTemplate.css" />
    <title><?= $VIEW->curFolder->localName ?> - Expresso Lite Accessível</title>
</head>
<body>

<div id="anchors">
    <div id="logomark"></div>
    <div id="anchors_area" name="anchors_area" class="menu">
        <nav id="anchors_nav" name="anchors_nav">
            <ul id="anchors_list" name="anchors_list">
                <li><a href="#menu_area" accesskey="1">Ir para o menu [1]</a></li>
                <li><a href="#headlines_area" accesskey="2">Ir para lista de emails [2]</a></li>
                <?php IF ($VIEW->curFolder->totalMails > $VIEW->requestLimit) : ?>
                <li><a href="#pagination_area" accesskey="3">Ir para paginação de emails [3]</a></li>
                <?php ENDIF; ?>
            </ul>
        </nav>
    </div>
</div>

<div id="menu_area" name="menu_area" class="menu">
    <h2 class="anchors_title">Menu</h2>
    <ul>
        <li><a href="<?= $VIEW->lnkRefreshFolder ?>" accesskey="a">Atualizar lista de emails da pasta <?= $VIEW->curFolder->localName ?> [a]</span></a></li>
        <li><a href="<?= $VIEW->lnkChangeFolder ?>" accesskey="p">Selecionar outra pasta [p]</a></li>
        <li><a href="<?= $VIEW->lnkComposeMessage ?>" accesskey="n">Escrever novo email [n]</span></a></li>
        <li><a href="<?= $VIEW->lnkLogoff ?>" title="Sair do expresso lite accessível" accesskey="s">Sair do sistema [s]</a></li>
    </ul>
</div>

<div id="headlines_area" name="headlines_area">
    <h2 class="anchors_title">Lista de emails</h2>
    <?php IF ($VIEW->curFolder->totalMails > 0) : ?>
    <table id="headlines_table" border="1" >
        <caption>
            A pasta <?= $VIEW->curFolder->localName ?> contém <?= $VIEW->curFolder->totalMails ?> emails,
            <?php IF ($VIEW->curFolder->unreadMails > 0) : ?>
                sendo <?= $VIEW->curFolder->unreadMails ?> não lido,
            <?php ENDIF; ?>
            listando de <?= $VIEW->start ?> a <?= $VIEW->limit ?>.
        </caption>
        <thead>
            <tr>
                <th id="id">Número</th>
                <th id="senderSubject">Remetente / Assunto</th>
                <th id="date">Data</th>
                <th id="observations">Observações</th>
                <th id="actions">Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $SEQ =  $VIEW->start;
            FOREACH ($VIEW->headlines AS $HEADLINE) :
            ?>
            <tr class="<?= $HEADLINE->unread ? 'markUnread' : '' ?>">
                <td headers="id" class="align_center">
                    <span><?= $SEQ ?></span>
                </td>
                <td headers="senderSubject" class="align_left">
                    <a href="<?= $HEADLINE->lnkOpen ?>" title="Abrir mensagem <?= $SEQ ?>">
                        De: <span> <?= $HEADLINE->from->name ?></span> 
                        <br />
                        <span ><?= $HEADLINE->subject ?></span>
                    </a>
                </td>
                <td headers="date" class="align_center">
                    <span><?= $HEADLINE->received ?></span>
                </td>
                <td headers="observations" class="align_left">
                    <?php IF ($HEADLINE->hasAttachment) : ?>
                    <div class="flags flag-attach" title="Este email contém anexo">&nbsp;</div>
                    <?php ENDIF; ?>
                    <?php IF ($HEADLINE->important) : ?>
                    <div class="flags flag-important" title="O remetente deste email o marcou como importante">&nbsp;</div>
                    <?php ENDIF; ?>
                </td>
                <td headers="actions">
                    <a href="<?= $HEADLINE->lnkDelete ?>" title="Apagar mensagem <?= $SEQ ?>">Apagar</a>
                    <!--  <a href="<?= $HEADLINE->lnkMarkMessage ?>" title="Marcar mensagem <?= $SEQ ?> como não lida">Marcar Não lida</a>-->
                </td>
            </tr>
            <?php ++$SEQ; ?>
            <?php ENDFOREACH; ?>
        </tbody>
    </table>
    <?php ELSE : ?>
    <span>A pasta <?= $VIEW->curFolder->localName ?> está vazia.</span>
    <?php ENDIF;?>
</div>

<?php IF ($VIEW->curFolder->totalMails > $VIEW->requestLimit) : ?>
    <div id="pagination_area" name="pagination_area">
        <h2 class="anchors_title">Paginação</h2>
        <ul>
            <?php IF ($VIEW->page > 1) : ?>
                <li><a href="<?= $VIEW->lnkPrevPage ?>">Página Anterior</a></li>
            <?php ENDIF; ?>
            <?php IF ($VIEW->page * $VIEW->requestLimit < $VIEW->curFolder->totalMails) : ?>
                <li><a href="<?= $VIEW->lnkNextPage ?>">Próxima Página</a></li>
            <?php ENDIF; ?>
        </ul>
    </div>
<?php ENDIF; ?>

</body>
</html>
