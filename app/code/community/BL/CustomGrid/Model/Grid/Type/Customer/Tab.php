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
    
    /**
     * Return the current website ID
     * 
     * @return int
     */
    protected function _getWebsiteId()
    {
        return $this->_getRequest()->getParam('website_id', null);
    }
    
    /**
     * Return the ID of the current customer
     * 
     * @return int
     */
    protected function _getCustomerId()
    {
        return ($customer = Mage::registry('current_customer'))
            /** @var $customer Mage_Customer_Model_Customer */
            ? $customer->getId()
            : 0;
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
    
    /**
     * Apply the website ID corresponding to the current export request to the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Exported grid block
     * @return BL_CustomGrid_Model_Grid_Type_Customer_Tab
     */
    protected function _applyExportCurrentWebsite(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        if (!$gridBlock->getWebsiteId() && ($websiteId = $this->_getWebsiteId())) {
            $gridBlock->setWebsiteId($websiteId);
        }
        return $this;
    }
    
    /**
     * Ensure that a customer is registered for the current export request,
     * and apply it to the grid blocks that need it
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Exported grid block
     * @return BL_CustomGrid_Model_Grid_Type_Customer_Tab
     */
    protected function _applyExportCurrentCustomer(Mage_Adminhtml_Block_Widget_Grid $gridBlock = null)
    {
        if (!$customer = Mage::registry('current_customer')) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer');
            
            if ($customerId = $this->_getRequest()->getParam('customer_id')) {
                $customer->load($customerId);
            }
            
            Mage::register('current_customer', $customer);
        }
        if (!is_null($gridBlock)) {
            if (($gridBlock->getType() == 'adminhtml/customer_edit_tab_tag')
                || ($gridBlock->getType() == 'adminhtml/customer_edit_tab_reviews')) {
                $gridBlock->setCustomerId($customer->getId());
            }
        }
        return $this;
    }
    
    /**
     * Ensure that a newsletter subscriber is registered for the current export request, if one is needed
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Exported grid block
     * @return BL_CustomGrid_Model_Grid_Type_Customer_Tab
     */
    protected function _applyExportCurrentSubscriber(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        if (($gridBlock->getType() == 'adminhtml/customer_edit_tab_newsletter_grid')
            && !Mage::registry('subscriber')) {
            /** @var $subscriber Mage_Newsletter_Model_Subscriber */
            $subscriber = Mage::getModel('newsletter/subscriber');
            $subscriber->loadByCustomer(Mage::registry('current_customer'));
            Mage::register('subscriber', $subscriber);
        }
        return $this;
    }
    
    public function beforeGridExport($format, Mage_Adminhtml_Block_Widget_Grid $gridBlock = null)
    {
        if (is_null($gridBlock)) {
            $this->_applyExportCurrentCustomer();
        } else {
            $this->_applyExportCurrentCustomer($gridBlock);
            $this->_applyExportCurrentWebsite($gridBlock);
            $this->_applyExportCurrentSubscriber($gridBlock);
        }
        return $this;
    }
}
