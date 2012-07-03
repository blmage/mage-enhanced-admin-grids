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

class BL_CustomGrid_Block_Custom_Column_Config
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_blockGroup = 'customgrid';
        $this->_controller = 'custom_column';
        $this->_mode = 'config';
        $this->_headerText = $this->getCustomColumn()->getName();
        
        $this->removeButton('reset');
        $this->removeButton('back');
        $this->_updateButton('save', 'label', $this->__('Apply Configuration'));
        $this->_updateButton('save', 'id', 'insert_button');
        $this->_updateButton('save', 'onclick', 'blcgCustomColumnForm.insertParams()');
        
        $this->_formScripts[] = 'blcgCustomColumnForm = new blcg.CustomColumn.Form("custom_column_config_options_form", "'
            . $this->getRequest()->getParam('renderer_target_id') . '");';
    }
    
    public function getCustomColumn()
    {
        if (!$column = Mage::registry('current_custom_column')) {
            Mage::throwException($this->__('Custom column is not specified'));
        }
        return $column;
    }
    
    protected function _beforeToHtml()
    {
        if ($formBlock = $this->getChild('form')) {
            $formBlock->setConfigParams($this->getConfigParams());
        }
        return parent::_beforeToHtml();
    }
}