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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Static_Default
    extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    protected function _renderTextValue($renderableValue, array $renderOptions, array $formOptions)
    {
        return ($renderableValue !== '' ? $this->htmlEscape($renderableValue) : '');
    }
    
    protected function _renderLongTextValue($renderableValue, array $renderOptions, array $formOptions)
    {
        return $this->getDefaultValue();
    }
    
    protected function _getChoiceValues(array $formOptions, $key)
    {
        // Must work like the edit block does (see BL_CustomGrid_Block_Widget_Grid_Editor_Form_Static_Default)
        $callbackKey = $key . '_callback';
        $callbackParamsKey  = $callbackKey . '_params';
        $choiceValues = null;
        
        if (isset($formOptions[$key])) {
            $choiceValues = $formOptions[$key];
        } elseif (isset($formOptions[$callbackKey])) {
            $editedValue  = $this->getEditedValue();
            $editParams   = $this->getEditParams();
            $editedEntity = $this->getEditedEntity();
            
            $choiceValues = call_user_func_array(
                $formOptions[$callbackKey],
                isset($formOptions[$callbackParamsKey])
                    ? (is_array($formOptions[$callbackParamsKey]) ? $formOptions[$callbackParamsKey] : array())
                    : array($this->getGridBlockType(), $editedValue, $editParams, $editedEntity)
            );
        }
        
        return $choiceValues;
    }
    
    protected function _sortChoiceValues(array $a, array $b)
    {
        if (!isset($a['path_id'])) {
            $a['path_id'] = '';
        }
        if (!isset($b['path_id'])) {
            $b['path_id'] = '';
        }
        return (($result = strcmp($a['path_id'], $b['path_id'])) !== 0 ? $result : strcmp($a['label'], $b['label']));
    }
    
    protected function _renderChoiceValue($renderableValue, array $renderOptions, array $choices, $allowMultiple=false)
    {
        if (!is_array($renderableValue)) {
            if ($allowMultiple) {
                $separator = (isset($renderOptions['separator']) ? $renderOptions['separator'] : ',');
                $renderableValue = explode($separator, $renderableValue);
            } else {
                $renderableValue = array($renderableValue);
            }
        }
        
        $selectedValues = array_intersect_key($choices, array_flip($renderableValue));
        $renderedValue = '';
        
        if (isset($renderOptions['without_path']) && $renderOptions['without_path']) {
            $labels = array();
            
            foreach ($selectedValues as $value) {
                $labels[] = $value['label'];
            }
            
            $separator = (isset($renderOptions['display_separator']) ? $renderOptions['display_separator'] : ', ');
            $renderedValue = implode($separator, $labels);
        } else {
            foreach ($selectedValues as $key => $value) {
                $selectedValues[$key] += array(
                    'path_id' => '',
                    'path_labels' => array(),
                );
            }
            
            uasort($choices, array($this, '_sortChoiceValues'));
            $currentPathId = null;
            $currentPath   = array();
            $currentDepth  = 0;
            $renderedValue = array();
            $spacesCount   = (isset($renderOptions['spaces_count']) ? $renderOptions['spaces_count'] : 3);
            $spacesCount   = ($spacesCount > 0 ? $spacesCount : 3);
            
            foreach ($selectedValues as $key => $value) {
                if (($value['value'] === '') // @todo callback to test for other kinds of emptiness ?
                    || (isset($renderOptions['with_empty_value']) && $renderOptions['with_empty_value'])) {
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
                        $renderedValue[] = str_repeat('&nbsp;', $j*$spacesCount) . $value['path_labels'][$j];
                    }
                    
                    $currentPath  = $path;
                    $currentDepth = $depth;
                }
                
                $renderedValue[] = str_repeat('&nbsp;', $currentDepth*$spacesCount) . $value['label'];
            }
            
            $renderedValue = implode('<br />', $renderedValue); // @todo separator, etc. (for, eg, tax rules)
        }
        
        return $renderedValue;
    }
    
    protected function _renderCheckboxesValue($renderableValue, array $renderOptions, array $formOptions)
    {
        if (!is_null($values = $this->_getChoiceValues($formOptions, 'values'))) {
            $values = (!is_array($values) ? array($values) : $values);
        } elseif (!is_array($values = $this->_getChoiceValues($formOptions, 'options'))) {
            $values = array();
        }
        
        $choices = array();
        
        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $choices[] = array(
                    'label' => $value,
                    'value' => $key,
                );
            } elseif (is_array($value) && isset($value['value'])) {
                if (!isset($value['label'])) {
                    $value['label'] = $value['value'];
                }
                $choices[] = array(
                    'label' => $value['label'],
                    'value' => $value['value'],
                );
            }
        }
        
        return $this->_renderChoiceValue($renderableValue, $renderOptions, $choices);
    }
    
    protected function _renderMultiSelectValue($renderableValue, $renderOptions, $formOptions)
    {
        $choices = array();
        
        if (is_array($values = $this->_getChoiceValues($formOptions, 'values'))) {
            $pathsCount = 1;
            
            foreach ($values as $value) {
                if (isset($value['value'])) {
                    if (is_array($value['value'])) {
                        foreach ($value['value'] as $subValue) {
                            if (isset($subValue['value'])) {
                                 if (!isset($value['label'])) {
                                    $value['label'] = $subValue['value'];
                                }
                                if (!isset($subValue['label'])) {
                                    $subValue['label'] = $subValue['value'];
                                }
                                
                                $choices[$subValue['value']] = array(
                                    'value'       => $subValue['value'],
                                    'label'       => $subValue['label'],
                                    'path_id'     => $pathsCount,
                                    'path_labels' => array($value['label']),
                                );
                            }
                        }
                        
                        $pathsCount++;
                    } else {
                         if (!isset($value['label'])) {
                            $value['label'] = $value['value'];
                        }
                        
                        $choices[$value['value']] = array(
                            'value' => $value['value'],
                            'label' => $value['label'],
                        );
                    }
                }
            }
        }
        
        return $this->_renderChoiceValue($renderableValue, $renderOptions, $choices, true);
    }
    
    protected function _renderRadiosValue($renderableValue, array $renderOptions, array $formOptions)
    {
        $choices = array();
        
        if (is_array($values = $this->_getChoiceValues($formOptions, 'values'))) {
            foreach ($values as $value) {
                if (is_array($value)) {
                    if (isset($value['value'])) {
                        if (!isset($value['label'])) {
                            $value['label'] = $value['value'];
                        }
                        
                        $choices[$value['value']] = array(
                            'value' => $value['value'],
                            'label' => $value['label']
                        );
                    }
                } elseif ($value instanceof Varien_Object) {
                    $choices[$value->getValue()] = array(
                        'value' => $value->getValue(),
                        'label' => $value->getLabel(),
                    );
                }
            }
        }
        
        return $this->_renderChoiceValue($renderableValue, $renderOptions, $choices, true);
    }
    
    protected function _collectSelectValues(array $options, $pathId, array $pathLabels)
    {
        $values = array();
        $pathsCount = 0;
        
        foreach ($options as $option) {
            if (isset($option['value'])) {
                if (is_array($option['value'])) {
                    if (isset($option['label'])) {
                        array_unshift($pathLabels, $option['label']);
                    }
                    
                    $values = array_merge(
                        $values,
                        $this->_collectSelectValues(
                            $option['value'],
                            $pathId . '_' . (++$pathsCount),
                            $pathLabels
                        ));
                    
                    array_pop($pathLabels);
                } else {
                    if (!isset($option['label'])) {
                        $option['label'] = $option['value'];
                    }
                    
                    $values[$option['value']] = array(
                        'value'       => $option['value'],
                        'label'       => $option['label'],
                        'path_id'     => $pathId,
                        'path_labels' => $pathLabels,
                    );
                }
            }
        }
        
        return $values;
    }
    
    protected function _renderSelectValue($renderableValue, array $renderOptions, array $formOptions)
    {
        $choices = array();
        
        if (!is_array($values = $this->_getChoiceValues($formOptions, 'values')) || empty($values)) {
            if (!is_null($values = $this->_getChoiceValues($formOptions, 'options'))) {
                if (is_array($values)) {
                    foreach ($values as $value => $label) {
                        $choices[$value] = array(
                            'value' => $value,
                            'label' => $label,
                        );
                    }
                } elseif (is_string($values)) {
                    $choices[$values] = array(
                        'value' => $values,
                        'label' => $values,
                    );
                }
            }
        } else {
            $pathsCount = 0;
            
            foreach ($values as $key => $value) {
                if (!is_array($value)) {
                    $choices[$key] = array(
                        'value' => $key,
                        'label' => $value,
                    );
                } elseif (isset($value['value'])) {
                    if (is_array($value['value'])) {
                        $choices = array_merge(
                            $choices,
                            $this->_collectSelectValues(
                                $value['value'],
                                ++$pathsCount,
                                array($value['label'])
                            )
                        );
                    } else {
                        if (!isset($value['label'])) {
                            $value['label'] = $value['value'];
                        }
                        
                        $choices[$value['value']] = array(
                            'value' => $value['value'],
                            'label' => $value['label'],
                        );
                    }
                }
            }
        }
        
        return $this->_renderChoiceValue($renderableValue, $renderOptions, $choices);
    }
    
    protected function _renderDateValue($renderableValue, array $renderOptions, array $formOptions)
    {
        if (empty($renderableValue)) {
            return '';
        }
        
        if (!$renderableValue instanceof Zend_Date) {
            if (ctype_digit($renderableValue)) {
                $renderableValue = (int) $renderableValue;
                
                if ($renderableValue > 3155760000) {
                    $renderableValue = 0;
                }
                
                $renderableValue = new Zend_Date($renderableValue);
            } else {
                $locale = isset($renderOptions['input_locale'])
                    ? $renderOptions['input_locale']
                    : null;
                
                $format = isset($renderOptions['input_format'])
                    ? $renderOptions['input_format']
                    : Varien_Date::DATETIME_INTERNAL_FORMAT;
                
                try {
                    $renderableValue = new Zend_Date($renderableValue, $format, $locale);
                } catch (Exception $e) {
                    return $this->getDefaultValue();
                }
            }
        }
        
        $locale = isset($renderOptions['output_locale'])
            ? $renderOptions['output_locale']
            : null;
        
        $format = isset($renderOptions['output_format'])
            ? $renderOptions['output_format']
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        
        return $renderableValue->toString($format, null, $locale);
    }
    
    protected function _getRenderedValue()
    {
        $editConfig    = $this->getEditConfig();
        $fieldType     = $editConfig->getData('type');
        $renderOptions = $editConfig->getData('renderer');
        $formOptions   = $editConfig->getData('form');
        $renderedValue = '';
        $renderableValue = $this->getRenderableValue();
        
        switch ($fieldType) {
            case 'checkboxes':
                $renderedValue = $this->_renderCheckboxesValue($renderableValue, $renderOptions, $formOptions);
                break;
            case 'date':
                $renderedValue = $this->_renderDateValue($renderableValue, $renderOptions, $formOptions);
                break;
            case 'radios':
                $renderedValue = $this->_renderRadiosValue($renderableValue, $renderOptions, $formOptions);
                break;
            case 'select':
                $renderedValue = $this->_renderSelectValue($renderableValue, $renderOptions, $formOptions);
                break;
            case 'multiselect':
                $renderedValue = $this->_renderMultiSelectValue($renderableValue, $renderOptions, $formOptions);
                break;
            case 'checkbox':
            case 'radio':
                // @todo ? (don't think those field types are ever used - in the core, at least)
                $renderedValue = $this->getDefaultValue();
                break;
            case 'editor':
            case 'textarea':
                $renderedValue = $this->_renderLongTextValue($renderableValue, $renderOptions, $formOptions);
                break;
            case 'text':
            default:
                $renderedValue = $this->_renderTextValue($renderableValue, $renderOptions, $formOptions);
                break;
        }
        
        return $renderedValue;
    }
}