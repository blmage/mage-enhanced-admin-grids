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

abstract class BL_CustomGrid_Block_Column_Renderer_Config_Abstract extends BL_CustomGrid_Block_Widget_Form_Container
{
    abstract protected function _getController();
    abstract public function getRenderer(); 
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_blockGroup = 'customgrid';
        $this->_controller = $this->_getController();
        $this->_mode = 'config';
        $this->_headerText = $this->getRenderer()->getName();
        
        $this->_removeButtons(array('back', 'delete', 'reset'));
        
        $this->_updateButton(
            'save',
            null,
            array(
                'id'         => 'blcg_renderer_config_insert_button',
                'label'      => $this->__('Apply Configuration'),
                'onclick'    => $this->getJsObjectName() . '.insertParams();',
                'sort_order' => 0,
            )
        );
    }
    
    protected function _beforeToHtml()
    {
         $this->_formScripts[] = $this->getJsObjectName() . ' = new blcg.Grid.Renderer.ConfigForm('
            . '"' . $this->getFormHtmlId() . '",'
            . '"' . $this->getRendererTargetId() . '"'
            . ');';
        
        if ($formBlock = $this->getChild('form')) {
            $formBlock->setConfigValues($this->getConfigValues());
        }
        
        return parent::_beforeToHtml();
    }
    
    public function getUseDefaultForm()
    {
        return false;
    }
    
    public function getJsObjectName()
    {
        return 'blcgRendererConfigForm';
    }
    
    public function getRendererTargetId()
    {
        return $this->getDataSetDefault('renderer_target_id', '');
    }
}
