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
 
class BL_CustomGrid_Model_Custom_Column_Order_Items_Custom extends BL_CustomGrid_Model_Custom_Column_Order_Items_Abstract
{
    protected function _isCustomizableList()
    {
        return true;
    }
    
    protected function _getItemBaseValues()
    {
        return array(
            'name'            => 'Name',
            'sku'             => 'SKU',
            'original_price'  => 'Original Price',
            'status'          => 'Status',
            'quantity'        => 'Qty',
            'qty_ordered'     => 'Ordered Qty',
            'qty_canceled'    => 'Canceled Qty',
            'qty_invoiced'    => 'Invoiced Qty',
            'qty_shipped'     => 'Shipped Qty',
            'qty_refunded'    => 'Refunded Qty',
            'tax_amount'      => 'Tax Amount',
            'tax_percent'     => 'Tax Percent',
            'discount_amount' => 'Discount Amount',
            'row_total'       => 'Row Total',
        );
    }
    
    protected function _getItemDefaultBaseValuesKeys()
    {
        return array(
            'name',
            'sku',
            'original_price',
            'status',
            'quantity',
            'tax_amount',
            'tax_percent',
            'discount_amount',
            'row_total',
        );
    }
    
    protected function _getItemAmountsValuesKeys()
    {
        return array(
            'original_price',
            'tax_amount',
            'tax_percent',
            'discount_amount',
            'row_total',
        );
    }
    
    protected function _getItemValuesEventName()
    {
        return 'blcg_custom_column_order_items_custom_values';
    }
    
    protected function _getColumnBlockRenderer()
    {
        return 'customgrid/widget_grid_column_renderer_order_items_custom';
    }
}
