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

class BL_CustomGrid_Model_Grid_Type_Order
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/sales_order_grid');
    }
    
    protected function _getAddressTypes()
    {
        $helper = Mage::helper('customgrid');
        return array(
            'billing'  => $helper->__('Billing Address'),
            'shipping' => $helper->__('Shipping Address'),
        );
    }
    
    protected function _getAddressFields()
    {
        $helper = Mage::helper('customgrid');
        return array(
            'prefix'     => $helper->__('Prefix'),
            'suffix'     => $helper->__('Suffix'),
            'firstname'  => $helper->__('First Name'),
            'middlename' => $helper->__('Middle Name'),
            'lastname'   => $helper->__('Last Name'),
            'company'    => $helper->__('Company'),
            'street'     => $helper->__('Street Address'),
            'city'       => $helper->__('City'),
            'postcode'   => $helper->__('Zip/Postal Code'),
            'region'     => $helper->__('State/Province'),
            'country_id' => $helper->__('Country'),
            'telephone'  => $helper->__('Telephone'),
            'fax'        => $helper->__('Fax'),
            'email'      => $helper->__('Email'),
        );
    }
    
    protected function _getAdditionalCustomColumns()
    {
        $columns = array();
        
        // Add address fields
        foreach ($this->_getAddressTypes() as $typeId => $typeLabel) {
            foreach ($this->_getAddressFields() as $fieldId => $fieldLabel) {
                $columnId = $typeId.'_'.$fieldId;
                
                $column = Mage::getModel('customgrid/custom_column_order_address_'.$typeId)
                    ->setId($columnId)
                    ->setModule('customgrid')
                    ->setName($fieldLabel)
                    ->setGroup($typeLabel)
                    ->setAllowRenderers(true)
                    ->setModelParams(array('address_field' => $fieldId));
                
                $columns[$columnId] = $column;
            }
        }
        
        return $columns;
    }
}
