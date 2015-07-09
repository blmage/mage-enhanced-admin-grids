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

class BL_CustomGrid_Grid_EditorController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Initialize and register the grid type model from the current request
     * 
     * @return BL_CustomGrid_Model_Grid_Type_Abstract
     */
    protected function _initGridType()
    {
        $gridType = null;
        
        if ($typeCode= $this->getRequest()->getParam('grid_type', null)) {
            $gridType = $this->_getGridTypeConfig()->getTypeModelByCode($typeCode);
        }
        if (!$gridType) {
            Mage::throwException($this->__('Unknown grid type'));
        }
        
        Mage::register('blcg_grid_type', $gridType);
        return $gridType;
    }
    
    
    /**
     * Return an object containing various informations (block type, ID, origin) about the edited value
     * from the current request
     * 
     * @param BL_CustomGrid_Model_Grid_Type_Abstract $gridType Current grid type
     * @return BL_CustomGrid_Object
     */
    protected function _getEditedValue(BL_CustomGrid_Model_Grid_Type_Abstract $gridType)
    {
        /** @var $helper Mage_Core_Helper_Data */
        $helper = Mage::helper('core');
        $values = null;
        
        if (($blockType = $this->getRequest()->getParam('block_type', null))
            && ($blockType = $helper->urlDecode($blockType))
            && ($id = $this->getRequest()->getParam('id', null))
            && ($origin = $this->getRequest()->getParam('origin', null))
            && $gridType->isEditableValue($blockType, $id, $origin)) {
            return new BL_CustomGrid_Object(
                array(
                    'block_type' => $blockType,
                    'id'         => $id,
                    'origin'     => $origin,
                )
            );
        }
        
        return $values;
    }
    
    public function indexAction()
    {
        $this->_redirect('*/grid/');
    }
    
    /**
     * Initialize the current edit request and return the corresponding initialized values
     * 
     * @return array
     */
    protected function _initEditRequest()
    {
        $gridModel = $this->_initGridModel();
        $this->_initGridProfile();
        $gridType = $this->_initGridType();
        
        if (!is_object($editedValue = $this->_getEditedValue($gridType))) {
            Mage::throwException($this->__('This value is not editable'));
        }
        
        return array($gridModel, $gridType, $editedValue);
    }
    
    public function editAction()
    {
        $editBlock = null;
        $errorMessage = null;
        
        try {
            /**
             * @var $gridModel BL_CustomGrid_Model_Grid
             * @var $gridType BL_CustomGrid_Model_Grid_Type_Abstract
             */
            list($gridModel, $gridType, $editedValue) = $this->_initEditRequest();
            
            $editBlock = $gridType->getValueEditBlock(
                $editedValue->getBlockType(),
                $editedValue->getId(),
                $editedValue->getOrigin(),
                $this->getRequest(),
                $gridModel,
                false,
                true
            );
        } catch (Mage_Core_Exception $e) {
            $errorMessage = $this->__('Failed to edit the value : "%s"', $e->getMessage());
        }
        
        $this->loadLayout();
        
        if ($containerBlock = $this->getLayout()->getBlock('blcg.grid_editor.form_container')) {
            /** @var $containerBlock BL_CustomGrid_Block_Widget_Grid_Editor_Form_Container */
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
            list($gridModel, $gridType, $editedValue) = $this->_initEditRequest();
            
            $content = $gridType->getValueEditBlock(
                $editedValue->getBlockType(),
                $editedValue->getId(),
                $editedValue->getOrigin(),
                $this->getRequest(),
                $gridModel
            );
            
            $this->_setActionSuccessJsonResponse(array('content' => $content));
        } catch (Exception $e) {
            $this->_setActionErrorJsonResponse($this->__('Failed to edit the value : "%s"', $e->getMessage()));
        }
    }
    
    public function saveAction()
    {
        try {
            list($gridModel, $gridType, $editedValue) = $this->_initEditRequest();
            
            $content = $gridType->saveEditedValue(
                $editedValue->getBlockType(),
                $editedValue->getId(),
                $editedValue->getOrigin(),
                $this->getRequest(),
                $gridModel
            );
            
            $this->_setActionSuccessJsonResponse(array('content' => $content));
        } catch (Exception $e) {
            $this->_setActionErrorJsonResponse($this->__('Failed to save the value : "%s"', $e->getMessage()));
        }
    }
    
    protected function _isAllowed()
    {
        return $this->_getAdminSession()->isAllowed('customgrid/editor/edit_columns');
    }
}
