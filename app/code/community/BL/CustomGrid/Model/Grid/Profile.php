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

class BL_CustomGrid_Model_Grid_Profile
    extends BL_CustomGrid_Object
{
    public function getId()
    {
        return (int) $this->_getData('profile_id');
    }
    
    public function getGridModel($graceful=false)
    {
        if (($gridModel = $this->_getData('grid_model')) instanceof BL_CustomGrid_Model_Grid) {
            return $gridModel;
        } elseif (!$graceful) {
            Mage::throwException(Mage::helper('customgrid')->__('Invalid grid model'));
        }
        return null;
    }
    
    public function isBase()
    {
        return ($this->getId() === BL_CustomGrid_Model_Grid::BASE_PROFILE_ID);
    }
    
    public function isCurrent()
    {
        return ($this->getId() === $this->getGridModel()->getProfileId());
    }
    
    public function getAssignedToRolesIds()
    {
        if (!$this->hasData('assigned_to_roles_ids')) {
            $rolesIds = array();
            $rolesConfig = $this->getGridModel()->getRolesConfig();
            
            foreach ($rolesConfig as $roleId => $roleConfig) {
                if (is_array($assignedProfilesIds = $roleConfig->getData('assigned_profiles_ids'))
                    && in_array($this->getId(), $assignedProfilesIds, true)) {
                    $rolesIds[] = $roleId;
                }
            }
            
            $this->setData('assigned_to_roles_ids', $rolesIds);
        }
        return $this->_getData('assigned_to_roles_ids');
    }
}
