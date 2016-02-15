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

class BL_CustomGrid_Model_System_Config_Source_Admin_User
{
    /**
     * Options cache
     * 
     * @var array|null
     */
    protected $_optionArray = null;
    
    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (is_null($this->_optionArray)) {
            /** @var $collection Mage_Admin_Model_Mysql4_User_Collection */
            $collection = Mage::getModel('admin/user')->getCollection();
            
            foreach ($collection as $user) {
                /** @var $user Mage_Admin_Model_User */
                $this->_optionArray[] = array(
                    'value' => $user->getUserId(),
                    'label' => $user->getUsername(),
                );
            }
        }
        return $this->_optionArray;
    }
}
