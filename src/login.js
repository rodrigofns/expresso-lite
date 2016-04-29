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
    paths: { jquery: 'common-js/jquery.min' }
});

require(['jquery',
    'common-js/App',
    'common-js/Cordova'
],
function($, App, Cordova) {
    App.ready(function() {
        if (Cordova) {
            $('#splash-screen').appendTo($('body'));
        } else {
            var isBrowserValid = ValidateBrowser([
                { name:'Firefox', version:24 },
                { name:'Chrome', version:25 },
                { name:'Safari', version:7 }
            ]);

            if (!isBrowserValid) {
                $('#main-screen,#unsupportedMsg').show();
                $('#frmLogin').hide();
                return false; // won't load anything else
            }
        }

        App.post('checkSessionStatus')
        .done(function(response) {
            if (response.status == 'active') {
                App.goToFolder('./mail'); // there is an active session, go to mail module
            } else if (Cordova) {
                // no active session, but since we have Cordova
                // we may have the credentials to start a new session
                DoCordovaInit();
            } else {
                // no active session and no Cordova, proceed with usual init
                Init();
            }
        }).fail(function(error) {
            window.alert('Ocorreu um erro ao realizar a conexão com o Expresso.\n'+
                'É possível que o sistema esteja fora do ar.');
        });
    });

    function DoCordovaInit() {
        Cordova.GetCurrentAccount()
        .done(function(account) {
            if (account == null) {
                Init(); //no credential found, proceed with usual init
            } else {
                function CordovaLoginFailed() {
                    window.alert('Não foi possível se reconectar ao Expresso com as credencias armazenadas.\n' +
                        'É necessário realizar o login novamente.');
                    Init();
                }

                // Use the credential to perform a new login.
                // This will be transparent to the user,
                // since no fields are yet being displayed on screen
                App.post('login', {
                    user: account.login,
                    pwd: account.password
                })
                .fail(CordovaLoginFailed)
                .done(function(response) {
                    if (!response.success) {
                        CordovaLoginFailed();
                    } else {
                        //since the only visible thing right now is the splash screen,
                        //just go straight to the mail module, without any fancy animations
                        App.goToFolder('./mail');
                    }
                });
            }
        })
        .fail(function (error) {
            // This should not happen, but in case something goes wrong,
            // at least we'll have some information to work on
            console.log('Cordova.GetCurrentAccount failed: ' + error);
            Init();
        });
    }


    function Init() {
        function ShowScreen() {
            $('#main-screen').fadeIn(300, function() {
                $('#splash-screen').hide();
                LoadServerStatus(); // async
                $('#user').focus();
                $('#frmLogin').submit(DoLogin);
                $('#frmChangePwd').submit(DoChangePassword);
            });
        }

        if (Cordova) {
            $('#splash-screen')
            .animate({ top: '16px' }, {
                duration: 300,
                queue: false,
                complete: ShowScreen
            });
        } else {
            var user = App.getCookie('user');
            if (user !== null) {
                $('#user').val(user);
            }

            if (location.href.indexOf('#') !== -1)
                history.pushState('', '', location.pathname);

            ShowScreen();
        }
    }

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
        App.post('getAllRegistryData')
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
        });
    }

    function DoLogin() {
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

        if (!ValidateLogin()) return false;
        $('#universalAccess,#externalLinks').fadeOut(200);
        $('#btnLogin').hide();
        $('#frmLogin input').prop('disabled', true);
        $('#frmLogin .throbber').show().children('span').text('Efetuando login...');

        function RestoreLoginState() {
            if (!App.isPhone()) $('#universalAccess').show();
            $('#externalLinks').show();
            $('#btnLogin').show();
            $('#frmLogin input').prop('disabled', false);
            $('#frmLogin .throbber').hide();
            $('#user').focus();
        }

        App.post('login', {
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
                    App.setUserInfo(i, response.userInfo[i]);
                }
                App.setCookie('user', $('#user').val(), 30); // store for 30 days

                if (Cordova) {
                    Cordova.SaveAccount($('#user').val(), $('#pwd').val())
                    .fail(function() {
                        console.log('O login foi bem sucedido, mas não foi possível armazenar as credencias do usuário neste dispositivo.');
                    })
                    .always(RedirectToMailModule);
                } else {
                    RedirectToMailModule();
                }
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

            function UglyTineFormatMsg(errorMessage) {
                // Ugly formatting function copied straight from Tine source.
                var title  = errorMessage.substr(0, (errorMessage.indexOf(':') + 1));
                var errorFull = errorMessage.substr((errorMessage.indexOf(':') + 1));
                var errorTitle = errorFull.substr(0, (errorFull.indexOf(':') + 1));
                var errors = errorFull.substr((errorFull.indexOf(':') + 1));
                return errorTitle+'\n\n'+errors.replace(/, /g, '\n').replace(/^\s/, '');
            }

            function RestoreChangePasswordState() {
                $('#btnNewPwd').show();
                $('#frmChangePwd input').prop('disabled', false);
                $('#frmChangePwd .throbber').hide();
                $('#cpNewPwd,#cpNewPwd2').val('');
                $('#cpNewPwd').focus();
            }

            App.post('changeExpiredPassword', {
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

    function RedirectToMailModule() {
        $(document.body).css('overflow', 'hidden');
        $('#versionInfo, #frmLogin .throbber').hide();

        if (App.isPhone()) {
            var animTime = 300;
            $('#credent').css({
                    position: 'fixed',
                    left: $('#credent').offset().left,
                    top: $('#credent').offset().top
                })
                .appendTo(document.body)
                .animate({ left:-$(window).width() }, { duration:animTime, queue:false });
            $('#frmLogin').css({
                    position: 'fixed',
                    display: 'block',
                    left: $('#logo-top').offset().left,
                    top: $('#frmLogin').offset().top
                })
                .appendTo(document.body)
                .animate({ left:$(window).width() }, {
                    duration: animTime,
                    queue: false,
                    complete: function() {
                        App.goToFolder('./mail');
                    }
                });
        } else {
            var animTime = 600;
            $('#topgray').animate({ opacity:0 }, { duration:animTime, queue:false });
            $('#thebg').animate({ left:-$(window).width(), opacity:0 }, { duration:animTime, queue:false });
            $('#credent').animate({ top:$(window).height() }, {
                duration: animTime,
                queue: false,
                complete: function() {
                    App.goToFolder('./mail');
                }
            });
        }
    }
});
