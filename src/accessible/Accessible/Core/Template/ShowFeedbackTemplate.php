<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry page for accessible module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/ShowFeedbackTemplate.css" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <title>Aviso - Expresso Lite Accessível</title>
</head>
<body>

<div id="anchors">
    <div id="logomark"></div>
</div>

<h2 class="anchors_title">Mensagens</h2>
<div id="feedback_message_container">
    <div id="feedback_message" name="feedback_message"> <?= $VIEW->message ?></div>
    <div id="feedback_link" name="feedback_link">
        <a href="<?= $VIEW->destinationUrl ?>" accesskey="v"><?= $VIEW->destinationText ?> [v]</a>
    </div>
</div>

</body>
</html>
