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

class BL_CustomGrid_Model_System_Config_Source_Admin_Role
{
    const CREATOR_ROLE = 'blcg_creator_role';
    
    /**
     * Options cache
     * 
     * @var array|null
     */
    protected $_optionArray = null;
    
    /**
     * @param bool $includeCreatorRole Whether the "Creator Role" option should be included
     * @return array
     */
    public function toOptionArray($includeCreatorRole = true)
    {
        if (is_null($this->_optionArray)) {
            /** @var $collection Mage_Admin_Model_Mysql4_Role_Collection */
            $collection = Mage::getModel('admin/role')->getCollection();
            $collection->setRolesFilter();
            
            foreach ($collection as $role) {
                /** @var $role Mage_Admin_Model_Role */
                $this->_optionArray[] = array(
                    'value' => $role->getRoleId(),
                    'label' => $role->getRoleName(),
                );
            }
        }
        
        $options = $this->_optionArray;
        
        if ($includeCreatorRole) {
            /** @var $helper BL_CustomGrid_Helper_Data */
            $helper = Mage::helper('customgrid');
            
            array_unshift(
                $options,
                array(
                    'value' => self::CREATOR_ROLE,
                    'label' => $helper->__('Creator Role'),
                )
            );
        }
        
        return $options;
    }
}
