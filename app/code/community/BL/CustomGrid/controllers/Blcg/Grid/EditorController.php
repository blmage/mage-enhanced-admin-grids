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

class BL_CustomGrid_Blcg_Grid_EditorController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Load and render the form layout with the given form block and error message
     * 
     * @param mixed $formBlock Intialized form block
     * @param mixed $errorMessage Error message, if any
     */
    protected function _prepareFormLayout($formBlock, $errorMessage)
    {
        $this->loadLayout();
    
        if ($containerBlock = $this->getLayout()->getBlock('blcg.grid_editor.form_container')) {
            /** @var $containerBlock BL_CustomGrid_Block_Widget_Grid_Editor_Form_Container */
            if ($formBlock instanceof Mage_Core_Block_Abstract) {
                $containerBlock->setIsEditedInGrid((bool) $formBlock->getIsEditedInGrid());
            }
            if (!is_null($errorMessage)) {
                $containerBlock->setErrorMessage($errorMessage);
            } elseif ($formBlock instanceof Mage_Core_Block_Abstract) {
                $containerBlock->setChildForm($formBlock);
            } else {
                $containerBlock->setErrorMessage($this->__('This value is not editable'));
            }
        }
    
        $this->renderLayout();
    }
    
    public function formAction()
    {
        /** @var BL_CustomGrid_Helper_Data $helper */
        $helper = Mage::helper('customgrid');
        $formBlock = null;
        $errorMessage = null;
        
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            $formBlock = $gridModel->getTypeModel()
                ->getEditor()
                ->getValueFormBlock($this->getRequest(), $gridModel);
        } catch (Mage_Core_Exception $e) {
            $errorMessage = $this->__('Failed to edit the value : "%s"', $e->getMessage());
        }
        
        if (!$helper->isAjaxRequest()
            || ($formBlock instanceof Mage_Core_Block_Abstract)) {
            $this->_prepareWindowProfileFormLayout($formBlock, $errorMessage);
        } elseif (!is_null($errorMessage)) {
            $this->_setActionErrorJsonResponse($errorMessage);
        } else {
            $this->_setActionSuccessJsonResponse(array('content' => $formBlock));
        }
    }
    
    public function saveAction()
    {
        try {
            $gridModel = $this->_initGridModel();
            $this->_initGridProfile();
            
            $content = $gridModel->getTypeModel()
                ->getEditor()
                ->updateEditedValue($this->getRequest(), $gridModel);
            
            $this->_setActionSuccessJsonResponse(array('content' => $content));
        } catch (Exception $e) {
            $this->_setActionErrorJsonResponse($this->__('Failed to save the value : "%s"', $e->getMessage()));
        }
    }
}
