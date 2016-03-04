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
    /**
     * Return the inventory helper
     *
     * @return BL_CustomGrid_Helper_Catalog_Inventory
     */
    public function getInventoryHelper()
    {
        return Mage::helper('customgrid/catalog_inventory');
    }
    
    protected function _initFormValues()
    {
        if ($form = $this->getForm()) {
            /** @var Mage_Catalog_Model_Product $editedProduct */
            $editedProduct   = $this->getEditedEntity();
            $valueConfig     = $this->getValueConfig();
            $inventoryHelper = $this->getInventoryHelper();
            
            $fieldId    = $valueConfig->getFormFieldId();
            $fieldName  = $valueConfig->getData('form_field/inventory_field');
            $fieldValue = $inventoryHelper->getProductOwnInventoryValue($editedProduct, $fieldName);
            $fieldValue = $inventoryHelper->prepareFormInventoryValue($fieldName, $fieldValue);
            
            $form->setValues(array($fieldId => $fieldValue));
        }
        return $this;
    }
    
    protected function _prepareFormField(
        Varien_Data_Form_Element_Abstract $field,
        $fieldType,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        /** @var Mage_Catalog_Model_Product $editedProduct */
        $editedProduct   = $this->getEditedEntity();
        $inventoryHelper = $this->getInventoryHelper();
        $fieldName = $valueConfig->getData('form_field/inventory_field');
        $requestValuesKey = $valueConfig->getRequestValuesKey();
        
        if ($fieldName == 'qty') {
            $field->setAfterElementHtml(
                $field->getAfterElementHtml()
                . '<input type="hidden"'
                . ' name="' . $requestValuesKey . '[original_inventory_qty]"'
                . ' value="' . ($inventoryHelper->getProductOwnInventoryValue($editedProduct, 'qty') * 1) . '" />'
            );
        }
        
        if ($useConfigFieldName = $inventoryHelper->getBaseFieldUseConfigFieldName($fieldName)) {
            $useConfigFieldId = 'inventory_' . $useConfigFieldName;
            $isProductUsingConfig = $inventoryHelper->getProductOwnInventoryValue($editedProduct, $useConfigFieldName); 
            
            $afterElementHtml = $field->getAfterElementHtml()
                . '<input type="checkbox" name="' . $requestValuesKey . '[' . $useConfigFieldName . ']" value="1" '
                . 'id="' . $useConfigFieldId . '" ' . ($isProductUsingConfig  ? 'checked="checked" ' : '')
                . 'onclick="toggleValueElements(this, this.parentNode);" class="checkbox" />'
                . '<label for="' . $useConfigFieldId . '">' . $this->__('Use Config Settings') . '</label>'
                . '<script type="text/javascript">'
                . 'toggleValueElements($("' . $useConfigFieldId . '"), $("' . $useConfigFieldId . '").parentNode);'
                . '</script>';
            
            $field->setAfterElementHtml($afterElementHtml);
        }
        
        return parent::_prepareFormField($field, $fieldType, $valueConfig);
    }
}
