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

class BL_CustomGrid_Model_Grid_Type_Order_Tab extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/sales_order_view_tab_creditmemos',
            'adminhtml/sales_order_view_tab_invoices',
            'adminhtml/sales_order_view_tab_shipments',
            'adminhtml/sales_order_view_tab_transactions',
        );
    }
    
    /**
     * Return the ID of the current order
     * 
     * @return int
     */
    protected function _getOrderId()
    {
        return ($order = Mage::registry('current_order'))
            /** @var $order Mage_Sales_Model_Order */
            ? $order->getId()
            : 0;
    }
    
    protected function _getExportTypes($blockType)
    {
        $exportTypes = parent::_getExportTypes($blockType);
        $orderId = $this->_getOrderId();
        
        foreach ($exportTypes as $exportType) {
            $exportType->setData('url_params/order_id', $orderId);
        }
        
        return $exportTypes;
    }
    
    public function beforeGridExport($format, Mage_Adminhtml_Block_Widget_Grid $gridBlock = null)
    {
        if (is_null($gridBlock)) {
            if (!Mage::registry('current_order')) {
                /** @var $order Mage_Sales_Model_Order */
                $order = Mage::getModel('sales/order');
                
                if ($orderId = $this->_getRequest()->getParam('order_id')) {
                    $order->load($orderId);
                }
                
                Mage::register('current_order', $order);
            }
        }
        return $this;
    }
}
