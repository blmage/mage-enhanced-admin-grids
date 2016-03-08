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

class BL_CustomGrid_Controller_Grid_Action extends Mage_Adminhtml_Controller_Action
{
    /**
     * Return the admin session model
     * 
     * @return Mage_Admin_Model_Session
     */
    protected function _getAdminSession()
    {
        return Mage::getSingleton('admin/session');
    }
    
    /**
     * Return our own session model
     * 
     * @return BL_CustomGrid_Model_Session
     */
    protected function _getBlcgSession()
    {
        return Mage::getSingleton('customgrid/session');
    }
    
    /**
     * Return the config model for grid types
     * 
     * @return BL_CustomGrid_Model_Grid_Type_Config
     */
    protected function _getGridTypeConfig()
    {
        return Mage::getSingleton('customgrid/grid_type_config');
    }
    
    /**
     * Return whether the current request uses Ajax
     * 
     * @return bool
     */
    protected function _isAjaxRequest()
    {
        return $this->getRequest()->getQuery('ajax', false)
            || $this->getRequest()->getQuery('isAjax', false);
    }
    
    /**
     * Save the states (collapsed or not) of some config form fieldsets,
     * if the information is present in the current request
     * 
     * @return BL_CustomGrid_Controller_Grid_Action
     */
    protected function _saveConfigFormFieldsetsStates()
    {
        if (is_array($fieldsetsStates = $this->getRequest()->getParam('blcg_form_config_fieldsets_states'))) {
            /** @var $helper BL_CustomGrid_Helper_Config_Form */
            $helper = Mage::helper('customgrid/config_form');
            $helper->saveFieldsetsStates($fieldsetsStates);
        }
        return $this;
    }
    
    /**
     * Set JSON response
     * 
     * @param string $type Response type
     * @param array $additional Additional values
     * @param bool $withMessages Whether session messages should be included in the response
     * @return array
     */
    protected function _setActionJsonResponse($type, array $additional = array(), $withMessages = true)
    {
        $values = $additional;
        $values['type'] = $type;
        
        if ($withMessages) {
            /** @var $messagesBlock BL_CustomGrid_Block_Messages */
            $messagesBlock = $this->getLayout()->createBlock('customgrid/messages');
            $messagesBlock->setIsAjaxMode(true);
            $messagesBlock->setIncludeJsScript(false);
            
            if ($this->_getBlcgSession()->hasMessages()) {
                $values['blcgMessagesHtml'] = $messagesBlock->toHtml();
                $values['blcgMessagesWrapperId'] = $messagesBlock->getAjaxModeWrapperId();
            }
        }
        
        /** @var $helper Mage_Core_Helper_Data */
        $helper = Mage::helper('core');
        $this->getResponse()->setBody($helper->jsonEncode($values));
        return $this;
    }
    
    /**
     * Set error JSON response
     * 
     * @param string $errorMessage Error message
     * @param bool $withMessages Whether session messages should be included in the response
     * @return BL_CustomGrid_Controller_Grid_Action
     */
    protected function _setActionErrorJsonResponse($errorMessage, $withMessages = true)
    {
        return $this->_setActionJsonResponse('error', array('message' => $errorMessage), $withMessages);
    }
    
    /**
     * Set success JSON response
     * 
     * @param array $additional Additional values
     * @param bool $withMessages Whether session messages should be included in the response 
     * @return BL_CustomGrid_Controller_Grid_Action
     */
    protected function _setActionSuccessJsonResponse(array $additional = array(), $withMessages = true)
    {
        return $this->_setActionJsonResponse('success', $additional, $withMessages);
    }
    
    /**
     * Initialize and register the grid model from the current request
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    protected function _initGridModel()
    {
        /** @var $gridModel BL_CustomGrid_Model_Grid */
        $gridModel = Mage::getModel('customgrid/grid');
        $gridId = (int) $this->getRequest()->getParam('grid_id');
        
        if ($gridId) {
            $gridModel->load($gridId);
        }
        if (!$gridModel->getId()) {
            Mage::throwException($this->__('This custom grid no longer exists'));
        }
        
