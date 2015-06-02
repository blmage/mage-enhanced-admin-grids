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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Helper_Config_Form extends Mage_Core_Helper_Abstract
{
    /**
     * Return the current admin user
     * 
     * @return Mage_Admin_Model_User
     */
    protected function _getAdminUser()
    {
        /** @var $session Mage_Admin_Model_Session */
        $session = Mage::getSingleton('admin/session');
        return $session->getUser();
    }
    
    /**
     * Return the extra data from the current admin user
     *
     * @return array
     */
    protected function _getAdminUserExtra()
    {
        $extra = $this->_getAdminUser()->getExtra();
        return (is_array($extra) ? $extra : array());
    }
    
    /**
     * Return the URL that must be used to save the states of some fieldsets
     *
     * @return string
     */
    public function getFieldsetStateSaveUrl()
    {
        /** @var $helper Mage_Adminhtml_Helper_Data */
        $helper = Mage::helper('adminhtml');
        return $helper->getUrl('customgrid/config_form/saveFieldsetState');
    }
    
    /**
     * Return the state of the given fieldset
     * 
     * @param string $fieldset Fieldset key
     * @return mixed
     */
    public function getFieldsetState($fieldset)
    {
        $extra = $this->_getAdminUserExtra();
        
        if (isset($extra['blcgConfigFieldsetsStates'])
            && isset($extra['blcgConfigFieldsetsStates'][$fieldset])) {
            return $extra['blcgConfigFieldsetsStates'][$fieldset];
        }
        
        return null;
    }
    
    /**
     * Save the given fieldsets state for the current admin user
     * 
     * @param array $state Fieldsets state
     * @return BL_CustomGrid_Helper_Config_Form
     */
    public function saveFieldsetsStates(array $state)
    {
        $extra = $this->_getAdminUserExtra();
        
        if (!isset($extra['blcgConfigFieldsetsStates'])) {
            $extra['blcgConfigFieldsetsStates'] = array();
        }
        foreach ($state as $fieldset => $fieldsetState) {
            $extra['blcgConfigFieldsetsStates'][$fieldset] = $fieldsetState;
        }
        
        $this->_getAdminUser()->saveExtra($extra);
        return $this;
    }
}
