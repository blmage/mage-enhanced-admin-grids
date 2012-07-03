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

class BL_CustomGrid_Block_Column_Renderer_Collection_Config_Form
    extends BL_CustomGrid_Block_Column_Renderer_Config_Form_Abstract
{
    protected function _getFormId()
    {
        return 'column_renderer_collection_options_form';
    }
    
    public function getRenderer()
    {
        $renderer = Mage::registry('current_collection_column_renderer');
        if (!$renderer) {
            Mage::throwException($this->__('Renderer is not specified'));
        }
        return $renderer;
    }
}