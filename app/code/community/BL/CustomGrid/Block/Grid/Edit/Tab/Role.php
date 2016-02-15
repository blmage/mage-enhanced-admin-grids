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

class BL_CustomGrid_Block_Grid_Edit_Tab_Role extends BL_CustomGrid_Block_Grid_Form_Abstract implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('%s Role (Grid)', $this->getRoleName());
    }
    
    public function getTabTitle()
    {
        return $this->__('%s Role (Grid)', $this->getRoleName());
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
    
    protected function _getFormHtmlIdPrefix()
    {
        return 'role_permissions_' . $this->getRoleId() . '_';
    }
    
    protected function _getFormFieldNameSuffix()
    {
        return 'roles_permissions[' . $this->getRoleId() . ']';
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        
        /** @var $booleanConfigSource BL_CustomGrid_Model_System_Config_Source_Boolean_Config */
        $booleanConfigSource = Mage::getSingleton('customgrid/system_config_source_boolean_config');
        $options = $booleanConfigSource->toOptionArray();
        
        $gridModel  = $this->getGridModel();
        $roleId  = $this->getRoleId();
        $roleConfig = $gridModel->getRoleConfig($roleId);
        
        foreach ($gridModel->getSentry()->getGridActions(true) as $key => $actionsGroup) {
            $fieldset = $form->addFieldset(
                'fieldset_' . $key,
                array(
                    'legend' => $this->__('Permissions: %s', $actionsGroup['label']),
                    'class'  => 'fieldset-wide'
                )
            );
            
            foreach ($actionsGroup['actions'] as $actionKey => $actionLabel) {
                $fieldset->addField(
                    $actionKey,
                    'select',
                    array(
                        'name'   => $actionKey,
                        'label'  => $actionLabel,
                        'title'  => $actionLabel,
                        'values' => $options,
                        'value'  => ($roleConfig ? $roleConfig->getData('permissions/' . $actionKey) : null),
                    )
                );
            }
        }
        
        return $this;
    }
    
    /**
     * Return the current admin role
     * 
     * @return Mage_Admin_Model_Role
     */
    public function getRole()
    {
        return $this->_getData('role');
    }
    
    /**
     * Return the ID of the current admin role
     * 
     * @return int
     */
    public function getRoleId()
    {
        return $this->getDataSetDefault('role_id', $this->getRole()->getId());
    }
    
    /**
     * Return the name of the current admin role
     * 
     * @return string
     */
    public function getRoleName()
    {
        return $this->getDataSetDefault('role_name', $this->getRole()->getRoleName());
    }
}