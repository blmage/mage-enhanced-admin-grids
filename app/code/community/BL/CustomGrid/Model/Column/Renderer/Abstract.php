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

abstract class BL_CustomGrid_Model_Column_Renderer_Abstract extends BL_CustomGrid_Object
{
    protected $_backwardsMap = array();
    
    protected function _getHelper()
    {
        return Mage::helper('customgrid');
    }
    
    protected function _getConfigHelper()
    {
        return Mage::helper('customgrid/config');
    }
    
    public function setValues(array $values)
    {
        $this->setData('values', $values);
        
        foreach ($this->_backwardsMap as $oldKey => $newKey) {
            if (isset($values[$oldKey]) && !isset($values[$newKey])) {
                $this->setData($newKey, $values[$oldKey]);
            }
        }
        
        return $this;
    }
}
