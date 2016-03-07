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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Checkbox extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Choice
{
    protected function _getRenderedValue($renderableValue)
    {
        $valueConfig = $this->getValueConfig();
        
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
}
