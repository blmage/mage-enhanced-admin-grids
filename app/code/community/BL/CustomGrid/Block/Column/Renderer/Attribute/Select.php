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

class BL_CustomGrid_Block_Column_Renderer_Attribute_Select
    extends BL_CustomGrid_Block_Column_Renderer_Select_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/column/renderer/attribute/select.phtml');
    }
    
    protected function _getHtmlIdPrefix()
    {
        return 'blcgARS';
    }
    
    protected function _getAttributes()
    {
        if ($model = $this->getGridModel()) {
            return $model->getAvailableAttributes(true, true);
        } else {
            return array();
        }
    }
    
    protected function _getAvailableRenderers($withEmpty=false)
    {
        return Mage::getSingleton('customgrid/column_renderer_attribute')
            ->getRenderersArray($withEmpty);
    }
    
    public function getAttributesJsonConfig()
    {
        $config = array();
        
        foreach ($this->_getAttributes() as $attribute) {
            $config[] = array(
                'code'           => $attribute->getAttributeCode(),
                'rendererCode'   => $attribute->getRendererCode(),
                'editableValues' => (bool) $attribute->getEditableValues(),
            );
        }
        
        return Mage::helper('core')->jsonEncode($config);
    }
    
    public function getRenderersJsonConfig($withEmpty=false)
    {
        $config = array();
        
        foreach ($this->_getAvailableRenderers($withEmpty) as $renderer) {
            $values = array(
                'code' => $renderer['code'],
                'isCustomizable' => $renderer['is_customizable'],
            );
            if (isset($renderer['config_window'])) {
                $values['windowConfig'] = array(
                    'width'  => $renderer['config_window']['width'],
                    'height' => $renderer['config_window']['height'],
                    'title'  => $this->htmlEscape($renderer['config_window']['title']),
                );
            }
            $config[] = $values;
        }
        
        return Mage::helper('core')->jsonEncode($config);
    }
    
    public function getEditableJsonConfig()
    {
        return Mage::helper('core')->jsonEncode(array(
            'editableContainerId' => ($this->hasData('editable_container_id') ? $this->_getData('editable_container_id') : null),
            'editableCheckboxId'  => ($this->hasData('editable_checkbox_id')  ? $this->_getData('editable_checkbox_id')  : null),
            'yesMessageText'      => '<span class="nobr">'.$this->__('Yes').'</span>',
            'noMessageText'       => '<span class="nobr">'.$this->__('No').'</span>',
        ));
    }
    
    public function getConfigUrl()
    {
        return $this->getUrl('adminhtml/blcg_column_renderer_attribute/index');
    }
    
    protected function _toHtml()
    {
        $html = parent::_toHtml();
        if ($this->getOutputAsJs()) {
            $html = $this->helper('customgrid/js')->prepareHtmlForJsOutput($html, true);
        }
        return $html;
    }
}