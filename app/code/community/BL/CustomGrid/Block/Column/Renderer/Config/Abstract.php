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

abstract class BL_CustomGrid_Block_Column_Renderer_Config_Abstract extends BL_CustomGrid_Block_Config_Container_Abstract
{
    protected $_blockGroup = 'customgrid';
    protected $_mode = 'config';
    
    /**
     * Return the current column renderer
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Abstract
     */
    abstract public function getRenderer(); 
    
    protected function _beforeToHtml()
    {
         $this->_formScripts[] = $this->getJsObjectName() . ' = new blcg.Grid.Renderer.ConfigForm('
            . '"' . $this->getFormHtmlId() . '",'
            . '"' . $this->getRendererTargetId() . '"'
            . ');';
        
        if ($formBlock = $this->getChild('form')) {
            /** @var $formBlock BL_CustomGrid_Block_Column_Renderer_Config_Form_Abstract */
            $formBlock->setConfigValues($this->getConfigValues());
        }
        
        return parent::_beforeToHtml();
    }
    
    protected function _getHeaderText()
    {
        return $this->getRenderer()->getName();
    }
    
    protected function _getSaveButtonId()
    {
        return 'blcg_renderer_config_insert_button';
    }
    
    public function getJsObjectName()
    {
        return 'blcgRendererConfigForm';
    }
    
    public function getUseDefaultJsFormObject()
    {
        return false;
    }
    
    /**
     * Return the HTML ID of the target element for the renderer values
     * 
     * @return string
     */
    public function getRendererTargetId()
    {
        return $this->getDataSetDefault('renderer_target_id', '');
    }
}
