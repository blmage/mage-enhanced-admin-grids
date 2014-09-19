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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Options
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    protected function _collectOptions(array $list, $keepPath, $separator, $path='', $first=true)
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
    
    public function render(Varien_Object $row)
    {
        $keepPath  = (bool) $this->getColumn()->getDisplayFullPath();
        $separator = $this->getColumn()->getOptionsSeparator();
        $options   = $this->_collectOptions($this->getColumn()->getOptions(), $keepPath, $separator);
        $imploded  = (bool) $this->getColumn()->getImplodedValues();
        $implodedSeparator = $this->getColumn()->getImplodedSeparator();
        $showMissingOptionValues = (bool) $this->getColumn()->getShowMissingOptionValues();
        
        if (!empty($options) && is_array($options)) {
            $value = $row->getData($this->getColumn()->getIndex());
            
            if ($imploded) {
                $value = explode($implodedSeparator, $value);
            }
            if (is_array($value)) {
                $result = array();
                
                foreach ($value as $item) {
                    if (isset($options[$item])) {
                        $result[] = $options[$item];
                    } elseif ($showMissingOptionValues) {
                        $result[] = $item;
                    }
                }
                
                return implode(', ', $result);
            } elseif (isset($options[$value])) {
                return $options[$value];
            } elseif ($showMissingOptionValues) {
                return $value;
            }
        }
        
        return '';
    }
}