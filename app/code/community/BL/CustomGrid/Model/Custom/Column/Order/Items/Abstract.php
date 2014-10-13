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
 
abstract class BL_CustomGrid_Model_Custom_Column_Order_Items_Abstract extends
    BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
{
    public function addItemsToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $firstTime
    ) {
        if (!$firstTime && !$gridBlock->blcg_isExport()) {
            $ordersIds = array();
            
            foreach ($collection as $order) {
                $ordersIds[] = $order->getId();
            }
            
            $eventName = 'blcg_custom_column_order_items_list_order_items_collection';
            $items = $this->_getOrdersItemsCollection($ordersIds, true, $eventName);
            $propertyName  = 'sales/order::_items';
            $itemsProperty = Mage::helper('customgrid/reflection')->getModelReflectionProperty($propertyName, true);
            
            if ($itemsProperty) {
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
            } else {
                foreach ($collection as $order) {
                    $order->setData('_blcg_items_init_error', true);
                }
                
                Mage::getSingleton('customgrid/session')
                    ->addError(Mage::helper('customgrid')->__('An error occured while initializing items'));
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
