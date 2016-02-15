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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Price extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected $_defaultWidth = 100;
    
    public function render(Varien_Object $row)
    {
        if ($value = $row->getData($this->getColumn()->getIndex())) {
            if (!$displayCurrency = $this->_getCurrencyCode($row, 'display')) {
                return $value;
            }
            
            if ($this->getColumn()->getApplyRates()) {
                if ($originalCurrency = $this->_getCurrencyCode($row, 'original')) {
                    $value = floatval($value) * $this->_getRate($originalCurrency, $displayCurrency);
                    $value = sprintf('%f', $value);
                }
            }
            
            $value = Mage::app()->getLocale()->currency($displayCurrency)->toCurrency($value);
            return $value;
        }
        return $this->getColumn()->getDefault();
    }
    
    protected function _getCurrencyModel()
    {
        return $this->getDataSetDefault('currency_model', Mage::getModel('directory/currency'));
    }
    
    protected function _getCurrenciesList()
    {
        return $this->getDataSetDefault(
            'currencies_list',
            $this->_getCurrencyModel()->getConfigAllowCurrencies()
        );
    }
    
    protected function _isValidCurrencyCode($code)
    {
        return in_array($code, $this->_getCurrenciesList());
    }
    
    protected function _getCurrencyCode($row, $baseCode)
    {
        if ($code = $this->getColumn()->getData($baseCode . '_currency_code')) {
            return $code;
        }
        if (($code = $row->getData($this->getColumn()->getData($baseCode . '_currency')))
            && $this->_isValidCurrencyCode($code)) {
            return $code;
        }
        if ($code = $this->getColumn()->getDefaultCurrencyCode()) {
            return $code;
        }
        return false;
    }
    
    protected function _getRate($fromPrice, $toPrice)
    {
        return $this->_getCurrencyModel()
            ->load($fromPrice)
            ->getAnyRate($toPrice);
    }
}
