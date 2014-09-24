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

class BL_CustomGrid_Grid_ProfileController
    extends BL_CustomGrid_Controller_Grid_Action
{
    const PERMISSIONS_MODE_OR  = 'or';
    const PERMISSIONS_MODE_AND = 'and';
    
    protected function _setActionSuccessJsonResponse(array $actions=array())
    {
        return parent::_setActionSuccessJsonResponse(array('actions' => $actions));
    }
    
    protected function _prepareFormLayout($actionCode, $permissions=null, $permissionsMode=self::PERMISSIONS_MODE_OR)
    {
        $handles = array('blcg_empty');
        $error = false;
        
        try {
            $gridModel = $this->_initGridModel();
            $gridProfile = $this->_initGridProfile();
            
            if (!is_null($permissions)) {
                $isAllowed = false;
                
                if (!is_array($permissions)) {
                    $permissions = array($permissions);
                }
                
                foreach ($permissions as $permission) {
                    if ($gridModel->checkUserActionPermission($permission)) {
                        $isAllowed = true;
                        
                        if ($permissionsMode == self::PERMISSIONS_MODE_OR) {
                            break;
                        }
                    } elseif ($permissionsMode == self::PERMISSIONS_MODE_AND) {
                        $isAllowed = false;
                        break;
                    }
                }
                
                if (!$isAllowed) {
                    Mage::throwException($this->__('You are not allowed to use this action'));
                }
            }
            
            $handles[] = 'customgrid_grid_profile_form_window_action'; 
            
        } catch (Mage_Core_Exception $e) {
            $handles[] = 'customgrid_grid_profile_form_window_error';
            $error = $e->getMessage();
        }
        
        $this->loadLayout($handles);
        
        if (is_string($error)) {
            if ($errorBlock = $this->getLayout()->getBlock('blcg.grid_profile.form_error')) {
                $errorBlock->setErrorText($error);
            }
        } elseif ($containerBlock = $this->getLayout()->getBlock('blcg.grid_profile.form_container')) {
            $containerBlock->setProfileId($gridProfile->getId())
                ->setActionCode($actionCode)
                ->setProfilesBarJsObjectName($this->getRequest()->getParam('js_object_name'));
        }
        
        return $this;
    }
    
    public function goToAction()
    {
        try {
            $this->_initGridModel();
            $this->_initGridProfile(false);
            $this->_setActionSuccessJsonResponse(array(array('type' => 'reload')));
        } catch (Mage_Core_Exception $e) {
            $this->_setActionErrorJsonResponse($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_setActionErrorJsonResponse($this->__('Could not go to the specified profile'));
        }
    }
    
    public function defaultFormAction()
    {
        $this->_prepareFormLayout(
                'default',
                array(
                    BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE,
                    BL_CustomGrid_Model_Grid::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE,
                )
            )
            ->renderLayout();
    }
    
    public function defaultAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel   = $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                $newValues   = $gridModel->chooseProfileAsDefault($gridProfile->getId(), $data);
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully chosen as default'));
                $this->_setActionSuccessJsonResponse();
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not choose as default the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function copyToNewFormAction()
    {
        $this->_prepareFormLayout('copy_new', BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_NEW);
        $this->renderLayout();
    }
    
    public function copyToNewAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel = $this->_initGridModel();
                $copiedProfile = $this->_initGridProfile();
                $newProfileId  = $gridModel->copyProfileToNew($copiedProfile->getId(), $data);
                $actions = array();
                
                if ($gridModel->isAvailableProfile($newProfileId)) {
                    $actions[] = array(
                        'type'    => 'create',
                        'profile' => array(
                            'id'        => $newProfileId,
                            'name'      => trim($data['name']),
                            'isBase'    => false,
                            'isCurrent' => false,
                        ),
                    );
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully copied'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not copy the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function copyToExistingFormAction()
    {
        $this->_prepareFormLayout('copy_existing', BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_EXISTING);
        $this->renderLayout();
    }
    
    public function copyToExistingAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel = $this->_initGridModel();
                $copiedProfile = $this->_initGridProfile();
                
                if (isset($data['to_profile_id'])) {
                    $toProfileId = (int) $data['to_profile_id'];
                    unset($data['to_profile_id']);
                } else {
                    Mage::throwException($this->__('Invalid request'));
                }
                
                $gridModel->copyProfileToExisting($copiedProfile->getId(), $toProfileId, $data);
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully copied')); 
                $this->_setActionSuccessJsonResponse();
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not copy the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function editFormAction()
    {
        $this->_prepareFormLayout('edit', BL_CustomGrid_Model_Grid::ACTION_EDIT_PROFILES);
        $this->renderLayout();
    }
    
    public function editAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel   = $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                $gridModel->updateProfile($gridProfile->getId(), $data);
                
                $actions = array(array(
                    'type'        => 'rename',
                    'profileId'   => $gridProfile->getId(),
                    'profileName' => trim($data['name']),
                ));
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully edited'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not edit the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function assignFormAction()
    {
        $this->_prepareFormLayout('assign', BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES);
        $this->renderLayout();
    }
    
    public function assignAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel   = $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                $isSessionProfile = ($gridProfile->getId() === $gridModel->getSessionProfileId());
                $gridModel->assignProfile($gridProfile->getId(), $data);
                $actions = array();
                
                if (!$gridModel->isAvailableProfile($gridProfile->getId())) {
                    $actions[] = array(
                        'type'      => 'delete',
                        'profileId' => $gridProfile->getId()
                    );
                    
                    if ($isSessionProfile) {
                        $actions[] = array('type' => 'reload');
                    }
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully assigned'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not assign the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
    
    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            try {
                $data = $this->getRequest()->getPost();
                $gridModel   = $this->_initGridModel();
                $gridProfile = $this->_initGridProfile();
                $isSessionProfile = ($gridProfile->getId() === $gridModel->getSessionProfileId());
                $gridModel->deleteProfile($gridProfile->getId());
                
                $actions = array(array(
                    'type'      => 'delete',
                    'profileId' => $gridProfile->getId(),
                ));
                
                if ($isSessionProfile) {
                    $actions[] = array('type' => 'reload');
                }
                
                $this->_getBlcgSession()->addSuccess($this->__('The profile has been successfully deleted'));
                $this->_setActionSuccessJsonResponse($actions);
                
            } catch (Mage_Core_Exception $e) {
                $this->_setActionErrorJsonResponse($e->getMessage());
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_setActionErrorJsonResponse($this->__('Could not delete the specified profile'));
            }
        } else {
            $this->_setActionErrorJsonResponse($this->__('Invalid request'));
        }
    }
}
