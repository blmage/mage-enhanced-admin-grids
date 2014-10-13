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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Customer_Tab extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array(
            'adminhtml/customer_edit_tab_newsletter_grid',
            'adminhtml/customer_edit_tab_orders',
            'adminhtml/customer_edit_tab_reviews',
            'adminhtml/customer_edit_tab_tag',
            'adminhtml/customer_edit_tab_view_cart',
            'adminhtml/customer_edit_tab_view_orders',
            'adminhtml/customer_edit_tab_view_wishlist',
            'sales/adminhtml_customer_edit_tab_agreement',
        );
    }
    
    protected function _getWebsiteId()
    {
        return $this->_getRequest()->getParam('website_id', null);
    }
    
    protected function _getCustomerId()
    {
        return (($customer = Mage::registry('current_customer')) ? $customer->getId() : 0);
    }
    
    protected function _getExportTypes($blockType)
    {
        $exportTypes = parent::_getExportTypes($blockType);
        $customerId  = $this->_getCustomerId();
        
        foreach ($exportTypes as $exportType) {
            $exportType->setData('url_params/customer_id', $customerId);
        }
        
        return $exportTypes;
    }
    
    public function beforeGridExport($format, Mage_Adminhtml_Block_Widget_Grid $gridBlock = null)
    {
        if (is_null($gridBlock)) {
            if (!Mage::registry('current_customer')) {
                $customer = Mage::getModel('customer/customer');
                
                if ($customerId = $this->_getRequest()->getParam('customer_id')) {
                    $customer->load($customerId);
                }
                
                Mage::register('current_customer', $customer);
            }
        } else {
            if (!$gridBlock->getWebsiteId() && ($websiteId = $this->_getWebsiteId())) {
                $gridBlock->setWebsiteId($websiteId);
            }
            if ($gridBlock->getType() == 'adminhtml/customer_edit_tab_newsletter_grid') {
                if (!Mage::registry('subscriber')) {
                    Mage::register(
                        'subscriber',
                        Mage::getModel('newsletter/subscriber')->loadByCustomer(Mage::registry('current_customer'))
                    );
                }
            } elseif (($gridBlock->getType() == 'adminhtml/customer_edit_tab_tag')
                || ($gridBlock->getType() == 'adminhtml/customer_edit_tab_reviews')) {
                $gridBlock->setCustomerId($this->_getCustomerId());
            }
        }
        return $this;
    }
}
