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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Price
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    const CURRENCY_TYPE_BASE   = 'base_currency';
    const CURRENCY_TYPE_COLUMN = 'column_currency';
    
    public function isAppliableToColumn($attribute, $grid)
    {
        return ($attribute->getFrontendInput() == 'price');
    }
    
    protected function _getCurrencyValues($baseCode, $store, $grid)
    {
        // Currency value key
        $key = $baseCode.'_currency_code';
        
        if (($currency = $this->_getData($baseCode.'_currency')) == self::CURRENCY_TYPE_BASE) {
            // Base currency explicitely used
            $currency = $store->getBaseCurrency()->getCode();
        } else {
            if ($currency == self::CURRENCY_TYPE_COLUMN) {
                $columnType = $this->_getData($baseCode.'_currency_column_type');
                
                if ($columnType == BL_CustomGrid_Model_Grid::GRID_COLUMN_ORIGIN_ATTRIBUTE) {
                    // Currency taken from attribute column
                    $key = $baseCode.'_currency';
                    $currency = $grid->getColumnIndexFromCode(
                        $this->_getData($baseCode.'_currency_column_attribute_code'),
                        $columnType,
                        intval($this->_getData($baseCode.'_currency_column_position'))
                    );
                } else {
                    // Currency taken from grid/collection column
                    $key = $baseCode.'_currency';
                    $currency = $grid->getColumnIndexFromCode(
                        $this->_getData($baseCode.'_currency_column'),
                        $columnType
                    );
                }
            } // Else may/should be a currency code
            
            $allowed = Mage::getModel('customgrid/column_renderer_collection_source_currency')
                ->toOptionHash();
            
            if (!isset($allowed[$currency])) {
                // Base currency if given currency is not allowed / does not exist
                $key = $baseCode.'_currency_code';
                $currency = $store->getBaseCurrency()->getCode();
            }
        }
        
        return array($key => $currency);
    }
    
    public function getColumnGridValues($index, $store, $grid)
    {
        // Custom filter / renderer
        $values = array(
            'filter'   => 'customgrid/widget_grid_column_filter_price',
            'renderer' => 'customgrid/widget_grid_column_renderer_price',
        );
        
        // Get original currency values
        $values += $this->_getCurrencyValues('original', $store, $grid);
        
        // Get display currency values
        $values += $this->_getCurrencyValues('display', $store, $grid);
        
        // Then add apply-rates flag
        $values += array(
            'apply_rates' => ($this->_getData('apply_rates') ? true : false),
        );
        
        return $values;
    }
}