/*!
 * Expresso Lite
 * Main script of addressbook module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

require.config({
    baseUrl: '..',
    paths: { jquery: 'inc/jquery.min' }
});

require(['jquery', 'inc/App', 'inc/Layout',
         'addressbook/WidgetCatalogMenu',
         'addressbook/WidgetContactList',
         'addressbook/WidgetContactDetails'],
function($, App, Layout,
        WidgetCatalogMenu,
        WidgetContactList,
        WidgetContactDetails) {

window.Cache = {
    layout: null,
    widgetCatalogMenu: null,
    widgetContactList: null,
    widgetContactDetails: null
};

$(document).ready(function() {
    function showDetailView() {
        if (!Cache.layout.isContentFullWidth()) {
            Cache.layout.setContentFullWidth(true)
            .onUnset(showListView);
            $('#mainContent>*').addClass('detailView');
            Cache.widgetContactList.scrollToCurrentItem();
        }
    }

    function showListView() {
        $('#mainContent>*').removeClass('detailView');
        Cache.widgetContactList.unselectCurrentItem();
        if (Cache.layout.isContentFullWidth()) {
            Cache.layout.setContentFullWidth(false);
        }
    }

    (function constructor() {
        Cache.layout = new Layout({
            userMail: App.GetUserInfo('mailAddress'),
            $menu: $('#leftMenu'),
            $content: $('#mainContent')
        });

        Cache.layout
        .onSearch(function (text){
            showListView();
            Cache.widgetContactList.changeQuery(text);
        })
        .onKeepAlive(function () {
            App.Post('checkSessionStatus');
            // we just want to keep the session alive,
            // so no need for onDone
        });


        Cache.widgetCatalogMenu = new WidgetCatalogMenu({
            $parentContainer: $('#tipoContatoDiv')
        });

        Cache.widgetCatalogMenu
        .addOption('Catálogo Pessoal', function () {
            Cache.layout.setLeftMenuVisibleOnPhone(false)
            .done(function() {
                Cache.widgetContactList.changeToPersonalCatalog();
            });
        }).addOption('Catálogo Corporativo', function () {
            Cache.layout.setLeftMenuVisibleOnPhone(false)
            .done(function() {
                Cache.widgetContactList.changeToCorporateCatalog();
            });
        });

        Cache.widgetContactList = new WidgetContactList({
            $parentContainer: $('#contactListSection')
        });

        Cache.widgetContactList.onItemClick(function(contact) {
            showDetailView();
            Cache.widgetContactDetails.showDetails(contact);
        });


        Cache.widgetContactDetails = new WidgetContactDetails({
            $parentContainer: $('#contactDetailsSection')
        });

        Cache.widgetContactDetails.load();
        //user shouldn't be kept waiting for this just right now

        $.when(
            Cache.widgetContactList.load(),
            Cache.layout.load()
        ).done(function() {
            Cache.widgetContactList.changeToPersonalCatalog();
        });
    })();
}); // $(document).ready

}); // require
