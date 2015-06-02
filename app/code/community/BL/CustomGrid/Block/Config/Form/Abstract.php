<?php
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
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Widget
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */ 

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

abstract class BL_CustomGrid_Block_Config_Form_Abstract extends BL_CustomGrid_Block_Widget_Form
{
    /**
     * Return the form ID
     * 
     * @return string
     */
    abstract public function getFormId();
    
    /**
     * Return the form code
     * 
     * @return string
     */
    abstract protected function _getFormCode();
    
    /**
     * Return the form action
     * 
     * @return string
     */
    abstract protected function _getFormAction();
    
    /**
     * Return the fields to add to the form
     * 
     * @return BL_CustomGrid_Object[]
     */
    abstract protected function _getFormFields();
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        /** @var $fieldsetRenderer BL_CustomGrid_Block_Widget_Form_Renderer_Fieldset */
        $fieldsetRenderer = $this->getLayout()->createBlock('customgrid/widget_form_renderer_fieldset');
        Varien_Data_Form::setFieldsetRenderer($fieldsetRenderer);
        
        return $this;
    }
    
    protected function _prepareForm()
    {
        $helper = $this->getTranslationHelper();
        
        $form = new Varien_Data_Form(
            array(
                'id'     => $this->getFormId(),
                'action' => $this->_getFormAction(),
                'method' => 'post',
                'use_container' => true,
            )
        );
        
        $this->setForm($form);
        $formCode  = $this->_getFormCode();
        $fieldsets = array();
        $fields = $this->_getFormFields();
        
        foreach ($fields as $field) {
            if ($fieldsetLabel = $field->getGroup()) {
                $fieldsetLabel = $helper->__($fieldsetLabel);
            } else {
                $fieldsetLabel = $this->__('General');
            }
            
            $fieldsetKey = md5($formCode . $fieldsetLabel);
            
            if (!isset($fieldsets[$fieldsetKey])) {
                $fieldsetId = 'blcg_config_form_fieldset_' . $fieldsetKey;
                $fieldsets[$fieldsetKey] = $form->addFieldset($fieldsetId, array('legend' => $fieldsetLabel));
                /*
                Use an own renderer for multiselect fields to prevent a bug between
                Prototype JS / Form.serializeElements() (imploding the values)
                and Varien_Data_Form_Element_Multiselect (generating an array parameter),
                leading to obtain an array with a single value containing the expected imploded values
                */
                $fieldsets[$fieldsetKey]->addType('multiselect', 'BL_CustomGrid_Block_Config_Form_Element_Multiselect');
            }
            
            $this->_addField($fieldsets[$fieldsetKey], $field);
        }
        
        return $this;
    }
    
    /**
     * Return the default value for the given field
     * 
     * @param Varien_Object $field Field object
     * @return mixed
     */
    protected function _getFieldDefaultValue(Varien_Object $field)
    {
        $fieldName = $field->getKey();
        
        if (is_array($values = $this->getConfigValues()) && isset($values[$fieldName])) {
            $value = $values[$fieldName];
        } else {
            $value = $field->getValue();
            
            if (($fieldName == 'unique_id') && ($value == '')) {
                $value = md5(microtime(true));
            }
        }
        
        return $value;
    }
    
    /**
     * Return the source values for the given field
     * 
     * @param Varien_Object $field Field object
     * @return array
     */
    protected function _getFieldValues(Varien_Object $field)
    {
        $helper = $this->getTranslationHelper();
        $values = array();
        
        if ($sourceModel = $field->getSourceModel()) {
            try {
                if (is_array($sourceModel)) {
                    $values = call_user_func(array(Mage::getModel($sourceModel['model']), $sourceModel['method']));
                } else {
                    $values = Mage::getModel($sourceModel)->toOptionArray();
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $values = array();
            }
        } elseif (is_array($fieldValues = $field->getValues())) {
            foreach ($fieldValues as $value) {
                $values[] = array(
                    'label' => $helper->__($value['label']),
                    'value' => $value['value']
                );
            }
        }
        
        return $values;
    }
    
    /**
     * Returne the type and renderer for the given field
     * 
     * @param Varien_Object $field Field object
     * @return array Type (string) and renderer (Mage_Core_Block_Abstract|null)
     */
    protected function _getFieldTypeAndRenderer(Varien_Object $field)
    {
        $fieldType = $field->getType();
        $fieldRenderer = null;
        
        if (!$field->getVisible()) {
            $fieldType = 'hidden';
        } elseif (strpos($fieldType, '/') !== false) {
            $fieldType = 'text';
            $fieldRenderer = $this->getLayout()->createBlock($fieldType);
        }
        
        return array($fieldType, $fieldRenderer);
    }
    
    /**
     * Apply the given helper block to the given form field
     * 
     * @param Varien_Object $helperBlock Helper block
     * @param Varien_Data_Form_Element_Fieldset $fieldset Form fieldset
     * @param Varien_Data_Form_Element_Abstract $formField Form field
     * @return BL_CustomGrid_Block_Config_Form_Abstract
     */
    protected function _applyHelperBlockToFormField(
        Varien_Object $helperBlock,
        Varien_Data_Form_Element_Fieldset $fieldset,
        Varien_Data_Form_Element_Abstract $formField
    ) {
        try {
            $helperData  = $helperBlock->getData();
            $helperBlock = $this->getLayout()->createBlock($helperBlock->getType(), '', $helperData);
            
            if ($helperBlock && method_exists($helperBlock, 'prepareElementHtml')) {
                $helperBlock->setConfig($helperData)
                    ->setFieldsetId($fieldset->getId())
                    ->prepareElementHtml($formField);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }
    
    /**
     * Prepare the dependences for the given field
     * 
     * @param Varien_Object $field Field object
     * @param string $formFieldId Form field ID
     * @return BL_CustomGrid_Block_Config_Form_Abstract
     */
    protected function _prepareFieldDependences(Varien_Object $field, $formFieldId)
    {
        $dependenceBlock = $this->getDependenceBlock();
        $fieldName = $field->getKey();
        $dependenceBlock->addFieldMap($formFieldId, $fieldName);
        
        if (is_array($depends = $field->getDepends())) {
            foreach ($depends as $fromFieldName => $fromValue) {
                if (is_array($fromValue)) {
                    if (isset($fromValue['value'])) {
                        $fromValue = (string) $fromValue['value'];
                    } elseif (isset($fromValue['values'])) {
                        $fromValue = array_values($fromValue['values']);
                    } else {
                        $fromValue = array_values($fromValue);
                    }
                }
                
                $dependenceBlock->addFieldDependence($fieldName, $fromFieldName, $fromValue);
            }
        }
        
        return $this;
    }
    
    /**
     * Add the given field to the given form fieldset
     * 
     * @param Varien_Data_Form_Element_Fieldset $fieldset Form fieldset
     * @param Varien_Object $field Field object
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _addField(Varien_Data_Form_Element_Fieldset $fieldset, Varien_Object $field)
    {
        $form = $this->getForm();
        $helper = $this->getTranslationHelper();
        $fieldName = $field->getKey();
        
        // Base data
        $fieldData = array(
            'name'     => $form->addSuffixToName($fieldName, 'parameters'),
            'label'    => $helper->__($field->getLabel()),
            'note'     => $helper->__($field->getDescription()),
            'required' => $field->getRequired(),
            'class'    => 'renderer-option',
            'value'    => $this->_getFieldDefaultValue($field),
            'values'   => $this->_getFieldValues($field),
        );
        
        // Create and prepare form field
        list($fieldType, $fieldRenderer) = $this->_getFieldTypeAndRenderer($field);
        $formField = $fieldset->addField($this->getFieldsetHtmlId() . '_' . $fieldName, $fieldType, $fieldData);
        
        if ($fieldRenderer) {
            $formField->setRenderer($fieldRenderer);
        }
        if (($fieldType == 'multiselect') && ($size = $field->getSize())) {
            $formField->setSize($size);
        }
        if (($helperBlock = $field->getHelperBlock()) instanceof Varien_Object) {
            $this->_applyHelperBlockToFormField($helperBlock, $fieldset, $formField);
        }
        
        $this->_prepareFieldDependences($field, $formField->getId());
        return $formField;
    }
}
