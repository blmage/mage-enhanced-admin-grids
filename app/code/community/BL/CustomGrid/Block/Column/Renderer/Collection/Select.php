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

class BL_CustomGrid_Block_Column_Renderer_Collection_Select
    extends Mage_Adminhtml_Block_Template
{
    static protected $_instanceNumber = 0;
    protected $_instanceId;
    
    static protected $_descriptionsOutput = false;
    
    public function _construct()
    {
        parent::_construct();
        $this->_instanceId = ++self::$_instanceNumber;
        $this->setId(Mage::helper('core')->uniqHash('blcgCollectionRendererSelect'.$this->_instanceId));
        $this->setTemplate('bl/customgrid/column/renderer/collection/select.phtml');
    }
    
    public function getHtmlId()
    {
        return $this->getId();
    }
    
    public function getJsObjectName()
    {
        return $this->getId() . 'JsObject';
    }
    
    protected function _getAvailableRenderers($withEmpty=false)
    {
        return Mage::getSingleton('customgrid/column_renderer_collection')
            ->getRenderersArray($withEmpty);
    }
    
    protected function _getAvailableRenderer($code)
    {
        $renderers = $this->_getAvailableRenderers(false);
        return (isset($renderers[$code]) ? $renderers[$code] : null);
    }
    
    public function getRenderersJsonConfig($withEmpty=false, $code=null)
    {
        if (!is_null($code)) {
            if ($renderer = $this->_getAvailableRenderer($code)) {
                $renderers = array($renderer);
            } else {
                $renderers = array();
            }
        } else {
            $renderers = $this->_getAvailableRenderers($withEmpty);
        }
        
        $config = array();
        
        foreach ($renderers as $renderer) {
            $values = array(
                'code' => $renderer['code'],
                'isCustomizable' => $renderer['is_customizable'],
            );
            if (isset($renderer['config_window'])) {
                $values['windowConfig'] = array(
                    'width'  => $renderer['config_window']['width'],
                    'height' => $renderer['config_window']['height'],
                    'title'  => $renderer['config_window']['title'],
                );
            }
            $config[] = $values;
        }
        
        return Mage::helper('core')->jsonEncode($config);
    }
    
    protected function _beforeToHtml()
    {
        if ($this->_getData('select_id') == '') {
            $this->setData('select_id', $this->getHtmlId().'-select');
        }
        return parent::_beforeToHtml();
    }
    
    public function getDescriptionsOutput()
    {
        return self::$_descriptionsOutput;
    }
    
    protected function _setDescriptionsOutput($flag=true)
    {
        self::$_descriptionsOutput = $flag;
        return $this;
    }
}