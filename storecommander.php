<?php
/**
 * Store Commander
 *
 * @category administration
 * @author Mise En Prod - support@storecommander.com
 * @version 2015-09-11
 * @uses Prestashop modules
 * @since 2009
 * @copyright Copyright &copy; 2009-2015, Mise En Prod
 * @license commercial
 * All rights reserved! Copying, duplication strictly prohibited
 *
 * *****************************************
 * *           STORE COMMANDER             *
 * *   http://www.StoreCommander.com       *
 * *            V 2015-09-11               *
 * *****************************************
 *
 * Compatibility: PS version: 1.1 to 1.6
 *
 **/

if (Tools::getIsset("DEBUG")) {
    error_reporting(E_ALL ^ E_NOTICE);
    @ini_set('display_errors', 'on');
}

class StoreCommander extends Module
{

    public $currentUrl = '';
    public $_err = array();
    private $url_zip_SC = "http://www.storecommander.com/files/StoreCommander.zip";
    public $context;

    public function __construct()
    {
        $this->name = 'storecommander';
        $this->tab = 'administration';
        $this->version = '1.5';
        $this->author = 'Mise En Prod';
        $this->module_key = '';
        parent::__construct();

        $this->currentUrl = $this->getCurrentUrl();
        $token = Tools::getValue("token", "");
        $this->baseParams = "?controller=AdminModules&configure=storecommander&token=" . $token;
        if (version_compare(_PS_VERSION_, '1.5.0.0', '<')) {
            $this->baseParams = "?tab=AdminModules&configure=storecommander&token=" . $token;
        }
        $this->page = basename(__FILE__, '.php');

        $this->displayName = $this->l('Store Commander Installer');
        $this->description = $this->l('60 days risk-free trial version. A revolution in Prestashop management, subscription-free, unlimited users, Mac & PC');
        $this->confirmUninstall = $this->l('Warning! This action definitely uninstall Store Commander!');
        $warning = '';
        if (!is_writeable(_PS_ROOT_DIR_ . '/modules/' . $this->name)) {
            $warning .= ' ' . $this->l('The /modules/storecommander folder must be writable.');
        }
        if (!Configuration::get('SC_INSTALLED')) {
            $warning .= ' ' . $this->l('Store Commander is not installed!');
        }
        if ($warning != '') {
            $this->warning = $warning;
        }
    }

    public function getContent()
    {
        if (class_exists('Context'))
            $this->context = Context::getContext();
        else
        {
            global $smarty, $cookie;
            $this->context = new StdClass();
            $this->context->smarty = $smarty;
            $this->context->cookie = $cookie;
        }

        $this->context->smarty->assign(array(
            'currentUrl' => $this->currentUrl,
            'baseParams' => $this->baseParams,
            'module_name' => $this->name
        ));

        $_html = '';
        $_html .=  $this->display(__FILE__, 'views/templates/hook/init_js.tpl');

        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
            $_html .=  $this->display(__FILE__, 'etape_preinstall_1.5.tpl');
        } else {
            $_html .= $this->display(__FILE__, 'views/templates/hook/etape_preinstall_1.4.tpl');
        }
        return $_html;
    }

    public function  getCurrentUrl()
    {
        $pageURL = 'http';
        if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["HTTP_HOST"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        }
        $exp = explode("?", $pageURL);
        $pageURL = $exp[0];
        return $pageURL;
    }
}
