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

abstract class BL_CustomGrid_Model_Grid_Type_Sales_Abstract extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    /**
     * Return available address types as an option hash
     * 
     * @return array
     */
    protected function _getAddressTypes()
    {
        return array(
            'billing'  => $this->getBaseHelper()->__('Billing Address'),
            'shipping' => $this->getBaseHelper()->__('Shipping Address'),
        );
    }
    
    /**
     * Return available address fields as an option hash
     * 
     * @return array
     */
    protected function _getAddressFields()
    {
        $helper = $this->getBaseHelper();
        
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
    
    /**
     * Return whether this grid type applies to the orders grids (otherwise to one or more of the other sales grids)
     * 
     * @return bool
     */
    protected function _isOrdersGrid()
    {
        return false;
    }
    
    /**
     * Return the name of the order ID field in the main table
     * 
     * @return string
     */
    protected function _getOrderIdFieldName()
    {
        return 'order_id';
    }
    
    /**
     * Prepare and return a base order-related custom column from the given config values
     * 
     * @param string $columnId Column ID
     * @param string $name Column name
     * @param string $group Column group
     * @param string $fieldName Order field name
     * @return BL_CustomGrid_Model_Custom_Column_Order_Base
     */
    protected function _getOrderBaseCustomColumn($columnId, $name, $group, $fieldName = null)
    {
        /** @var $customColumn BL_CustomGrid_Model_Custom_Column_Order_Base */
        $customColumn = Mage::getModel('customgrid/custom_column_order_base');
        $customColumn->setId($columnId)
            ->setModule('customgrid')
            ->setName($name)
            ->setGroup($group)
            ->setAllowRenderers(true)
            ->setConfigParams(array('order_field_name' => (empty($fieldName) ? $columnId : $fieldName)));
        
        if (!$this->_isOrdersGrid()) {
            $customColumn->setConfigParams(
                array('join_condition_main_field_name' => $this->_getOrderIdFieldName()),
                true
            );
        }
        
        return $customColumn;
    }
    
    /**
     * Prepare and return an order address-related custom column from the given config values
     * 
     * @param string $addressTypeId Address type ID
     * @param string $columnId Column ID
     * @param string $name Column name
     * @param string $group Column group
     * @param string $fieldName Address field name
     * @return BL_CustomGrid_Model_Custom_Column_Order_Address_Abstract
     */
    protected function _getAddressCustomColumn($addressTypeId, $columnId, $name, $group, $fieldName)
    {
        /** @var $customColumn BL_CustomGrid_Model_Custom_Column_Order_Address_Abstract */
        $customColumn = Mage::getModel('customgrid/custom_column_order_address_' . $addressTypeId);
        $customColumn->setId($columnId)
            ->setModule('customgrid')
            ->setName($name)
            ->setGroup($group)
            ->setAllowRenderers(true)
            ->setConfigParams(array('address_field_name' => $fieldName));
        
        if (!$this->_isOrdersGrid()) {
            $customColumn->setConfigParams(
                array('join_condition_main_field_name' => $this->_getOrderIdFieldName()),
                true
            );
        }
        
        return $customColumn;
    }
    
    /**
     * Prepare and return an order payment-related custom column from the given config values
     * 
     * @param string $columnId Column ID
     * @param string $name Column name
     * @param string $group Column group
     * @param string $fieldName Payment field name
     * @return BL_CustomGrid_Model_Custom_Column_Order_Payment
     */
    protected function _getPaymentCustomColumn($columnId, $name, $group, $fieldName = null)
    {
        /** @var $customColumn BL_CustomGrid_Model_Custom_Column_Order_Payment */
        $customColumn = Mage::getModel('customgrid/custom_column_order_payment');
        $customColumn->setId($columnId)
            ->setModule('customgrid')
            ->setName($name)
            ->setGroup($group)
            ->setAllowRenderers(true)
            ->setConfigParams(array('payment_field_name' => (empty($fieldName) ? $columnId : $fieldName)));
        
        if (!$this->_isOrdersGrid()) {
            $customColumn->setConfigParams(
                array('join_condition_main_field_name' => $this->_getOrderIdFieldName()),
                true
            );
        }
        
        return $customColumn;
    }
    
    /**
     * Return the model type usable for items list-based custom columns
     * 
     * @param bool $customizable Whether the items list should be customizable
     * @return string
     */
    abstract protected function _getItemsCustomColumnModel($customizable = false);
    
    /**
     * Prepare and return an items list-related custom column from the given config values
     * 
     * @param string $columnId Column ID
     * @param bool $customizable Whether the items list should be customizable
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    protected function _getItemsCustomColumn($columnId, $customizable = false)
    {
        /** @var $customColumn BL_CustomGrid_Model_Custom_Column_Abstract */
        $customColumn = Mage::getModel($this->_getItemsCustomColumnModel($customizable));
        return $customColumn->setId($columnId)
            ->setModule('customgrid')
            ->setName($this->getBaseHelper()->__($customizable ? 'Customizable' : 'Default'))
            ->setGroup($this->getBaseHelper()->__('Items'))
            ->setAllowCustomization(true);
    }
    
    protected function _getAdditionalCustomColumns()
    {
        $helper  = $this->getBaseHelper();
        
        $customColumns = array(
            'shipping_method' => $this->_getOrderBaseCustomColumn(
                'shipping_method',
                $helper->__('Method'),
                $helper->__('Shipping')
            ),
            'shipping_description' => $this->_getOrderBaseCustomColumn(
                'shipping_description',
                $helper->__('Description'),
                $helper->__('Shipping')
            ),
            'payment_method' => $this->_getPaymentCustomColumn(
                'payment_method',
                $helper->__('Method'),
                $helper->__('Payment'),
                'method'
            ),
            'default_items' => $this->_getItemsCustomColumn('default_items'),
            'custom_items'  => $this->_getItemsCustomColumn('custom_items', true),
        );
        
        if (!$this->_isOrdersGrid()) {
            $customColumns['base_grand_total'] = $this->_getOrderBaseCustomColumn(
                'base_grand_total',
                Mage::helper('sales')->__('G.T. (Base)'),
                $helper->__('Order Amounts')
            );
            
            $customColumns['grand_total'] = $this->_getOrderBaseCustomColumn(
                'grand_total',
                Mage::helper('sales')->__('G.T. (Purchased)'),
                $helper->__('Order Amounts')
            );
        }
        
        foreach ($this->_getAddressTypes() as $typeId => $typeLabel) {
            foreach ($this->_getAddressFields() as $fieldId => $fieldLabel) {
                $id = $typeId . '_' . $fieldId;
                $customColumns[$id] = $this->_getAddressCustomColumn($typeId, $id, $fieldLabel, $typeLabel, $fieldId);
            }
        }
        
        return $customColumns;
    }
}
