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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Multiselect extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Choice
{
    /**
     * Return whether the given choice value is valid for rendering
     * 
     * @param array $value Choice value
     * @return bool
     */
    protected function _isValidChoiceValue(array $value)
    {
        return isset($value['value']);
    }
    
    protected function _getRenderedValue($renderableValue)
    {
        $valueConfig = $this->getValueConfig();
        $choices = array();
        
        if (is_array($values = $this->_getChoicesValues($valueConfig, 'values'))) {
            $pathsCount = 1;
            $values = array_filter($values, array($this, '_isValidChoiceValue'));
            
            foreach ($values as $value) {
                if (is_array($value['value'])) {
                    $value['value'] = array_filter($value['value'], array($this, '_isValidChoiceValue'));
                    
                    foreach ($value['value'] as $subValue) {
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
        
        return $this->_renderChoicesValue($renderableValue, $choices, true, $valueConfig);
    }
}
