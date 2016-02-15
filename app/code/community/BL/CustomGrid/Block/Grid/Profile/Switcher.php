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

class BL_CustomGrid_Block_Grid_Profile_Switcher extends Mage_Adminhtml_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/grid/profile/switcher.phtml');
    }
    
    protected function _toHtml()
    {
        $gridModel = $this->getGridModel();
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS)) {
            $profiles = $this->getProfiles();
            return (count($profiles) > 1 ? parent::_toHtml() : '');
        }
        
        return '';
    }
    
    /**
     * Return the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        return Mage::registry('blcg_grid');
    }
    
    /**
     * Return the current grid profile
     * 
     * @return BL_CustomGrid_Model_Grid_Profile
     */
    public function getGridProfile()
    {
        return Mage::registry('blcg_grid_profile');
    }
    
    /**
     * Return available profiles
     * 
     * @return BL_CustomGrid_Model_Grid_Profile[]
     */
    public function getProfiles()
    {
        if (!$this->hasData('profiles')) {
            $this->setData('profiles', $this->getGridModel()->getProfiles(true, true));
        }
        return $this->_getData('profiles');
    }
    
    /**
     * Return the placeholder usable for profiles IDs
     * 
     * @return string
     */
    public function getProfileIdPlaceholder()
    {
        return '{{profile_id}}';
    }
    
    /**
     * Return the profile switching URL
     * 
     * @return string
     */
    public function getSwitchUrl()
    {
        return $this->getUrl(
            '*/*/*',
            array(
                '_current'   => true,
                'profile_id' => $this->getProfileIdPlaceholder()
            )
        );
    }
}
