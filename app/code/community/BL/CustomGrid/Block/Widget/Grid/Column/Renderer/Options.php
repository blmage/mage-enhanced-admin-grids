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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Options extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    protected function _collectOptions(array $list, $keepPath, $separator, $path = '', $first = true)
    {
        $options = array();
        
        foreach ($list as $option) {
            if (is_array($option['value'])) {
                $options = array_merge(
                    $options,
                    $this->_collectOptions(
                        $option['value'],
                        $keepPath,
                        $separator,
                        (!$first ? $path . $separator : '') . $option['label'],
                        false
                    )
                );
            } elseif (!is_null($option['value']) && ($option['value'] !== '')) {
                $options[$option['value']] = ($keepPath ? $path . $separator : '') . $option['label'];
            }
        }
        
        return $options;
    }
    
    protected function _renderImplodedValues(array $values, array $options, $showMissingValues)
    {
        $result = array();
        
        foreach ($values as $item) {
            if (isset($options[$item])) {
                $result[] = $options[$item];
            } elseif ($showMissingValues) {
                $result[] = $item;
            }
        }
        
        return implode($this->getColumn()->getValuesSeparator(), $result);
    }
    
    public function render(Varien_Object $row)
    {
        $columnBlock = $this->getColumn();
        $showMissingValues = (bool) $columnBlock->getShowMissingOptionValues();
        $result = '';
        
        $options = $this->_collectOptions(
            $columnBlock->getOptions(),
            (bool) $columnBlock->getDisplayFullPath(),
            $columnBlock->getSubValuesSeparator()
        );
        
        if (!empty($options) && is_array($options)) {
            $value = $row->getData($columnBlock->getIndex());
            
            if ($columnBlock->getImplodedValues()) {
                $value = explode($columnBlock->getImplodedSeparator(), $value);
            }
            if (is_array($value)) {
                $result = $this->_renderImplodedValues($value, $options, $showMissingValues);
            } elseif (isset($options[$value])) {
                $result = $options[$value];
            } elseif ($showMissingValues) {
                $result = $value;
            }
        }
        
        return $result;
    }
}
