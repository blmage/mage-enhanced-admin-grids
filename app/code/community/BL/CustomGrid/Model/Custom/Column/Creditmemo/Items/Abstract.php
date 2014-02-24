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

abstract class BL_CustomGrid_Model_Custom_Column_Creditmemo_Items_Abstract
    extends BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
{
    public function addItemsToGridCollection($alias, $params, $block, $collection, $firstTime)
    {
        if (!$firstTime && !$block->blcg_isExport()) {
            $creditmemosIds = array();
            $ordersIds = array();
            
            foreach ($collection as $creditmemo) {
                $creditmemosIds[] = $creditmemo->getId();
                $ordersIds[] = $creditmemo->getOrderId();
            }
            
            $items = Mage::getModel('sales/order_creditmemo_item')
                ->getCollection()
                ->addFieldToFilter('parent_id', array('in' => $creditmemosIds))
                ->load();
            
            $orders = $this->_getOrdersCollection($ordersIds);
            $ordersItems = $this->_getOrdersItemsCollection($ordersIds, false, 'blcg_custom_column_creditmemo_items_list_order_items_collection');
            
            $creditmemoReflection = new ReflectionClass('Mage_Sales_Model_Order_Creditmemo');
            $itemsProperty = $creditmemoReflection->getProperty('_items');
            
            if (!method_exists($itemsProperty, 'setAccessible')) {
                // PHP < 5.3.0
                $itemsProperty = Mage::getSingleton('customgrid/reflection_property_sales_order_creditmemo_items');
            } else {
                $itemsProperty->setAccessible(true);
            }
            
            foreach ($collection as $creditmemo) {
                $creditmemoId = $creditmemo->getId();
                $orderId = $creditmemo->getOrderId();
                $creditmemoItems = clone $items;
                
                if ($order = $orders->getItemById($orderId)) {
                    $creditmemo->setOrder($order);
                }
                
                foreach ($creditmemoItems as $item) {
                    if ($item->getParentId() != $creditmemoId) {
                        $creditmemoItems->removeItemByKey($item->getId());
                    } else {
                        $item->setCreditmemo($creditmemo);
                        
                        if ($orderItem = $ordersItems->getItemById($item->getOrderItemId())) {
                            $item->setOrderItem($orderItem);
                            
                            if ($order) {
                                $orderItem->setOrder($order);
                            }
                        }
                    }
                }
                
                $itemsProperty->setValue($creditmemo, $creditmemoItems);
            }
        }
        return $this;
    }
    
    protected function _getItemsTable()
    {
        return 'sales/creditmemo_item';
    }
    
    protected function _getParentFkFieldName()
    {
        return 'parent_id';
    }
    
    protected function _getParentPkFieldName()
    {
        return 'entity_id';
    }
}