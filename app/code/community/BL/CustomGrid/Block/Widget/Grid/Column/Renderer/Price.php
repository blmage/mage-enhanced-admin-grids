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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Price
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected $_currencyModel  = null;
    protected $_currenciesList = null;
    protected $_defaultWidth   = 100;
    
    public function render(Varien_Object $row)
    {
        if ($data = $row->getData($this->getColumn()->getIndex())) {
            if (!$displayCurrency = $this->_getCurrencyCode($row, 'display')) {
                return $data;
            }
            if ($this->getColumn()->getApplyRates()) {
                if ($originalCurrency = $this->_getCurrencyCode($row, 'original')) {
                    $data = floatval($data) * $this->_getRate($originalCurrency, $displayCurrency);
                    $data = sprintf('%f', $data);
                }
            }
            $data = Mage::app()->getLocale()->currency($displayCurrency)->toCurrency($data);
            return $data;
        }
        return $this->getColumn()->getDefault();
    }
    
    protected function _getCurrencyModel()
    {
        if (is_null($this->_currencyModel)) {
            $this->_currencyModel = Mage::getModel('directory/currency');
        }
        return $this->_currencyModel;
    }
    
    protected function _isValidCurrencyCode($code)
    {
        if (is_null($this->_currenciesList)) {
            $this->_currenciesList = $this->_getCurrencyModel()->getConfigAllowCurrencies();
        }
        return in_array($code, $this->_currenciesList);
    }
    
    protected function _getCurrencyCode($row, $baseCode)
    {
        if ($code = $this->getColumn()->getData($baseCode.'_currency_code')) {
            return $code;
        }
        if (($code = $row->getData($this->getColumn()->getData($baseCode.'_currency')))
            && $this->_isValidCurrencyCode($code)) {
            return $code;
        }
        if ($code = $this->getColumn()->getDefaultCurrencyCode()) {
            return $code;
        }
        return false;
    }
    
    protected function _getRate($from, $to)
    {
        return $this->_getCurrencyModel()->load($from)->getAnyRate($to);
    }
}