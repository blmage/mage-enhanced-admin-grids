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

abstract class BL_CustomGrid_Model_Custom_Column_Shipment_Items_Abstract extends
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
            $shipmentsIds = array();
            $ordersIds = array();
            
            foreach ($collection as $shipment) {
                $shipmentsIds[] = $shipment->getId();
                $ordersIds[] = $shipment->getOrderId();
            }
            
            $items = Mage::getModel('sales/order_shipment_item')
                ->getCollection()
                ->addFieldToFilter('parent_id', array('in' => $shipmentsIds))
                ->load();
            
            $orders = $this->_getOrdersCollection($ordersIds);
            $eventName = 'blcg_custom_column_shipment_items_list_order_items_collection';
            $ordersItems = $this->_getOrdersItemsCollection($ordersIds, false, $eventName);
            $propertyName = 'sales/order_shipment::_items';
            $itemsProperty = Mage::helper('customgrid/reflection')->getModelReflectionProperty($propertyName, true);
            
            if ($itemsProperty) {
                foreach ($collection as $shipment) {
                    $orderId = $shipment->getOrderId();
                    $shipmentId = $shipment->getId();
                    $shipmentItems = clone $items;
                    
                    if ($order = $orders->getItemById($orderId)) {
                        $shipment->setOrder($order);
                    }
                    
                    foreach ($shipmentItems as $item) {
                        if ($item->getParentId() != $shipmentId) {
                            $shipmentItems->removeItemByKey($item->getId());
                        } else {
                            $item->setShipment($shipment);
                            
                            if ($orderItem = $ordersItems->getItemById($item->getOrderItemId())) {
                                $item->setOrderItem($orderItem);
                                
                                if ($order) {
                                    $orderItem->setOrder($order);
                                }
                            }
                        }
                    }
                    
                    $itemsProperty->setValue($shipment, $shipmentItems);
                }
            } else {
                foreach ($collection as $creditmemo) {
                    $creditmemo->setData('_blcg_items_init_error', true);
                }
                
                Mage::getSingleton('customgrid/session')
                    ->addError(Mage::helper('customgrid')->__('An error occured while initializing items'));
            }
        }
        return $this;
    }
    
    protected function _getItemsTable()
    {
        return 'sales/shipment_item';
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
