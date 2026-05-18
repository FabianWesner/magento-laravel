<?php

use Magento\Framework\App\Bootstrap;

$model = Mage::getModel('catalog/product');
$legacy = new Mage_Catalog_Model_Product();
$varien = new Varien_Object();
$zend = new Zend_Db_Select();
$bootstrap = Bootstrap::class;
