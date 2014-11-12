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
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)) {
            $profiles = $this->getProfiles();
            return (count($profiles) > 1 ? parent::_toHtml() : '');
        }
        
        return '';
    }
    
    public function getGridModel()
    {
        return Mage::registry('blcg_grid');
    }
    
    public function getCurrentGridProfile()
    {
        return Mage::registry('blcg_grid_profile');
    }
    
    public function getProfiles()
    {
        if (!$this->hasData('profiles')) {
            $this->setData('profiles', $this->getGridModel()->getProfiles(true, true));
        }
        return $this->_getData('profiles');
    }
    
    public function getProfileIdPlaceholder()
    {
        return '{{profile_id}}';
    }
    
    public function getSwitchUrl()
    {
        return $this->getUrl(
            '*/*/*',
            array(
                '_current' => true,
                'profile_id' => $this->getProfileIdPlaceholder()
            )
        );
    }
}
