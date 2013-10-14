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
 
class BL_CustomGrid_Model_Custom_Column_Order_Items_Custom
    extends BL_CustomGrid_Model_Custom_Column_Order_Items
{
    protected function _sortItemValues($a, $b)
    {
        return ($a['position'] > $b['position'] ? 1 : ($a['position'] < $b['position'] ? -1 : 0));
    }
    
    public function getItemValues()
    {
        if (!$this->hasData('item_values')) {
            $baseValues = array(
                'name'            => 'Name',
                'sku'             => 'SKU',
                'original_price'  => 'Original Price',
                'status'          => 'Status',
                'quantity'        => 'Qty',
                'tax_amount'      => 'Tax Amount',
                'tax_percent'     => 'Tax Percent',
                'discount_amount' => 'Discount Amount',
                'row_total'       => 'Row Total',
            );
            $amountsKeys = array(
                'original_price',
                'tax_amount',
                'tax_percent',
                'discount_amount',
                'row_total',
            );
            
            $itemValues  = array();
            $salesHelper = Mage::helper('sales');
            $position    = 0;
            
            foreach ($baseValues as $key => $value) {
                $itemValues[$key] = array(
                    'code'         => $key,
                    'name'         => $salesHelper->__($value),
                    'description'  => '',
                    'default'      => true,
                    'position'     => ($position += 100),
                    'renderers'    => array(999999 => 'customgrid/widget_grid_column_renderer_order_items_sub_value_default'),
                );
                
                if (in_array($key, $amountsKeys)) {
                    $itemValues[$key]['value_align'] = 'right';
                }
                // Also usable: "header_align" for header label alignment
            }
            
            $response = new Varien_Object(array('item_values' => $itemValues));
            Mage::dispatchEvent('blcg_custom_column_order_items_custom_values', array('response' => $response));
            $itemValues = $response->getItemValues();
            uasort($itemValues, array($this, '_sortItemValues'));
            
            foreach ($itemValues as $key => $value) {
                $itemValues[$key]['last'] = false;
                sort($itemValues[$key]['renderers'], SORT_NUMERIC);
            }
            if (!is_null($key)) {
                $itemValues[$key]['last'] = true;
            }
            
            $this->setData('item_values', $itemValues);
        }
        return $this->_getData('item_values');
    }
    
    public function initConfig()
    {
        parent::initConfig();
        $this->setCustomParamsWindowConfig(array('height' => 500));
        
        $helper = Mage::helper('customgrid');
        $itemValues = array_reverse($this->getItemValues());
        $position = -10;
        
        foreach ($itemValues as $key => $value) {
             $this->addCustomParam('display_'.$key, array(
                'label'        => $helper->__('Display "%s"', $value['name']),
                'description'  => $value['description'],
                'type'         => 'select',
                'source_model' => 'customgrid/system_config_source_yesno',
                'value'        => ($value['default'] ? 1 : 0),
            ), ($position -= 10));
        }
        
        $this->addCustomParam('hide_header', array(
            'label'        => $helper->__('Hide Header'),
            'description'  => $helper->__('Choose "Yes" if you do not want the field labels to be displayed in the header'),
            'type'         => 'select',
            'source_model' => 'customgrid/system_config_source_yesno',
        ), 0);
        
        return $this;
    }
    
    protected function _getGridColumnRenderer()
    {
        return 'customgrid/widget_grid_column_renderer_order_items_custom';
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $values = parent::_getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer);
        $itemValues = $this->getItemValues();
        
        foreach ($itemValues as $key => $value) {
            if (!$this->_extractBoolParam($params, 'display_'.$value['code'], true)) {
                unset($itemValues[$key]);
            }
        }
        
        $values['hide_header'] = $this->_extractBoolParam($params, 'hide_header', false);
        $values['item_values'] = $itemValues;
        return $values;
    }
}