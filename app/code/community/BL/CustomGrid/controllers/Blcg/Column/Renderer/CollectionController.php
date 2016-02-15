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

class BL_CustomGrid_Blcg_Column_Renderer_CollectionController extends BL_CustomGrid_Controller_Grid_Action
{
    /**
     * Return the config model for collection column renderers
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Config_Collection
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('customgrid/column_renderer_config_collection');
    }
    
    /**
     * Initialize and register the current collection column renderer from the current request
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
     */
    protected function _initRenderer()
    {
        if ($code = $this->getRequest()->getParam('code')) {
            $renderer = $this->_getConfig()->getRendererModelByCode($code);
        } else {
            $renderer = null;
        }
        Mage::register('blcg_collection_column_renderer', $renderer);
        return $renderer;
    }
    
    public function indexAction()
    {
        if ($this->_initRenderer()) {
            $this->loadLayout('blcg_empty');
            
            if ($configBlock = $this->getLayout()->getBlock('blcg.column_renderer.collection.config')) {
                /** @var $configBlock BL_CustomGrid_Block_Column_Renderer_Collection_Config */
                if ($rendererTargetId = $this->getRequest()->getParam('renderer_target_id')) {
                    $configBlock->setRendererTargetId($rendererTargetId);
                }
                if ($params = $this->getRequest()->getParam('params')) {
                    $configBlock->setConfigValues($this->_getConfig()->decodeParameters($params));
                }
            }
            
            $this->renderLayout();
        } else {
            $this->loadLayout(
                array(
                    'blcg_empty', 
                    strtolower($this->getFullActionName()),
                    'adminhtml_blcg_column_renderer_collection_unknown',
                )
            );
            $this->renderLayout();
        }
    }
    
    public function buildConfigAction()
    {
        $this->_saveConfigFormFieldsetsStates();
        $params = $this->getRequest()->getPost('parameters', array());
        $params = $this->_getConfig()->encodeParameters($params);
        $this->_setActionSuccessJsonResponse(array('parameters' => $params));
    }
    
    protected function _isAllowed()
    {
        return $this->_getAdminSession()->isAllowed('customgrid/customization/edit_columns');
    }
}
