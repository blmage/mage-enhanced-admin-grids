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

class BL_CustomGrid_Model_Mysql4_Grid_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid');
    }
    
    protected function _afterLoad()
    {
        if ($this->count() > 0) {
            $this->addRolesConfigToResult();
            $this->addUsersConfigToResult();
            $this->addProfilesToResult();
            $this->walk('afterLoad');
        }
        return parent::_afterLoad();
    }
    
    protected function _getGridIds()
    {
        $gridIds = array();
        
        foreach ($this as $grid) {
            $gridIds[] = $grid->getId();
        }
        
        return $gridIds;
    }
    
    protected function _addArrangedValuesToResult(array $values, $key)
    {
        foreach ($values as $gridId => $gridValues) {
            if ($grid = $this->getItemById($gridId)) {
                $grid->setDataUsingMethod($key, $gridValues);
            }
        }
        return $this;
    }
    
    public function addRolesConfigToResult()
    {
        $roles = $this->getResource()->getGridRoles($this->_getGridIds());
        return $this->_addArrangedValuesToResult($roles, 'roles_config');
    }
    
    public function addUsersConfigToResult()
    {
        $users = $this->getResource()->getGridUsers($this->_getGridIds());
        return $this->_addArrangedValuesToResult($users, 'users_config');
    }
    
    public function addProfilesToResult()
    {
        $profiles = $this->getResource()->getGridProfiles($this->_getGridIds());
        return $this->_addArrangedValuesToResult($profiles, 'profiles');
    }
}
