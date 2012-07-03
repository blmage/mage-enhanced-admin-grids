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

abstract class BL_CustomGrid_Block_Column_Renderer_Select_Abstract
    extends Mage_Adminhtml_Block_Template
{
    static protected $_instanceNumber = 0;
    static protected $_htmlIds = array();
    protected $_instanceId;
    
    protected function _construct()
    {
        parent::_construct();
        $this->_instanceId = ++self::$_instanceNumber;
    }
    
    abstract protected function _getHtmlIdPrefix();
    
    public function getId()
    {
        if (!$this->hasData('id')) {
            $this->setId('_'.$this->_instanceId);
        }
        return $this->_getData('id');
    }
    
    public function setId($id)
    {
        if ($id == $this->_getData('id')) {
            return $this;
        }
        return $this->setData('id', $id)
            ->unsetData('html_id')
            ->unsetData('select_id')
            ->unsetData('config_button_id');
    }
    
    public function getHtmlId()
    {
        if (!$this->hasData('html_id')) {
            $id = $this->getId();
            
            if (isset(self::$_htmlIds[$id])) {
                $this->setHtmlId(self::$_htmlIds[$id]);
            } else {
                $this->setHtmlId(Mage::helper('core')->uniqHash($this->_getHtmlIdPrefix().$id));
            }
        }
        return $this->_getData('html_id');
    }
    
    public function setHtmlId($htmlId)
    {
        if ($htmlId == $this->_getData('html_id')) {
            return $this;
        } else {
            self::$_htmlIds[$this->getId()] = $htmlId;
        }
        return $this->setData('html_id', $htmlId)
            ->unsetData('select_id')
            ->unsetData('config_button_id');
    }
    
    public function getSelectId()
    {
        if ($this->hasData('select_id') == '') {
            $this->setData('select_id', $this->getHtmlId().'-select');
        }
        return $this->_getData('select_id');
    }
    
    public function getConfigButtonId()
    {
        if (!$this->hasData('config_button_id')) {
            $this->setData('config_button_id', $this->getHtmlId().'-config-button');
        }
        return $this->_getData('config_button_id');
    }
    
    public function getJsObjectName()
    {
        return $this->getHtmlId().'JsObject';
    }
}