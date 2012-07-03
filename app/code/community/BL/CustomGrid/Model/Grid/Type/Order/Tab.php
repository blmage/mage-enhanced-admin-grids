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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Order_Tab
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return in_array(
            $type,
            array(
                'adminhtml/sales_order_view_tab_creditmemos',
                'adminhtml/sales_order_view_tab_invoices',
                'adminhtml/sales_order_view_tab_shipments',
                'adminhtml/sales_order_view_tab_transactions',
            )
        );
    }
    
    protected function _getOrderId()
    {
        if ($order = Mage::registry('current_order')) {
            return $order->getId();
        } else {
            return 0;
        }
    }
    
    protected function _getExportTypes($gridType)
    {
        $exportTypes = parent::_getExportTypes($gridType);
        
        foreach ($exportTypes as $key => $type) {
            if (!isset($type['params'])) {
                $exportTypes[$key]['params'] = array();
            }
            $exportTypes[$key]['params'] = array_merge(
                $exportTypes[$key]['params'],
                array(
                    'order_id' => $this->_getOrderId(),
                )
            );
        }
        
        return $exportTypes;
    }
    
    public function beforeGridExport($format, $grid=null)
    {
        if (is_null($grid)) {
            // Register current order if needed
            if (!Mage::registry('current_order')) {
                $order = Mage::getModel('sales/order');
                
                if ($orderId = $this->_getRequest()->getParam('order_id')) {
                    $order->load($orderId);
                }
                
                Mage::register('current_order', $order);
            }
        }
    }
}