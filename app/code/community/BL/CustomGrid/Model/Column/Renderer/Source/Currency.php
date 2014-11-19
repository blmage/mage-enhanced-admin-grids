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

class BL_CustomGrid_Model_Column_Renderer_Source_Currency
{
    static protected $_optionArray = null;
    
    public function toOptionArray()
    {
        if (is_null(self::$_optionArray)) {
            $currencies = Mage::app()->getLocale()->getOptionCurrencies();
            $allowedCodes = Mage::getModel('directory/currency')->getConfigAllowCurrencies();
            self::$_optionArray = array();
            
            foreach ($currencies as $currency) {
                if (in_array($currency['value'], $allowedCodes)) {
                    self::$_optionArray[] = $currency;
                }
            }
            
            array_unshift(
                self::$_optionArray, 
                array(
                    'value' => BL_CustomGrid_Helper_Column_Renderer::CURRENCY_TYPE_BASE,
                    'label' => Mage::helper('customgrid')->__('Use Base Currency'),
                ), 
                array(
                    'value' => BL_CustomGrid_Helper_Column_Renderer::CURRENCY_TYPE_COLUMN,
                    'label' => Mage::helper('customgrid')->__('Use Column Currency'),
                )
            );
        }
        return self::$_optionArray;
    }
    
    public function toOptionHash()
    {
        return Mage::helper('customgrid')->getOptionHashFromOptionArray($this->toOptionArray(), false);
    }
}
