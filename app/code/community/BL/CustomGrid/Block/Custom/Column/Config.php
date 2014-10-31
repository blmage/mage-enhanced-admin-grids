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

class BL_CustomGrid_Block_Custom_Column_Config extends BL_CustomGrid_Block_Config_Container_Abstract
{
    protected $_blockGroup = 'customgrid';
    protected $_mode = 'config';
    
    protected function _beforeToHtml()
    {
        $this->_formScripts[] =  $this->getJsObjectName() . ' = new blcg.Grid.CustomColumn.ConfigForm('
            . '"blcg_custom_column_config_form", '
            . '"' . $this->getConfigTargetId() . '"'
            . ');';
        
        if ($formBlock = $this->getChild('form')) {
            $formBlock->setConfigValues($this->getConfigValues());
        }
        
        return parent::_beforeToHtml();
    }
    
    public function getCustomColumn()
    {
        return Mage::registry('blcg_custom_column');
    }
    
    protected function _getController()
    {
        return 'custom_column';
    }
    
    protected function _getHeaderText()
    {
        return $this->getCustomColumn()->getName();
    }
    
    protected function _getSaveButtonId()
    {
        return 'blcg_custom_column_config_insert_button';
    }
    
    public function getJsObjectName()
    {
        return 'blcgCustomColumnConfigForm';
    }
    
    public function getUseDefaultForm()
    {
        return false;
    }
    
    public function getConfigTargetId()
    {
        return $this->getDataSetDefault('config_target_id', '');
    }
}
