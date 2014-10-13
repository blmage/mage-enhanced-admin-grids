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

abstract class BL_CustomGrid_Block_Column_Renderer_Collection_Abstract extends Mage_Adminhtml_Block_Template
{
    protected function _getAvailableRenderers()
    {
        return Mage::getSingleton('customgrid/column_renderer_config_collection')->getRenderersArray();
    }
    
    protected function _getAvailableRenderer($code)
    {
        $renderers = $this->_getAvailableRenderers();
        return (isset($renderers[$code]) ? $renderers[$code] : null);
    }
}
