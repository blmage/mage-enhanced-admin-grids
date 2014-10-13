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

class BL_CustomGrid_GridController extends BL_CustomGrid_Controller_Grid_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/customgrid')
            ->_title($this->__('Custom Grids'))
            ->_addBreadcrumb($this->__('Custom Grids'), $this->__('Custom Grids'));
        return $this;
    }
    
    protected function _prepareWindowFormLayout($formType, array $formData, $permissions = null, $anyPermission = true)
    {
        $handles = array('blcg_empty');
        $error = false;
        
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if (!is_null($permissions)) {
                if (!$gridModel->checkUserPermissions($permissions, null, $anyPermission)) {
                    Mage::throwException($this->__('You are not allowed to use this action'));
                }
            }
            
            $handles[] = 'customgrid_grid_form_window_action'; 
            
        } catch (Mage_Core_Exception $e) {
            $handles[] = 'customgrid_grid_form_window_error';
            $error = $e->getMessage();
        }
        
        $this->loadLayout($handles);
        
        if (is_string($error)) {
            if ($errorBlock = $this->getLayout()->getBlock('blcg.grid.form_error')) {
                $errorBlock->setErrorText($error);
            }
        } elseif ($containerBlock = $this->getLayout()->getBlock('blcg.grid.form_container')) {
            $containerBlock->setFormData($formData)
                ->setFormType($formType);
        }
        
        return $this;
    }
    
    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->_initAction()->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout()
            ->getResponse()
            ->setBody(
                $this->getLayout()
                    ->createBlock('customgrid/grid')
                    ->toHtml()
            );
    }
    
    public function reapplyDefaultFilterAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if ($sessionKey = $gridModel->getBlockParamSessionKey($gridModel->getBlockVarName('filter'))) {
                $this->_getSession()->unsetData($sessionKey);
            }
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = 'An error occured while reapplying the default filter';
        }
        
        if ($isSuccess) {
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function saveColumnsAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()
                || !is_array($columns = $this->getRequest()->getParam('columns'))) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            $gridModel->updateColumns($columns);
            $gridModel->save();
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = 'An error occured while saving the columns';
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The columns have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function customColumnsFormAction()
    {
        $this->_prepareWindowFormLayout(
                'custom_columns',
                array(),
                BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS
            )
            ->renderLayout();
    }
    
    public function saveCustomColumnsAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $this->_saveConfigFormFieldsetsStates();
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            $gridModel->updateCustomColumns($this->getRequest()->getParam('custom_columns', array()));
            $gridModel->save();
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = 'An error occured while saving the custom columns';
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The custom columns have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function defaultParamsFormAction()
    {
        if ($defaultParams = $this->getRequest()->getParam('default_params', '')) {
            $defaultParams = Mage::helper('customgrid')->unserializeArray($defaultParams);
        } else {
            $defaultParams = array();
        }
        
        $this->_prepareWindowFormLayout(
                'default_params',
                array('default_params' => $defaultParams),
                BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS
            )
            ->renderLayout();
    }
    
    public function saveDefaultParamsAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $this->_saveConfigFormFieldsetsStates();
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            $appliableParams = $this->getRequest()->getParam('appliable_default_params', array());
            $appliableValues = $this->getRequest()->getParam('appliable_values', array());
            $removableParams = $this->getRequest()->getParam('removable_default_params', array());
            
            if (is_array($appliableParams) && is_array($appliableValues)) {
                foreach ($appliableParams as $key => $isAppliable) {
                    if ($isAppliable && isset($appliableValues[$key])) {
                        $appliableParams[$key] = $appliableValues[$key];
                    } else {
                        unset($appliableParams[$key]);
                    }
                }
            } else {
                $appliableParams = array();
            }
            
            $gridModel->updateDefaultParameters($appliableParams, $removableParams);
            $gridModel->save();
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = 'An error occured while saving the default parameters';
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The default parameters have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function exportFormAction()
    {
        $this->_prepareWindowFormLayout(
                'export',
                array(
                    'total_size'  => $this->getRequest()->getParam('total_size'),
                    'first_index' => $this->getRequest()->getParam('first_index'),
                ),
                BL_CustomGrid_Model_Grid::ACTION_EXPORT_RESULTS
            )
            ->renderLayout();
    }
    
    public function exportCsvAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $data     = $this->getRequest()->getParams();
            $config   = (isset($data['export']) && is_array($data['export']) ? $data['export'] : null);
            $fileName = 'export.csv';
            $this->_prepareDownloadResponse($fileName, $gridModel->exportCsvFile($config));
            
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError('An error occured while exporting grid results');
            
        }
        $this->_redirectReferer();
    }
    
    public function exportExcelAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $data     = $this->getRequest()->getParams();
            $config   = (isset($data['export']) && is_array($data['export']) ? $data['export'] : null);
            $fileName = 'export.xml';
            $this->_prepareDownloadResponse($fileName, $gridModel->exportExcelFile($config));
            
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError('An error occured while exporting grid results');
            
        }
        $this->_redirectReferer();
    }
    
    public function gridInfosAction()
    {
        $this->_prepareWindowFormLayout(
                'grid_infos',
                array(),
                array(
                    BL_CustomGrid_Model_Grid::ACTION_VIEW_GRID_INFOS,
                    BL_CustomGrid_Model_Grid::ACTION_EDIT_FORCED_TYPE,
                )
            )
            ->renderLayout();
    }
    
    public function saveGridInfosAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $this->_saveConfigFormFieldsetsStates();
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if ($this->getRequest()->has('disabled')) {
                $gridModel->setDisabled((bool) $this->getRequest()->getParam('disabled'));
            }
            if ($this->getRequest()->has('forced_type_code')) {
                $gridModel->updateForcedType($this->getRequest()->getParam('forced_type_code'));
            }
            
            $gridModel->save();
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = 'An error occured while saving the grid infos';
        }
        
        if ($isSuccess) {
            $this->_getBlcgSession()->addSuccess($this->__('The grid infos have been successfully updated'));
            $this->_setActionSuccessJsonResponse();
        } else {
            $this->_setActionErrorJsonResponse($resultMessage);
        }
    }
    
    public function editAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectReferer();
            return;
        }
        
        $gridTitle = $this->__('Custom Grid: %s', $gridModel->getBlockType()) . ' - ';
        
        if ($gridModel->getRewritingClassName()) {
            $gridTitle .= $gridModel->getRewritingClassName();
        } else {
            $gridTitle .= $this->__('Base Class');
        }
        
        $this->_initAction()
            ->_title($gridTitle)
            ->_addBreadcrumb($gridTitle, $gridTitle)
            ->renderLayout();
    }
    
    public function saveAction()
    {
        $isSuccess = false;
        $resultMessage = '';
        
        try {
            if (!$this->getRequest()->isPost()) {
                Mage::throwException($this->__('Invalid request'));
            }
            
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $data = $this->getRequest()->getPost();
            
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
            if (isset($data['columns']) && is_array($data['columns'])) {
                $gridModel->updateColumns($data['columns']);
            }
            if (isset($data['disabled'])) {
                $gridModel->setDisabled((bool) $data['disabled']);
            }
            if (isset($data['forced_type_code'])) {
                $gridModel->updateForcedType($data['forced_type_code']);
            }
            if (isset($data['profiles_defaults']) && is_array($data['profiles_defaults'])) {
                $gridModel->updateProfilesDefaults($data['profiles_defaults']);
            }
            if (isset($data['customization_params']) && is_array($data['customization_params'])) {
                $gridModel->updateCustomizationParameters($data['customization_params']);
            }
            if (isset($data['default_params_behaviours']) && is_array($data['default_params_behaviours'])) {
                $gridModel->updateDefaultParametersBehaviours($data['default_params_behaviours']);
            }
            if (isset($data['roles_permissions']) && is_array($data['roles_permissions'])) {
                $gridModel->updateRolesPermissions($data['roles_permissions']);
            }
            
            $gridModel->save();
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = 'An error occured while saving the grid';
        }
        
        if ($isSuccess) {
            $this->_getSession()->addSuccess($this->__('The custom grid has been successfully updated'));
        } else {
            $this->_getSession()->addError($resultMessage);
        }
        if ($isSuccess && isset($gridModel) && $this->getRequest()->getParam('back', false)) {
            $this->_redirect(
                '*/*/edit',
                array(
                    '_current' => true,
                    'grid_id'  => $gridModel->getId(),
                )
            );
        } else {
            $this->_redirectReferer();
        }
    }
    
    public function disableAction()
    {
        try {
            $this->_initGridModel()
                ->setDisabled(true)
                ->save();
            
            $this->_getSession()->addSuccess($this->__('The custom grid has been successfully disabled'));
            $this->_redirect('*/*/');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while disabling the grid'));
        }
        return $this->_redirectReferer();
    }
    
    public function enableAction()
    {
        try {
            $this->_initGridModel()
                ->setDisabled(false)
                ->save();
            
            $this->_getSession()->addSuccess($this->__('The custom grid has been successfully enabled'));
            $this->_redirect('*/*/');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while enabling the grid'));
        }
        return $this->_redirectReferer();
    }
    
    public function deleteAction()
    {
        try {
            $this->_initGridModel()->delete();
            $this->_getSession()->addSuccess($this->__('The custom grid has been successfully deleted'));
            $this->_redirect('*/*/');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while deleting the grid'));
        }
        return $this->_redirectReferer();
    }
    
    protected function _validateMassActionGrids()
    {
        if (!is_array($this->getRequest()->getParam('grid', null))) {
            $this->_getSession()->addError($this->__('Please select grids to update'));
            $this->_redirect('*/*/', array('_current' => true));
            return false;
        }
        return true;
    }
    
    protected function _massDisableGrid($gridId)
    {
        Mage::getSingleton('customgrid/grid')
            ->load($gridId)
            ->setDisabled(true)
            ->save();
        return $this;
    }
    
    public function massDisableAction()
    {
        if (!$this->_validateMassActionGrids()) {
            return;
        }
        
        $gridsIds = $this->getRequest()->getParam('grid');
        $disabledCount = 0;
        $permissionErrorsCount = 0;
        
        try {
            foreach ($gridsIds as $gridId) {
                try {
                    $this->_massDisableGrid($gridId);
                    ++$disabledCount;
                } catch (BL_CustomGrid_Grid_Permission_Exception $e) {
                    ++$permissionErrorsCount;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while disabling a grid'));
        }
        
        if ($disabledCount > 0) {
            $this->_getSession()
                ->addSuccess($this->__('Total of %d grid(s) have been disabled.', $disabledCount));
        }
        if ($permissionErrorsCount > 0) {
            $this->_getSession()
                ->addError($this->__('You were not allowed to disable %d of the chosen grids', $permissionErrorsCount));
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/'));
    }
    
    protected function _massEnableGrid($gridId)
    {
        Mage::getSingleton('customgrid/grid')
            ->load($gridId)
            ->setDisabled(false)
            ->save();
        return $this;
    }
    
    public function massEnableAction()
    {
        if (!$this->_validateMassActionGrids()) {
            return;
        }
        
        $gridsIds = $this->getRequest()->getParam('grid');
        $enabledCount = 0;
        $permissionErrorsCount = 0;
        
        try {
            foreach ($gridsIds as $gridId) {
                try {
                    $this->_massEnableGrid($gridId);
                    ++$enabledCount;
                } catch (BL_CustomGrid_Grid_Permission_Exception $e) {
                    ++$permissionErrorsCount;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while enabling a grid'));
        }
        
        if ($enabledCount > 0) {
            $this->_getSession()
                ->addSuccess($this->__('Total of %d grid(s) have been enabled.', $enabledCount));
        }
        if ($permissionErrorsCount > 0) {
            $this->_getSession()
                ->addError($this->__('You were not allowed to enable %d of the chosen grids', $permissionErrorsCount));
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/'));
    }
    
    public function massDeleteAction()
    {
        if (!$this->_validateMassActionGrids()) {
            return;
        }
        
        $gridsIds = $this->getRequest()->getParam('grid');
        $deletedCount = 0;
        $permissionErrorsCount = 0;
        
        try {
            foreach ($gridsIds as $gridId) {
                try {
                    Mage::getSingleton('customgrid/grid')
                        ->load($gridId)
                        ->delete();
                    
                    ++$deletedCount;
                } catch (BL_CustomGrid_Grid_Permission_Exception $e) {
                    ++$permissionErrorsCount;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while deleting a grid'));
        }
        
        if ($deletedCount > 0) {
            $this->_getSession()
                ->addSuccess($this->__('Total of %d grid(s) have been deleted.', $deletedCount));
        }
        if ($permissionErrorsCount > 0) {
            $this->_getSession()
                ->addError($this->__('You were not allowed to delete %d of the chosen grids', $permissionErrorsCount));
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/'));
    }
    
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid':
            case 'massDelete':
            case 'massDisable':
            case 'massEnable':
                return Mage::getSingleton('admin/session')
                    ->isAllowed('customgrid/administration/view_grids_list');;
        }
        return true;
    }
}
