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

abstract class BL_CustomGrid_Model_Grid_Worker_Abstract extends BL_CustomGrid_Model_Worker_Abstract
{
    /**
     * Set the current grid model
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model to set as current
     * @return BL_CustomGrid_Model_Grid_Worker_Abstract
     */
    public function setGridModel(BL_CustomGrid_Model_Grid $gridModel)
    {
        return $this->setData('grid_model', $gridModel);
    }
    
    /**
     * Return the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        if ((!$gridModel = $this->_getData('grid_model'))
            || (!$gridModel instanceof BL_CustomGrid_Model_Grid)) {
            Mage::throwException('Invalid grid model');
        }
        return $gridModel;
    }
    
    /**
     * Return the current grid profile
     * 
     * @return BL_CustomGrid_Model_Grid_Profile
     */
    public function getGridProfile()
    {
        return $this->getGridModel()->getProfile();
    }
}
