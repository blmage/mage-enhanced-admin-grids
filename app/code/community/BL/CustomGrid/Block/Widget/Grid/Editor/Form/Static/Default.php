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

class BL_CustomGrid_Block_Widget_Grid_Editor_Form_Static_Default extends BL_CustomGrid_Block_Widget_Grid_Editor_Form_Abstract
{
    protected function _prepareForm()
    {
        $form = $this->_initializeForm();
        $editConfig = $this->getEditConfig();
        
        // Prepare field values
        $fieldId   = $editConfig->getData('form/id');
        $fieldType = $editConfig->getData('type');
        $fieldName = $editConfig->getData('form/name');
        
        $fieldValues = array(
            'name'     => $fieldName,
            'label'    => $editConfig->getData('form/label'),
            'title'    => $editConfig->getData('form/title'),
            'required' => $editConfig->isRequired(),
        );
        
        $fieldValues += $this->_getAdditionalFieldValues($fieldId, $fieldType, $fieldName, $editConfig);
        $fieldsetLegend = $this->__('%s : %s', $this->getEditedEntityName(), $editConfig->getData('form/label'));
        
        if ($fieldValues['required']) {
            $fieldsetLegend .= ' (<span class="blcg-editor-required-marker">' . $this->__('Required') . '</span>)';
        }
        
        // Prepare fieldset and field
        $fieldset = $form->addFieldset(
            'base_fieldset',
            array(
                'legend' => $fieldsetLegend,
                'class'  => 'fieldset-wide blcg-editor-fieldset',
            )
        );
        
        $field = $fieldset->addField($fieldId, $fieldType, $fieldValues);
        $this->_prepareFormField($fieldId, $fieldType, $fieldName, $editConfig, $field);
        $form->setFieldNameSuffix($editConfig->getData('values_key'));
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
    
    protected function _initFormValues()
    {
        if ($form = $this->getForm()) {
            /** @var $editConfig BL_CustomGrid_Model_Grid_Edit_Config */
            $editConfig   = $this->getEditConfig();
            $editedEntity = $this->getEditedEntity();
            
            if ($editConfig->hasData('entity_value_callback')) {
                $editedValue = $this->getEditedValue();
                $editParams  = $this->getEditParams();
                
                if ($editConfig->hasData('entity_value_callback_params')) {
                    if (!is_array($callbackParams = $editConfig->getData('entity_value_callback_params'))) {
                        $callbackParams = array();
                    }
                } else {
                    $callbackParams = array($this->getGridBlockType(), $editedValue, $editParams, $editedEntity);
                }
                
                $value = call_user_func_array($editConfig->getData('entity_value_callback'), $callbackParams);
            } else {
                $value = $editedEntity->getData($editConfig->getData('field_name'));
            }
            
            $form->setValues(array($editConfig->getData('form/id') => $value));
        }
        return parent::_initFormValues();
    }
    
    /**
     * Return additional field values for the choices-based field from the given edit config
     * 
     * @param BL_CustomGrid_Model_Grid_Edit_Config $editConfig Edit config
     * @return array
     */
    protected function _getAdditionalChoicesFieldValues(BL_CustomGrid_Model_Grid_Edit_Config $editConfig)
    {
        $values = array();
        $sourceType  = null;
        $sourceTypes = array('options', 'values');
        $sourceOptions = null;
        
        foreach ($sourceTypes as $sourceType) {
            $key = 'form/' . $sourceType;
            $callbackKey = $key . '_callback';
            $callbackParamsKey = $callbackKey . '_params';
            
            if ($editConfig->hasData($key)) {
                $sourceOptions = $editConfig->getData($key);
            } elseif ($editConfig->hasData($callbackKey)) {
                $editedValue  = $this->getEditedValue();
                $editParams   = $this->getEditParams();
                $editedEntity = $this->getEditedEntity();
                
                if ($editConfig->hasData($callbackParamsKey)) {
                    if (!is_array($callbackParams = $editConfig->getData($callbackParamsKey))) {
                        $callbackParams = array();
                    }
                } else {
                    $callbackParams = array($this->getGridBlockType(), $editedValue, $editParams, $editedEntity);
                }
                
                $sourceOptions = call_user_func_array($editConfig->getData($callbackKey), $callbackParams);
            }
            if (is_array($sourceOptions)) {
                // Stop as soon as a valid options source is found
                break;
            }
        }
        
        if (is_array($sourceOptions)) {
            $values[$sourceType] = $sourceOptions;
        } else {
            Mage::throwException($this->__('Cannot find any option to use for edited value'));
        }
        
        return $values;
    }
    
    /**
     * Return additional field values for the date-based field from the given edit config
     * 
     * @param BL_CustomGrid_Model_Grid_Edit_Config $editConfig Edit config
     * @return array
     */
    protected function _getAdditionalDateFieldValues(BL_CustomGrid_Model_Grid_Edit_Config $editConfig)
    {
        $values = array('after_element_html' => '');
        
        if ($editConfig->hasData('form/date_image')) {
            $values['image'] = $editConfig->getData('form/date_image');
        } else {
            $values['image'] = $this->getSkinUrl('images/grid-cal.gif');
        }
        if ($editConfig->hasData('form/date_format')) {
            $values['format'] = $editConfig->getData('form/date_format');
        } else {
            $values['format'] = Mage::app()->getLocale()
                ->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        }
        
        return $values;
    }
    
    /**
     * Return additional field values for the editor-based field from the given edit config
     * 
     * @param BL_CustomGrid_Model_Grid_Edit_Config $editConfig Edit config
     * @return array
     */
    protected function _getAdditionalEditorFieldValues(BL_CustomGrid_Model_Grid_Edit_Config $editConfig)
    {
        $values = array();
        
        if ($editConfig->getData('form/wysiwyg')) {
            /** @var $helper BL_CustomGrid_Helper_Editor */
            $helper = $this->helper('customgrid/editor');
            
            $values['config'] = $editConfig->hasData('form/wysiwyg_config')
                ? $editConfig->getData('form/wysiwyg_config')
                : $helper->getWysiwygConfig();
        } elseif ($editConfig->hasData('form/config')) {
            $editConfig->unsetData('form/config');
        }
        
        return $values;
    }
    
    /**
     * Return additional field values for the given field and edit config
     * 
     * @param string $fieldId Field ID
     * @param string $fieldType Field type
     * @param string $fieldName Field name
     * @param BL_CustomGrid_Model_Grid_Edit_Config $editConfig Edit config
     * @return array
     */
    protected function _getAdditionalFieldValues(
        $fieldId,
        $fieldType,
        $fieldName,
        BL_CustomGrid_Model_Grid_Edit_Config $editConfig
    ) {
        $values = array();
        
        if (in_array($fieldType, array('checkboxes', 'multiselect', 'radios', 'select'))) {
            $values = $this->_getAdditionalChoicesFieldValues($editConfig);
        } elseif ($fieldType == 'date') {
            $values = $this->_getAdditionalDateFieldValues($editConfig);
        } elseif ($fieldType == 'editor') {
            $values = $this->_getAdditionalEditorFieldValues($editConfig);
        }
        
        // Additional values (that won't override type-specific ones)
        if (is_array($formData = $editConfig->getData('form'))) {
            foreach ($formData as $key => $value) {
                if (!in_array($key, array('label', 'title', 'options', 'values'))
                    && !isset($values[$key])) {
                    $values[$key] = $value;
                }
            }
        }
        
        return $values;
    }
    
    /**
     * Prepare the given form field
     * 
     * @param string $fieldId Field ID
     * @param string $fieldType Field type
     * @param string $fieldName Field name
     * @param BL_CustomGrid_Model_Grid_Edit_Config $editConfig Edit config
     * @param Varien_Data_Form_Element_Abstract $field Form field
     * @return BL_CustomGrid_Block_Widget_Grid_Editor_Form_Static_Default
     */
    protected function _prepareFormField(
        $fieldId,
        $fieldType,
        $fieldName,
        BL_CustomGrid_Model_Grid_Edit_Config $editConfig,
        Varien_Data_Form_Element_Abstract $field
    ) {
        if ($fieldType == 'date') {
            // Stop click events on icons, else row click events will be handled too (eg redirecting to edit pages)
            $field->setAfterElementHtml(
                $field->getAfterElementHtml() 
                . '<script type="text/javascript">'
                . '//<![CDATA['
                . '$("' . $field->getHtmlId() . '_trig").observe("click", function(e) {'
                . 'e.stop();'
                . 'return false;'
                . '});'
                . '//]]>'
                . '</script>'
            );
        }
        return $this;
    }
    
    public function getIsRequiredValueEdit()
    {
        return (is_object($config = $this->getEditConfig()) ? $config->isRequired() : false);
    }
}
