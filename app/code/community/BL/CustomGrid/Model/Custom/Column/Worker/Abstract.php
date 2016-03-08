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

abstract class BL_CustomGrid_Model_Custom_Column_Worker_Abstract extends BL_CustomGrid_Model_Worker_Abstract
{
    /**
     * Set the current custom column model
     *
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $customColumn Custom column model to set as current
     * @return BL_CustomGrid_Model_Custom_Column_Worker_Abstract
     */
    public function setCustomColumn(BL_CustomGrid_Model_Custom_Column_Abstract $customColumn)
    {
        return $this->setData('custom_column', $customColumn);
    }
    
    /**
     * Return the current custom column model
     *
     * @return BL_CustomGrid_Model_Custom_Column_Abstract
     */
    public function getCustomColumn()
    {
        if ((!$customColumn = $this->_getData('custom_column'))
            || (!$customColumn instanceof BL_CustomGrid_Model_Custom_Column_Abstract)) {
            Mage::throwException('Invalid custom column model');
        }
        return $customColumn;
    }
}
