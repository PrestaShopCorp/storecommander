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

if (version_compare(_PS_VERSION_, '1.5.0.0', '<')) {
    require(dirname(__FILE__) . "/AdminStoreCommander_1_4.php");
} else {
    require(dirname(__FILE__) . "/controllers/admin/AdminStoreCommander.php");
}

