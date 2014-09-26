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

abstract class BL_CustomGrid_Block_Config_Form_Abstract
    extends Mage_Adminhtml_Block_Widget_Form
{
    abstract public function getFormId();
    abstract protected function _getFormCode();
    abstract protected function _getFormAction();
    abstract protected function _getFormFields();
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        Varien_Data_Form::setFieldsetRenderer(
            $this->getLayout()->createBlock('customgrid/config_form_renderer_fieldset')
        );
        
        return $this;
    }
    
    protected function _prepareForm()
    {
        $helper = $this->getTranslationHelper();
        $form = new Varien_Data_Form();
        $formCode = $this->_getFormCode();
        
        $form->setUseContainer(true);
        $form->setId($this->getFormId());
        $form->setMethod('post');
        $form->setAction($this->_getFormAction());
        $this->setForm($form);
        
        $dependenceBlock = $this->getLayout()->createBlock('customgrid/widget_form_element_dependence');
        $this->setChild('form_after', $dependenceBlock);
        
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
    
    protected function _addField(Varien_Data_Form_Element_Fieldset $fieldset, Varien_Object $parameter)
    {
        $form = $this->getForm();
        $helper = $this->getTranslationHelper();
        
        // Base field data
        $fieldName = $parameter->getKey();
        
        $fieldData = array(
            'name'     => $form->addSuffixToName($fieldName, 'parameters'),
            'label'    => $parameter->getLabel(),
            'required' => $parameter->getRequired(),
            'class'    => 'renderer-option',
            'note'     => $parameter->getDescription(),
        );
        
        $fieldData['label'] = $helper->__($fieldData['label']);
        $fieldData['note']  = $helper->__($fieldData['note']);
        
        // Initial value
        if (is_array($values = $this->getConfigValues()) && isset($values[$fieldName])) {
            $fieldData['value'] = $values[$fieldName];
        } else {
            $fieldData['value'] = $parameter->getValue();
            
            if (($fieldName == 'unique_id') && ($fieldData['value'] == '')) {
                $fieldData['value'] = md5(microtime(true));
            }
        }
        
        // Options source
        if ($sourceModel = $parameter->getSourceModel()) {
            try {
                if (is_array($sourceModel)) {
                    $fieldData['values'] = call_user_func(array(
                        Mage::getModel($sourceModel['model']),
                        $sourceModel['method'],
                    ));
                } else {
                    $fieldData['values'] = Mage::getModel($sourceModel)->toOptionArray();
                }
            } catch (Exception $e) {
                Mage::logException($e);
                $fieldData['values'] = array();
            }
        } elseif (is_array($values = $parameter->getValues())) {
            $fieldData['values'] = array();
            
            foreach ($values as $value) {
                $fieldData['values'][] = array(
                    'label' => $helper->__($value['label']),
                    'value' => $value['value']
                );
            }
        }
        
        // Field rendering
        $fieldType = $parameter->getType();
        $fieldRenderer = null;
        
        if (!$parameter->getVisible()) {
            $fieldType = 'hidden';
        } elseif (strpos($fieldType, '/') !== false) {
            $fieldType = 'text';
            $fieldRenderer = $this->getLayout()->createBlock($fieldType);
        }
        
        // Prepare form field
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
        
        // Prepare dependencies
        if ($dependenceBlock = $this->getChild('form_after')) {
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
        }
        
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