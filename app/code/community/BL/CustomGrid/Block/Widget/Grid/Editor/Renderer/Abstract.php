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

abstract class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
    extends Mage_Adminhtml_Block_Abstract
{
    protected $_defaultValue = null;
    
    protected function _construct()
    {
        parent::_construct();
        $this->_defaultValue = Mage::helper('customgrid')->__('<em>Updated</em>');
    }
    
    abstract protected function _getRenderedValue();
    
    protected function _toHtml()
    {
        return $this->_getRenderedValue();
    }
}