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
            $blockFilterVarName = $gridModel->getBlockVarName(BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER);
            
            if ($sessionKey = $gridModel->getBlockParamSessionKey($blockFilterVarName)) {
                $this->_getSession()->unsetData($sessionKey);
            }
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occured while reapplying the default filter');
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
            
            Mage::getSingleton('customgrid/grid_column')
                ->updateGridModelColumns($gridModel, $columns)
                ->save();
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occured while saving the columns');
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
        );
        $this->renderLayout();
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
            
            Mage::getSingleton('customgrid/grid_column')
                ->updateGridModelCustomColumns($gridModel, $this->getRequest()->getParam('custom_columns', array()))
                ->save();
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occured while saving the custom columns');
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
        );
        
        $this->renderLayout();
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
            $this->_initGridModel();
            $gridProfile = $this->_initGridProfile();
            
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
            
            $gridProfile->updateDefaultParameters($appliableParams, $removableParams);
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occured while saving the default parameters');
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
        );
        $this->renderLayout();
    }
    
    protected function _exportAction($format, $fileName)
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if (!is_array($config = $this->getRequest()->getParam('export'))) {
                $config = null;
            }
            if ($format == 'csv') {
                $exportOutput = $gridModel->getExporter()->exportToCsv($config);
            } elseif ($format == 'xml') {
                $exportOutput = $gridModel->getExporter()->exportToExcel($config);
            } else {
                $exportOutput = '';
            }
            
            $this->_prepareDownloadResponse($fileName, $exportOutput);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occured while exporting grid results'));
            
        }
        $this->_redirectReferer();
    }
    
    public function exportCsvAction()
    {
        $this->_exportAction('csv', 'export.csv');
    }
    
    public function exportExcelAction()
    {
        $this->_exportAction('xml', 'export.xml');
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
        );
        $this->renderLayout();
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
            $resultMessage = $this->__('An error occured while saving the grid infos');
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
            
            if (!$this->getRequest()->has('profile_id')) {
                $this->getRequest()->setParam('profile_id', $gridModel->getProfileId());
            }
            
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
    
    protected function _applyGridInfosValues(BL_CustomGrid_Model_Grid $gridModel, array $values)
    {
        if (isset($values['disabled'])) {
            $gridModel->setDisabled((bool) $values['disabled']);
        }
        if (isset($values['forced_type_code'])) {
            $gridModel->updateForcedType($values['forced_type_code']);
        }
        return $this;
    }
    
    protected function _saveGridEditValues(
        BL_CustomGrid_Model_Grid $gridModel,
        BL_CustomGrid_Model_Grid_Profile $gridProfile,
        array $data
    ) {
        $updateCallbacks = array(
            'columns' => array(
                'type' => 'grid',
                'callback' => array(Mage::getSingleton('customgrid/grid_column'), 'updateGridModelColumns'),
                'params_before' => array($gridModel),
            ),
            'grid' => array(
                'type' => 'grid',
                'callback' => array($this, '_applyGridInfosValues'),
                'params_before' => array($gridModel),
            ),
            'profiles_defaults' => array(
                'type' => 'grid',
                'callback' => array($gridModel, 'updateProfilesDefaults'),
            ),
            'customization_params' => array(
                'type' => 'grid',
                'callback' => array($gridModel, 'updateCustomizationParameters'),
            ),
            'default_params_behaviours' => array(
                'type' => 'grid',
                'callback' => array($gridModel, 'updateDefaultParametersBehaviours'),
            ),
            'roles_permissions' => array(
                'type' => 'grid',
                'callback' => array($gridModel, 'updateRolesPermissions'),
            ),
            'profile_edit' => array(
                'type' => 'profile',
                'callback' => array($gridProfile, 'update'),
            ),
            'profile_assign' => array(
                'type' => 'profile',
                'callback' => array($gridProfile, 'assign'),
            ),
        );
        
        $transaction = Mage::getModel('customgrid/resource_transaction');
        $transaction->addObject($gridModel);
        
        foreach ($updateCallbacks as $key => $updateCallback) {
            if (isset($data[$key]) && is_array($data[$key])) {
                if (isset($updateCallback['params_before'])) {
                    $params = $updateCallback['params_before'];
                } else {
                    $params = array();
                }
                
                $params[] = $data[$key];
                
                if (isset($updateCallback['params_after'])) {
                    $params = array_merge($params, $updateCallback['params_after']);
                }
                
                if ($updateCallback['type'] == 'profile') {
                    $transaction->addParameterizedCommitCallback($updateCallback['callback'], array($data[$key]));
                } else {
                    call_user_func_array($updateCallback['callback'], $params);
                }
            }
        }
        
        $gridProfile->setIsBulkSaveMode(true);
        $transaction->save();
        $gridProfile->setIsBulkSaveMode(false);
        
        return $this;
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
            $gridProfile = $this->_initGridProfile();
            $data = $this->getRequest()->getPost();
            
            $this->_applyUseConfigValuesToRequestData($data);
            $this->_saveGridEditValues($gridModel, $gridProfile, $data);
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occured while saving the grid');
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
                    ->isAllowed('customgrid/administration/view_grids_list');
        }
        return true;
    }
}
