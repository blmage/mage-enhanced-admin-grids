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

class BL_CustomGrid_Blcg_Custom_Column_ConfigController
    extends Mage_Adminhtml_Controller_Action
{
    protected function _initCustomColumn()
    {
        $config = Mage::getSingleton('customgrid/grid_type');
        
        if (($code = $this->getRequest()->getParam('code'))
            && (count($code = explode('/', $code)) == 2)
            && ($gridType = $config->getTypeInstanceByCode($code[0]))
            && ($customColumn = $gridType->getCustomColumn($code[1]))) {
            Mage::register('current_custom_column', $customColumn);
        } else {
            $customColumn = null;
        }
        
        return $customColumn;
    }
    
    public function indexAction()
    {
        if ($column = $this->_initCustomColumn()) {
            $this->loadLayout('empty');
            
            if (($params = $this->getRequest()->getParam('params'))
                && ($block = $this->getLayout()->getBlock('custom_column_config'))) {
                $params = Mage::getSingleton('customgrid/grid_type')->decodeParameters($params);
                $block->setConfigParams($params);
            }
            
            $this->renderLayout();
        } else {
            $this->loadLayout(array(
                'empty', 
                strtolower($this->getFullActionName()),
                'adminhtml_blcg_custom_column_config_unknown',
            ))->renderLayout();
        }
    }
    
    public function buildConfigAction()
    {
        $params  = $this->getRequest()->getPost('parameters', array());
        $encoded = Mage::getSingleton('customgrid/grid_type')->encodeParameters($params);
        $this->getResponse()->setBody($encoded);
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
            ->isAllowed('system/customgrid/customization/edit_columns');
    }
}