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

abstract class BL_CustomGrid_Block_Column_Renderer_Abstract
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    abstract protected function _getController();
    abstract protected function _getFormId();
    abstract public function getRenderer(); 
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_blockGroup = 'customgrid';
        $this->_controller = $this->_getController();
        $this->_mode = 'config';
        $this->_headerText = $this->getRenderer()->getName();
        
        $this->removeButton('reset');
        $this->removeButton('back');
        $this->_updateButton('save', 'label', $this->__('Apply Configuration'));
        $this->_updateButton('save', 'id', 'insert_button');
        $this->_updateButton('save', 'onclick', 'blcgRendererConfig.insertParams()');
        
        $this->_formScripts[] = 'blcgRendererConfig = new blcg.Renderer.Config("' . $this->_getFormId() . '", "'
            . $this->getRequest()->getParam('renderer_target_id') . '");';
    }
    
    protected function _beforeToHtml()
    {
        if ($formBlock = $this->getChild('form')) {
            $formBlock->setConfigParams($this->getConfigParams());
        }
        return parent::_beforeToHtml();
    }
}