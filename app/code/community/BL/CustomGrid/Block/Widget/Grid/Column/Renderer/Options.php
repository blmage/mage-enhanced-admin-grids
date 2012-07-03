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
                        (!$first ? $path.$separator : '').$option['label'],
                        false
                    )
                );
            } elseif (!is_null($option['value']) && ($option['value'] !== '')) {
                $options[$option['value']] = ($keepPath ? $path.$separator : '') . $option['label'];
            } // Don't display empty values
        }
        
        return $options;
    }
    
    public function render(Varien_Object $row)
    {
        $keepPath   = (bool) $this->getColumn()->getDisplayFullPath();
        $separator  = $this->getColumn()->getOptionsSeparator();
        $imploded   = (bool) $this->getColumn()->getImplodedValues();
        $implodeSep = $this->getColumn()->getImplodedSeparator();
        $showMissingOptionValues = (bool) $this->getColumn()->getShowMissingOptionValues();
        $options    = $this->_collectOptions($this->getColumn()->getOptions(), $keepPath, $separator);
        
        if (!empty($options) && is_array($options)) {
            $value = $row->getData($this->getColumn()->getIndex());
            if ($imploded) {
                $value = explode($implodeSep, $value);
            }
            if (is_array($value)) {
                $res = array();
                
                foreach ($value as $item) {
                    if (isset($options[$item])) {
                        $res[] = $options[$item];
                    } elseif ($showMissingOptionValues) {
                        $res[] = $item;
                    }
                }
                
                return implode(', ', $res);
            } elseif (isset($options[$value])) {
                return $options[$value];
            } elseif ($showMissingOptionValues) {
                return $value;
            }
            return '';
        }
    }
}