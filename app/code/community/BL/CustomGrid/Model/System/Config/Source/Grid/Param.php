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

class BL_CustomGrid_Model_System_Config_Source_Grid_Param extends BL_CustomGrid_Model_Source_Fixed
{
    protected $_optionHash = array(
        BL_CustomGrid_Model_Grid::GRID_PARAM_PAGE   => 'Page Number',
        BL_CustomGrid_Model_Grid::GRID_PARAM_LIMIT  => 'Page Size',
        BL_CustomGrid_Model_Grid::GRID_PARAM_SORT   => 'Sort',
        BL_CustomGrid_Model_Grid::GRID_PARAM_DIR    => 'Sort Direction',
        BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER => 'Filter',
    );
    
    /**
     * @param bool $withNone Whether "None" option should be included
     * @return array
     */
    public function toOptionArray($withNone = true)
    {
        $optionArray = parent::toOptionArray();
        
        if ($withNone) {
            array_unshift(
                $optionArray,
                array(
                    'value' => BL_CustomGrid_Model_Grid::GRID_PARAM_NONE,
                    'label' => $this->_getTranslationHelper()->__('None'),
                )
            );
        }
        
        return $optionArray;
    }
}
