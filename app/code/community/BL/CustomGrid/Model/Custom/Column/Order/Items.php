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
 
class BL_CustomGrid_Model_Custom_Column_Order_Items
    extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    public function addItemsToGridCollection($alias, $params, $block, $collection, $firstTime)
    {
        if (!$firstTime && !$block->blcg_isExport()) {
            $ordersIds = array();
            
            foreach ($collection as $order) {
                $ordersIds[] = $order->getId();
            }
            
            $items = Mage::getModel('sales/order_item')
                ->getCollection()
                ->addFieldToFilter('order_id', array('in' => $ordersIds))
                ->filterByParent()
                ->load();
            
            $orderReflection = new ReflectionClass('Mage_Sales_Model_Order');
            $itemsProperty   = $orderReflection->getProperty('_items');
            $itemsProperty->setAccessible(true);
            
            foreach ($collection as $order) {
                $orderItems = clone $items;
                
                foreach ($orderItems as $item) {
                    if ($item->getOrderId() != $order->getId()) {
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
    
    protected function _applyToGridCollection($collection, $block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $block->blcg_addCollectionCallback(
            self::GC_EVENT_AFTER_PREPARE,
            array($this, 'addItemsToGridCollection'),
            array($alias, $params),
            true
        );
        return $this;
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array(
            'filter'   => false,
            'renderer' => $this->getModelParam('renderer'),
        );
    }
}