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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Column_Renderer_Attribute_Select
    extends Mage_Adminhtml_Block_Template
{
    static protected $_instanceNumber = 0;
    protected $_instanceId;
    
    public function _construct()
    {
        parent::_construct();
        $this->_instanceId = ++self::$_instanceNumber;
        $this->setTemplate('bl/customgrid/column/renderer/attribute/select.phtml');
    }
    
    public function getJsObjectName()
    {
        return $this->getHtmlId() . 'JsObject';
    }
    
    protected function _getAttributes()
    {
        if ($model = $this->getGridModel()) {
            return $model->getAvailableAttributes(true, true);
        } else {
            return array();
        }
    }
    
    /**
     * Return array of available renderers based on configuration
     *
     * @return array
     */
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
            'editableContainerId' => ($this->hasData('editable_container_id') ? $this->getData('editable_container_id') : null),
            'editableCheckboxId'  => ($this->hasData('editable_checkbox_id')  ? $this->getData('editable_checkbox_id')  : null),
            'yesMessageText'      => '<span class="nobr">'.$this->__('Yes').'</span>',
            'noMessageText'       => '<span class="nobr">'.$this->__('No').'</span>',
        ));
    }
    
    protected function _beforeToHtml()
    {
        if ($this->_getData('id') == '') {
            $this->setData('id', $this->_instanceId);
        }
        if ($this->_getData('html_id') == '') {
            $this->setData('html_id', 'blcgAttributeRendererSelect'.$this->getId());
        }
        if ($this->_getData('select_id') == '') {
            $this->setData('select_id', $this->getHtmlId().'-select');
        }
        return parent::_beforeToHtml();
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