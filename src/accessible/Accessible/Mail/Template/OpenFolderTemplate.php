<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry index page for mail module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Edgar de Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/OpenFolderTemplate.css" />
    <title>Seleção de pasta - ExpressoBr Acessível</title>
</head>
<body>

<div id="anchors">
    <div id="logomark"></div>
    <div id="anchors_area" name="anchors_area">
        <nav id="anchors_nav" name="anchors_nav" class="menu">
            <ul>
                <li><a href="#folders_area" accesskey="1">Ir para listagem de pastas [1]</a></li>
                <!-- <li><a href="#menu_area" accesskey="2">Ir para ações da pasta de email [2]</a></li> -->
                <li><a href="<?= $VIEW->lnkRefreshFolder ?>" accesskey="v">Voltar para <?= $VIEW->folderName ?> [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="folders_area" name="folders_area" class="menu">
<h2 class="anchors_title">Pastas</h2>
    <ul>
        <?php FOREACH ($VIEW->folders AS $FOLDER) : ?>
            <li><a href="<?= $FOLDER->lnkOpenFolder ?> " title="<?= $FOLDER->title ?>"><?= $FOLDER->localName ?></a></li>
        <?php ENDFOREACH ?>
    </ul>
</div>

</body>
</html>
