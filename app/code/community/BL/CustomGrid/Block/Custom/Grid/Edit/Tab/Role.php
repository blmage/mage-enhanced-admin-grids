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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Custom_Grid_Edit_Tab_Role
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $grid   = Mage::registry('custom_grid');
        $role   = $this->getRole();
        $roleId = $role->getId();
        $config = $grid->getRolesConfig($roleId);
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('role_'.$roleId.'_');
        $form->setFieldNameSuffix('role['.$roleId.']');
        
        // @todo for profiles
        /*
        $fieldset = $form->addFieldset('role_'.$roleId.'_fieldset_settings', array(
            'legend' => $this->__('Settings', $role->getRoleName()),
            'class'  => 'fielset-wide'
        ));
        */
        
        $fieldset = $form->addFieldset('role_'.$roleId.'_fieldset_permissions', array(
            'legend' => $this->__('Permissions', $role->getRoleName()),
            'class'  => 'fieldset-wide'
        ));
        
        $options = Mage::getModel('customgrid/system_config_source_boolean_config')->getOptions();
        
        foreach ($grid->getGridActions() as $actionId => $actionLabel) {
            $fieldset->addField('permissions['.$actionId.']', 'select', array(
                'name'   => 'permissions['.$actionId.']',
                'label'  => $actionLabel,
                'title'  => $actionLabel,
                'values' => $options,
                'value'  => (isset($config['permissions'][$actionId]) ? $config['permissions'][$actionId] : null),
            ));
        }
        
        $this->setForm($form);
        return parent::_prepareForm();
    }
}