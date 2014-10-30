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

class BL_CustomGrid_Model_System_Config_Source_Grid_Param
{
    public function toOptionArray($withNone = true)
    {
        $helper  = Mage::helper('customgrid');
        
        $options = array(
            array(
                'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_PAGE,
                'label' => $helper->__('Page Number'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_LIMIT,
                'label' => $helper->__('Page Size'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_SORT,
                'label' => $helper->__('Sort'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_DIR,
                'label' => $helper->__('Sort Direction'),
            ),
            array(
                'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER,
                'label' => $helper->__('Filter'),
            ),
        );
        
        if ($withNone) {
            array_unshift(
                $options,
                array(
                    'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_NONE,
                    'label' => $helper->__('None'),
                )
            );
        }
        
        return $options;
    }
}
