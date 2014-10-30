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

class BL_CustomGrid_Block_Column_Renderer_Attribute_Select extends
    BL_CustomGrid_Block_Column_Renderer_Attribute_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/column/renderer/attribute/select.phtml');
    }
    
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        
        if ($this->getOutputAsJs()) {
            $html = $this->helper('customgrid/js')->prepareHtmlForJsOutput($html, true);
        }
        
        return $html;
    }
    
    public function getId()
    {
        return $this->getDataSetDefault('id', $this->helper('core')->uniqHash('blcgCRS'));
    }
    
    public function getSelectId()
    {
        return $this->getDataSetDefault('select_id', $this->getId() . '-renderer-select');
    }
    
    public function getConfigButtonId()
    {
        return $this->getDataSetDefault('config_button_id', $this->getId() . '-config-button');
    }
    
    public function getRendererTargetId()
    {
        return $this->getDataSetDefault('renderer_target_id', $this->getId() . '-renderer-params');
    }
    
    public function getRendererParams()
    {
        return ($this->getAttributeCode() && ($params = $this->_getData('renderer_params')))
            ? $this->helper('customgrid/string')->htmlDoubleEscape($params)
            : '';
    }
    
    public function getEditableJsonConfig()
    {
        return $this->helper('core')
            ->jsonEncode(
                array(
                    'editableContainerId' => $this->_getData('editable_container_id'),
                    'editableCheckboxId'  => $this->_getData('editable_checkbox_id'),
                )
            );
    }
}
