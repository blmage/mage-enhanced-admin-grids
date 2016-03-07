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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Select extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Choice
{
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
    
    protected function _getRenderedValue($renderableValue)
    {
        $valueConfig = $this->getValueConfig();
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
}
