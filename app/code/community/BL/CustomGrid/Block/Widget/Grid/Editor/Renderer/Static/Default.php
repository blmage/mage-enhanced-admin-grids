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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Static_Default extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    /**
     * Self render callbacks based on field type
     * 
     * @var string[]
     */
    static protected $_renderMethods = array(
        'checkboxes'  => '_renderCheckboxesValue',
        'date'        => '_renderDateValue',
        'radios'      => '_renderRadioButtonsValue',
        'select'      => '_renderSelectValue',
        'multiselect' => '_renderMultiSelectValue',
        'checkbox'    => '_renderDefaultValue',
        'radio'       => '_renderDefaultValue',
        'editor'      => '_renderLongTextValue',
        'textarea'    => '_renderLongTextValue',
        'text'        => '_renderTextValue',
     );
    
    /**
     * Render the given text value
     * 
     * @param string $renderableValue Renderable text value
     * @return string
     */
    protected function _renderTextValue($renderableValue)
    {
        return ($renderableValue !== '' ? $this->htmlEscape($renderableValue) : '');
    }
    
    /**
     * Render the given long text value
     * 
     * @param string $renderableValue Renderable long text value
     * @return string
     */
    protected function _renderLongTextValue($renderableValue)
    {
        /** @var $helper BL_CustomGrid_Helper_String */
        $helper = $this->helper('core/string');
        return (($helper->strlen($renderableValue) < 255) ? $renderableValue : $this->getDefaultValue());
    }
    
    /**
     * Render the given choices-based value
     * 
     * @param array $formOptions Form options
     * @param string $key Base key where to find values in the form options
     * @return string
     */
    protected function _getChoiceValues(array $formOptions, $key)
    {
        // Must work like the edit block does (see BL_CustomGrid_Block_Widget_Grid_Editor_Form_Static_Default)
        $callbackKey = $key . '_callback';
        $callbackParamsKey = $callbackKey . '_params';
        $choiceValues = null;
        
        if (isset($formOptions[$key])) {
            $choiceValues = $formOptions[$key];
        } elseif (isset($formOptions[$callbackKey])) {
            $editedValue  = $this->getEditedValue();
            $editParams   = $this->getEditParams();
            $editedEntity = $this->getEditedEntity();
            
            if (isset($formOptions[$callbackParamsKey])) {
                if (is_array($formOptions[$callbackParamsKey])) {
                    $callbackParams = $formOptions[$callbackParamsKey];
                } else {
                    $callbackParams = array();
                }
            } else {
                $callbackParams = array($this->getGridBlockType(), $editedValue, $editParams, $editedEntity);
            }
            
            $choiceValues = call_user_func_array($formOptions[$callbackKey], $callbackParams);
        }
        
        return $choiceValues;
    }
    
    /**
     * Return a convenient renderable value for the given choices-based value
     * 
     * @param string $renderableValue Renderable choices-based value
     * @param array $renderOptions Render options
     * @param bool $allowMultiple Whether the corresponding field allows multiple values
     * @return array
     */
    protected function _getRenderableChoiceValue($renderableValue, array $renderOptions, $allowMultiple)
    {
        if (!is_array($renderableValue)) {
            if ($allowMultiple) {
                $separator = (isset($renderOptions['separator']) ? $renderOptions['separator'] : ',');
                $renderableValue = explode($separator, $renderableValue);
            } else {
                $renderableValue = array($renderableValue);
            }
        }
        return $renderableValue;
    }
    
     /**
      * Render the given selected values from a choices-based field, without rendering their paths
      * 
      * @param array $selectedValues Selected values
      * @param array $renderOptions Render options
      * @return string
      */
    protected function _renderChoiceValueWithoutPath(array $selectedValues, array $renderOptions)
    {
        $labels = array();
        
        foreach ($selectedValues as $value) {
            $labels[] = $value['label'];
        }
        
        $separator = (isset($renderOptions['display_separator']) ? $renderOptions['display_separator'] : ', ');
        return implode($separator, $labels);
    }
    
    /**
     * Sort callback for choices-based values
     * 
     * @param array $valueA One value
     * @param array $valueB Another value
     * @return int
     */
    protected function _sortChoiceValues(array $valueA, array $valueB)
    {
        if (!isset($valueA['path_id'])) {
            $valueA['path_id'] = '';
        }
        if (!isset($valueB['path_id'])) {
            $valueB['path_id'] = '';
        }
        return (($result = strcmp($valueA['path_id'], $valueB['path_id'])) !== 0)
            ? $result
            : strcmp($valueA['label'], $valueB['label']);
    }
    
    /**
      * Render the given selected values from a choices-based field, including their paths
      * 
      * @param array $choices Available choices
      * @param array $selectedValues Selected values
      * @param array $renderOptions Render options
      * @return string
      */
    protected function _renderChoiceValueWithPath(array $choices, array $selectedValues, array $renderOptions)
    {
        uasort($choices, array($this, '_sortChoiceValues'));
        $currentPath   = array();
        $currentDepth  = 0;
        $renderedValue = array();
        
        if (isset($renderOptions['spaces_count']) && ($renderOptions['spaces_count'] > 0)) {
            $spacesCount = $renderOptions['spaces_count'];
        } else {
            $spacesCount = 3;
        }
        
        foreach ($selectedValues as $value) {
            if (($value['value'] === '')
                || (isset($renderOptions['with_empty_value']) && $renderOptions['with_empty_value'])) {
                continue;
            }
            
            if ($value['path_id'] !== '') {
                $path  = explode('_', $value['path_id']);
                $depth = count($path);
            } else {
                $path  = array();
                $depth = 0;
            }
            
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
            $renderedValue[] = str_repeat('&nbsp;', $currentDepth*$spacesCount) . $value['label'];
        }
        
        return implode('<br />', $renderedValue);
    }
    
    /**
     * Render the given choices-based value
     * 
     * @param string $renderableValue Renderable value
     * @param array $renderOptions Render options
     * @param array $choices Available choices
     * @param bool $allowMultiple Whether the corresponding field allows multiple values
     * @return string
     */
    protected function _renderChoiceValue(
        $renderableValue,
        array $renderOptions,
        array $choices,
        $allowMultiple = false
    ) {
        $renderableValue = $this->_getRenderableChoiceValue($renderableValue, $renderOptions, $allowMultiple);
        $selectedValues  = array_intersect_key($choices, array_flip($renderableValue));
        
        if (isset($renderOptions['without_path']) && $renderOptions['without_path']) {
            $renderedValue = $this->_renderChoiceValueWithoutPath($selectedValues, $renderOptions);
        } else {
            foreach (array_keys($selectedValues) as $key) {
                $selectedValues[$key] += array(
                    'path_id' => '',
                    'path_labels' => array(),
                );
            }
            
            $renderedValue = $this->_renderChoiceValueWithPath($choices, $selectedValues, $renderOptions);
        }
        
        return $renderedValue;
    }
    
    /**
     * Render the given checkboxes-based value
     * 
     * @param string $renderableValue Renderable value
     * @param array $renderOptions Render options
     * @param array $formOptions Form options
     * @return string
     */
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
    
    /**
     * Render the given multiple select-based value
     * 
     * @param string $renderableValue Renderable value
     * @param array $renderOptions Render options
     * @param array $formOptions Form options
     * @return string
     */
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
    
    /**
     * Render the given radio buttons-based value
     * 
     * @param string $renderableValue Renderable value
     * @param array $renderOptions Render options
     * @param array $formOptions Form options
     * @return string
     */
    protected function _renderRadioButtonsValue($renderableValue, array $renderOptions, array $formOptions)
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
    
    /**
     * Recursively collect values, pre-compute their paths, and return a flat array from the given select-based options
     * 
     * @param array $options Available options
     * @param string $pathId Current path ID
     * @param array $pathLabels Current path labels
     * @return array
     */
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
                        )
                    );
                    
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
    
    /**
     * Render the given select-based value
     * 
     * @param string $renderableValue Renderable value
     * @param array $renderOptions Render options
     * @param array $formOptions Form options
     * @return string
     */
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
    
    /**
     * Return the input locale from the given render options
     * 
     * @param array $renderOptions Render Options
     * @return string|Zend_Locale|null
     */
    protected function _getDateInputLocale(array $renderOptions)
    {
        return isset($renderOptions['input_locale'])
            ? $renderOptions['input_locale']
            : null;
    }
    
    /**
     * Return the input date format from the given render options
     * 
     * @param array $renderOptions Render Options
     * @return string
     */
    protected function _getDateInputFormat(array $renderOptions)
    {
        return isset($renderOptions['input_format'])
            ? $renderOptions['input_format']
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    
    /**
     * Return a Zend_Date object from the given renderable date value
     * 
     * @param Zend_Date $renderableValue Renderable value
     * @param mixed $renderOptions Render options
     * @return Zend_Date|null
     */
    protected function _getRenderableZendDate($renderableValue, array $renderOptions)
    {
        if (ctype_digit($renderableValue)) {
            $renderableValue = (int) $renderableValue;
            
            if ($renderableValue > 3155760000) {
                $renderableValue = 0;
            }
            
            $renderableValue = new Zend_Date($renderableValue);
        } else {
            try {
                $renderableValue = new Zend_Date(
                    $renderableValue,
                    $this->_getDateInputFormat($renderOptions),
                    $this->_getDateInputLocale($renderOptions)
                );
            } catch (Exception $e) {
                $renderableValue = null;
            }
        }
        return $renderableValue;
    }
    
    /**
     * Return the output locale from the given render options
     * 
     * @param array $renderOptions Render Options
     * @return string|Zend_Locale|null
     */
    protected function _getDateOutputLocale(array $renderOptions)
    {
        return isset($renderOptions['output_locale'])
            ? $renderOptions['output_locale']
            : null;
    }
    
    /**
     * Return the output date format from the given render options
     * 
     * @param array $renderOptions Render Options
     * @return string
     */
    protected function _getDateOutputFormat(array $renderOptions)
    {
        return isset($renderOptions['output_format'])
            ? $renderOptions['output_format']
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    
    /**
     * Render the given date value
     * 
     * @param mixed $renderableValue Renderable value
     * @param array $renderOptions Render options
     * @return string
     */
    protected function _renderDateValue($renderableValue, array $renderOptions)
    {
        if (empty($renderableValue)) {
            return '';
        } elseif ((!$renderableValue instanceof Zend_Date)
            && (!$renderableValue = $this->_getRenderableZendDate($renderableValue))) {
            return $this->getDefaultValue();
        }
        
        return $renderableValue->toString(
            $this->_getDateOutputFormat($renderOptions),
            null,
            $this->_getDateOutputLocale($renderOptions)
        );
    }
    
    /**
     * Return the default rendered value
     * 
     * @return string
     */
    protected function _renderDefaultValue()
    {
        return $this->getDefaultValue();
    }
    
    protected function _getRenderedValue()
    {
        $editConfig      = $this->getEditConfig();
        $fieldType       = $editConfig->getData('type');
        $renderOptions   = $editConfig->getData('renderer');
        $formOptions     = $editConfig->getData('form');
        $renderableValue = $this->getRenderableValue();
        
        if (isset(self::$_renderMethods[$fieldType])) {
            $renderedValue = call_user_func(
                array($this, self::$_renderMethods[$fieldType]),
                $renderableValue,
                $renderOptions,
                $formOptions
            );
        } else {
            $renderedValue = $this->_renderTextValue($renderableValue);
        }
        
        return $renderedValue;
    }
}
