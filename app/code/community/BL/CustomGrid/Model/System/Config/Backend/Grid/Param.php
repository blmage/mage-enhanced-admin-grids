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

class BL_CustomGrid_Model_System_Config_Backend_Grid_Param extends Mage_Core_Model_Config_Data
{
    protected function _beforeSave()
    {
        if (is_array($value = $this->getValue())) {
            if (empty($value) || in_array(BL_CustomGrid_Model_Grid::GRID_PARAM_NONE, $value)) {
                $this->setValue(array(BL_CustomGrid_Model_Grid::GRID_PARAM_NONE));
            }
        }
        return parent::_beforeSave();
    }
}
