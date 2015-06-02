/*!
 * Expresso Lite
 * Main script to login page.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2015 Serpro (http://www.serpro.gov.br)
 */

require.config({
    paths: { jquery: 'inc/jquery.min' }
});

require(['jquery', 'inc/App'], function($, App) {
    $(document).ready(function() {
        // Browser validation.
        if (!ValidateBrowser([ {name:'Firefox',version:17}, {name:'Chrome',version:25}, {name:'Safari',version:7} ])) {
            $('#frmLogin').html('Os browsers mínimos suportados são Firefox 24, Chrome 25 e Safari 7.<br/>' +
                'Utilize o webmail padrão do Expresso em:<br/>' +
                '<a href="http://expressobr.serpro.gov.br">http://expressobr.serpro.gov.br</a>');
            return false;
        }

        // Initial stuff.
        var user = App.GetCookie('user');
        if (user !== null) {
            $('#user').val(user);
        }

        if (location.href.indexOf('#') !== -1)
            history.pushState('', '', location.pathname);
        LoadServerStatus();
        $('#user').focus();
        $('#frmLogin').submit(DoLogin);
        $('#frmChangePwd').submit(DoChangePassword);
    });

    function ValidateBrowser(minBrowsers) {
        var ua = navigator.userAgent;
        for (var m = 0; m < minBrowsers.length; ++m) {
            var bname = minBrowsers[m].name,
                bver = minBrowsers[m].version;
            var browserPrefixIndex = ua.indexOf(bname+'/');
            if (browserPrefixIndex !== -1) {
                // We found the browser within the user agent, let's check its version.
                var ver;
                if (bname !== 'Safari') {
                    ver = ua.substr(browserPrefixIndex + (bname+'/').length);
                    if (ver.indexOf(' ') !== -1) {
                        ver = ver.substr(0, ver.indexOf(' '));
                    }
                } else {
                    // Safari is a bit of a special case, its major version is
                    // expressed after the Version\ prefix.
                    var versionPrefixIndex = ua.indexOf('Version/');
                    if (versionPrefixIndex !== -1) {
                        ver = ua.substr(versionPrefixIndex + 'Version/'.length);
                        if (ver.indexOf(' ') !== -1) {
                            ver = ver.substr(0, ver.indexOf(' '));
                        }
                    } else {
                        bver = '-1'; // may happen when using Google Chrome on iPhone
                    }
                }
                return (bver <= parseInt(ver));
            }
        }
        return false;
    }

    function LoadServerStatus() {
        App.Post('getAllRegistryData')
        .fail(function(resp) {
            window.alert('Erro ao consultar a versão atual do Expresso.\n'+
                'É possível que o Expresso esteja fora do ar.');
        }).done(function(data) {
            $('#classicHref').attr('href', data.liteConfig.classicUrl);
            $('#versionInfo').append(
                data.liteConfig.packageString+'<br/>'+
                data.Tinebase.version.packageString
            );

            // Android and iOS badges are shown only if the respective apps
            // are currently available at Play & Apple store.
            if (data.liteConfig.androidUrl === '' || data.liteConfig.androidUrl === undefined) {
                $('#androidBadge').remove();
            } else {
                $('#androidBadge').attr('href', data.liteConfig.androidUrl);
            }

            if (data.liteConfig.iosUrl === '' || data.liteConfig.iosUrl === undefined) {
                $('#iosBadge').remove();
            } else {
                $('#iosBadge').attr('href', data.liteConfig.iosUrl);
            }

            $('#externalLinks,#versionInfo').fadeIn(400);

            // Store any other user information in application repository.
            App.SetUserInfo('mailBatch', data.liteConfig.mailBatch);
        });
    }

    function DoLogin() {
        if (!ValidateLogin()) return false;
        $('#btnLogin').hide();
        $('#frmLogin input').prop('disabled', true);
        $('#frmLogin .throbber').show().children('span').text('Efetuando login...');

        function RestoreLoginState() {
            $('#btnLogin').show();
            $('#frmLogin input').prop('disabled', false);
            $('#frmLogin .throbber').hide();
            $('#user').focus();
        }

        App.Post('login', {
            user:$('#user').val(),
            pwd:$('#pwd').val(),
            captcha: $('#captcha').val()
        }).fail(function(resp) {
            window.alert('Não foi possível efetuar login.\n' +
                'O usuário ou a senha estão incorretos.');
            RestoreLoginState();
        }).done(function(response) {
            if (response.expired) {
                RestoreLoginState();
                window.alert('Sua senha expirou, é necessário trocá-la.');
                var $frmLogin = $('#frmLogin').replaceWith($('#frmChangePwd')).appendTo('#templates');
                $('#cpNewPwd').focus();
            } else if (response.captcha) {
                window.alert('Número máximo de tentativas excedido.\n' +
                             'Informe também o CAPTCHA para efetuar o login');
                if (!$('#captchaDiv').is(':visible')) {
                    $('#captchaDiv').insertAfter('#pwd');
                }
                $('#captchaImg').attr('src', 'data:image/png;base64,' + response.captcha);
                RestoreLoginState();
            } else if (!response.success) {
                window.alert('Não foi possível efetuar login.\n' +
                    'O usuário ou a senha estão incorretos.');
                RestoreLoginState();
            } else {
                for (var i in response.userInfo) {
                    App.SetUserInfo(i, response.userInfo[i]);
                }
                App.SetCookie('user', $('#user').val(), 30); // store for 30 days
                $('#credent,#externalLinks,#versionInfo').hide();
                $('#thebg').fadeOut({ duration:400, queue:false });
                $('#topgray').animate({ height:'7.5%' }, { duration:500, queue:false });
                $('#blue').animate({ top: '7.5%'}, {
                    duration: 500,
                    queue: false,
                    complete: function() {
                        location.href = './mail'; // automatically redirect to email module
                    }
                });
            }
        });
        return false;
    }

    function DoChangePassword() {
        if ($('#cpNewPwd').val() !== $('#cpNewPwd2').val()) {
            window.alert('As novas senha não coincidem, por favor digite-as novamente.');
            $('#cpNewPwd,#cpNewPwd2').val('');
            $('#cpNewPwd').focus();
        } else {
            $('#btnNewPwd').hide();
            $('#frmChangePwd input').prop('disabled', true);
            $('#frmChangePwd .throbber').show().children('span').text('Trocando senha...');

            function RestoreChangePasswordState() {
                $('#btnNewPwd').show();
                $('#frmChangePwd input').prop('disabled', false);
                $('#frmChangePwd .throbber').hide();
                $('#cpNewPwd,#cpNewPwd2').val('');
                $('#cpNewPwd').focus();
            }

            App.Post('changeExpiredPassword', {
                userName: $('#user').val(), // from login form
                oldPassword: $('#cpOldPwd').val(),
                newPassword: $('#cpNewPwd').val()
            }).fail(function(resp) {
                window.alert(UglyTineFormatMsg(resp.responseText));
                RestoreChangePasswordState();
            }).done(function(data) {
                window.alert('Senha alterada com sucesso.\nEfetue login com sua nova senha.');
                location.reload();
            });
        }
        return false;
    }

    function UglyTineFormatMsg(errorMessage) {
        // Ugly formatting function copied straight from Tine source.
        var title  = errorMessage.substr(0, (errorMessage.indexOf(':') + 1));
        var errorFull = errorMessage.substr((errorMessage.indexOf(':') + 1));
        var errorTitle = errorFull.substr(0, (errorFull.indexOf(':') + 1));
        var errors = errorFull.substr((errorFull.indexOf(':') + 1));
        return errorTitle+'\n\n'+errors.replace(/, /g, '\n').replace(/^\s/, '');
    }

    function ValidateLogin() {
        if ($('#user').val() == '') {
            window.alert('Por favor, digite seu nome de usuário.');
            $('#user').focus();
            return false;
        } else if ($('#pwd').val() == '') {
            window.alert('Por favor, digite sua senha.');
            $('#pwd').focus();
            return false;
        }
        return true;
    }
});
