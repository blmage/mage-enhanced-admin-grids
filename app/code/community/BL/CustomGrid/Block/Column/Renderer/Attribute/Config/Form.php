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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Column_Renderer_Attribute_Config_Form extends BL_CustomGrid_Block_Column_Renderer_Config_Form_Abstract
{
    public function getFormId()
    {
        return 'blcg_column_renderer_attribute_config_form';
    }
    
    public function getRendererType()
    {
        return 'attribute';
    }
    
    /**
     * Return the current attribute column renderer
     * 
     * @return BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
     */
    public function getRenderer()
    {
        return Mage::registry('blcg_attribute_column_renderer');
    }
}
