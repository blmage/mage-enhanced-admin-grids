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

class BL_CustomGrid_Block_Column_Renderer_Collection_Select extends
    BL_CustomGrid_Block_Column_Renderer_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/column/renderer/collection/select.phtml');
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
    
    public function getRenderer()
    {
        return (($code = $this->getRendererCode()) && ($renderer = $this->_getAvailableRenderer($code)))
            ? $renderer
            : null; 
    }
    
    public function getRendererParams()
    {
        return ($this->getRenderer() && ($params = $this->_getData('renderer_params')))
            ? $this->helper('customgrid/string')->htmlDoubleEscape($params)
            : '';
    }
}
