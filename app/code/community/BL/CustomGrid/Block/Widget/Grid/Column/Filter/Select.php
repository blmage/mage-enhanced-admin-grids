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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    const BOOLEAN_FILTER_VALUE_WITH    = 'with';
    const BOOLEAN_FILTER_VALUE_WITHOUT = 'without';
    
    protected function _getOptions()
    {
        if ((bool)$this->getColumn()->getBooleanFilter()) {
            return array(
                array(
                    'value' => self::BOOLEAN_FILTER_VALUE_WITH,
                    'label' => Mage::helper('customgrid')->__('With'),
                ),
                array(
                    'value' => self::BOOLEAN_FILTER_VALUE_WITHOUT,
                    'label' => Mage::helper('customgrid')->__('Without'),
                ),
            );
        } else {
            $options = $this->getColumn()->getOptions();
            
            if (!empty($options) && is_array($options)) {
                return $options;
            }
            
            return array();
        }
    }
    
    protected function _renderOption($option, $value, $removeEmpty=true)
    {
        if (is_array($option['value'])) {
            $html = '<optgroup label="'.$this->escapeHtml($option['label']).'">';
            foreach ($option['value'] as $subOption) {
                $html .= $this->_renderOption($subOption, $value);
            }
            $html .= '</optgroup>';
            return $html;
        } else {
            if (!$removeEmpty || (!is_null($option['value']) && ($option['value'] !== ''))) {
                $selected = (($option['value'] == $value) && !is_null($value) ? ' selected="selected"' : '' );
                return '<option value="'. $this->escapeHtml($option['value']).'"'.$selected.'>'.$this->escapeHtml($option['label']).'</option>';
            } // Don't take empty value from options source (as we could not filter on it btw - at least basically)
        }
    }
    
    protected function _isExistingOptionValue($options, $value)
    {
        foreach ($options as $option) {
            if (is_array($option['value'])) {
                if ($this->_isExistingOptionValue($option['value'], $value)) {
                    return true;
                }
            } elseif (($option['value'] == $value) && !is_null($value)) {
                return true;
            }
        }
        return false;
    }
    
    public function getHtml()
    {
        $html    = '<select name="'.$this->_getHtmlName().'" id="'.$this->_getHtmlId().'" class="no-changes">';
        $value   = $this->getValue();
        $empty   = array(
            'value' => '',
            'label' => '',
        );
        $options = $this->_getOptions();
       
        if (!empty($options)) {
            $html .= $this->_renderOption($empty, $value, false);
            foreach ($this->_getOptions() as $option){
                $html .= $this->_renderOption($option, $value);
            }
        }
        
        $html.='</select>';
        return $html;
    }
    
    public function getCondition()
    {
        if (is_null($value = $this->getValue())) {
            return null;
        }
        if ((bool)$this->getColumn()->getBooleanFilter()) {
            if ($value == self::BOOLEAN_FILTER_VALUE_WITH) {
                return array('neq' => '');
            } elseif ($value == self::BOOLEAN_FILTER_VALUE_WITHOUT) {
                return array(array('null' => 1), array('eq' => ''));
            }
        } elseif ($this->_isExistingOptionValue($this->_getOptions(), $value)) {
            if ((bool)$this->getColumn()->getImplodedValues()) {
                $separator = $this->getColumn()->getImplodedSeparator();
                // No regexes available for database conditions
                return array(
                    array('eq' => $value),
                    array('like' => $value.$separator.'%'),
                    array('like' => '%'.$separator.$value.$separator.'%'),
                    array('like' => '%'.$separator.$value)
                );
            } else {
                return array('eq' => $this->getValue());
            }
        } else {
            return null;
        }
    }
}