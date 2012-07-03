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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Number
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    public function isAppliableToColumn($attribute, $grid)
    {
        return in_array($attribute->getBackendType(), array('decimal', 'int'));
    }
    
    public function getColumnGridValues($attribute, $store, $grid)
    {
        return array(
            'type' => 'number',
            'show_number_sign' => (bool) $this->_getData('show_number_sign'),
        );
    }
}