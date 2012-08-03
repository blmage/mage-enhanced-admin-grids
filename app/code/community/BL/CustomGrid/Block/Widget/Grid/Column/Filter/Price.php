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

class Bl_CustomGrid_Block_Widget_Grid_Column_Filter_Price
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    protected $_currencyList  = null;
    protected $_currencyModel = null;
    
    public function getHtml()
    {
        $html  = '<div class="range">';
        
        $html .= '<div class="range-line"><span class="label">'
            . Mage::helper('adminhtml')->__('From')
            .':</span> <input type="text" name="'.$this->_getHtmlName().'[from]" id="'.$this->_getHtmlId().'_from" value="'.$this->getEscapedValue('from').'" class="input-text no-changes"/></div>';
        $html .= '<div class="range-line"><span class="label">'
            . Mage::helper('adminhtml')->__('To')
            .' : </span><input type="text" name="'.$this->_getHtmlName().'[to]" id="'.$this->_getHtmlId().'_to" value="'.$this->getEscapedValue('to').'" class="input-text no-changes"/></div>';
        
        if ($this->getDisplayCurrencySelect()) {
            $html .= '<div class="range-line"><span class="label">' . Mage::helper('adminhtml')->__('In').' : </span>' . $this->_getCurrencySelectHtml() . '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    protected function _getColumnOriginalCurrency()
    {
        if ($code = $this->getColumn()->getOriginalCurrencyCode()) {
            return $code;
        }
        // Only a fixed currency code is usable for filtering
        return false;
    }
    
    public function getDisplayCurrencySelect()
    {
        return $this->_getColumnOriginalCurrency();
    }
    
    protected function _getCurrencyModel()
    {
        if (is_null($this->_currencyModel)) {
            $this->_currencyModel = Mage::getModel('directory/currency');
        }
        return $this->_currencyModel;
    }
    
    protected function _getCurrencySelectHtml()
    {
        if (!$value = $this->_getColumnOriginalCurrency()) {
            return '';
        }
        $html = '<select name="'.$this->_getHtmlName().'[currency]" id="'.$this->_getHtmlId().'_currency">';
        
        foreach ($this->_getCurrencyList() as $currency) {
            $html .= '<option value="'.$currency.'" '.($currency == $value ? 'selected="selected"' : '').'>'.$currency.'</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
    
    protected function _getCurrencyList()
    {
        if (is_null($this->_currencyList)) {
            $this->_currencyList = $this->_getCurrencyModel()->getConfigAllowCurrencies();
        }
        return $this->_currencyList;
    }
    
    public function getValue($index=null)
    {
        if ($index) {
            return $this->getData('value', $index);
        }
        $value = $this->_getData('value');
        
        if ((isset($value['from']) && strlen($value['from']) > 0) 
            || (isset($value['to']) && strlen($value['to']) > 0)) {
            return $value;
        }
        
        return null;
    }
    
    public function getCondition()
    {
        $value = $this->getValue();
        
        if ($columnCurrency = $this->_getColumnOriginalCurrency()) {
            if (isset($value['currency'])) {
                $filterCurrency = $value['currency'];
            } else {
                $filterCurrency = $columnCurrency;
            }
            
            $rate = $this->_getRate($filterCurrency, $columnCurrency);
            
            if (isset($value['from'])) {
                $value['from'] *= $rate;
            }
            if (isset($value['to'])) {
                $value['to'] *= $rate;
            }
        }
        
        return $value;
    }
    
    protected function _getRate($from, $to)
    {
        return $this->_getCurrencyModel()->load($from)->getAnyRate($to);
    }
}
