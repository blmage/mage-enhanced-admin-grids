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

abstract class BL_CustomGrid_Model_Column_Renderer_Source_Currency_Abstract
{
    protected $_optionsArray = null;
    
    abstract protected function _getUseBaseCurrencyValue();
    abstract protected function _getUseColumnCurrencyValue();
    
    public function toOptionArray()
    {
        if (is_null($this->_optionsArray)) {
            $currencies = Mage::app()->getLocale()->getOptionCurrencies();
            $allowedCodes = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
            $this->_optionsArray = array();
            
            foreach ($currencies as $currency) {
                if (in_array($currency['value'], $allowedCodes)) {
                    $this->_optionsArray[] = $currency;
                }
            }
            
            array_unshift(
                $this->_optionsArray, 
                array(
                    'value' => $this->_getUseBaseCurrencyValue(), 
                    'label' => Mage::helper('customgrid')->__('Use Base Currency'),
                ), 
                array(
                    'value' => $this->_getUseColumnCurrencyValue(), 
                    'label' => Mage::helper('customgrid')->__('Use Column Currency'),
                )
            );
        }
        return $this->_optionsArray;
    }
    
    public function toOptionHash()
    {
        return Mage::helper('customgrid')->getOptionsHashFromOptionsArray($this->toOptionArray(), false);
    }
}