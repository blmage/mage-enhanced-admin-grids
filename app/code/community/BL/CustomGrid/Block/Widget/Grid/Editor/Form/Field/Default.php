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

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Field_Default extends BL_CustomGrid_Block_Widget_Grid_Editor_Form_Abstract
{
    protected function _prepareForm()
    {
        $form = $this->_initializeForm();
        $valueConfig = $this->getValueConfig();
        
        $fieldValues  = $valueConfig->getFormFieldValues();
        $fieldValues += $this->_getAdditionalFieldValues($fieldValues['type'], $valueConfig);
        
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => $this->getBaseFieldsetLegend($fieldValues['label'], $fieldValues['required']),
                'class'  => 'fieldset-wide blcg-editor-fieldset',
            )
        );
        
        $field = $this->_initFormField($fieldValues, $fieldset);
        $this->_prepareFormField($field, $fieldValues['type'], $valueConfig);
        $form->setFieldNameSuffix($valueConfig->getRequestValuesKey());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
    
    /**
     * Initialize the current form field from the given values and add it to the given fieldset
     * 
     * @param array $fieldValues Field values
     * @param Varien_Data_Form_Element_Fieldset $fieldset Form fieldset
     * @return Varien_Data_Form_Element_Abstract
     */
    protected function _initFormField(array $fieldValues, Varien_Data_Form_Element_Fieldset $fieldset)
    {
        return $fieldset->addField($fieldValues['id'], $fieldValues['type'], $fieldValues);
    }
    
    protected function _initFormValues()
    {
        if ($form = $this->getForm()) {
            $editorContext = $this->getEditorContext();
            $valueConfig   = $this->getValueConfig();
            $fieldValue    = null;
            
            if ($valueConfig->hasData('global/entity_value_callback')) {
                $fieldValue = $valueConfig->runConfigCallback('global/entity_value_callback', array($editorContext));
            } elseif (($valueKey = $valueConfig->getEntityValueKey())
                || ($valueKey = $valueConfig->getFormFieldName())) {
                $fieldValue = $editorContext->getEditedEntity()->getData($valueKey);
            }
            
            $form->setValues(array($valueConfig->getFormFieldId() => $fieldValue));
        }
        return parent::_initFormValues();
    }
    
    /**
     * Return additional values for the choices-based field from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return array
     */
    protected function _getAdditionalChoicesFieldValues(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        $values = array();
        $sourceType = null;
        $sourceOptions = null;
        $hasValidSource = false;
        $handledSourceTypes = array('options', 'values');
        
        foreach ($handledSourceTypes as $sourceType) {
            $key = 'form_field/' . $sourceType;
            $callbackKey = $key . '_callback';
            
            if ($valueConfig->hasData($key)) {
                $sourceOptions = $valueConfig->getData($key);
            } elseif ($valueConfig->hasData($callbackKey)) {
                $sourceOptions = $valueConfig->runConfigCallback($callbackKey);
            }
            if (is_array($sourceOptions)) {
                // Stop as soon as a valid options source is found
                $hasValidSource = true;
                break;
            }
        }
        
        if ($hasValidSource) {
            $values[$sourceType] = $sourceOptions;
        } else {
            Mage::throwException('Could not find any options source to initialize the edited value field');
        }
        
        return $values;
    }
    
    /**
     * Return additional values for the checkbox-based field from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return array
     */
    protected function _getAdditionalCheckboxFieldValues(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        $values = array();
        
        if ($valueConfig->hasData('form_field/checked_callback')) {
            $values['checked'] = $valueConfig->runConfigCallback(
                'form_field/checked_callback',
                array($this->getEditorContext())
            );
        }
        
        return $values;
    }
    
    /**
     * Return additional values for the date-based field from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return array
     */
    protected function _getAdditionalDateFieldValues(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        $values = array('after_element_html' => '');
        
        if ($valueConfig->hasData('form_field/date_image')) {
            $values['image'] = $valueConfig->getData('form_field/date_image');
        } else {
            $values['image'] = $this->getSkinUrl('images/grid-cal.gif');
        }
        if ($valueConfig->hasData('form_field/date_format')) {
            $values['format'] = $valueConfig->getData('form_field/date_format');
        } else {
            $values['format'] = Mage::app()->getLocale()
                ->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        }
        
        return $values;
    }
    
    /**
     * Return additional values for the editor-based field from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return array
     */
    protected function _getAdditionalEditorFieldValues(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        $values = array();
        
        if ($valueConfig->getData('form_field/wysiwyg')) {
            /** @var $helper BL_CustomGrid_Helper_Editor */
            $helper = $this->helper('customgrid/editor');
            
            $values['config'] = $valueConfig->hasData('form_field/wysiwyg_config')
                ? $valueConfig->getData('form_field/wysiwyg_config')
                : $helper->getWysiwygConfig();
        }
        
        return $values;
    }
    
    /**
     * Return additional values for the field of the given type from the given value config
     * 
     * @param string $fieldType Field type
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return array
     */
    protected function _getAdditionalFieldValues($fieldType, BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        $values = array();
        
        if (in_array($fieldType, array('checkboxes', 'multiselect', 'radios', 'select'))) {
            $values = $this->_getAdditionalChoicesFieldValues($valueConfig);
        } elseif ($fieldType == 'checkbox') {
            $values = $this->_getAdditionalCheckboxFieldValues($valueConfig);
        } elseif ($fieldType == 'date') {
            $values = $this->_getAdditionalDateFieldValues($valueConfig);
        } elseif ($fieldType == 'editor') {
            $values = $this->_getAdditionalEditorFieldValues($valueConfig);
        }
        
        return $values;
    }
    
    /**
     * Prepare the form field from the given value config
     * 
     * @param Varien_Data_Form_Element_Abstract $field Form field
     * @param string $fieldType Field type
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return BL_CustomGrid_Block_Widget_Grid_Editor_Form_Field_Default
     */
    protected function _prepareFormField(
        Varien_Data_Form_Element_Abstract $field,
        $fieldType,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        if ($fieldType == 'date') {
            // Stop click events on icons, else row click events will be handled too (eg redirecting to edit pages)
            $field->setAfterElementHtml(
                $field->getAfterElementHtml() 
                . '<script type="text/javascript">'
                . '$("' . $field->getHtmlId() . '_trig").observe("click", function(e) {'
                . 'e.stop();'
                . 'return false;'
                . '});'
                . '</script>'
            );
        }
        return $this;
    }
    
    public function getIsRequiredValueEdit()
    {
        return (bool) $this->getValueConfig()->getData('form_field/required');
    }
}
