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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Customer
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{   
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return (($type == 'adminhtml/customer_grid')
            || ($type == 'adminhtml/sales_order_create_customer_grid'));
    }
    
    public function canHaveAttributeColumns($type)
    {
        return true;
    }
    
    protected function _getAvailableAttributes($type)
    {
        $attributes = Mage::getModel('customer/customer')->getResource()
            ->loadAllAttributes()
            ->getAttributesByCode();
        $keptAttributes = array();
        
        foreach ($attributes as $key => $attribute) {
            if ($attribute->getBackendType() != 'static') {
                // All attributes but static ones
                $keptAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        
        return $keptAttributes;
    }
    
    protected function _getEditableAttributes($type)
    {
        return array();
    }
    
    protected function _getAddressTypes()
    {
        $helper = Mage::helper('customgrid');
        return array(
            'billing'  => $helper->__('Billing Address'),
            'shipping' => $helper->__('Shipping Address'),
        );
    }
    
    protected function _getAdditionalCustomColumns()
    {
        $columns = array();
        $helper  = Mage::helper('customer');
        
        // Add address attributes
        $addressAttributes = Mage::getModel('customer/address')->getResource()
            ->loadAllAttributes()
            ->getAttributesByCode();
        $keptAttributes = array();
        
        foreach ($addressAttributes as $attribute) {
            if ($attribute->getBackendType() != 'static') {
                $keptAttributes[$attribute->getAttributeCode()] = $attribute;
            }
        }
        
        foreach ($this->_getAddressTypes() as $addressType => $typeLabel) {
            foreach ($keptAttributes as $attributeCode => $attribute) {
                $columnId   = $addressType.'_address_'.$attributeCode;
                // Force translation, customer address attributes may not be translated by default
                $columnName = $helper->__($attribute->getFrontendLabel());
                
                // Clarify attributes labels for which we can't know which is which by default
                if ($attributeCode == 'region') {
                    $columnName .= ' ('.$helper->__('Name').')';
                } elseif ($attributeCode == 'region_id') {
                    $columnName .= ' ('.$helper->__('ID').')';
                }
                
                $column = Mage::getModel('customgrid/custom_column_customer_address_'.$addressType)
                    ->setId($columnId)
                    ->setModule('customgrid')
                    ->setName($columnName)
                    ->setGroup($typeLabel)
                    ->setAllowRenderers(true)
                    ->setModelParams(array('attribute_code' => $attributeCode));
                
                $columns[$columnId] = $column;
            }
        }
        
        return $columns;
    }
}