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

class BL_CustomGrid_Model_System_Config_Source_Product_Categories_Filter_Mode
{
    public function toOptionArray()
    {
        $helper = Mage::helper('customgrid');
        return array(
            array(
                'value' => BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_ONE_CHOOSEN,
                'label' => $helper->__('The filtered products must belong to at least one chosen category'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_ALL_CHOOSEN,
                'label' => $helper->__('The filtered products must belong to all chosen categories'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_NONE_CHOOSEN,
                'label' => $helper->__('The filtered products must not belong to any of the chosen categories'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Custom_Column_Product_Categories::FILTER_MODE_CUSTOM,
                'label' => $helper->__('Custom'),
            ),
        );
    }
}