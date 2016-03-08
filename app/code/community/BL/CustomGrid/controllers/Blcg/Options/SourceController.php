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

class BL_CustomGrid_Blcg_Options_SourceController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Initialize and register the options source from the current request
     * 
     * @param bool $requireId Whether an existing options source must be initialized
     * @return BL_CustomGrid_Model_Options_Source|false
     */
    protected function _initOptionsSource($requireId = false)
    {
        /** @var $source BL_CustomGrid_Model_Options_Source */
        $source = Mage::getModel('customgrid/options_source');
        
        if (!$sourceId = (int) $this->getRequest()->getParam('id')) {
            if ($type = $this->getRequest()->getParam('type')) {
                if (is_array($typeValues = $source->getPredefinedType($type))) {
                    $source->addData($typeValues);
                } else {
                    $source->setType($type);
                }
            }
        } else {
            $source->load($sourceId);
        }
        
        if ($requireId && !$source->getId()) {
            return false;
        }
        
        Mage::register('blcg_options_source', $source);
        return $source;
    }
    
    /**
     * Load layout and initialize active menu, title and breadcrumbs for an options source action
     * 
     * @param string[]|null $layoutHandles Layout handles
     * @return BL_CustomGrid_Options_SourceController
     */
    protected function _initAction($layoutHandles = null)
    {
        return $this->loadLayout($layoutHandles)
            ->_setActiveMenu('system/customgrid/options_source')
            ->_title($this->__('Custom Grids'))
            ->_title($this->__('Manage Options Source'))
            ->_addBreadcrumb($this->__('Custom Grids'), $this->__('Custom Grids'))
            ->_addBreadcrumb($this->__('Manage Options Source'), $this->__('Manage Options Source'));
    }
    
    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->_initAction()->renderLayout();
    }
    
    public function newAction()
    {
        $source = $this->_initOptionsSource();
        
        if ($source->getId()) {
            $this->_redirect('*/*/edit', array('_current' => true));
            return;
        }
        
        $data = $this->_getSession()->getOptionsSourceData(true);
        
        if (!empty($data)) {
            $source->addData($data);
        }
        
        $this->_initAction(
            array(
                'default',
                strtolower($this->getFullActionName()),
                'adminhtml_blcg_options_source_' . $source->getType()
            )
        );
        
        $this->_title($this->__('New Options Source'))->renderLayout();
    }
    
    public function editAction()
    {
        if (!$source= $this->_initOptionsSource(true)) {
            $this->_getSession()->addError($this->__('This options source no longer exists'));
            $this->_redirect('*/*/');
            return;
        }
        
        $data = $this->_getSession()->getOptionsSourceData(true);
        
        if (!empty($data)) {
            $source->addData($data);
        }
        
        $this->_initAction(
            array(
                'default',
                strtolower($this->getFullActionName()),
                'adminhtml_blcg_options_source_' . $source->getType()
            )
        );
        
        $this->_title($source->getName())
            ->_addBreadcrumb($source->getName(), $source->getName())
            ->renderLayout();
    }
    
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            try {
                $source = $this->_initOptionsSource();
                $source->addData($data)->save();
                
                $this->_getSession()
                    ->setOptionsSourceData(false)
                    ->addSuccess($this->__('The options source has been successfully saved'));
                
                if ($this->getRequest()->getParam('back', false)) {
                    $this->_redirect('*/*/edit', array('id' => $source->getId(), '_current' => true));
                    return;
                }
                
            } catch (Exception $e) {
                $this->_getSession()
                    ->setOptionsSourceData($data)
                    ->addError($e->getMessage());
                
                if ($source->getId()) {
                    $this->_redirect('*/*/edit', array('_current' => true));
                    return;
                }
                
                $this->_redirect('*/*/new');
                return;
            }
        }
        $this->_redirect('*/*/');
    }
    
    public function deleteAction()
    {
        if ($source = $this->_initOptionsSource(true)) {
            try {
                $source->delete();
                $this->_getSession()->addSuccess($this->__('The options source has been successfully deleted'));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $source->getId()));
                return;
            }
        } else {
            $this->_getSession()->addError($this->__('This options source no longer exists'));
        }
        $this->_redirect('*/*/');
    }
    
    public function massDeleteAction()
    {
        if (!$this->_validateMassActionValues('options_source')) {
            return;
        }
        
        /** @var $sourceModel BL_CustomGrid_Model_Options_Source */
        $sourceModel  = Mage::getSingleton('customgrid/options_source');
        $sourcesIds   = $this->getRequest()->getParam('options_source');
        $deletedCount = 0;
        
        try {
            foreach ($sourcesIds as $sourceId) {
                $sourceModel->load($sourceId)->delete();
                ++$deletedCount;
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('An error occurred while deleting an options source'));
        }
        
        if ($deletedCount > 0) {
            $this->_getSession()
                ->addSuccess($this->__('Total of %d options source(s) have been deleted', $deletedCount));
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/index'));
    }
    
    protected function _isAllowed()
    {
        return $this->_getAdminSession()->isAllowed('system/customgrid/options_source');
    }
}
