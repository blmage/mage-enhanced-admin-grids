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

class BL_CustomGrid_Blcg_Custom_Column_ConfigController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Initialize and register the custom column from the current request
     * 
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    protected function _initCustomColumn()
    {
        if (($code = $this->getRequest()->getParam('code'))
            && (count($codeParts = explode('/', $code)) == 2)
            && ($gridType = $this->_getGridTypeConfig()->getTypeModelByCode($codeParts[0]))
            && ($customColumn = $gridType->getCustomColumn($codeParts[1]))) {
            Mage::register('blcg_custom_column', $customColumn);
        } else {
            $customColumn = null;
        }
        return $customColumn;
    }
    
    public function indexAction()
    {
        if ($this->_initCustomColumn()) {
            $this->loadLayout('blcg_empty');
            
            if ($configBlock = $this->getLayout()->getBlock('blcg.custom_column.config')) {
                /** @var $configBlock BL_CustomGrid_Block_Custom_Column_Config */
                if ($configTargetId = $this->getRequest()->getParam('config_target_id')) {
                    $configBlock->setConfigTargetId($configTargetId);
                }
                if ($params = $this->getRequest()->getParam('params')) {
                    $configBlock->setConfigValues($this->_getGridTypeConfig()->decodeParameters($params));
                }
            }
            
            $this->renderLayout();
        } else {
            $this->loadLayout(
                array(
                    'blcg_empty', 
                    strtolower($this->getFullActionName()),
                    'adminhtml_blcg_custom_column_config_unknown',
                )
            );
            $this->renderLayout();
        }
    }
    
    public function buildConfigAction()
    {
        $this->_saveConfigFormFieldsetsStates();
        $params = $this->getRequest()->getPost('parameters', array());
        $params = $this->_getGridTypeConfig()->encodeParameters($params);
        $this->_setActionSuccessJsonResponse(array('parameters' => $params));
    }
    
    protected function _isAllowed()
    {
        return $this->_getAdminSession()->isAllowed('customgrid/customization/edit_columns');
    }
}
