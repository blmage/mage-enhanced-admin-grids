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

class BL_CustomGrid_Block_Grid_Profile_Form_Container extends BL_CustomGrid_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->setTemplate('bl/customgrid/grid/profile/form/container.phtml');
        parent::__construct();
        
        $this->_removeButtons(array('back', 'delete', 'reset'));
        
        $this->_updateButton(
            'save',
            null,
            array(
                'label'      => $this->__('Apply'),
                'onclick'    => 'blcgGridProfileForm.submit();',
                'class'      => 'save',
                'sort_order' => 0,
            )
        );
    }
    
    protected function _prepareLayout()
    {
        return Mage_Adminhtml_Block_Widget_Container::_prepareLayout();
    }
    
    /**
     * Set the form action code
     * 
     * @param string $actionCode Form action code
     * @return BL_CustomGrid_Block_Grid_Profile_Form_Container
     */
    public function setActionCode($actionCode)
    {
        return $this->setData('action_code', $actionCode)
            ->setChild(
                'form',
                $this->getLayout()
                    ->createBlock('customgrid/grid_profile_form_' . $actionCode)
                    ->setActionCode($actionCode)
            );
    }
    
    public function getHeaderText()
    {
        return '';
    }
    
    public function getSaveUrl()
    {
        return '';
    }
    
    public function getProfilesJsObjectName()
    {
        return $this->_getJsObjectName('profiles_js_object_name');
    }
}
