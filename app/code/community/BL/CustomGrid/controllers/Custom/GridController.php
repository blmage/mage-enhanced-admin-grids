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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Custom_GridController
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
        parent::_redirectReferer($defaultUrl=null);
    }
    
    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            if (($grid = $this->_initGrid()) && $grid->getId() 
                && isset($data['config'])) {
                try {
                    $grid->updateColumns($data['config']);
                    $this->_getSession()->addSuccess($this->__('The custom grid has been successfully updated'));
                } catch (Exception $e) {
                    $this->_getSession()->addError($e->getMessage());
                }
            } else {
                $this->_getSession()->addError($this->__('Unknown custom grid'));
            }
        }
        $this->_redirectReferer();
    }
    
    public function saveDefaultAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            
            if (($grid = $this->_initGrid()) && $grid->getId()) {
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
                $this->_getSession()->addError($this->__('Unknown custom grid'));
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
                ->_addBreadcrumb($gridName)
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
        
        if (($grid = $this->_initGrid()) && $grid->getId()) {
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
        
        if (($grid = $this->_initGrid()) && $grid->getId()) {
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
        $customAllowed = Mage::getModel('admin/session')->isAllowed('system/customgrid/customization');
        $gridsAllowed  = Mage::getModel('admin/session')->isAllowed('system/customgrid/grids');
       
        switch ($this->getRequest()->getActionName()) {
            // All actions used for in-grid customization
            case 'exportCsv':
            case 'exportXls':
            case 'saveDefault':
                return $customAllowed;
            case 'save':
                return ($customAllowed || $gridsAllowed);
        }
        
        // Else actions only used for dedicated custom grids management
        return $gridsAllowed;
    }
}