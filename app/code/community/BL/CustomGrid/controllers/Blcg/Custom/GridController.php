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

class BL_CustomGrid_Blcg_Custom_GridController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/customgrid')
            ->_title($this->__('Custom Grids'))
            ->_addBreadcrumb($this->__('Custom Grids'), $this->__('Custom Grids'));
        return $this;
    }
    
    protected function _initGrid()
    {
        $gridId = (int) $this->getRequest()->getParam('grid_id');
        $grid   = Mage::getModel('customgrid/grid');
        
        if ($gridId) {
            $grid->load($gridId);
        } else {
            return false;
        }
        
        Mage::register('custom_grid', $grid);
        Mage::register('current_custom_grid', $grid);
        return $grid;
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
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->createBlock('customgrid/custom_grid')->toHtml());
    }
    
    protected function _redirectReferer($defaultUrl=null)
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            if (isset($data['filter_param_name']) && isset($data['filter_param_value'])) {
                $refererUrl = $this->_getRefererUrl();
                
                if (empty($refererUrl)) {
                    $refererUrl = Mage::helper('adminhtml')->getUrl();
                } else {
                    // Update filter param value in referer URL, as it may contain unvalidated filters
                    $urlParts    = explode('?', $refererUrl);
                    $paramRegex  = '/'.preg_quote($data['filter_param_name'], '#').'/.*?/';
                    $refererUrl  = preg_replace('#'.$paramRegex.'#', '/', $urlParts[0]);
                    $refererUrl .= (substr($refererUrl, -1) != '/' ? '/' : '')
                        . $data['filter_param_name'].'/'.$data['filter_param_value'].'/';
                    
                    if (count($urlParts) > 1) {
                        $refererUrl .= '?'.$urlParts[1];
                    }
                }
                
                $this->getResponse()->setRedirect($refererUrl);
                return;
            }
        }
        parent::_redirectReferer($defaultUrl);
    }
    
    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            if (($grid = $this->_initGrid()) && $grid->getId()) {
                try {
                    $changes = false;
                    
                    if (isset($data['config'])
                        && $grid->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_CUSTOMIZE_COLUMNS)) {
                        // Columns config
                        $grid->updateColumns($data['config'], false);
                        $changes = true;
                    }
                    if (Mage::getSingleton('admin/session')->isAllowed('system/customgrid/grids')) {
                        $changes = true;
                        
                        // Roles config
                        if (isset($data['role']) && is_array($data['role'])) {
                            $grid->updateRolesConfig($data['role'], false);
                        }
                        
                        // Custom default params behaviours
                        $data = array_filter($data, 'is_scalar');
                        
                        foreach ($data as $key => $value) {
                            if (substr($key, 0, 8) == 'default_') {
                                $grid->setData($key, (empty($value) ? null : $value));
                                unset($data[$key]);
                            }
                        }
                        
                        // All other (scalar) settings
                        $grid->addData($data);
                    }
                    
                    if ($changes) {
                        $grid->save();
                    } else {
                        Mage::throwException($this->__('You have no access to this grid'));
                    }
                    
                    $this->_getSession()->addSuccess($this->__('The custom grid has been successfully updated'));
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            } else {
                $this->_getSession()->addError($this->__('This custom grid no longer exists'));
            }
        }
        if ($this->getRequest()->getParam('back', false)) {
            $this->_redirect('*/*/edit', array(
                '_current' => true,
                'grid_id'  => $grid->getId(),
            ));
        } else {
            $this->_redirectReferer();
        }
    }
    
    public function saveCustomColumnsAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            if (($grid = $this->_initGrid()) && $grid->getId()
                && $grid->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_CUSTOMIZE_COLUMNS)) {
                try {
                    $grid->updateCustomColumns(isset($data['custom_columns']) ? $data['custom_columns'] : array());
                    $this->_getSession()->addSuccess($this->__('The custom grid has been successfully updated'));
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            } else {
                $this->_getSession()->addError($this->__('This custom grid no longer exists'));
            }
        }
        $this->_redirectReferer();
    }
    
    public function saveDefaultAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            if (($grid = $this->_initGrid()) && $grid->getId()
                && $grid->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_DEFAULT_PARAMS)) {
                try {
                    $grid->updateDefaultParameters(
                        isset($data['grid_default_params'])   ? $data['grid_default_params']   : array(),
                        isset($data['remove_default_params']) ? $data['remove_default_params'] : array()
                    );
                    $this->_getSession()->addSuccess($this->__('The custom grid has been successfully updated'));
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            } else {
                $this->_getSession()->addError($this->__('This custom grid no longer exists'));
            }
        }
        $this->_redirectReferer();
    }
    
    public function editAction()
    {
        if (($grid = $this->_initGrid()) && $grid->getId()) {
            $gridName = Mage::helper('customgrid')->__('Custom Grid: %s', $grid->getBlockType());
            if ($grid->getRewritingClassName()) {
                $gridName .= ' - '.$grid->getRewritingClassName();
            } else {
                $gridName .= ' - '.Mage::helper('customgrid')->__('Base Class');
            }
            
            $this->_initAction()
                ->_title($gridName)
                ->_addBreadcrumb($gridName, $gridName)
                ->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__('This custom grid no longer exists'));
            $this->_redirect('*/*/');
        }
    }
    
    public function disableAction()
    {
        if (($grid = $this->_initGrid()) && $grid->getId()) {
            try {
                $grid->setDisabled(true)->save();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The custom grid has been successfully disabled'));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__('This custom grid no longer exists'));
        }
        $this->_redirect('*/*/');
    }
    
    public function enableAction()
    {
        if (($grid = $this->_initGrid()) && $grid->getId()) {
            try {
                $grid->setDisabled(false)->save();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The custom grid has been successfully enabled'));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__('This custom grid no longer exists'));
        }
        $this->_redirect('*/*/');
    }
    
    public function deleteAction()
    {
        if (($grid = $this->_initGrid()) && $grid->getId()) {
            try {
                $grid->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The custom grid has been successfully deleted'));
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $grid->getId()));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError($this->__('This custom grid no longer exists'));
        $this->_redirect('*/*/');
    }
    
    public function exportCsvAction()
    {
        $data  = $this->getRequest()->getParams();
        $infos = (isset($data['export']) && is_array($data['export']) ? $data['export'] : null);
        
        if (($grid = $this->_initGrid()) && $grid->getId()
            && $grid->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EXPORT_RESULTS)) {
            try {
                $fileName = 'export.csv';
                $this->_prepareDownloadResponse($fileName, $grid->exportCsvFile($infos));
                return;
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        } else {
            $this->_getSession()->addError($this->__('Unknown custom grid'));
        }
        
        $this->_redirectReferer();
    }
    
    public function exportExcelAction()
    {
        $data  = $this->getRequest()->getParams();
        $infos = (isset($data['export']) && is_array($data['export']) ? $data['export'] : null);
        
        if (($grid = $this->_initGrid()) && $grid->getId()
            && $grid->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EXPORT_RESULTS)) {
            try {
                $fileName = 'export.xml';
                $this->_prepareDownloadResponse($fileName, $grid->exportExcelFile($infos));
                return;
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        } else {
            $this->_getSession()->addError($this->__('Unknown custom grid'));
        }
        
        $this->_redirectReferer();
    }
    
    public function massEnableAction()
    {
        if (!$this->_validateGrids()) {
            return;
        }
        
        try {
            $gridsIds = $this->getRequest()->getParam('grid');
            
            foreach ($gridsIds as $gridId) {
                Mage::getSingleton('customgrid/grid')
                    ->load($gridId)
                    ->setDisabled(false)
                    ->save();
            }
            
            $this->_getSession()->addSuccess($this->__('Total of %d grid(s) have been enabled.', count($gridsIds)));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/index'));
    }
    
    public function massDisableAction()
    {
        if (!$this->_validateGrids()) {
            return;
        }
        
        try {
            $gridsIds = $this->getRequest()->getParam('grid');
            
            foreach ($gridsIds as $gridId) {
                Mage::getSingleton('customgrid/grid')
                    ->load($gridId)
                    ->setDisabled(true)
                    ->save();
            }
            
            $this->_getSession()->addSuccess($this->__('Total of %d grid(s) have been disabled.', count($gridsIds)));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/index'));
    }
    
    public function massDeleteAction()
    {
        if (!$this->_validateGrids()) {
            return;
        }
        
        try {
            $gridsIds = $this->getRequest()->getParam('grid');
            
            foreach ($gridsIds as $gridId) {
                Mage::getSingleton('customgrid/grid')->load($gridId)->delete();
            }
            
            $this->_getSession()->addSuccess($this->__('Total of %d grid(s) have been deleted.', count($gridsIds)));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/index'));
    }
    
    protected function _validateGrids()
    {
        if (!is_array($this->getRequest()->getParam('grid', null))) {
            $this->_getSession()->addError($this->__('Please select grids to update'));
            $this->_redirect('*/*/index', array('_current' => true));
            return false;
        }
        return true;
    }
    
    protected function _isAllowed()
    {
        // Only return allowed flag for actions that don't (at least, atm) have grid-level permissions
        switch ($this->getRequest()->getActionName()) {
            case 'exportCsv':
            case 'exportXls':
            case 'save':
            case 'saveCustom':
            case 'saveDefault':
                return true;
            default:
                return Mage::getSingleton('admin/session')
                    ->isAllowed('system/customgrid/grids');
        }
    }
}