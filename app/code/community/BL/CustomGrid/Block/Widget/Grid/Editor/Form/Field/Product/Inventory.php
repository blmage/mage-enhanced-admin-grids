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

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Field_Product_Inventory extends BL_CustomGrid_Block_Widget_Grid_Editor_Form_Field_Default
{
    protected function _initFormValues()
    {
        return $this;
    }
    
    protected function _prepareFormField(
        Varien_Data_Form_Element_Abstract $field,
        $fieldType,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        if ($valueConfig->getData('form_field/inventory_field') == 'qty') {
            $entity    = $this->getEditedEntity();
            $qtyValue  = $this->_getProductInventoryData($entity, 'qty') * 1;
            $afterHtml = $field->getAfterElementHtml()
                . '<input type="hidden"'
                . ' name="' . $valueConfig->getData('request/values_key') . '[original_inventory_qty]"'
                . ' value="' . $qtyValue . '" />';
            
            $field->setValue($qtyValue);
            $field->setAfterElementHtml($afterHtml);
        }
        return parent::_prepareFormField($field, $fieldType, $valueConfig);
    }
    
    /**
     * Return the specified inventory value from the given product
     * 
     * @param Mage_Catalog_Model_Product $product Edited product
     * @param string $fieldName Inventory field name
     * @return mixed
     */
    protected function _getProductInventoryData(Mage_Catalog_Model_Product $product, $fieldName)
    {
        return $product->getStockItem()
            ? $product->getStockItem()->getDataUsingMethod($fieldName)
            : Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $fieldName);
    }
}
