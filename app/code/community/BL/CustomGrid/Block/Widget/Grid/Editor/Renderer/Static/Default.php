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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Static_Default
    extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    protected function _renderTextValue($value, $renderOptions, $formOptions, $fieldType)
    {
        return ($value !== '' ? htmlspecialchars($value) : '');
    }
    
    protected function _renderLongTextValue($value, $renderOptions, $formOptions, $fieldType)
    {
        return $this->_defaultValue;
    }
    
    protected function _getChoiceValues($formOptions, $key)
    {
        // Must work the same way as edit block (see BL_CustomGrid_Block_Widget_Grid_Form_Static_Default)
        $cbKey = $key.'_callback';
        $cbParamsKey  = $cbKey.'_params';
        $choiceValues = null;
        
        if (isset($formOptions[$key])) {
            $choiceValues = $formOptions[$key];
        } elseif (isset($formOptions[$cbKey])) {
            $choiceValues = call_user_func_array(
                $formOptions[$cbKey],
                (isset($formOptions[$cbParamsKey])
                    ? (is_array($formOptions[$cbParamsKey]) ? $formOptions[$cbParamsKey] : array())
                    : array($this->getGridBlockType(), $this->getEditedValue(), $this->getEditParams(), $this->getEditedEntity()))
            );
        }
        
        return $choiceValues;
    }
    
    protected function _sortChoiceValues($a, $b)
    {
        if (!isset($a['path_id'])) {
            $a['path_id'] = '';
        }
        if (!isset($b['path_id'])) {
            $b['path_id'] = '';
        }
        return (($res = strcmp($a['path_id'], $b['path_id'])) != 0 ? $res : strcmp($a['label'], $b['label']));
    }
    
    protected function _renderChoiceValue($value, $renderOptions, $formOptions, $fieldType, $choices, $allowMultiple=false)
    {
        if (!is_array($value)) {
            if ($allowMultiple) {
                $value = explode(
                    (isset($renderOptions['separator']) ? $renderOptions['separator'] : ','),
                    $value
                );
            } else {
                $value = array($value);
            }
        }
        
        $selected = array_intersect_key($choices, array_flip($value));
        
        if (isset($renderOptions['without_path'])
            && (bool)$renderOptions['without_path']) {
            $labels = array();
            foreach ($selected as $value) {
                $labels[] = $value['label'];
            }
            $rendered = implode(
                (isset($renderOptions['display_separator']) ? $renderOptions['display_separator'] : ', '),
                $labels
            );
        } else {
            foreach ($selected as $key => $value) {
                $selected[$key] += array(
                    'path_id'     => '',
                    'path_labels' => array(),
                );
            }
            
            uasort($choices, array($this, '_sortChoiceValues'));
            $currentPathId = null;
            $currentPath   = array();
            $currentDepth  = 0;
            $rendered      = array();
            $spacesCount   = (isset($renderOptions['spaces_count']) ? $renderOptions['spaces_count'] : 3);
            $spacesCount   = ($spacesCount > 0 ? $spacesCount : 3);
            
            foreach ($selected as $key => $value) {
                if (($value['value'] === '') // @todo callback to test for other kinds of emptiness ?
                    || (isset($renderOptions['with_empty_value']) 
                        && (bool)$renderOptions['with_empty_value'])) {
                    continue;
                }
                if ($currentPathId != $value['path_id']) {
                    $path  = explode('_', $value['path_id']);
                    $depth = count($path);
                    
                    for ($i=0; $i<$currentDepth; $i++) {
                        if ($path[$i] != $currentPath[$i]) {
                            break;
                        }
                    }
                    for ($j=$i; $j<$depth; $j++) {
                        $rendered[] = str_repeat('&nbsp;', $j*$spacesCount).$value['path_labels'][$j];
                    }
                    
                    $currentPath  = $path;
                    $currentDepth = $depth;
                }
                $rendered[] = str_repeat('&nbsp;', $currentDepth*$spacesCount).$value['label'];
            }
            
            $rendered = implode('<br />', $rendered); // @todo separator, etc... (=> tax rules / ..)
        }
        
        return $rendered;
    }
    
    protected function _renderCheckboxesValue($value, $renderOptions, $formOptions, $fieldType)
    {
        $options = array();
        $values  = array();
        
        if (!is_null($choices = $this->_getChoiceValues($formOptions, 'values'))) {
            $options = (!is_array($choices) ? array($choices) : $choices);
        } elseif (is_array($choices = $this->_getChoiceValues($formOptions, 'options'))) {
            $options = $choices;
        }
        foreach ($options as $k => $v) {
            if (is_string($v)) {
                $values[] = array('label' => $v, 'value' => $k);
            } elseif (isset($v['value'])) {
                if (!isset($v['label'])) {
                    $v['label'] = $v['value'];
                }
                $values[] = array('label' => $v['label'], 'value' => $v['value']);
            }
        }
        
        return $this->_renderChoiceValue($value, $renderOptions, $formOptions, $fieldType, $values);
    }
    
    protected function _renderMultiSelectValue($value, $renderOptions, $formOptions, $fieldType)
    {
        $values = array();
        
        if (is_array($choices = $this->_getChoiceValues($formOptions, 'values'))) {
            $pathsCount = 1;
            foreach ($choices as $v) {
                if (is_array($v['value'])) {
                    foreach ($v['value'] as $subValue) {
                        $values[$subValue['value']] = array(
                            'value'       => $subValue['value'],
                            'label'       => $subValue['label'],
                            'path_id'     => $pathsCount,
                            'path_labels' => array($v['label']),
                        );
                    }
                    $pathsCount++;
                } else {
                    $values[$v['value']] = array(
                        'value' => $v['value'],
                        'label' => $v['label'],
                    );
                }
            }
        }
        
        return $this->_renderChoiceValue($value, $renderOptions, $formOptions, $fieldType, $values, true);
    }
    
    protected function _renderRadiosValue($value, $renderOptions, $formOptions, $fieldType)
    {
        $values = array();
        
        if (is_array($choices = $this->_getChoiceValues($formOptions, 'values'))) {
            foreach ($choices as $option) {
                if (is_array($option)) {
                    $values[$option['value']] = array(
                        'value' => $option['value'],
                        'label' => $option['label']
                    );
                } elseif ($option instanceof Varien_Object) {
                    $values[$option->getValue()] = array(
                        'value' => $option->getValue(),
                        'label' => $option->getLabel(),
                    );
                }
            }
        }
        
        return $this->_renderChoiceValue($value, $renderOptions, $formOptions, $fieldType, $values, true);
    }
    
    protected function _collectSelectValues($options, $pathId, $pathLabels)
    {
        $values     = array();
        $pathsCount = 0;
        
        foreach ($options as $option) {
            if (is_array($option['value'])) {
                array_unshift($pathLabels, $option['label']);
                
                $values = array_merge(
                    $values,
                    $this->_collectSelectValues(
                        $option['value'],
                        $pathId.'_'.(++$pathsCount),
                        $pathLabels
                    )
                );
                
                array_pop($pathLabels);
            } else {
                $values[$option['value']] = array(
                    'value'       => $option['value'],
                    'label'       => $option['label'],
                    'path_id'     => $pathId,
                    'path_labels' => $pathLabels,
                );
            }
            
        }
        
        return $values;
    }
    
    protected function _renderSelectValue($value, $renderOptions, $formOptions, $fieldType)
    {
        $values  = array();
        
        if (!is_array($choices = $this->_getChoiceValues($formOptions, 'values')) || empty($choices)) {
            if (!is_null($choices = $this->_getChoiceValues($formOptions, 'options'))) {
                if (is_array($choices)) {
                    foreach ($choices as $choiceValue => $label) {
                        $values[$choiceValue] = array('value' => $choiceValue, 'label' => $label);
                    }
                } elseif (is_string($choices)) {
                    $values[$choices] = array(
                        'value' => $choices,
                        'label' => $choices,
                    );
                }
            }
        } else {
            $pathsCount = 0;
            foreach ($choices as $key => $option) {
                if (!is_array($option)) {
                    $values[$key] = array(
                        'value' => $key,
                        'label' => $option,
                    );
                } elseif (is_array($option['value'])) {
                    $values = array_merge(
                        $values,
                        $this->_collectSelectValues(
                            $option['value'],
                            ++$pathsCount,
                            array($option['label'])
                        )
                    );
                } else {
                    $values[$option['value']] = array(
                        'value' => $option['value'],
                        'label' => $option['label'],
                    );
                }
            }
        }
        
        return $this->_renderChoiceValue($value, $renderOptions, $formOptions, $fieldType, $values);
    }
    
    protected function _renderDateValue($value, $renderOptions, $formOptions, $fieldType)
    {
        if (empty($value)) {
            return '';
        }
        
        if (!($value instanceof Zend_Date)) {
            if (preg_match('/^[0-9]+$/', $value)) {
                if ((int)$value > 3155760000) {
                    $value = 0;
                }
                $value = new Zend_Date($this->_toTimestamp((int)$value));
            } else {
                $format = (isset($renderOptions['input_format']) 
                    ? $renderOptions['input_format'] 
                    : Varien_Date::DATETIME_INTERNAL_FORMAT);
                $locale = (isset($renderOptions['input_locale']) ? $renderOptions['input_locale'] : null);
                
                try {
                    $value = new Zend_Date($value, $format, $locale);
                } catch (Exception $e) {
                    return $this->_defaultValue;
                }
            }
        }
        
        if (isset($renderOptions['output_format'])) {
            $format = $renderOptions['output_format'];
        } else {
            $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        }
        $locale = (isset($renderOptions['output_locale']) ? $renderOptions['output_locale'] : null);
        
        return $value->toString($format, null, $locale);
    }
    
    protected function _getRenderedValue()
    {
        // @todo whenever needed, make (- a far more powerful -) use of render options :)
        $editedConfig    = $this->getEditedConfig();
        $fieldType       = $editedConfig['type'];
        $renderOptions   = $editedConfig['renderer'];
        $formOptions     = $editedConfig['form'];
        $renderableValue = $this->getRenderableValue();
        $renderedValue   = '';
        
        switch ($fieldType) {
            case 'checkboxes':
                $renderedValue = $this->_renderCheckboxesValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
            case 'date':
                $renderedValue = $this->_renderDateValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
            case 'radios':
                $renderedValue = $this->_renderRadiosValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
            case 'select':
                $renderedValue = $this->_renderSelectValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
            case 'multiselect':
                $renderedValue = $this->_renderMultiSelectValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
            case 'checkbox':
            case 'radio':
                // @todo ? (don't think this is ever used)
                $renderedValue = $this->_defaultValue;
                break;
            case 'editor':
            case 'textarea':
                $renderedValue = $this->_renderLongTextValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
            case 'text':
            default:
                $renderedValue = $this->_renderTextValue($renderableValue, $renderOptions, $formOptions, $fieldType);
                break;
        }
        
        return $renderedValue;
    }
}