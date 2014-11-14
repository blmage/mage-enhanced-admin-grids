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

class BL_CustomGrid_Model_System_Config_Source_Admin_Role
{
    const CREATOR_ROLE = 'blcg_creator_role';
    
    static protected $_optionArray = null;
    
    public function toOptionArray($includeCreatorRole=true)
    {
        if (is_null(self::$_optionArray)) {
            $collection = Mage::getModel('admin/role')
                ->getCollection()
                ->setRolesFilter();
            
            foreach ($collection as $role) {
                self::$_optionArray[] = array(
                    'value' => $role->getRoleId(),
                    'label' => $role->getRoleName(),
                );
            }
        }
        
        $options = self::$_optionArray;
        
        if ($includeCreatorRole) {
            array_unshift(
                $options,
                array(
                    'value' => self::CREATOR_ROLE,
                    'label' => Mage::helper('customgrid')->__('Creator Role'),
                )
            );
        }
        
        return $options;
    }
}
