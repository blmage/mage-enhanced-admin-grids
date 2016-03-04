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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Default extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    /**
     * Self render callbacks based on field types
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
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderTextValue($renderableValue, BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return ($renderableValue !== '' ? $this->htmlEscape($renderableValue) : '');
    }
    
    /**
     * Render the given long text value
     * 
     * @param string $renderableValue Renderable long text value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderLongTextValue(
        $renderableValue,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        /** @var $helper Mage_Core_Helper_String */
        $helper = $this->helper('core/string');
        return (($helper->strlen($renderableValue) < 255) ? $renderableValue : $this->getDefaultValue());
    }
    
    /**
     * Return the options available in the given value config for the given source type, or null if there are none
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @param string $sourceType Source type
     * @return array|null
     */
    protected function _getChoicesValues(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig, $sourceType)
    {
        $choicesValues = null;
        
        $key = 'form_field/' . $sourceType;
        $callbackKey = $key . '_callback';
        
        if ($valueConfig->hasData($key)) {
            $choicesValues = $valueConfig->getData($key);
        } elseif ($valueConfig->hasData($callbackKey)) {
            $choicesValues = $valueConfig->runConfigCallback($callbackKey);
        }
        
        return (is_array($choicesValues) ? $choicesValues : null);
    }
    
    /**
     * Prepare the given choices-based value so that it is suitable for rendering
     * 
     * @param string $renderableValue Renderable choices-based value
     * @param bool $allowMultiple Whether the corresponding field allows multiple values
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return array
     */
    protected function _getRenderableChoicesValue(
        $renderableValue,
        $allowMultiple,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        if (!is_array($renderableValue)) {
            if ($allowMultiple) {
                $separator = $valueConfig->getDataSetDefault('renderer/separator', ',');
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
      * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
      * @return string
      */
    protected function _renderChoicesValueWithoutPath(
        array $selectedValues,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        $renderedValue = array();
        
        foreach ($selectedValues as $value) {
            $renderedValue[] = $value['label'];
        }
        
        $separator = $valueConfig->getData('renderer/display_separator', ', ');
        return implode($separator, $renderedValue);
    }
    
    /**
     * Sort callback for choices-based values
     * 
     * @param array $valueA One value
     * @param array $valueB Another value
     * @return int
     */
    protected function _sortChoicesValues(array $valueA, array $valueB)
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
      * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
      * @return string
      */
    protected function _renderChoicesValueWithPath(
        array $choices,
        array $selectedValues,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        uasort($choices, array($this, '_sortChoicesValues'));
        $spacesCount    = (int) $valueConfig->getDataSetDefault('renderer/spaces_count', 0);
        $spacesCount    = ($spacesCount > 0 ? $spacesCount : 3);
        $currentPath    = array();
        $currentDepth   = 0;
        $renderedValue  = array();
        
        foreach ($selectedValues as $selectedValue) {
            if ($selectedValue['value'] === '') {
                continue;
            }
            
            if ($selectedValue['path_id'] !== '') {
                $path  = explode('_', $selectedValue['path_id']);
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
                $renderedValue[] = str_repeat('&nbsp;', $j * $spacesCount) . $selectedValue['path_labels'][$j];
            }
            
            $currentPath  = $path;
            $currentDepth = $depth;
            $renderedValue[] = str_repeat('&nbsp;', $currentDepth * $spacesCount) . $selectedValue['label'];
        }
        
        return implode('<br />', $renderedValue);
    }
    
    /**
     * Render the given choices-based value
     * 
     * @param string $renderableValue Renderable value
     * @param array $choices Available choices
     * @param bool $allowMultiple Whether multiple values are allowed
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderChoicesValue(
        $renderableValue,
        array $choices,
        $allowMultiple = false,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        $renderableValue = $this->_getRenderableChoicesValue($renderableValue, $allowMultiple, $valueConfig);
        $selectedValues  = array_intersect_key($choices, array_flip($renderableValue));
        
        if ($valueConfig->getData('renderer/without_path')) {
            $renderedValue = $this->_renderChoicesValueWithoutPath($selectedValues, $valueConfig);
        } else {
            foreach (array_keys($selectedValues) as $key) {
                $selectedValues[$key] += array(
                    'path_id' => '',
                    'path_labels' => array(),
                );
            }
            
            $renderedValue = $this->_renderChoicesValueWithPath($choices, $selectedValues, $valueConfig);
        }
        
        return $renderedValue;
    }
    
    /**
     * Render the given checkboxes-based value
     * 
     * @param string $renderableValue Renderable value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderCheckboxesValue(
        $renderableValue,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        if (!is_array($values = $this->_getChoicesValues($valueConfig, 'values'))
            && !is_array($values = $this->_getChoicesValues($valueConfig, 'options'))) {
            $values = array();
        }
        
        $choices = array();
        
        foreach ($values as $key => $value) {
            if (is_string($value)) {
                $choices[] = array(
                    'value' => $key,
                    'label' => $value,
                );
            } elseif (is_array($value) && isset($value['value'])) {
                if (!isset($value['label'])) {
                    $value['label'] = $value['value'];
                }
                
                $choices[] = array(
                    'value' => $value['value'],
                    'label' => $value['label'],
                );
            }
        }
        
        return $this->_renderChoicesValue($renderableValue, $choices, false, $valueConfig);
    }
    
    /**
     * Render the given multiple select-based value
     * 
     * @param string $renderableValue Renderable value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderMultiSelectValue(
        $renderableValue,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        $choices = array();
        
        if (is_array($values = $this->_getChoicesValues($valueConfig, 'values'))) {
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
        
        return $this->_renderChoicesValue($renderableValue, $choices, true, $valueConfig);
    }
    
    /**
     * Render the given radio buttons-based value
     * 
     * @param string $renderableValue Renderable value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderRadioButtonsValue(
        $renderableValue,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
        $choices = array();
        
        if (is_array($values = $this->_getChoicesValues($valueConfig, 'values'))) {
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
        
        return $this->_renderChoicesValue($renderableValue, $choices, true, $valueConfig);
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
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderSelectValue($renderableValue, BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        $choices = array();
        
        if (is_array($values = $this->_getChoicesValues($valueConfig, 'values'))) {
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
        } elseif (is_array($values = $this->_getChoicesValues($valueConfig, 'options'))) {
            foreach ($values as $value => $label) {
                $choices[$value] = array(
                    'value' => $value,
                    'label' => $label,
                );
            }
        }
        
        return $this->_renderChoicesValue($renderableValue, $choices, false, $valueConfig);
    }
    
    /**
     * Return the input locale from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string|Zend_Locale|null
     */
    protected function _getDateInputLocale(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->getData('renderer/input_locale');
    }
    
    /**
     * Return the input date format from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _getDateInputFormat(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->hasData('renderer/input_format')
            ? $valueConfig->etData('renderer/input_format')
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    
    /**
     * Return a Zend_Date object from the given renderable date value
     * 
     * @param Zend_Date $renderableValue Renderable value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return Zend_Date|null
     */
    protected function _getRenderableZendDate(
        $renderableValue,
        BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig
    ) {
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
                    $this->_getDateInputFormat($valueConfig),
                    $this->_getDateInputLocale($valueConfig)
                );
            } catch (Exception $e) {
                $renderableValue = null;
            }
        }
        return $renderableValue;
    }
    
    /**
     * Return the output locale from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string|Zend_Locale|null
     */
    protected function _getDateOutputLocale(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->getData('renderer/output_locale');
    }
    
    /**
     * Return the output date format from the given value config
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _getDateOutputFormat(BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        return $valueConfig->hasData('renderer/output_format')
            ? $valueConfig->etData('renderer/output_format')
            : Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
    
    /**
     * Render the given date value
     * 
     * @param mixed $renderableValue Renderable value
     * @param BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig Value config
     * @return string
     */
    protected function _renderDateValue($renderableValue, BL_CustomGrid_Model_Grid_Editor_Value_Config $valueConfig)
    {
        if (empty($renderableValue)) {
            return '';
        } elseif ((!$renderableValue instanceof Zend_Date)
            && (!$renderableValue = $this->_getRenderableZendDate($renderableValue, $valueConfig))) {
            return $this->getDefaultValue();
        }
        
        return $renderableValue->toString(
            $this->_getDateOutputFormat($valueConfig),
            null,
            $this->_getDateOutputLocale($valueConfig)
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
        $valueConfig = $this->getValueConfig();
        $fieldType   = $valueConfig->getFormFieldType();
        $renderableValue = $this->getRenderableValue();
        
        if (isset(self::$_renderMethods[$fieldType])) {
            $renderedValue = call_user_func(
                array($this, self::$_renderMethods[$fieldType]),
                $renderableValue,
                $valueConfig
            );
        } else {
            $renderedValue = $this->_renderTextValue($renderableValue, $valueConfig);
        }
        
        return $renderedValue;
    }
}
