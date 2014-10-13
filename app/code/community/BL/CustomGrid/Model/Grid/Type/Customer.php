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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Customer extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/customer_grid',
            'adminhtml/sales_order_create_customer_grid'
        );
    }
    
    public function canHaveAttributeColumns($blockType)
    {
        return true;
    }
    
    protected function _getAvailableAttributes($blockType)
    {
        $attributes = Mage::getResourceModel('customer/customer')
            ->loadAllAttributes()
            ->getAttributesByCode();
        
        $availableAttributes = array();
        
        foreach ($attributes as $attribute) {
            if ($attribute->getBackendType() != 'static') {
                $availableAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        
        return $availableAttributes;
    }
    
    protected function _getEditableAttributes($blockType)
    {
        return array();
    }
    
    protected function _getAddressTypes()
    {
        return array(
            'billing'  => $this->_getBaseHelper()->__('Billing Address'),
            'shipping' => $this->_getBaseHelper()->__('Shipping Address'),
        );
    }
    
    protected function _getAdditionalCustomColumns()
    {
        $columns = array();
        $helper  = Mage::helper('customer');
        
        // Add address attributes for both address types
        $addressAttributes = Mage::getResourceModel('customer/address')
            ->loadAllAttributes()
            ->getAttributesByCode();
        
        $availableAttributes = array();
        
        foreach ($addressAttributes as $attribute) {
            if ($attribute->getBackendType() != 'static') {
                $availableAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        
        foreach ($this->_getAddressTypes() as $addressType => $typeLabel) {
            foreach ($availableAttributes as $attributeCode => $attribute) {
                $columnId = $addressType . '_address_' . $attributeCode;
                // Force translation, because customer address attributes may not be translated by default
                $columnName = $helper->__($attribute->getFrontendLabel());
                
                // Clarify attributes labels for which we can't know which is which by default
                if ($attributeCode == 'region') {
                    $columnName .= ' (' . $helper->__('Name') . ')';
                } elseif ($attributeCode == 'region_id') {
                    $columnName .= ' (' . $helper->__('ID') . ')';
                }
                
                $column = Mage::getModel('customgrid/custom_column_customer_address_' . $addressType)
                    ->setId($columnId)
                    ->setModule('customgrid')
                    ->setName($columnName)
                    ->setGroup($typeLabel)
                    ->setAllowRenderers(true)
                    ->setConfigParams(array('attribute_code' => $attributeCode));
                
                $columns[$columnId] = $column;
            }
        }
        
        return $columns;
    }
}
