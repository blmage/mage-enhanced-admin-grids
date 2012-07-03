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

class BL_CustomGrid_Block_Widget_Grid_Form_Static_Default
    extends BL_CustomGrid_Block_Widget_Grid_Form
{
    public function getIsRequiredValueEdit()
    {
        if (is_array($config = $this->getEditedConfig())) {
            return $config['required'];
        }
        return false;
    }
    
    protected function _getAdditionalFieldValues($id, $type, $name, $editedConfig)
    {
        $values = array();
        
       /*
       Type specific values
       */
        
        if (in_array($type, array('checkboxes', 'multiselect', 'radios', 'select'))) {
            // Choices
            $sourceTypes   = array('options', 'values');
            $sourceOptions = null;
            
            foreach ($sourceTypes as $sourceType) {
                $key   = $sourceType;
                $cbKey = $key.'_callback';
                $cbParamsKey = $cbKey.'_params';
                
                if (isset($editedConfig['form'][$key])) {
                    $sourceOptions = $editedConfig['form'][$key];
                } elseif (isset($editedConfig['form'][$cbKey])) {
                    $sourceOptions = call_user_func_array(
                        $editedConfig['form'][$cbKey],
                        (isset($editedConfig['form'][$cbParamsKey])
                            ? (is_array($editedConfig['form'][$cbParamsKey]) ? $editedConfig['form'][$cbParamsKey] : array())
                            : array($this->getGridBlockType(), $this->getEditedValue(), $this->getEditParams(), $this->getEditedEntity()))
                    );
                }
                if (is_array($sourceOptions)) {
                    break;
                }
            }
            
            if (is_array($sourceOptions)) {
                $values[$sourceType] = $sourceOptions;
            } else {
                Mage::throwException($this->__('Can\'t find any option to use for edited value'));
            }
        } elseif ($type == 'date') {
            // Date
            $dateFormatIso = Mage::app()->getLocale()->getDateFormat(
                Mage_Core_Model_Locale::FORMAT_TYPE_SHORT
            );
            $values += array(
                'image'  => (isset($editedConfig['form']['date_image'])
                    ? $editedConfig['form']['date_image']
                    : $this->getSkinUrl('images/grid-cal.gif')),
                'format' => (isset($editedConfig['form']['date_format'])
                    ? $editedConfig['form']['date_format']
                    : $dateFormatIso),
                'after_element_html' => '',
            );
        } elseif ($type == 'editor') {
            // WYSIWYG editor
            if (isset($editedConfig['form']['wysiwyg']) && $editedConfig['form']['wysiwyg']) {
                if (isset($editedConfig['form']['wysiwyg_config'])) {
                    $wysiwygConfig = $editedConfig['form']['wysiwyg_config'];
                } else {
                    $wysiwygConfig = Mage::helper('customgrid/editor')->getWysiwygConfig();
                }
                $values['config'] = $wysiwygConfig;
            } else {
                // No config if it appears to be not needed
                unset($values['config']);
            }
        }
        
        /**
        * Additional values (won't override type specific ones)
        */
        
        foreach ($editedConfig['form'] as $key => $value) {
            if (!in_array($key, array('label', 'title', 'options', 'values'))) {
                if (!isset($values[$key])) {
                    $values[$key] = $value;
                }
            }
        }
        
        return $values;
    }
    
    protected function _prepareAddedField($id, $type, $name, $editedConfig, $field)
    {
        if ($type == 'date') {
            // Stop click events on icons, else row click events will be handled too (eg redirecting to edit pages)
            $field->setAfterElementHtml($field->getAfterElementHtml().'
            <script type="text/javascript">
            //<![CDATA[
            $("'.$field->getHtmlId().'_trig").observe("click", function(e){
                e.stop();
                return false;
            });
            //]]>
            </script>
            ');
        }
    }
    
    protected function _prepareForm()
    {
        $form = $this->_createForm();
        $editedConfig = $this->getEditedConfig();
        
        // Prepare field values
        $fieldId   = $editedConfig['form']['id'];
        $fieldType = $editedConfig['type'];
        $fieldName = $editedConfig['form']['name'];
        
        $fieldValues = array(
            'name'     => $fieldName,
            'label'    => $editedConfig['form']['label'],
            'title'    => $editedConfig['form']['title'],
            'required' => $editedConfig['required'],
        );
        $fieldValues += $this->_getAdditionalFieldValues($fieldId, $fieldType, $fieldName, $editedConfig);
        
        // Prepare fieldset and field
        $fieldset = $form->addFieldset('base_fieldset', array(
            'legend' => $this->__('%s : %s', $this->getEditedEntityName(), $editedConfig['form']['label'])
                . ($fieldValues['required'] ? ' (<span class="blcg-editor-required-marker">'.$this->__('Required').'</span>)' : ''),
            'class'  => 'fieldset-wide blcg-editor-fieldset',
        ));
        $field = $fieldset->addField($fieldId, $fieldType, $fieldValues);
        $this->_prepareAddedField($fieldId, $fieldType, $fieldName, $editedConfig, $field);
        
        $form->setFieldNameSuffix($editedConfig['values_key']);
        $this->setForm($form);
        return parent::_prepareForm();
    }
    
    protected function _initFormValues()
    {
        if ($form = $this->getForm()) {
            $editedConfig = $this->getEditedConfig();
            $editedEntity = $this->getEditedEntity();
            
            if (isset($editedConfig['entity_value_callback'])) {
                $value = call_user_func_array(
                    $editedConfig['entity_value_callback'],
                    (isset($editedConfig['entity_value_callback_params'])
                        ? (is_array($editedConfig['entity_value_callback_params']) ? $editedConfig['entity_value_callback_params'] : array())
                        : array($this->getGridBlockType(), $this->getEditedValue(), $this->getEditParams(), $this->getEditedEntity()))
                );
            } else {
                $value = $editedEntity->getData($editedConfig['field_name']);
            }
            
            $form->setValues(array($editedConfig['form']['id'] => $value));
        }
        return parent::_initFormValues();
    }
}