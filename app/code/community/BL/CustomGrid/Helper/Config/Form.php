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

class BL_CustomGrid_Helper_Config_Form
    extends Mage_Core_Helper_Abstract
{
    protected function _getAdminUserExtra()
    {
        $adminUser = Mage::getSingleton('admin/session')->getUser();
        $extra = $adminUser->getExtra();
        return (is_array($extra) ? $extra : array());
    }
    
    public function getFieldsetStateSaveUrl()
    {
        return Mage::helper('adminhtml')->getUrl('customgrid/config_form/saveFieldsetState');
    }
    
    public function getFieldsetState($fieldset)
    {
        $extra = $this->_getAdminUserExtra();
        
        if (isset($extra['blcgConfigFieldsetsStates'])
            && isset($extra['blcgConfigFieldsetsStates'][$fieldset])) {
            return $extra['blcgConfigFieldsetsStates'][$fieldset];
        }
        
        return null;
    }
    
    public function saveFieldsetsStates(array $state)
    {
        $extra = $this->_getAdminUserExtra();
        
        if (!isset($extra['blcgConfigFieldsetsStates'])) {
            $extra['blcgConfigFieldsetsStates'] = array();
        }
        foreach ($state as $fieldset => $fieldsetState) {
            $extra['blcgConfigFieldsetsStates'][$fieldset] = $fieldsetState;
        }
        
        Mage::getSingleton('admin/session')->getUser()->saveExtra($extra);
        return $this;
    }
}