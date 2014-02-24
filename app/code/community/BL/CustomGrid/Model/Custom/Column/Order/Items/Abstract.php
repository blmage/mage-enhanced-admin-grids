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
 
abstract class BL_CustomGrid_Model_Custom_Column_Order_Items_Abstract
    extends BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
{
    public function addItemsToGridCollection($alias, $params, $block, $collection, $firstTime)
    {
        if (!$firstTime && !$block->blcg_isExport()) {
            $ordersIds = array();
            
            foreach ($collection as $order) {
                $ordersIds[] = $order->getId();
            }
            
            $items = $this->_getOrdersItemsCollection($ordersIds, true, 'blcg_custom_column_order_items_list_order_items_collection');
            $orderReflection = new ReflectionClass('Mage_Sales_Model_Order');
            $itemsProperty   = $orderReflection->getProperty('_items');
            
            if (!method_exists($itemsProperty, 'setAccessible')) {
                // PHP < 5.3.0
                $itemsProperty = Mage::getSingleton('customgrid/reflection_property_sales_order_items');
            } else {
                $itemsProperty->setAccessible(true);
            }
            
            foreach ($collection as $order) {
                $orderId = $order->getId();
                $orderItems = clone $items;
                
                foreach ($orderItems as $item) {
                    if ($item->getOrderId() != $orderId) {
                        $orderItems->removeItemByKey($item->getId());
                    } else {
                        $item->setOrder($order);
                    }
                }
                
                $itemsProperty->setValue($order, $orderItems);
            }
        }
        return $this;
    }
    
    protected function _getItemsTable()
    {
        return 'sales/order_item';
    }
    
    protected function _getParentFkFieldName()
    {
        return 'order_id';
    }
    
    protected function _getParentPkFieldName()
    {
        return 'entity_id';
    }
}