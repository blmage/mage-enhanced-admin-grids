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

class BL_CustomGrid_Block_Grid_Edit_Tab_Role extends BL_CustomGrid_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $role    = $this->getRole();
        $roleId  = $role->getId();
        $options = Mage::getSingleton('customgrid/system_config_source_boolean_config')->toOptionArray();
        $gridModel  = $this->getGridModel();
        $roleConfig = $gridModel->getRoleConfig($roleId);
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('role_permissions_' . $roleId . '_');
        $form->setFieldNameSuffix('roles_permissions[' . $roleId . ']');
        
        foreach ($gridModel->getGridActions(true) as $key => $actionsGroup) {
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
        
        $this->setForm($form);
        return parent::_prepareForm();
    }
}