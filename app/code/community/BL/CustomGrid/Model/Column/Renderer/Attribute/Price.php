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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Price
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    const CURRENCY_TYPE_BASE   = 'base_currency';
    const CURRENCY_TYPE_COLUMN = 'column_currency';
    
    public function isAppliableToAttribute(Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid $gridModel)
    {
        return ($attribute->getFrontendInput() == 'price');
    }
    
    protected function _getCurrencyValues($baseCode, Mage_Core_Model_Store $store, BL_CustomGrid_Model_Grid $gridModel)
    {
        $isFixedCurrency = true;
        
        if (($currency = $this->getData('values/' . $baseCode . '_currency')) == self::CURRENCY_TYPE_BASE) {
            // Base currency
            $currency = $store->getBaseCurrency()->getCode();
        } else if ($currency == self::CURRENCY_TYPE_COLUMN) {
            // Currency from column value
            $columnType = $this->getData('values/' . $baseCode . '_currency_column_type');
            $isFixedCurrency = false;
            
            if (($columnType == BL_CustomGrid_Model_Grid::COLUMN_ORIGIN_ATTRIBUTE)
                || ($columnType == BL_CustomGrid_Model_Grid::COLUMN_ORIGIN_CUSTOM)) {
                $currency = $gridModel->getColumnIndexFromCode(
                    $this->getData('values/' . $baseCode . '_currency_column_index'),
                    $columnType,
                    (int) $this->getData('values/' . $baseCode . '_currency_column_position')
                );
            } else {
                $currency = $gridModel->getColumnIndexFromCode(
                    $this->getData('values/' . $baseCode . '_currency_column'),
                    $columnType
                );
            }
        } // Else fixed currency code
        
        if ($isFixedCurrency) {
            $allowedCurrencies = Mage::getModel('customgrid/column_renderer_source_attribute_currency')
                ->toOptionHash();
            
            if (!isset($allowedCurrencies[$currency])) {
                $currency = $store->getBaseCurrency()->getCode();
            }
            
            $key = $baseCode . '_currency_code';
        } else {
            $key = $baseCode . '_currency';
        }
        
        return array($key => $currency);
    }
    
    public function getColumnBlockValues(Mage_Eav_Model_Entity_Attribute $attribute,
        Mage_Core_Model_Store $store, BL_CustomGrid_Model_Grid $gridModel)
    {
        $values = array(
            'renderer' => 'customgrid/widget_grid_column_renderer_price',
            'filter'   => 'customgrid/widget_grid_column_filter_price',
            'default_currency_code' => $store->getBaseCurrency()->getCode(),
        );
        
        $values += $this->_getCurrencyValues('original', $store, $gridModel);
        $values += $this->_getCurrencyValues('display',  $store, $gridModel);
        $values += array('apply_rates' => (bool) $this->getData('values/apply_rates'));
        
        return $values;
    }
}