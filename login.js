/*!
 * Expresso Lite
 * Main script to login page.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2013-2014 Serpro (http://www.serpro.gov.br)
 */

(function( $ ) {
$(document).ready(function() {
	// Browser validation.
	if(!ValidateBrowser([ {name:'Firefox',version:17}, {name:'Chrome',version:25} ])) {
		$('#frmLogin').html('Os browsers mínimos suportados são Firefox 24 e Chrome 25.<br/>' +
			'Utilize o webmail padrão do Expresso em:<br/>' +
			'<a href="http://expressov3.serpro.gov.br">http://expressov3.serpro.gov.br</a>');
		return false;
	}

	// Initial stuff.
	if(location.href.indexOf('#') !== -1)
		history.pushState('', '', location.pathname);
	LoadServerStatus();
	$.post('.', { r:'setLocale' }); // default to PT/BR
	$('#user').focus();
	$('#frmLogin').submit(DoLogin);
});

function ValidateBrowser(minBrowsers) {
	var	ua = navigator.userAgent;
	for(var m = 0; m < minBrowsers.length; ++m) {
		var bname = minBrowsers[m].name,
			bver = minBrowsers[m].version;
		if(ua.indexOf(bname+'/') !== -1) {
			var ver = ua.substr(ua.indexOf(bname+'/') + (bname+'/').length);
			if(ver.indexOf(' ') !== -1) ver = ver.substr(0, ver.indexOf(' '));
			return (bver <= parseInt(ver));
		}
	}
	return false;
}

function LoadServerStatus() {
	$('#versionInfo').hide();
	$.post('.', { r:'getAllRegistryData', validateLogin:0 })
	.fail(function(resp) {
		window.alert('Erro ao consultar a versão atual do Expresso.\n'+
			'É possível que o Expresso esteja fora do ar.');
	}).done(function(data) {
		$('#versionInfo').html(data.Tinebase.version.codeName+'<br/>' +
			data.Tinebase.version.buildType+', '+data.Tinebase.version.packageString+'<br/>' +
			data.Tinebase.version.releaseTime).fadeIn(400);
	});
}

function DoLogin() {
	if(!ValidateLogin()) return false;
	$('#btnLogin').hide();
	$('#credent input').prop('disabled', true);
	$('#throbber').show().children('span').text('Efetuando login...');

	var RestoreLoginState = function() {
		$('#btnLogin').show();
		$('#credent input').prop('disabled', false);
		$('#throbber').hide();
		$('#user').focus();
	};

	$.post('.', { r:'login', user:$('#user').val(), pwd:$('#pwd').val() })
	.fail(function(resp) {
		window.alert('Não foi possível efetuar login.\n' +
			'O usuário ou a senha estão incorretos.');
		RestoreLoginState();
	}).done(function(data) {
		$('#throbber').children('span').text('Autenticando...');

		$.post('.', { r:'getAllRegistryData', validateLogin:1 })
		.fail(function(resp) {
			window.alert('Erro na consulta aos dados do usuário.\n'+resp.responseText);
			RestoreLoginState();
		}).done(function(data) {
			$('#throbber').remove();
			$('#credent').hide();
			$('#thebg').fadeOut({ duration:400, queue:false });
			$('#topgray').animate({ height:'7.5%' }, { duration:500, queue:false });
			$('#blue').animate({ top:'7.5%' }, { duration:500, queue:false, complete:function() {
				location.href = './mail';
			} });
		});
	});
	return false;
}

function ValidateLogin() {
	if($('#user').val() == '') {
		window.alert('Que tal digitar um nome de usuário?');
		$('#user').focus();
		return false;
	} else if($('#pwd').val() == '') {
		window.alert('Tentando entrar sem senha, hacker?');
		$('#pwd').focus();
		return false;
	}
	return true;
}
})( jQuery );