<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   BL
 * @package    BL_CustomGrid
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_Catalog_Inventory extends Mage_Core_Helper_Abstract
{
    /**
     * Map from the base inventory field names to their corresponding "Use config settings" field names
     *
     * @var string[]
     */
    protected $_useConfigFieldsMap = array(
        'backorders'       => 'use_config_backorders',
        'manage_stock'     => 'use_config_manage_stock',
        'min_qty'          => 'use_config_min_qty',
        'min_sale_qty'     => 'use_config_min_sale_qty',
        'max_sale_qty'     => 'use_config_max_sale_qty',
        'notify_stock_qty' => 'use_config_notify_stock_qty',
        'qty_increments'   => 'use_config_qty_increments',
    );
    
    public function __construct()
    {
        /** @var BL_CustomGrid_Helper_Data $baseHelper */
        $baseHelper = Mage::helper('customgrid');
        
        if ($baseHelper->isMageVersionGreaterThan(1, 5)) {
            $this->_useConfigFieldsMap['enable_qty_increments'] = 'use_config_enable_qty_inc';
        } else {
            $this->_useConfigFieldsMap['enable_qty_increments'] = 'use_config_enable_qty_increments';
        }
    }
    
    /**
     * Return the name of the "Use config settings" field corresponding to the given base inventory field name,
     * if any exists
     *
     * @param string $fieldName Inventory field name
     * @return string|false
     */
    public function getBaseFieldUseConfigFieldName($fieldName)
    {
        return (isset($this->_useConfigFieldsMap[$fieldName])  ? $this->_useConfigFieldsMap[$fieldName] : false);
    }
    
    /**
     * Return the default config value for the given inventory field
     *
     * @param string $fieldName Inventory field name
     * @return mixed
     */
    public function getDefaultConfigInventoryValue($fieldName)
    {
        return Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $fieldName);
    }
    
    /**
     * Return the own value of the given product for the given inventory field
     *
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param string $fieldName Inventory field name
     * @return mixed
     */
    public function getProductOwnInventoryValue(Mage_Catalog_Model_Product $product, $fieldName)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item */
        return ($stockItem = $product->getStockItem())
            ? $stockItem->getDataUsingMethod($fieldName)
            : Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $fieldName);
    }
    
    /**
     * Return the actual (either inherited or own) value of the given product for the given inventory field
     *
     * @param Mage_Catalog_Model_Product $product Checked product
     * @param string $fieldName Inventory field name
     * @return mixed
     */
    public function getProductActualInventoryValue(Mage_Catalog_Model_Product $product, $fieldName)
    {
        /** @var Mage_CatalogInventory_Model_Stock_Item */
        if ($stockItem = $product->getStockItem()) {
            if ((!$useConfigFieldName = $this->getBaseFieldUseConfigFieldName($fieldName))
                || ($stockItem->getData($useConfigFieldName) == 0)) {
                return $stockItem->getData($fieldName);
            }
        }
        return Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $fieldName);
    }
    
    /**
     * Prepare the given inventory field value for use in forms
     * 
     * @param string $fieldName Inventory field name
     * @param mixed $value Inventory field value
     * @return mixed
     */
    public function prepareFormInventoryValue($fieldName, $value)
    {
        if ((substr($fieldName, 0, 3) == 'qty') || (substr($fieldName, -3) == 'qty')) {
            $value *= 1;
        }
        return $value;
    }
}
