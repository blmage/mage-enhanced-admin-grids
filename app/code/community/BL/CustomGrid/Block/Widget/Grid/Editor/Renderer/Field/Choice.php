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

abstract class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Choice extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
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
}
