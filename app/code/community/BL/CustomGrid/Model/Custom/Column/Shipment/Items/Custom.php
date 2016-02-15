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
 
class BL_CustomGrid_Model_Custom_Column_Shipment_Items_Custom extends BL_CustomGrid_Model_Custom_Column_Shipment_Items_Abstract
{
    protected function _isCustomizableList()
    {
        return true;
    }
    
    protected function _getItemBaseValues()
    {
        return array(
            'name'     => 'Name',
            'sku'      => 'SKU',
            'quantity' => 'Qty',
        );
    }
    
    protected function _getItemValuesEventName()
    {
        return 'blcg_custom_column_shipment_items_custom_values';
    }
    
    protected function _getColumnBlockRenderer()
    {
        return 'customgrid/widget_grid_column_renderer_shipment_items_custom';
    }
}
