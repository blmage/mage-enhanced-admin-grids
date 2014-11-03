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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Model_Grid_Type_Sales_Abstract
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
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
    
    protected function _isOrdersGrid()
    {
        return false;
    }
    
    protected function _getOrderIdField()
    {
        return 'order_id';
    }
    
    protected function _getOrderBaseCustomColumn($id, $name, $group, $orderField=null)
    {
        $column = Mage::getModel('customgrid/custom_column_order_base')
            ->setId($id)
            ->setModule('customgrid')
            ->setName($name)
            ->setGroup($group)
            ->setAllowRenderers(true)
            ->setModelParams(array('order_field' => (empty($orderField) ? $id : $orderField)));
        
        if (!$this->_isOrdersGrid()) {
            $column->setModelParams(array('join_condition_main_field' => $this->_getOrderIdField()), true);
        }
        
        return $column;
    }
    
    protected function _getAddressCustomColumn($id, $typeId, $fieldId, $typeLabel, $fieldLabel)
    {
        $column = Mage::getModel('customgrid/custom_column_order_address_'.$typeId)
            ->setId($id)
            ->setModule('customgrid')
            ->setName($fieldLabel)
            ->setGroup($typeLabel)
            ->setAllowRenderers(true)
            ->setModelParams(array('address_field' => $fieldId));
        
        if (!$this->_isOrdersGrid()) {
            $column->setModelParams(array('join_condition_main_field' => $this->_getOrderIdField()), true);
        }
        
        return $column;
    }
    
    protected function _getPaymentCustomColumn($id, $name, $group, $paymentField=null)
    {
        $column = Mage::getModel('customgrid/custom_column_order_payment')
            ->setId($id)
            ->setModule('customgrid')
            ->setName($name)
            ->setGroup($group)
            ->setAllowRenderers(true)
            ->setModelParams(array('payment_field' => (empty($paymentField) ? $id : $paymentField)));
        
        if (!$this->_isOrdersGrid()) {
            $column->setModelParams(array('join_condition_main_field' => $this->_getOrderIdField()), true);
        }
        
        return $column;
    }
    
    abstract protected function _getItemsCustomColumnModel($customizable=false);
    
    protected function _getItemsCustomColumn($id, $customizable=false)
    {
        return Mage::getModel($this->_getItemsCustomColumnModel($customizable))
            ->setId($id)
            ->setModule('customgrid')
            ->setName(Mage::helper('customgrid')->__($customizable ? 'Customizable' : 'Default'))
            ->setGroup(Mage::helper('customgrid')->__('Items'))
            ->setAllowCustomization(true);
    }
    
    protected function _getAdditionalCustomColumns()
    {
        $helper  = Mage::helper('customgrid');
        $columns = array();
        
        // Base order fields
        $columns['shipping_method']  = $this->_getOrderBaseCustomColumn('shipping_method', $helper->__('Method'), $helper->__('Shipping'));
        $columns['shipping_description'] = $this->_getOrderBaseCustomColumn('shipping_description', $helper->__('Description'), $helper->__('Shipping'));
        
        if (!$this->_isOrdersGrid()) {
            $columns['base_grand_total'] = $this->_getOrderBaseCustomColumn('base_grand_total', Mage::helper('sales')->__('G.T. (Base)'), $helper->__('Order Amounts'));
            $columns['grand_total'] = $this->_getOrderBaseCustomColumn('grand_total', Mage::helper('sales')->__('G.T. (Purchased)'), $helper->__('Order Amounts'));
        }
        
        // Payment fields
        $columns['payment_method'] = $this->_getPaymentCustomColumn('payment_method', $helper->__('Method'), $helper->__('Payment'), 'method');
        $columns['cc_type'] = $this->_getPaymentCustomColumn('cc_type', $helper->__('CC Type'), $helper->__('Payment'));
        
        // Items
        $columns['default_items'] = $this->_getItemsCustomColumn('default_items');
        $columns['custom_items']  = $this->_getItemsCustomColumn('custom_items', true);
        
        // Address fields
        foreach ($this->_getAddressTypes() as $typeId => $typeLabel) {
            foreach ($this->_getAddressFields() as $fieldId => $fieldLabel) {
                $columnId = $typeId.'_'.$fieldId;
                $columns[$columnId] = $this->_getAddressCustomColumn($columnId, $typeId, $fieldId, $typeLabel, $fieldLabel);
            }
        }
        
        return array_filter($columns);
    }
}
