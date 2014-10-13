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

class BL_CustomGrid_Grid_EditorController extends BL_CustomGrid_Controller_Grid_Action
{
    protected function _initGridType()
    {
        $gridType = null;
        
        if ($typeCode= $this->getRequest()->getParam('grid_type', null)) {
            $gridType = Mage::getSingleton('customgrid/grid_type_config')->getTypeInstanceByCode($typeCode);
        }
        if (!$gridType) {
            Mage::throwException($this->__('Unknown grid type'));
        }
        
        Mage::register('blcg_grid_type', $gridType);
        return $gridType;
    }
    
    protected function _initEditedValue(BL_CustomGrid_Model_Grid_Type_Abstract $gridType)
    {
        if (($blockType = $this->getRequest()->getParam('block_type', null))
            && ($blockType = Mage::helper('core')->urlDecode($blockType))
            && ($id = $this->getRequest()->getParam('id', null))
            && ($origin = $this->getRequest()->getParam('origin', null))
            && $gridType->isEditableValue($blockType, $id, $origin)) {
            return array($blockType, $id, $origin);
        }
        return null;
    }
    
    public function indexAction()
    {
        return $this->_redirect('*/grid/');
    }
    
    public function editAction()
    {
        $editBlock = null;
        $errorMessage = null;
        
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $gridType = $this->_initGridType();
            
            if ($editValues = $this->_initEditedValue($gridType)) {
                list($blockType, $id, $origin) = $editValues;
                
                $editBlock = $gridType->getValueEditBlock(
                    $blockType,
                    $id,
                    $origin,
                    $this->getRequest(),
                    $gridModel,
                    false,
                    true
                );
            } else {
                Mage::throwException($this->__('This value is not editable'));
            }
        } catch (Mage_Core_Exception $e) {
            $errorMessage = $this->__('Failed to edit the value : "%s"', $e->getMessage());
        }
        
        $this->loadLayout();
        
        if ($containerBlock = $this->getLayout()->getBlock('blcg.grid_editor.form_container')) {
            if (!is_null($errorMessage)) {
                $containerBlock->setErrorMessage($errorMessage);
            } elseif ($editBlock instanceof Mage_Core_Block_Abstract) {
                $containerBlock->setChildForm($editBlock);
            } else {
                $containerBlock->setErrorMessage($this->__('This value is not editable'));
            }
        }
        
        $this->renderLayout();
    }
    
    public function editInGridAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $gridType = $this->_initGridType();
            
            if ($editValues = $this->_initEditedValue($gridType)) {
                list($blockType, $id, $origin) = $editValues;
                $content = $gridType->getValueEditBlock($blockType, $id, $origin, $this->getRequest(), $gridModel);
                $this->_setActionSuccessJsonResponse(array('content' => $content));
            } else {
                Mage::throwException($this->__('This value is not editable'));
            }
        } catch (Exception $e) {
            $this->_setActionErrorJsonResponse($this->__('Failed to edit the value : "%s"', $e->getMessage()));
        }
    }
    
    public function saveAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            $gridType = $this->_initGridType();
            
            if ($editValues = $this->_initEditedValue($gridType)) {
                list($blockType, $id, $origin) = $editValues;
                $content = $gridType->saveEditedValue($blockType, $id, $origin, $this->getRequest(), $gridModel);
                $this->_setActionSuccessJsonResponse(array('content' => $content));
            } else {
                Mage::throwException($this->__('This value is not editable'));
            }
        } catch (Exception $e) {
            $this->_setActionErrorJsonResponse($this->__('Failed to save the value : "%s"', $e->getMessage()));
        }
    }
}
