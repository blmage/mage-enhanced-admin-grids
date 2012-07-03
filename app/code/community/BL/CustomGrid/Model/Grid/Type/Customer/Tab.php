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

class BL_CustomGrid_Model_Grid_Type_Customer_Tab
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return in_array(
            $type,
            array(
                'adminhtml/customer_edit_tab_newsletter_grid',
                'adminhtml/customer_edit_tab_orders',
                'adminhtml/customer_edit_tab_reviews',
                'adminhtml/customer_edit_tab_tag',
                'adminhtml/customer_edit_tab_view_cart',
                'adminhtml/customer_edit_tab_view_orders',
                'adminhtml/customer_edit_tab_view_wishlist',
                'sales/adminhtml_customer_edit_tab_agreement',
            )
        );
    }
    
    protected function _getWebsiteId()
    {
        return $this->_getRequest()->getParam('website_id', null);
    }
    
    protected function _getCustomerId()
    {
        if ($customer = Mage::registry('current_customer')) {
            return $customer->getId();
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
                    'customer_id' => $this->_getCustomerId(),
                )
            );
        }
        
        return $exportTypes;
    }
    
    public function beforeGridExport($format, $grid=null)
    {
        if (is_null($grid)) {
            // Register current customer if needed
            if (!Mage::registry('current_customer')) {
                $customer = Mage::getModel('customer/customer');
                
                if ($customerId = $this->_getRequest()->getParam('customer_id')) {
                    $customer->load($customerId);
                }
                
                Mage::register('current_customer', $customer);
            }
        } else {
            if (!$grid->getWebsiteId()
                && ($websiteId = $this->_getWebsiteId())) {
                // Add needed website ID
                $grid->setWebsiteId($websiteId);
            }
            if ($grid->getType() == 'adminhtml/customer_edit_tab_newsletter_grid') {
                // Register current subscriber if needed
                if (!Mage::registry('subscriber')) {
                    $subscriber = Mage::getModel('newsletter/subscriber')
                        ->loadByCustomer(Mage::registry('current_customer'));
                    Mage::register('subscriber', $subscriber);
                }
            } elseif (($grid->getType() == 'adminhtml/customer_edit_tab_reviews')
                      || ($grid->getType() == 'adminhtml/customer_edit_tab_tag')) {
                // Add needed customer ID
                $grid->setCustomerId($this->_getCustomerId());
            }
        }
    }
}