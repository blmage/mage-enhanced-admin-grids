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

class BL_CustomGrid_Controller_Grid_Action extends Mage_Adminhtml_Controller_Action
{
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
     * @return this
     */
    protected function _saveConfigFormFieldsetsStates()
    {
        if (is_array($fieldsetsStates = $this->getRequest()->getParam('blcg_form_config_fieldsets_states'))) {
            Mage::helper('customgrid/config_form')->saveFieldsetsStates($fieldsetsStates);
        }
        return $this;
    }
    
    /**
     * Set JSON response
     * 
     * @param string $type Response type
     * @param array $additional Additional values
     * @return array
     */
    protected function _setActionJsonResponse($type, array $additional = array())
    {
        $messagesBlock = $this->getLayout()
            ->createBlock('customgrid/messages')
            ->setIsAjaxMode(true)
            ->setIncludeJsScript(false);
        
        $values = $additional;
        $values['type'] = $type;
        
        if ($this->_getBlcgSession()->hasMessages()) {
            $values['blcgMessagesHtml'] = $messagesBlock->toHtml();
            $values['blcgMessagesWrapperId'] = $messagesBlock->getAjaxModeWrapperId();
        }
        
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($values));
        return $this;
    }
    
    /**
     * Set error JSON response
     * 
     * @param string $errorMessage Error message
     * @return this
     */
    protected function _setActionErrorJsonResponse($errorMessage)
    {
        return $this->_setActionJsonResponse('error', array('message' => $errorMessage));
    }
    
    /**
     * Set success JSON response
     * 
     * @param array $additional Additional values
     * @return this
     */
    protected function _setActionSuccessJsonResponse(array $additional = array())
    {
        return $this->_setActionJsonResponse('success', $additional);
    }
    
    /**
     * Initialize and register the grid model from the current request
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    protected function _initGridModel()
    {
        $gridId = (int) $this->getRequest()->getParam('grid_id');
        $gridModel = Mage::getModel('customgrid/grid');
        
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
     * @return this
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
}
