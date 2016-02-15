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

class BL_CustomGrid_Model_System_Config_Source_Product_Categories_Filter_Mode extends BL_CustomGrid_Model_Source_Fixed
{
    protected $_optionHash = array(
        BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_ONE_CHOOSEN
            => 'The filtered products must belong to at least one chosen category',
        BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_ALL_CHOOSEN
            => 'The filtered products must belong to all of the chosen categories',
        BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_NONE_CHOOSEN
            => 'The filtered products must not belong to any of the chosen categories',
        BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_CUSTOM
            => 'Custom',
    );
}
