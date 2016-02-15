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

class BL_CustomGrid_Block_Store_Select extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/store/select.phtml');
        $this->setUseConfirm(true);
        $this->setUseAjax(true);
        $this->setUseGridLabel($this->__('Use Grid Store View'));
        $this->setDefaultStoreName($this->__('Default Values'));
    }
    
    protected function _toHtml()
    {
        $html = (!Mage::app()->isSingleStoreMode() ? parent::_toHtml() : '');
        /** @var $jsHelper BL_CustomGrid_Helper_Js */
        $jsHelper = $this->helper('customgrid/js');
        return ($this->getOutputAsJs() ? $jsHelper->prepareHtmlForJsOutput($html, true) : $html);
    }
    
    /**
     * Return the IDs of the websites that should be proposed
     * 
     * @return int[]
     */
    public function getWebsiteIds()
    {
        return $this->getDataSetDefault('website_ids', array());
    }
    
    /**
     * Return the IDs of the stores that should be proposed
     * 
     * @return int[]
     */
    public function getStoreIds()
    {
        return $this->getDataSetDefault('store_ids', array());
    }
    
    /**
     * Return the available websites
     * 
     * @return Mage_Core_Model_Website[]
     */
    public function getWebsites()
    {
        $websites = Mage::app()->getWebsites();
        
        if ($websiteIds = $this->getWebsiteIds()) {
            foreach ($websites as $websiteId => $website) {
                if (!in_array($websiteId, $websiteIds)) {
                    unset($websites[$websiteId]);
                }
            }
        }
        
        return $websites;
    }
    
    /**
     * Return the available store groups for the given website
     * 
     * @param Mage_Core_Model_Website|int $website Website model or ID
     * @return Mage_Core_Model_Store_Group[]
     */
    public function getStoreGroups($website)
    {
        if (!$website instanceof Mage_Core_Model_Website) {
            $website = Mage::app()->getWebsite($website);
        }
        return $website->getGroups();
    }
    
    /**
     * Return the available stores for the given store group
     * 
     * @param Mage_Core_Model_Store_Group $group Store group model or ID
     * @return Mage_Core_Model_Store[]
     */
    public function getStores($group)
    {
        if (!$group instanceof Mage_Core_Model_Store_Group) {
            $group = Mage::app()->getGroup($group);
        }
        
        $stores = $group->getStores();
        
        if ($storeIds = $this->getStoreIds()) {
            foreach ($stores as $storeId => $store) {
                if (!in_array($storeId, $storeIds)) {
                    unset($stores[$storeId]);
                }
            }
        }
        
        return $stores;
    }
}
