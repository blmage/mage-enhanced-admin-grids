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

class BL_CustomGrid_Blcg_Options_SourceController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction($layoutIds=null)
    {
        $this->loadLayout($layoutIds)
            ->_setActiveMenu('system/customgrid/options_source')
            ->_title($this->__('Custom Grids'))
            ->_title($this->__('Manage Options Source'))
            ->_addBreadcrumb($this->__('Custom Grids'), $this->__('Custom Grids'))
            ->_addBreadcrumb($this->__('Manage Options Source'), $this->__('Manage Options Source'));
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
    
    protected function _initOptionsSource($needId=false)
    {
        $sourceId = (int) $this->getRequest()->getParam('id');
        $source   = Mage::getModel('customgrid/options_source');
        
        if (!$sourceId) {
            if ($type = $this->getRequest()->getParam('type')) {
                if (is_array($typeValue = $source->getPredefinedType($type))) {
                    $source->addData($typeValue);
                } else {
                    $source->setType($type);
                }
            }
        } else {
            $source->load($sourceId);
        }
        
        if ($needId && !$source->getId()) {
            return false;
        }
        
        Mage::register('options_source', $source);
        Mage::register('current_options_source', $source);
        return $source;
    }
    
    public function newAction()
    {
        $source = $this->_initOptionsSource();
        
        if ($source->getId()) {
            return $this->_redirect('*/*/edit', array('_current' => true));
        }
        
        // Set entered data if was error when we saved
        $data = Mage::getSingleton('adminhtml/session')->getOptionsSourceData(true);
        if (!empty($data)) {
            $source->addData($data);
        }
        
        $this->_initAction(array(
            'default',
            strtolower($this->getFullActionName()),
            'adminhtml_blcg_options_source_'.$source->getType()
        ))->_title($this->__('New Options Source'))->renderLayout();
    }
    
    public function editAction()
    {
        if (!($source= $this->_initOptionsSource(true))) {
            $this->_getSession()->addError($this->__('This options source no longer exists.'));
            return $this->_redirect('*/*/');
        }
        
        // Set entered data if was error when we saved
        $data = Mage::getSingleton('adminhtml/session')->getOptionsSourceData(true);
        if (!empty($data)) {
            $source->addData($data);
        }
        
        $this->_initAction(array(
            'default',
            strtolower($this->getFullActionName()),
            'adminhtml_blcg_options_source_'.$source->getType()
        ))->_title($source->getName())
            ->_addBreadcrumb($source->getName(), $source->getName())
            ->renderLayout();
    }
    
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            if (!$source = $this->_initOptionsSource()) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Wrong options source was specified.'));
                return $this->_redirect('*/*/index');
            }
            
            $source->addData($data);
            
            try {
                $source->save();
                
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The options source has been successfully saved.'));
                Mage::getSingleton('adminhtml/session')->setOptionsSourceData(false);
                
                if ($redirectBack = $this->getRequest()->getParam('back', false)) {
                    return $this->_redirect('*/*/edit', array(
                        'id' => $source->getId(),
                        '_current' => true,
                    ));
                } else {
                    return $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setOptionsSourceData($data);
                
                if ($source->getId()) {
                    return $this->_redirect('*/*/edit', array(
                        'id' => $source->getId(),
                        '_current' => true,
                    ));
                } else {
                    return $this->_redirect('*/*/new');
                }
            }
        }
        return $this->_redirect('*/*/', array('_current' => true));
    }
    
    public function deleteAction()
    {
        if ($source = $this->_initOptionsSource(true)) {
            try {
                $source->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('The options source has been successfully deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $source->getId()));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError($this->__('This options source no longer exists.'));
        $this->_redirect('*/*/');
    }
    
    public function massDeleteAction()
    {
        if (!$this->_validateSources()) {
            return;
        }
        
        try {
            $sourcesIds = $this->getRequest()->getParam('options_source');
            
            foreach ($sourcesIds as $sourceId) {
                Mage::getSingleton('customgrid/options_source')->load($sourceId)->delete();
            }
            
            $this->_getSession()->addSuccess($this->__('Total of %d options source(s) have been deleted.', count($sourcesIds)));
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        
        $this->getResponse()->setRedirect($this->getUrl('*/*/index'));
    }
    
    protected function _validateSources()
    {
        if (!is_array($this->getRequest()->getParam('options_source', null))) {
            $this->_getSession()->addError($this->__('Please select options sources to update'));
            $this->_redirect('*/*/index', array('_current' => true));
            return false;
        }
        return true;
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/customgrid/options_source');
    }
}