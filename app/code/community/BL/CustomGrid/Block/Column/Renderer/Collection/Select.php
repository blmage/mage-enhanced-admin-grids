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

class BL_CustomGrid_Block_Column_Renderer_Collection_Select
    extends BL_CustomGrid_Block_Column_Renderer_Select_Abstract
{
    static protected $_descriptionsOutput = false;
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/column/renderer/collection/select.phtml');
    }
    
    protected function _getHtmlIdPrefix()
    {
        return 'blcgCRS';
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
    
    public function getConfigUrl()
    {
        return $this->getUrl('adminhtml/blcg_column_renderer_collection/index');
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