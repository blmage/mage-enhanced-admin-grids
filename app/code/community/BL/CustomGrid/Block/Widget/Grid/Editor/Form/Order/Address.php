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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Order_Address extends BL_CustomGrid_Block_Widget_Grid_Editor_Form_Abstract
{
    protected function _prepareForm()
    {
        $form = $this->_initializeForm();
        $orderAddress = $this->getEditedOrderAddress();
        $addressAttribute = $this->getEditedAddressAttribute();
        $attributeCode  = $addressAttribute->getAttributeCode();
        $attributeLabel = $this->__($addressAttribute->getStoreLabel());
        
        if (!$inputType = $addressAttribute->getFrontend()->getInputType()) {
            Mage::throwException('Invalid attribute input type');
        }
        
        /** @var Mage_Directory_Helper_Data $directoryHelper */
        $directoryHelper = Mage::helper('directory');
        
        if ($attributeCode == 'postcode') {
            $isFieldRequired = !$directoryHelper->isZipCodeOptional($orderAddress->getCountryId());
        } else {
            $isFieldRequired = $addressAttribute->getIsRequired();
        }
        
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => $this->getBaseFieldsetLegend($attributeLabel, $isFieldRequired),
                'class'  => 'fieldset-wide blcg-editor-fieldset',
            )
        );
        
        if ($inputType) {
            $formElement = $fieldset->addField(
                $addressAttribute->getAttributeCode(),
                $inputType,
                array(
                    'name'     => $addressAttribute->getAttributeCode(),
                    'label'    => $this->__($addressAttribute->getStoreLabel()),
                    'class'    => $addressAttribute->getFrontend()->getClass(),
                    'required' => $isFieldRequired,
                )
            );
            
            if ($inputType == 'multiline') {
                $formElement->setLineCount($addressAttribute->getMultilineCount());
            }
            
            $formElement->setEntityAttribute($addressAttribute);
            
            if (($inputType == 'select') || ($inputType == 'multiselect')) {
                $formElement->setValues($addressAttribute->getFrontend()->getSelectOptions());
            } else if ($inputType == 'date') {
                $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                $formElement->setImage($this->getSkinUrl('images/grid-cal.gif'));
                $formElement->setFormat($dateFormat);
            }
            
            $this->_prepareFieldFormElement($formElement, $fieldset);
        }
        
        $form->setValues($this->getEditedOrderAddress()->getData());
        $form->setFieldNameSuffix($this->getValueConfig()->getRequestValuesKey());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
    
    public function getIsRequiredValueEdit()
    {
        return $this->getEditedAddressAttribute()->getIsRequired();
    }
    
    /**
     * Return the edited order
     * 
     * @return Mage_Sales_Model_Order
     */
    public function getEditedOrder()
    {
        return $this->getEditedEntity();
    }
    
    /**
     * Return the type of the edited order address
     * 
     * @return string
     */
    public function getEditedAddressType()
    {
        return $this->getValueConfig()->getData('form_field/address_type');
    }
    
    /**
     * Return the name of the edited address field
     * 
     * @return string
     */
    public function getEditedAddressField()
    {
        return $this->getValueConfig()->getData('form_field/address_field');
    }
    
    /**
     * Return the edited order address
     *
     * @return Mage_Sales_Model_Order
     */
    public function getEditedOrderAddress()
    {
        return ($this->getEditedAddressType() == Mage_Sales_Model_Order_Address::TYPE_SHIPPING)
            ? $this->getEditedOrder()->getShippingAddress()
            : $this->getEditedOrder()->getBillingAddress();
    }
    
    /**
     * Return the edited address attribute
     * 
     * @return Mage_Customer_Model_Attribute
     */
    public function getEditedAddressAttribute()
    {
        if (!$this->hasData('edited_address_attribute')) {
            /* @var Mage_Customer_Model_Address $addressModel */
            $addressModel = Mage::getModel('customer/address');
            
            /** @var Mage_Customer_Model_Form $addressForm */
            $addressForm = Mage::getModel('customer/form');
            $addressForm->setFormCode('adminhtml_customer_address');
            $addressForm->setStore($this->getEditedOrder()->getStore());
            $addressForm->setEntity($addressModel);
            
            $addressField = $this->getEditedAddressField();
            $addressAttributes = $addressForm->getAttributes();
            
            if (!isset($addressAttributes[$addressField])) {
                Mage::throwEception('Unknown address attribute');
            }
            
            /** @var Mage_Customer_Model_Attribute $addressAttribute */
            $addressAttribute = $addressAttributes[$addressField];
            $addressAttribute->setStoreId($this->getEditedOrder()->getStoreId());
            
            if ($addressField == 'street') {
                /** @var Mage_Adminhtml_Helper_Addresses $addressesHelper */
                $addressesHelper = Mage::helper('adminhtml/addresses');
                $addressesHelper->processStreetAttribute($addressAttribute);
            }
            
            $this->setData('edited_address_attribute', $addressAttributes[$addressField]);
        }
        return $this->_getData('edited_address_attribute');
    }
    
    /**
     * Prepare the given field form element
     * 
     * @param Varien_Data_Form_Element_Abstract $formElement Field form element
     * @param Varien_Data_Form_Element_Fieldset $fieldset Form fieldset
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _prepareFieldFormElement(
        Varien_Data_Form_Element_Abstract $formElement,
        Varien_Data_Form_Element_Fieldset $fieldset
    ) {
        $editedAddress = $this->getEditedOrderAddress();
        $addressAttribute = $this->getEditedAddressAttribute();
        $attributeCode = $addressAttribute->getAttributeCode();
        $store = $this->getEditedOrder()->getStore();
        
        /** @var Mage_Customer_Helper_Data $customerHelper */
        $customerHelper = Mage::helper('customer');
        
        if ($attributeCode == 'prefix') {
            $prefixOptions = $customerHelper->getNamePrefixOptions($store);
            
            if (!empty($prefixOptions)) {
                $fieldset->removeField($formElement->getId());
                
                $formElement = $fieldset->addField(
                    $formElement->getId(),
                    'select',
                    $formElement->getData()
                );
                
                $formElement->setValues($prefixOptions);
                $formElement->addElementValues($editedAddress->getPrefix());
            }
        } elseif ($attributeCode == 'suffix') {
            $suffixOptions = $customerHelper->getNameSuffixOptions($store);
            
            if (!empty($suffixOptions)) {
                $fieldset->removeField($formElement->getId());
                
                $formElement = $fieldset->addField(
                    $formElement->getId(),
                    'select',
                    $formElement->getData()
                );
                
                $formElement->setValues($suffixOptions);
                $formElement->addElementValues($editedAddress->getSuffix());
            }
        }
        
        return $formElement;
    }
}
