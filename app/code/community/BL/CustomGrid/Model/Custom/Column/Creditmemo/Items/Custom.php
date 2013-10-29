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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
class BL_CustomGrid_Model_Custom_Column_Creditmemo_Items_Custom
    extends BL_CustomGrid_Model_Custom_Column_Creditmemo_Items_Abstract
{
    protected function _isCustomizableList()
    {
        return true;
    }
    
    public function getItemValues()
    {
        if (!$this->hasData('item_values')) {
            $baseValues = array(
                'name'            => 'Name',
                'sku'             => 'SKU',
                'quantity'        => 'Qty',
                'tax_amount'      => 'Tax Amount',
                'discount_amount' => 'Discount Amount',
                'row_total'       => 'Row Total',
            );
            
            $amountsKeys = array(
                'tax_amount',
                'discount_amount',
                'row_total',
            );
            
            $this->setData('item_values', $this->_getItemValuesList($baseValues, $amountsKeys, 'blcg_custom_column_creditmemo_items_custom_values'));
        }
        return $this->_getData('item_values');
    }
    
    protected function _getGridColumnRenderer()
    {
        return 'customgrid/widget_grid_column_renderer_creditmemo_items_custom';
    }
}