        Mage::register('blcg_grid', $gridModel);
        return $gridModel;
    }
    
    /**
     * Initialize and register the grid profile from the current request
     * 
     * @param bool $temporary Whether the grid profile should not be set as the active grid profile
     * @param bool $baseIfNone Whether the base profile should be used if no profile is set on the current request
     * @return BL_CustomGrid_Model_Grid_Profile
     */
    protected function _initGridProfile($temporary = true, $baseIfNone = false)
    {
        $profile = false;
        
        if ($gridModel = Mage::registry('blcg_grid')) {
            if (!$baseIfNone && !$this->getRequest()->has('profile_id')) {
                Mage::throwException($this->__('You must specify a grid profile'));
            }
            
            $profileId = (int) $this->getRequest()->getParam('profile_id');
            $gridModel->setProfileId($profileId, $temporary);
            $profile = $gridModel->getProfile();
        }
        if (!$profile) {
            Mage::throwException($this->__('This profile is not available'));
        }
        
        Mage::register('blcg_grid_profile', $profile);
        return $profile;
    }
    
    /**
     * Parse and apply the "Use config" checkboxes values from/to the given request data
     * 
     * @param array $data Request data
     * @return BL_CustomGrid_Controller_Grid_Action
     */
    protected function _applyUseConfigValuesToRequestData(array &$data)
    {
        if (isset($data['use_config']) && is_array($data['use_config'])) {
            foreach ($data['use_config'] as $key => $value) {
                if (is_array($value)) {
                    $data['use_config'][$key] = array_fill_keys(array_keys($value), '');
                } else {
                    unset($data['use_config'][$key]);
                }
            }
            $data = array_merge_recursive($data['use_config'], $data);
            unset($data['use_config']);
        }
        return $this;
    }
    
    /**
     * Initialize the current grid model and profile, check the given permissions,
     * then prepare the layout for a window form
     * 
     * @param string $formHandle Form page layout handle
     * @param string $errorHandle Error page layout handle
     * @param string $errorBlockName Error message block name
     * @param string|array $permissions Required user permission(s)
     * @param bool $anyPermission Whether all the given permissions are required, or just any of them
     * @param array $handles Layout handles
     * @return BL_CustomGrid_Controller_Grid_Action
     */
    protected function _initWindowFormLayout(
        $formHandle,
        $errorHandle,
        $errorBlockName,
        $permissions = null,
        $anyPermission = true,
        array $handles = array('blcg_empty')
    ) {
        $error = false;
        
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if (!is_null($permissions)) {
                if (!$gridModel->checkUserPermissions($permissions, null, $anyPermission)) {
                    Mage::throwException($this->__('You are not allowed to use this action'));
                }
            }
            
            $handles[] = $formHandle;
        } catch (Mage_Core_Exception $e) {
            $handles[] = $errorHandle;
            $error = $e->getMessage();
        }
        
        $this->loadLayout($handles);
        
        if ($error !== false) {
            if ($errorBlock = $this->getLayout()->getBlock($errorBlockName)) {
                /** @var $errorBlock Mage_Adminhtml_Block_Template */
                $errorBlock->setErrorText($error);
            }
        }
        
        return $this;
    }
    
    /**
     * Initialize the current grid model and profile, check the given permissions,
     * then prepare the layout for the given window grid form type
     *
     * @param string $formType Form type
     * @param array $formData Form data
     * @param string|array $permissions Required user permission(s)
     * @param bool $anyPermission Whether all the given permissions are required, or just any of them
     * @param array $handles Layout handles
     * @return BL_CustomGrid_Blcg_GridController
     */
    protected function _prepareWindowGridFormLayout(
        $formType,
        array $formData,
        $permissions = null,
        $anyPermission = true,
        array $handles = array('blcg_empty')
    ) {
        $this->_initWindowFormLayout(
            'adminhtml_blcg_grid_form_window_action',
            'adminhtml_blcg_grid_form_window_error',
            'blcg.grid.form_error',
            $permissions,
            $anyPermission,
            $handles
        );
        
        if ($containerBlock = $this->getLayout()->getBlock('blcg.grid.form_container')) {
            /** @var $containerBlock BL_CustomGrid_Block_Grid_Form_Container */
            $containerBlock->setFormData($formData)->setFormType($formType);
        }
        
        return $this;
    }
    
    /**
     * Initialize the current grid model and profile, check the given permissions,
     * then prepare the layout for the given profile action
     *
     * @param string $actionCode Profile action
     * @param string|array $permissions Required user permission(s)
     * @param bool $anyPermission Whether all the given permissions are required, or just one of them
     * @return BL_CustomGrid_Blcg_Grid_ProfileController
     */
    protected function _prepareWindowProfileFormLayout($actionCode, $permissions = null, $anyPermission = true)
    {
        $this->_initWindowFormLayout(
            'adminhtml_blcg_grid_profile_form_window_action',
            'adminhtml_blcg_grid_profile_form_window_error',
            'blcg.grid_profile.form_error',
            $permissions,
            $anyPermission
        );
        
        if (($gridProfile = Mage::registry('blcg_grid_profile'))
            && ($containerBlock = $this->getLayout()->getBlock('blcg.grid_profile.form_container'))) {
            /**
             * @var BL_CustomGrid_Model_Grid_Profile $gridProfile
             * @var BL_CustomGrid_Block_Grid_Profile_Form_Container $containerBlock
             */
            $containerBlock->setProfileId($gridProfile->getId())->setActionCode($actionCode);
        }
        
        return $this;
    }
    
    /**
     * Validate that one or more values were selected for a mass-action,
     * otherwise force a redirect to the index action
     *
     * @param string $requestKey Values request key
     * @return bool
     */
    protected function _validateMassActionValues($requestKey)
    {
        if (!is_array($this->getRequest()->getParam($requestKey, null))) {
            $this->_getSession()->addError($this->__('Please select values to update'));
            $this->_redirect('*/*/', array('_current' => true));
            return false;
        }
        return true;
    }
}
