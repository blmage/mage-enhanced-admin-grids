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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Grid_Profile_Form_Default extends BL_CustomGrid_Block_Grid_Profile_Form_Abstract
{
    protected function _getFormType()
    {
        return 'default';
    }
    
    /**
     * Add users-related fields to the given fieldset
     * 
     * @param Varien_Data_Form_Element_Fieldset $fieldset Fieldset
     * @return BL_CustomGrid_Block_Grid_Profile_Form_Default
     */
    protected function _addUsersFieldsToFieldset(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        $gridModel   = $this->getGridModel();
        $profileId   = $this->getGridProfile()->getId();
        $sessionUser = $gridModel->getSessionUser();
        $permissions = array(
            'own_user' => BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE,
            'other_users' => BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE,
        );
        
        if ($gridModel->checkUserActionPermission($permissions['other_users'])) {
            $usersValues   = $this->_getAdminUsersOptionArray();
            $defaultValues = array();
             
            foreach ($usersValues as $key => $userValue) {
                if ($userValue['value'] == $sessionUser->getId()) {
                    if ($gridModel->checkUserActionPermission($permissions['own_user'])) {
                        $usersValues[$key]['label'] .= ' ' . $this->__('(me)');
                    } else {
                        unset($usersValues[$key]);
                        continue;
                    }
                }
                if ($gridModel->getUserDefaultProfileId($userValue['value']) === $profileId) {
                    $defaultValues[] = $userValue['value'];
                }
            }
            
            array_unshift(
                $usersValues,
                array(
                    'value' => '',
                    'label' => $this->__('None'),
                )
            );
            
            $fieldset->addField(
                'users',
                'multiselect',
                array(
                    'name'   => 'users',
                    'label'  => $this->__('Users'),
                    'values' => $usersValues,
                    'value'  => (empty($defaultValues) ? array('') : $defaultValues),
                    'class'  => 'validate-select',
                )
            );
        } elseif ($gridModel->checkUserActionPermission($permissions['own_user'])) {
            $fieldset->addField(
                'users',
                'select',
                array(
                    'name'   => 'users[]',
                    'label'  => $this->__('Me (%s)', $sessionUser->getUsername()),
                    'values' => array(
                            $sessionUser->getId() => $this->__('Yes'),
                            '' => $this->__('No'),
                        ),
                    'value'  => ($gridModel->getUserDefaultProfileId() === $profileId ? $sessionUser->getId() : ''),
                )
            );
        }
        
        return $this;
    }
    
    /**
     * Add roles-related fields to the given fieldset
     * 
     * @param Varien_Data_Form_Element_Fieldset $fieldset Fieldset
     * @return BL_CustomGrid_Block_Grid_Profile_Form_Default
     */
    protected function _addRolesFieldsToFieldset(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        $gridModel   = $this->getGridModel();
        $profileId   = $this->getGridProfile()->getId();
        $sessionRole = $gridModel->getSessionRole();
        $permissions = array(
            'own_role'   => BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE,
            'other_roles'=> BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE,
        );
        
        if ($gridModel->checkUserActionPermission($permissions['other_roles'])) {
            $rolesValues   = $this->_getAdminRolesOptionArray(false);
            $defaultValues = array();
             
            foreach ($rolesValues as $key => $roleValue) {
                if ($roleValue['value'] == $sessionRole->getId()) {
                    if ($gridModel->checkUserActionPermission($permissions['own_role'])) {
                        $rolesValues[$key]['label'] .= ' ' . $this->__('(me)');
                    } else {
                        unset($rolesValues[$key]);
                        continue;
                    }
                }
                if ($gridModel->getRoleDefaultProfileId($roleValue['value']) === $profileId) {
                    $defaultValues[] = $roleValue['value'];
                }
            }
            
            array_unshift(
                $rolesValues,
                array(
                    'value' => '',
                    'label' => $this->__('None'),
                )
            );
            
            $fieldset->addField(
                'roles',
                'multiselect',
                array(
                    'name'   => 'roles',
                    'label'  => $this->__('Roles'),
                    'values' => $rolesValues,
                    'value'  => (empty($defaultValues) ? array('') : $defaultValues),
                    'class'  => 'validate-select',
                )
            );
        } elseif ($gridModel->checkUserActionPermission($permissions['own_role'])) {
            $fieldset->addField(
                'roles',
                'select',
                array(
                    'name'   => 'roles[]',
                    'label'  => $this->__('My Role (%s)', $sessionRole->getRoleName()),
                    'values' => array(
                            $sessionRole->getId() => $this->__('Yes'),
                            '' => $this->__('No'),
                        ),
                    'value'  => (($gridModel->getRoleDefaultProfileId() === $profileId) ? $sessionRole->getId() : ''),
                )
            );
        }
        
        return $this;
    }
    
    /**
     * Add global fields to the given fieldset
     * 
     * @param Varien_Data_Form_Element_Fieldset $fieldset Fieldset
     * @return BL_CustomGrid_Block_Grid_Profile_Form_Default
     */
    protected function _addGlobalFieldsToFieldset(Varien_Data_Form_Element_Fieldset $fieldset)
    {
        $gridModel = $this->getGridModel();
        $profileId = $this->getGridProfile()->getId();
        
        $hasUserPermission = $gridModel->checkUserActionPermission(
            BL_CustomGrid_Model_Grid_Sentry::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE
        );
        
        if ($hasUserPermission) {
            $fieldset->addField(
                'global',
                'select',
                array(
                    'name'     => 'global',
                    'label'    => $this->__('Global'),
                    'required' => true,
                    'values'   => $this->_getYesNoOptionArray(),
                    'value'    => ($profileId === $gridModel->getGlobalDefaultProfileId() ? 1 : 0),
                )
            );
        }
        
        return $this;
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        $fieldset = $form->addFieldset(
            'values',
            array(
                'legend' => $this->__('By Default For'),
                'class'  => 'fielset-wide',
            )
        );
        
        $noteSummary = 'Priorities for the current profile choice (the first value that is set and available will be '
            . 'used) :';
        
        $fieldset->addField(
            'priorities_note',
            'note',
            array(
                'after_element_html' => '<div class="blcg-form-note-text">'
                        . $this->__($noteSummary)
                        . '<ul>'
                            . '<li>' . $this->__('Session current profile') . '</li>'
                            . '<li>' . $this->__('User default profile') . '</li>'
                            . '<li>' . $this->__('Role default profile') . '</li>'
                            . '<li>' . $this->__('Global default profile') . '</li>'
                            . '<li>' . $this->__('Base profile') . '</li>'
                            . '<li>' . $this->__('First available profile (undetermined order)') . '</li>'
                        . '</ul>'
                    . '</div>',
            )
        );
        
        $this->_addUsersFieldsToFieldset($fieldset)
            ->_addRolesFieldsToFieldset($fieldset)
            ->_addGlobalFieldsToFieldset($fieldset);
        
        return $this;
    }
}
