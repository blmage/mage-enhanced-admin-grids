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

class BL_CustomGrid_Blcg_GridController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Return grid column model
     * 
     * @return BL_CustomGrid_Model_Grid_Column
     */
    protected function _getGridColumnModel()
    {
        return Mage::getSingleton('customgrid/grid_column');
    }
    
    /**
     * Load layout and initialize active menu, title and breadcrumbs for a common system page action
     * 
     * @return BL_CustomGrid_GridController
     */
    protected function _initSystemPageAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/customgrid')
            ->_title($this->__('Custom Grids'))
            ->_addBreadcrumb($this->__('Custom Grids'), $this->__('Custom Grids'));
        return $this;
    }
    
    /**
     * Initialize the current grid model and profile, check the given permissions,
     * then prepare the layout for the given window form type
     * 
     * @param string $formType Form type
     * @param array $formData Form data
     * @param string|array $permissions Required user permission(s)
     * @param bool $anyPermission Whether all the given permissions are required, or just one of them
     * @return BL_CustomGrid_GridController
     */
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
            
            $handles[] = 'adminhtml_blcg_grid_form_window_action'; 
            
        } catch (Mage_Core_Exception $e) {
            $handles[] = 'adminhtml_blcg_grid_form_window_error';
            $error = $e->getMessage();
        }
        
        $this->loadLayout($handles);
        
        if (is_string($error)) {
            if ($errorBlock = $this->getLayout()->getBlock('blcg.grid.form_error')) {
                /** @var $errorBlock Mage_Adminhtml_Block_Template */
                $errorBlock->setErrorText($error);
            }
        } elseif ($containerBlock = $this->getLayout()->getBlock('blcg.grid.form_container')) {
            /** @var $containerBlock BL_CustomGrid_Block_Grid_Form_Container */
            $containerBlock->setFormData($formData)->setFormType($formType);
        }
        
        return $this;
    }
    
    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->_initSystemPageAction()->renderLayout();
    }
    
    public function gridAction()
    {
        $this->loadLayout()->renderLayout();
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
            $resultMessage = $this->__('An error occurred while reapplying the default filter');
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
            $this->_getGridColumnModel()->updateGridModelColumns($gridModel, $columns)->save();
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the columns');
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
            BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS
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
            $customColumns = $this->getRequest()->getParam('custom_columns', array());
            $this->_getGridColumnModel()->updateGridModelCustomColumns($gridModel, $customColumns)->save();
            
            $isSuccess = true;
            
        } catch (Mage_Core_Exception $e) {
            $resultMessage = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $resultMessage = $this->__('An error occurred while saving the custom columns');
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
            /** @var $helper BL_CustomGrid_Helper_Data */
            $helper = Mage::helper('customgrid');
            $defaultParams = $helper->unserializeArray($defaultParams);
        } else {
            $defaultParams = array();
        }
        
        $this->_prepareWindowFormLayout(
            'default_params',
            array('default_params' => $defaultParams),
            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS
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
            $resultMessage = $this->__('An error occurred while saving the default parameters');
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
                'additional_params' => $this->getRequest()->getParam('additional_params', array()),
            ),
            BL_CustomGrid_Model_Grid_Sentry::ACTION_EXPORT_RESULTS
        );
        $this->renderLayout();
    }
    
    /**
     * Restore in the request the additional parameters from the given export config
     * 
     * @param array $exportConfig Export config values
     */
    protected function _restoreExportAdditionalParams(array $exportConfig)
    {
        if (isset($exportConfig['additional_params']) && is_array($exportConfig['additional_params'])) {
            foreach ($exportConfig['additional_params'] as $key => $value) {
                if (!$this->getRequest()->has($key)) {
                    $this->getRequest()->setParam($key, $value);
                }
            }
        }
    }
    
    /**
     * Apply an export action for the given format and file name
     * 
     * @param string $format Export format
     * @param string $fileName Exported file name
     */
    protected function _applyExportAction($format, $fileName)
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            if (!is_array($config = $this->getRequest()->getParam('export'))) {
                $config = null;
            }
            
            $this->_restoreExportAdditionalParams($config);
            
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
            $this->_redirectReferer();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occurred while exporting grid results'));
            $this->_redirectReferer();
        }
    }
    
    public function exportCsvAction()
    {
        $this->_applyExportAction('csv', 'export.csv');
    }
    
    public function exportExcelAction()
    {
        $this->_applyExportAction('xml', 'export.xml');
    }
    
    public function gridInfosAction()
    {
        $this->_prepareWindowFormLayout(
            'grid_infos',
            array(),
            array(
                BL_CustomGrid_Model_Grid_Sentry::ACTION_VIEW_GRID_INFOS,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
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
            $resultMessage = $this->__('An error occurred while saving the grid infos');
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
        
        $this->_initSystemPageAction()
            ->_title($gridTitle)
            ->_addBreadcrumb($gridTitle, $gridTitle)
            ->renderLayout();
    }
    
    /**
     * Apply the given grid informations values to the given grid model
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param array $values Grid informations values
     * @return BL_CustomGrid_GridController
     */
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
    
    /**
     * Save the values from the grid edit page to the given grid model and profile
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param BL_CustomGrid_Model_Grid_Profile $gridProfile Grid profile
     * @param array $data Request data
     * @return BL_CustomGrid_GridController
     */
    protected function _saveGridEditValues(
        BL_CustomGrid_Model_Grid $gridModel,
        BL_CustomGrid_Model_Grid_Profile $gridProfile,
        array $data
    ) {
        $updateCallbacks = array(
            'columns' => array(
                'type' => 'grid',
                'callback' => array($this->_getGridColumnModel(), 'updateGridModelColumns'),
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
                'type' => 'sentry',
                'callback' => array($gridModel->getSentry(), 'setGridRolesPermissions'),
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
        
        /** @var $transaction BL_CustomGrid_Model_Resource_Transaction */
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
            $resultMessage = $this->__('An error occurred while saving the grid');
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
                    '_current'   => true,
                    'grid_id'    => $gridModel->getId(),
                    'profile_id' => $gridModel->getProfileId(),
                )
            );
        } else {
            $this->_redirect('*/*/index');
        }
    }
    
    /**
     * Apply a base action from the grids list page, by calling the given method on the current grid model,
     * and saving the grid model afterwards if required
     * 
     * @param string $methodName Method name to call on the grid model
     * @param array $parameters Method parameters
     * @param bool $saveAfter Whether the grid model should be saved after the method call
     * @param string $successMessage Success message
     * @param string $defaultErrorMessage Default error message to display if a non-Magento exception is caught
     */
    protected function _applyGridsListAction(
        $methodName,
        array $parameters,
        $saveAfter = false,
        $successMessage,
        $defaultErrorMessage
    ) {
         try {
            $gridModel = $this->_initGridModel();
            call_user_func_array(array($gridModel, $methodName), $parameters);
            
            if ($saveAfter) {
                $gridModel->save();
            }
            
            $this->_getSession()->addSuccess($this->__($successMessage));
            $this->_redirect('*/*/');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__($defaultErrorMessage));
        }
        $this->_redirectReferer();
    }
    
    public function disableAction()
    {
        $this->_applyGridsListAction(
            'setDisabled',
            array(true),
            true,
            'The custom grid has been successfully disabled',
            'An error occurred while disabling the grid'
        );
    }
    
    public function enableAction()
    {
        $this->_applyGridsListAction(
            'setDisabled',
            array(false),
            true,
            'The custom grid has been successfully enabled',
            'An error occurred while enabling the grid'
        );
    }
    
    public function deleteAction()
    {
        $this->_applyGridsListAction(
            'delete',
            array(),
            false,
            'The custom grid has been successfully deleted',
            'An error occurred while deleting the grid'
        );
    }
    
    /**
     * Validate that one or more grids were selected for a mass-action, otherwise force a redirect to the grids list
     * 
     * @return bool
     */
    protected function _validateMassActionGrids()
    {
        if (!is_array($this->getRequest()->getParam('grid', null))) {
            $this->_getSession()->addError($this->__('Please select grids to update'));
            $this->_redirect('*/*/', array('_current' => true));
            return false;
        }
        return true;
    }
    
    /**
     * Apply a mass-action action with the given callback that will be used for each selected grid ID
     * 
     * @param callback $callback Callback to use for each grid ID
     * @param string $defaultErrorMessage Default error message to display if a non-Magento exception is caught
     * @param string $successfulMessage Message that will be displayed with the number of successfully handled IDs
     * @param string $permissionErrorsMessage Message that will be displayed with the number of IDs that could not be
     *                                        handled due to permission errors
     */
    protected function _applyMassactionAction(
        $callback,
        $defaultErrorMessage,
        $successfulMessage,
        $permissionErrorsMessage
    ) {
        if (!$this->_validateMassActionGrids()) {
            return;
        }
        
        $gridsIds = $this->getRequest()->getParam('grid');
        $successfulCount = 0;
        $permissionErrorsCount = 0;
        
        try {
            foreach ($gridsIds as $gridId) {
                try {
                    call_user_func($callback, $gridId);
                    ++$successfulCount;
                } catch (BL_CustomGrid_Grid_Permission_Exception $e) {
                    ++$permissionErrorsCount;
                }
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__($defaultErrorMessage));
        }
        
        if ($successfulCount > 0) {
            $this->_getSession()->addSuccess($this->__($successfulMessage, $successfulCount));
        }
        if ($permissionErrorsCount > 0) {
            $this->_getSession()->addError($this->__($permissionErrorsMessage, $permissionErrorsCount));
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/'));
    }
    
    /**
     * Disable the grid model corresponding to the ID
     * 
     * @param int $gridId Grid model ID
     */
    protected function _massDisableGrid($gridId)
    {
        /** @var $gridModel BL_CustomGrid_Model_Grid */
        $gridModel = Mage::getSingleton('customgrid/grid');
        $gridModel->load($gridId)->setDisabled(true)->save();
    }
    
    public function massDisableAction()
    {
        $this->_applyMassactionAction(
            array($this, '_massDisableGrid'),
            'An error occurred while disabling a grid',
            'Total of %d grid(s) have been disabled',
            'You were not allowed to disable %d of the chosen grids'
        );
    }
    
    /**
     * Enable the grid model corresponding to the given ID
     * 
     * @param int $gridId Grid model ID
     */
    protected function _massEnableGrid($gridId)
    {
        /** @var $gridModel BL_CustomGrid_Model_Grid */
        $gridModel = Mage::getSingleton('customgrid/grid');
        $gridModel->load($gridId)->setDisabled(false)->save();
    }
    
    public function massEnableAction()
    {
        $this->_applyMassactionAction(
            array($this, '_massEnableGrid'),
            'An error occurred while enabling a grid',
            'Total of %d grid(s) have been enabled',
            'You were not allowed to enable %d of the chosen grids'
        );
    }
    
    /**
     * Delete the grid model corresponding to the given ID
     * 
     * @param int $gridId Grid model ID
     */
    protected function _massDeleteGrid($gridId)
    {
        /** @var $gridModel BL_CustomGrid_Model_Grid */
        $gridModel = Mage::getSingleton('customgrid/grid');
        $gridModel->load($gridId)->delete();
    }
    
    public function massDeleteAction()
    {
        $this->_applyMassactionAction(
            array($this, '_massDeleteGrid'),
            'An error occurred while deleting a grid',
            'Total of %d grid(s) have been deleted',
            'You were not allowed to delete %d of the chosen grids'
        );
    }
    
    protected function _isAllowed()
    {
        // Specific permissions are enforced by the models
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid':
            case 'massDelete':
            case 'massDisable':
            case 'massEnable':
                return $this->_getAdminSession()->isAllowed('customgrid/administration/view_grids_list');
        }
        return true;
    }
}
