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

    public function install()
    {
        if (!parent::install()
            || !Configuration::updateValue('SC_FOLDER_HASH', Tools::substr(md5(date("YmdHis") . _COOKIE_KEY_), 0, 11))
            || !$this->createSCFolder(Configuration::get('SC_FOLDER_HASH'))
            || !Configuration::updateValue('SC_INSTALLED', false)
        ) {
            return false;
        }
        Tools::redirectAdmin($this->currentUrl . $this->baseParams);
        return true;
    }

    public function uninstall()
    {
        $qaccess = Db::getInstance()->ExecuteS("SELECT GROUP_CONCAT(`id_quick_access`) AS qaccess FROM `" . _DB_PREFIX_ . "quick_access` WHERE `link` LIKE '%storecommander%'");
        if (count($qaccess) && $qaccess[0]['qaccess'] != '') {
            Db::getInstance()->Execute("DELETE FROM `" . _DB_PREFIX_ . "quick_access` WHERE id_quick_access IN (" . psql($qaccess[0]['qaccess']) . ")");
            Db::getInstance()->Execute("DELETE FROM `" . _DB_PREFIX_ . "quick_access_lang` WHERE id_quick_access IN (" . psql($qaccess[0]['qaccess']) . ")");
        }
        $tab = new Tab(Tab::getIdFromClassName('AdminStoreCommander'));
        $tab->delete();
        $this->removeSCFolder(Configuration::get('SC_FOLDER_HASH'));
        Configuration::deleteByName('SC_FOLDER_HASH');
        Configuration::deleteByName('SC_INSTALLED');
        Configuration::deleteByName('SC_SETTINGS');
        Configuration::deleteByName('SC_LICENSE_DATA');
        Configuration::deleteByName('SC_LICENSE_KEY');
        Configuration::deleteByName('SC_VERSIONS');
        Configuration::deleteByName('SC_VERSIONS_LAST');
        Configuration::deleteByName('SC_VERSIONS_LAST_CHECK');

        parent::uninstall();
        return true;
    }

    private function createSCFolder($folder)
    {
        if (!is_dir(dirname(__FILE__) . '/' . $folder)) {
            return mkdir(dirname(__FILE__) . '/' . $folder);
        }
    }

    private function removeSCFolder($folder)
    {
        if (is_dir(dirname(__FILE__) . '/' . $folder)) {
            $this->rrmdir(dirname(__FILE__) . '/' . $folder);
        }
        return true;
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            @rmdir($dir);
        }
        return true;
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

        $sql = "SELECT class_name FROM " . _DB_PREFIX_ . "tab
				WHERE class_name = 'AdminStoreCommander'";
        $exists = Db::getInstance()->ExecuteS($sql);
        if (empty($exists[0]["class_name"])) {
            $tab = new Tab();
            $tab->class_name = 'AdminStoreCommander';
            if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                $tab->id_parent = (int)(Tab::getIdFromClassName('AdminParentModules'));
            } else {
                $tab->id_parent = (int)(Tab::getIdFromClassName('AdminModules'));
            }
            $tab->module = $this->name;
            $tab->name[Configuration::get('PS_LANG_DEFAULT')] = 'Store Commander';
            foreach (Language::getLanguages(false) AS $language) {
                $tab->name[$language["id_lang"]] = 'Store Commander';
            }
            $tab->add();
        }

        $this->context->smarty->assign(array(
            'currentUrl' => $this->currentUrl,
            'baseParams' => $this->baseParams,
            'module_name' => $this->name
        ));

        $_html = '';
        $_html .=  $this->display(__FILE__, 'views/templates/hook/init_js.tpl');
        $_html .= $this->displayStep(Tools::getValue("sc_step"));
        return $_html;
    }

    private function displayStep($step)
    {
        $_html = '';
        switch ((int)$step) {
            case 1 :
                if (Configuration::get('SC_INSTALLED')) {
                    Tools::redirectAdmin($this->currentUrl . $this->baseParams . '&sc_step=3');
                } else {
                    if ($this->isSCFolderReady()) {
                        Tools::redirectAdmin($this->currentUrl . $this->baseParams . '&sc_step=3');
                    } else {
                        if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                            return $this->display(__FILE__, 'etape_preinstall_1.5.tpl');
                        } else {
                            return $this->display(__FILE__, 'views/templates/hook/etape_preinstall_1.4.tpl');
                        }
                    }
                }
                break;

            case 2 :
                if (Configuration::get('SC_INSTALLED') || $this->isSCFolderReady()) {
                    Tools::redirectAdmin($this->currentUrl . $this->baseParams . '&sc_step=3');
                } else {
                    if (!$this->downloadExtractSC()) {
                        $this->_err[] = Tools::displayError('Error downloading StoreCommander');
                        $this->displayErrors($this->_err);
                    } else {
                        $this->createTab();
                        Configuration::updateValue('SC_INSTALLED', true);
                        if (file_exists(dirname(__FILE__).'/license.php'))
                            @copy(dirname(__FILE__).'/license.php',_PS_MODULE_DIR_.$this->name.'/'.Configuration::get('SC_FOLDER_HASH').'/SC/license.php');
                        Tools::redirectAdmin($this->currentUrl . $this->baseParams . '&sc_step=3');
                    }
                }
                break;


            case 3 :

                if (version_compare(_PS_VERSION_, '1.5.0.0', '>=')) {
                    $this->context->smarty->assign(array(
                        'token' => Tools::getAdminToken('AdminStoreCommander' . (int)(Tab::getIdFromClassName('AdminStoreCommander')) . (int)($this->context->employee->id))
                    ));
                    return $this->display(__FILE__, 'etape_postinstall_1.5.tpl');
                } else {
                    global $cookie;
                    $this->context->smarty->assign(array(
                        'token' => Tools::getAdminToken('AdminStoreCommander' . (int)(Tab::getIdFromClassName('AdminStoreCommander')) . (int)($cookie->id_employee))
                    ));
                    return $this->display(__FILE__, 'views/templates/hook/etape_postinstall_1.4.tpl');
                }

                break;

            default :
                if (!$this->isSCFolderReady()) {
                    Tools::redirectAdmin($this->currentUrl . $this->baseParams . '&sc_step=1');
                } else {
                    Tools::redirectAdmin($this->currentUrl . $this->baseParams . '&sc_step=3');
                }
                break;
        }
        return $_html;
    }

    private function createTab()
    {
        if (!Tab::getIdFromClassName('AdminStoreCommander')) {
            $tab = new Tab();
            $tab->class_name = 'AdminStoreCommander';
            $tab->id_parent = (int)(Tab::getIdFromClassName((version_compare(_PS_VERSION_, '1.5.0.0',
                '>=') ? 'AdminParentModules' : 'AdminModules')));
            $tab->module = $this->name;
            foreach (Language::getLanguages(false) AS $language) {
                $tab->name[$language["id_lang"]] = 'Store Commander';
            }
            $tab->add();
            @copy(_PS_MODULE_DIR_ . $this->name . '/logo.gif', _PS_IMG_DIR_ . 't/AdminStoreCommander.gif');
        }

    }

    public function isSCFolderReady()
    {
        if (file_exists(dirname(__FILE__) . '/' . Configuration::get('SC_FOLDER_HASH') . '/SC/index.php')) {
            return true;
        }
        return false;
    }

    public function sc_file_get_contents($param, $querystring = '')
    {
        $result = '';
        if (function_exists('file_get_contents')) {
            @$result = Tools::file_get_contents($param);
        }
        if ($result == '' && function_exists('curl_init')) {
            $curl = curl_init();
            $header = '';
            $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
            $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
            $header[] = "Cache-Control: max-age=0";
            $header[] = "Connection: keep-alive";
            $header[] = "Keep-Alive: 300";
            $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
            $header[] = "Accept-Language: en-us,en;q=0.5";
            $header[] = "Pragma: ";
            curl_setopt($curl, CURLOPT_URL, $param);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Store Commander (http://www.storecommander.com)');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
            curl_setopt($curl, CURLOPT_AUTOREFERER, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $querystring);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            $result = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            if ((int)$info['http_code'] != 200) {
                return '';
            }
        }
        return $result;
    }

    private function downloadExtractSC()
    {
        $data = $this->sc_file_get_contents($this->url_zip_SC);
        file_put_contents(_PS_MODULE_DIR_ . $this->name . '/' . basename($this->url_zip_SC), $data);
        return $this->extractArchive(_PS_MODULE_DIR_ . $this->name . '/' . basename($this->url_zip_SC));
    }


    private function extractArchive($file)
    {
        $success = true;
        require_once(_PS_TOOL_DIR_ . 'pclzip/pclzip.lib.php');
        $zip = new PclZip($file);
        $list = $zip->extract(PCLZIP_OPT_PATH,
            _PS_MODULE_DIR_ . $this->name . '/' . Configuration::get('SC_FOLDER_HASH'));
        foreach ($list as $extractedFile) {
            if ($extractedFile['status'] != 'ok') {
                $success = false;
            }
        }
        @unlink($file);
        return $success;
    }

    public function displayErrors($errors)
    {
        if (is_array($errors) && count($errors)) {
            $_html = '<div class="error">';
            foreach ($errors AS $error) {
                $_html .= '<p><img src="../modules/storecommander/views/img/error2.png" />&nbsp;' . $error . '</p>';
            }
            $_html .= '</div>';
            return $_html;
        }
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
