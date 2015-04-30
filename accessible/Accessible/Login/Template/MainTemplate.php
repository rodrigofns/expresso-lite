<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry page for accessible module.
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
    <link type="text/css" rel="stylesheet" href="./Accessible/Login/Template/MainTemplate.css" />
    <title>Expresso Lite Accessível - Email - Página de Login</title>
</head>
<body>

<div id="signup-inner" name="signup-inner">
    <div class="clearfix" id="header">
        <img id="logo" name="logo" src="../img/expressobr_lite_200.png" alt="Logotipo do Expresso Lite Acessível" />
    </div>

    <form id="frmLogin" name="frmLogin" method="post" action=".">
        <input type="hidden" id="r" name="r" value="Login.Login">

        <label for="user">Usuário</label>
        <input id="user" name="user" type="text" placeholder="Digite o email do usuário" value="<?= $VIEW->lastLogin ?>" tabindex="1"/>

        <label for="pwd">Senha</label>
        <input id="pwd" name="pwd" type="password" placeholder="Digite a senha" tabindex="2" />

        <button id="submit" name="submit" type="submit" tabindex="3">Fazer login</button>
    </form>
</div>

</body>
</html>
