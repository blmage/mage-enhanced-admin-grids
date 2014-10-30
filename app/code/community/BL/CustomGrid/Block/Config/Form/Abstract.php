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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Block_Config_Form_Abstract extends BL_CustomGrid_Block_Widget_Form
{
    abstract public function getFormId();
    abstract protected function _getFormCode();
    abstract protected function _getFormAction();
    abstract protected function _getFormFields();
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        Varien_Data_Form::setFieldsetRenderer(
            $this->getLayout()->createBlock('customgrid/widget_form_renderer_fieldset')
        );
        
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
    
    protected function _getFieldDefaultValue(Varien_Object $parameter)
    {
        $fieldName = $parameter->getKey();
        
        if (is_array($values = $this->getConfigValues()) && isset($values[$fieldName])) {
            $value = $values[$fieldName];
        } else {
            $value = $parameter->getValue();
            
            if (($fieldName == 'unique_id') && ($value == '')) {
                $value = md5(microtime(true));
            }
        }
        
        return $value;
    }
    
    protected function _getFieldValues(Varien_Object $parameter)
    {
        $helper = $this->getTranslationHelper();
        $values = array();
        
        if ($sourceModel = $parameter->getSourceModel()) {
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
        } elseif (is_array($paramValues = $parameter->getValues())) {
            foreach ($paramValues as $value) {
                $values[] = array(
                    'label' => $helper->__($value['label']),
                    'value' => $value['value']
                );
            }
        }
        
        return $values;
    }
    
    protected function _getFieldTypeAndRenderer(Varien_Object $parameter)
    {
        $fieldType = $parameter->getType();
        $fieldRenderer = null;
        
        if (!$parameter->getVisible()) {
            $fieldType = 'hidden';
        } elseif (strpos($fieldType, '/') !== false) {
            $fieldType = 'text';
            $fieldRenderer = $this->getLayout()->createBlock($fieldType);
        }
        
        return array($fieldType, $fieldRenderer);
    }
    
    protected function _prepareFieldDependences(Varien_Object $parameter, Varien_Data_Form_Element_Abstract $field)
    {
        $dependenceBlock = $this->getDependenceBlock();
        $fieldName = $parameter->getKey();
        $dependenceBlock->addFieldMap($field->getId(), $fieldName);
        
        if (is_array($depends = $parameter->getDepends())) {
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
    
    protected function _addField(Varien_Data_Form_Element_Fieldset $fieldset, Varien_Object $parameter)
    {
        $form = $this->getForm();
        $helper = $this->getTranslationHelper();
        $fieldName = $parameter->getKey();
        
        // Base data
        $fieldData = array(
            'name'     => $form->addSuffixToName($fieldName, 'parameters'),
            'label'    => $helper->__($parameter->getLabel()),
            'note'     => $helper->__($parameter->getDescription()),
            'required' => $parameter->getRequired(),
            'class'    => 'renderer-option',
            'value'    => $this->_getFieldDefaultValue($parameter),
            'values'   => $this->_getFieldValues($parameter),
        );
        
        // Create and prepare form field
        list($fieldType, $fieldRenderer) = $this->_getFieldTypeAndRenderer($parameter);
        $field = $fieldset->addField($this->getFieldsetHtmlId() . '_' . $fieldName, $fieldType, $fieldData);
        
        if ($fieldRenderer) {
            $field->setRenderer($fieldRenderer);
        }
        if (($fieldType == 'multiselect') && ($size = $parameter->getSize())) {
            $field->setSize($size);
        }
        if (($helperBlock = $parameter->getHelperBlock()) instanceof Varien_Object) {
            try {
                $helperData  = $helperBlock->getData();
                $helperBlock = $this->getLayout()->createBlock($helperBlock->getType(), '', $helperData);
                
                if ($helperBlock && method_exists($helperBlock, 'prepareElementHtml')) {
                    $helperBlock->setConfig($helperData)
                        ->setFieldsetId($fieldset->getId())
                        ->prepareElementHtml($field);
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        $this->_prepareFieldDependences($parameter, $field);
        return $field;
    }
    
    public function getTranslationModule()
    {
        return $this->getDataSetDefault('translation_module', 'customgrid');
    }
    
    public function getTranslationHelper()
    {
        return $this->getDataSetDefault(
            'translation_helper',
            $this->helper($this->getTranslationModule())
        );
    }
}
