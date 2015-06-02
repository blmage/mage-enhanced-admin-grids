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

class BL_CustomGrid_Model_Column_Renderer_Config_Attribute extends BL_CustomGrid_Model_Column_Renderer_Config_Abstract
{
    public function getConfigType()
    {
        return BL_CustomGrid_Model_Config_Manager::TYPE_COLUMN_RENDERERS_ATTRIBUTE;
    }
    
    protected function _checkElementModelCompliance($model)
    {
        return ($model instanceof BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract);
    }
}
