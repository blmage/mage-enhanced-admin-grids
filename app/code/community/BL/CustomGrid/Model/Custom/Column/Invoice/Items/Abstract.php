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

abstract class BL_CustomGrid_Model_Custom_Column_Invoice_Items_Abstract
    extends BL_CustomGrid_Model_Custom_Column_Sales_Items_Abstract
{
    public function addItemsToGridCollection($alias, $params, $block, $collection, $firstTime)
    {
        if (!$firstTime && !$block->blcg_isExport()) {
            $invoicesIds = array();
            $ordersIds   = array();
            
            foreach ($collection as $invoice) {
                $invoicesIds[] = $invoice->getId();
                $ordersIds[]   = $invoice->getOrderId();
            }
            
            $items = Mage::getModel('sales/order_invoice_item')
                ->getCollection()
                ->addFieldToFilter('parent_id', array('in' => $invoicesIds))
                ->load();
            
            $orders = $this->_getOrdersCollection($ordersIds);
            $ordersItems = $this->_getOrdersItemsCollection($ordersIds, false, 'blcg_custom_column_invoice_items_list_order_items_collection');
            
            $invoiceReflection = new ReflectionClass('Mage_Sales_Model_Order_Invoice');
            $itemsProperty = $invoiceReflection->getProperty('_items');
            
            if (!method_exists($itemsProperty, 'setAccessible')) {
                // PHP < 5.3.0
                $itemsProperty = Mage::getSingleton('customgrid/reflection_property_sales_order_invoice_items');
            } else {
                $itemsProperty->setAccessible(true);
            }
            
            foreach ($collection as $invoice) {
                $invoiceId = $invoice->getId();
                $orderId   = $invoice->getOrderId();
                $invoiceItems = clone $items;
                
                if ($order = $orders->getItemById($orderId)) {
                    $invoice->setOrder($order);
                }
                
                foreach ($invoiceItems as $item) {
                    if ($item->getParentId() != $invoiceId) {
                        $invoiceItems->removeItemByKey($item->getId());
                    } else {
                        $item->setInvoice($invoice);
                        
                        if ($orderItem = $ordersItems->getItemById($item->getOrderItemId())) {
                            $item->setOrderItem($orderItem);
                            
                            if ($order) {
                                $orderItem->setOrder($order);
                            }
                        }
                    }
                }
                
                $itemsProperty->setValue($invoice, $invoiceItems);
            }
        }
        return $this;
    }
    
    protected function _getItemsTable()
    {
        return 'sales/invoice_item';
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