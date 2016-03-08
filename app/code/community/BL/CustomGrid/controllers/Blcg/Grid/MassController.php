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

class BL_CustomGrid_Blcg_Grid_MassController extends BL_CustomGrid_Controller_Grid_Action
{
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
        if (!$this->_validateMassActionValues('grid')) {
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
        return $this->_getAdminSession()->isAllowed('customgrid/administration/view_grids_list');
    }
}
