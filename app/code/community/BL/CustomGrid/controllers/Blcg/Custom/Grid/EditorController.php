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

class BL_CustomGrid_Blcg_Custom_Grid_EditorController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _initGridModel()
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
    
    protected function _initGridType()
    {
        if ($typeCode= $this->getRequest()->getParam('grid_type', null)) {
            $gridType = Mage::getSingleton('customgrid/grid_type')
                ->getTypeInstanceByCode($typeCode);
            
            if ($gridType) {
                Mage::register('grid_type', $gridType);
                Mage::register('current_grid_type', $gridType);
                return $gridType;
            }
        }
        return null;
    }
    
    protected function _initEditedValue($gridType)
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
        return $this->_redirect('*/custom_grid/');
    }
    
    public function editAction()
    {
        $editBlock    = null;
        $errorMessage = null;
        
        if (($gridModel = $this->_initGridModel())
            // Same call done in the grid type model
            //&& $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_COLUMNS_VALUES, true)
            && ($gridType = $this->_initGridType())) {
            if ($infos = $this->_initEditedValue($gridType)) {
                list($blockType, $id, $origin) = $infos;
                try {
                    $editBlock = $gridType->getValueEditBlock($blockType, $id, $origin, $this->getRequest(), $gridModel, false, true);
                } catch (Exception $e) {
                    $errorMessage = $this->__('Failed to edit the value : "%s"', $e->getMessage());
                }
            } else {
                $errorMessage = $this->__('This value is not editable');
            }
        } else {
            $errorMessage = $this->__('Unknown grid');
        }
        
        $this->loadLayout();
        
        if ($container = $this->getLayout()->getBlock('customgrid.grid_form_container')) {
            if (!is_null($errorMessage)) {
                $container->setErrorMessage($errorMessage);
            } elseif ($editBlock instanceof Mage_Core_Block_Abstract) {
                $container->setChildForm($editBlock);
            } else {
                $container->setErrorMessage($this->__('This value is not editable'));
            }
        }
        
        $this->renderLayout();
    }
    
    public function editInGridAction()
    {
        if (($gridModel = $this->_initGridModel())
            //&& $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_COLUMNS_VALUES, true)
            && ($gridType = $this->_initGridType())) {
            if ($infos = $this->_initEditedValue($gridType)) {
                list($blockType, $id, $origin) = $infos;
                try {
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        'result'  => true,
                        'content' => $gridType->getValueEditBlock($blockType, $id, $origin, $this->getRequest(), $gridModel),
                    )));
                } catch (Exception $e) {
                    $this->getResponse()->setBody($this->__('Failed to edit the value : "%s"', $e->getMessage()));
                }
            } else {
                $this->getResponse()->setBody($this->__('This value is not editable'));
            }
        } else {
            $this->getResponse()->setBody($this->__('Unknown grid'));
        }
    }
    
    public function saveAction()
    {
        if (($gridModel = $this->_initGridModel())
            //&& $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_COLUMNS_VALUES, true)
            && ($gridType = $this->_initGridType())) {
            if ($infos = $this->_initEditedValue($gridType)) {
                list($blockType, $id, $origin) = $infos;
                try {
                    $this->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        'result'  => true,
                        'content' => $gridType->saveEditedValue($blockType, $id, $origin, $this->getRequest(), $gridModel),
                    )));
                } catch (Exception $e) {
                    $this->getResponse()->setBody($this->__('Failed to save the value : "%s"', $e->getMessage()));
                }
            } else {
                $this->getResponse()->setBody($this->__('This value is not editable'));
            }
        } else {
            $this->getResponse()->setBody($this->__('Unknown grid'));
        }
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/customgrid/editor/edit_columns');
    }
}