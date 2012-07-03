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

class BL_CustomGrid_Model_Column_Renderer_Collection_Price
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    const CURRENCY_TYPE_BASE   = 'base_currency';
    const CURRENCY_TYPE_COLUMN = 'column_currency';
    
    protected function _getCurrencyValues($baseCode, $store, $grid)
    {
        // Currency value key
        $key = $baseCode.'_currency_code';
        
        if (($currency = $this->_getData($baseCode.'_currency')) == self::CURRENCY_TYPE_BASE) {
            // Base currency explicitely used
            $currency = $store->getBaseCurrency()->getCode();
        } elseif ($currency == self::CURRENCY_TYPE_COLUMN) {
            // Currency taken from column value
            $columnType = $this->_getData($baseCode.'_currency_column_type');
            $key = $baseCode.'_currency';
            
            if (($columnType == BL_CustomGrid_Model_Grid::GRID_COLUMN_ORIGIN_ATTRIBUTE)
                || ($columnType == BL_CustomGrid_Model_Grid::GRID_COLUMN_ORIGIN_CUSTOM)) {
                // Currency taken from attribute / custom column
                $currency = $grid->getColumnIndexFromCode(
                    $this->_getData($baseCode.'_currency_column_index'),
                    $columnType,
                    intval($this->_getData($baseCode.'_currency_column_position'))
                );
            } else {
                // Currency taken from grid / collection column
                $currency = $grid->getColumnIndexFromCode(
                    $this->_getData($baseCode.'_currency_column'),
                    $columnType
                );
            }
        } else {
            // Else may / should be a currency code
            $allowed = Mage::getModel('customgrid/column_renderer_collection_source_currency')
                ->toOptionHash();
            
            if (!isset($allowed[$currency])) {
                // Base currency if given currency is not allowed / does not exist
                $currency = $store->getBaseCurrency()->getCode();
            }
        }
        
        return array($key => $currency);
    }
    
    public function getColumnGridValues($index, $store, $grid)
    {
        $values = array(
            'filter'   => 'customgrid/widget_grid_column_filter_price',
            'renderer' => 'customgrid/widget_grid_column_renderer_price',
            'default_currency_code' => $store->getBaseCurrency()->getCode(),
        );
        
        $values += $this->_getCurrencyValues('original', $store, $grid);
        $values += $this->_getCurrencyValues('display',  $store, $grid);
        $values += array('apply_rates' => ($this->_getData('apply_rates') ? true : false));
        
        return $values;
    }
}