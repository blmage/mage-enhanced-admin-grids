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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Field_Longtext extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    protected function _getRenderedValue($renderableValue)
    {
        /** @var $helper Mage_Core_Helper_String */
        $helper = $this->helper('core/string');
        return (($helper->strlen($renderableValue) < 255) ? $renderableValue : $this->getDefaultValue());
    }
}
