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

class BL_CustomGrid_Model_Column_Renderer_Source_Options_Source
{
    static protected $_optionArray = null;
    
    public function toOptionArray()
    {
        if (is_null(self::$_optionArray)) {
            self::$_optionArray = Mage::getModel('customgrid/options_source')
                ->getCollection()
                ->load()
                ->toOptionArray();
            
            array_unshift(self::$_optionArray, array('value' => '', 'label' => ''));
        }
        return self::$_optionArray;
    }
}
