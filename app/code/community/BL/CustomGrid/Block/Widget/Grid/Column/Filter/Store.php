<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Store extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    /**
     * Return the HTML content for the options corresponding to the given stores that belong to the given store group
     * 
     * @param Mage_Core_Model_Website $website Current website
     * @param Mage_Core_Model_Store_Group $storeGroup Current store group
     * @param Mage_Core_Model_Store[] $stores All stores
     * @param bool $shownWebsite Whether the website has been displayed
     * @param bool $shownStoreGroup Whether the store group has been displayed
     * @return string
     */
    protected function _getStoresOptionsHtml(
        Mage_Core_Model_Website $website,
        Mage_Core_Model_Store_Group $storeGroup,
        array $stores,
        &$shownWebsite,
        &$shownStoreGroup
    ) {
        $html   = '';
        $value  = $this->getValue();
        $spaces = str_repeat('&nbsp;', 4);
        
        foreach ($stores as $store) {
            if ($store->getGroupId() != $storeGroup->getId()) {
                continue;
            }
            if (!$shownWebsite) {
                $shownWebsite = true;
                $html .= '<optgroup label="' . $this->htmlEscape($website->getName()) . '"></optgroup>';
            }
            if (!$shownStoreGroup) {
                $shownStoreGroup = true;
                $html .= '<optgroup label="' . $this->htmlEscape($storeGroup->getName()) . '">';
            }
            
            $html .= '<option value="' . $store->getId() . '"'
                . ($value == $store->getId() ? ' selected="selected" ' : '') . '>'
                . $spaces . $store->getName()
                . '</option>';
        }
        
        if ($shownStoreGroup) {
            $html .= '</optgroup>';
        }
        
        return $html;
    }
    
    /**
     * Return the HTML content for the options corresponding to the given store groups and their stores
     * that belong to the given website
     * 
     * @param Mage_Core_Model_Website $website Current website
     * @param Mage_Core_Model_Store_Group[] $storeGroups All store groups
     * @param Mage_Core_Model_Store[] $stores All stores
     * @param bool $shownWebsite Whether the website has been displayed
     * @return string
     */
    protected function _getStoreGroupsOptionsHtml(
        Mage_Core_Model_Website $website,
        array $storeGroups,
        array $stores,
        &$shownWebsite
    ) {
        $html = '';
        
        foreach ($storeGroups as $storeGroup) {
            $shownStoreGroup = false;
            
            if ($storeGroup->getWebsiteId() != $website->getId()) {
                continue;
            }
            
            $html .= $this->_getStoresOptionsHtml($website, $storeGroup, $stores, $shownWebsite, $shownStoreGroup);
        }
        
        return $html;
    }
    
    /**
     * Return the HTML content for the options corresponding to the available websites
     * 
     * @return string
     */
    protected function _getWebsitesOptionsHtml()
    {
        $storeModel  = Mage::getSingleton('adminhtml/system_store');
        $websites    = $storeModel->getWebsiteCollection();
        $storeGroups = $storeModel->getGroupCollection();
        $stores      = $storeModel->getStoreCollection();
        $html = '';
        
        foreach ($websites as $website) {
            $shownWebsite = false;
            $html .= $this->_getStoreGroupsOptionsHtml($website, $storeGroups, $stores, $shownWebsite);
        }
        
        return $html;
    }
    
    public function getHtml()
    {
        $value = $this->getValue();
        
        $html = '<select name="' . $this->_getHtmlName() . '" ' . $this->getColumn()->getValidateClass() . '>'
            . '<option value=""'  . (!$value ? ' selected="selected"' : '') . '></option>'
            . '<option value="0"' . (strval($value) === '0' ? ' selected="selected"' : '') . '>'
            . $this->__('All Store Views')
            . '</option>'
            . $this->_getWebsitesOptionsHtml()
            . '<option value="_deleted_"' . ($value == '_deleted_' ? ' selected="selected"' : '') . '>'
            . $this->__('[ deleted ]')
            . '</option>'
            . '</select>';
        
        return $html;
    }
    
    public function getCondition()
    {
        if (is_null($value = $this->getValue())) {
            return null;
        }
        if ($value == '_deleted_') {
            return array('null' => true);
        }
        return array('eq' => $value);
    }
}
