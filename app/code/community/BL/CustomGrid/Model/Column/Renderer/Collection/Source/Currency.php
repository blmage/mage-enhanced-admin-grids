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

class BL_CustomGrid_Model_Column_Renderer_Collection_Source_Currency
{
    protected $_optionArray = null;
    protected $_optionHash  = null;
    
    public function toOptionArray()
    {
        if (is_null($this->_optionArray)) {
            $currencies = Mage::app()->getLocale()->getOptionCurrencies();
            $codes      = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
            $options    = array();
            
            foreach ($currencies as $currency) {
                if (in_array($currency['value'], $codes)) {
                    $options[] = $currency;
                }
            }
            
            array_unshift($options, 
                array(
                    'value' => BL_CustomGrid_Model_Column_Renderer_Collection_Price::CURRENCY_TYPE_BASE, 
                    'label' => Mage::helper('customgrid')->__('Use Base Currency'),
                ), 
                array(
                    'value' => BL_CustomGrid_Model_Column_Renderer_Collection_Price::CURRENCY_TYPE_COLUMN, 
                    'label' => Mage::helper('customgrid')->__('Use Column Currency'),
                )
            );
            
            $this->_optionArray = $options;
        }
        return $this->_optionArray;
    }
    
    public function toOptionHash()
    {
        if (is_null($this->_optionHash)) {
            $options = $this->toOptionArray();
            $this->_optionHash = array();
            
            foreach ($options as $option) {
                $this->_optionHash[$option['value']] = $option['label'];
            }
        }
        return $this->_optionHash;
    }
}