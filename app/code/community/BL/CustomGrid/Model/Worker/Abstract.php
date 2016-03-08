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

abstract class BL_CustomGrid_Model_Worker_Abstract extends BL_CustomGrid_Object
{
    /**
     * Return the type of this worker
     *
     * @return string
     */
    abstract public function getType();
    
    /**
     * Return the base helper
     *
     * @return BL_CustomGrid_Helper_Data
     */
    public function getBaseHelper()
    {
        return Mage::helper('customgrid');
    }
    
    /**
     * Return the config helper
     *
     * @return BL_CustomGrid_Helper_Config
     */
    public function getConfigHelper()
    {
        return Mage::helper('customgrid/config');
    }
}
