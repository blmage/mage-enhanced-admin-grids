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

class BL_CustomGrid_Block_Options_Source_Edit extends BL_CustomGrid_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId   = 'id';
        $this->_blockGroup = 'customgrid';
        $this->_controller = 'options_source';
        parent::__construct();
        
        if ($this->getOptionsSourceType()) {
            $this->_addSaveAndContinueButton();
        }
    }
    
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        if (!$this->getOptionsSourceType()) {
            $this->_formScripts[] = '
function setSettings(urlTemplate, typeElement)
{
    var template = new Template(urlTemplate, /(^|.|\\r|\\n)({{(\w+)}})/);
    setLocation(template.evaluate({type: $F(typeElement)}));
}
            ';
            
            $this->_removeButton('save');
        }
        
        return $this;
    }
    
    public function isTabbedFormContainer()
    {
        return true;
    }
    
    public function getFormTabsBlock()
    {
        return $this->getLayout()->getBlock('blcg.options_source.edit.tabs');
    }
    
    public function getHeaderText()
    {
        $optionsSource = $this->getOptionsSource();
        
        if ($optionsSource->getId()) {
            $headerText = $this->htmlEscape($optionsSource->getName());
        } else {
            $headerText = $this->__('New Options Source');
        }
        
        return $headerText;
    }
    
    /**
     * Return the edited options source
     * 
     * @return BL_CustomGrid_Model_Options_Source
     */
    public function getOptionsSource()
    {
        return Mage::registry('blcg_options_source');
    }
    
    /**
     * Return the type of the edited options source
     * 
     * @return string
     */
    public function getOptionsSourceType()
    {
        return (!$type = $this->getOptionsSource()->getType())
            ? $this->getRequest()->getParam('type', null)
            : $type;
    }
}
