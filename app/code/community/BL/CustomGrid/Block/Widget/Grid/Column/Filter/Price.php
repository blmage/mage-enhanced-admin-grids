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

class Bl_CustomGrid_Block_Widget_Grid_Column_Filter_Price extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    /**
     * Currency model
     * 
     * @var Mage_Directory_Model_Currency
     */
    protected $_currencyModel  = null;
    
    /**
     * Allowed currencies list
     * 
     * @var array
     */
    protected $_currenciesList = null;
    
    public function getHtml()
    {
        $html = '<div class="range"><div class="range-line">'
            . '<span class="label">' . $this->__('From') . ':</span> '
            . '<input type="text" name="' . $this->_getHtmlName() . '[from]" id="' . $this->_getHtmlId() . '_from" '
            .  'value="' . $this->getEscapedValue('from') . '" class="input-text no-changes"/>'
            . '</div>'
            . '<div class="range-line">'
            . '<span class="label">' . $this->__('To') . ' : </span>'
            . '<input type="text" name="' . $this->_getHtmlName() . '[to]" id="' . $this->_getHtmlId() . '_to" '
            . 'value="' . $this->getEscapedValue('to') . '" class="input-text no-changes"/>'
            . '</div>';
        
        if ($this->getDisplayCurrencySelect()) {
            $html .= '<div class="range-line">'
                . '<span class="label">' . $this->__('In') . ' : </span>'
                . $this->_getCurrencySelectHtml()
                . '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    public function getValue($index = null)
    {
        if ($index) {
            return $this->getData('value', $index);
        }
        
        $value = $this->_getData('value');
        
        if (is_array($value)) {
            if ((isset($value['from']) && strlen($value['from']) > 0) 
                || (isset($value['to']) && strlen($value['to'])  > 0)) {
                return $value;
            }
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
            
            $rate = $this->_getCurrenciesRate($filterCurrency, $columnCurrency);
            
            if (isset($value['from'])) {
                $value['from'] *= $rate;
            }
            if (isset($value['to'])) {
                $value['to'] *= $rate;
            }
        }
        
        return $value;
    }
    
    /**
     * Return the code of the original currency
     * 
     * @return string
     */
    protected function _getColumnOriginalCurrency()
    {
        // Only a fixed currency code is usable for filtering
        return ($code = $this->getColumn()->getOriginalCurrencyCode())
            ? $code
            : false;
    }
    
    /**
     * Return whether the currency select can be displayed
     * 
     * @return bool
     */
    public function getDisplayCurrencySelect()
    {
        return $this->_getColumnOriginalCurrency();
    }
    
    /**
     * Return the currency model
     * 
     * @return Mage_Directory_Model_Currency
     */
    protected function _getCurrencyModel()
    {
        if (is_null($this->_currencyModel)) {
            $this->_currencyModel = Mage::getModel('directory/currency');
        }
        return $this->_currencyModel;
    }
    
    /**
     * Return the allowed currencies list
     * 
     * @return string[]
     */
    protected function _getCurrenciesList()
    {
        if (is_null($this->_currenciesList)) {
            $this->_currenciesList = $this->_getCurrencyModel()->getConfigAllowCurrencies();
        }
        return $this->_currenciesList;
    }
    
    /**
     * Return the conversion rate between the two given currencies
     * 
     * @param string $fromCurrency Base currency
     * @param string $toCurrency Result currency
     * @return float
     */
    protected function _getCurrenciesRate($fromCurrency, $toCurrency)
    {
        return $this->_getCurrencyModel()
            ->load($fromCurrency)
            ->getAnyRate($toCurrency);
    }
    
    /**
     * Return the HTML content of the currency select
     * 
     * @return string
     */
    protected function _getCurrencySelectHtml()
    {
        if (!$value = $this->_getColumnOriginalCurrency()) {
            return '';
        }
        
        $html = '<select name="' . $this->_getHtmlName() . '[currency]" id="' . $this->_getHtmlId() . '_currency">';
        
        foreach ($this->_getCurrenciesList() as $currency) {
            $html .= '<option value="' . $this->htmlEscape($currency) . '" '
                . ($currency == $value ? 'selected="selected"' : '') . '>'
                . $this->htmlEscape($currency)
                . '</option>';
        }
        
        $html .= '</select>';
        return $html;
    }
}
