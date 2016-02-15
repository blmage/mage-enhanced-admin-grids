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
 * @copyright  Copyright (c) 2015 Matthew Gamble (https://github.com/mwgamble)
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_System_Config_Source_Shipping_Methods
{
    /**
     * Options cache
     * 
     * @var array|null
     */
    protected $_optionArray = null;
    
    /**
     * Designed for use in the Sales Order grid.
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (is_null($this->_optionArray)) {
            /** @var $res Mage_Core_Model_Resource */
            $resource   = Mage::getSingleton('core/resource');
            $connection = $resource->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);
            
            $select = $connection->select();
            $select->from($resource->getTableName('sales/order'), array('shipping_method'));
            $select->distinct(true);
            
            $methods = $connection->fetchCol($select);
            $this->_optionArray = array();
            
            foreach ($methods as $method) {
                // There isn't a sensible way to get a generic label for a method, as it can differ for each order.
                // As a result, just use the method code as the label as well.
                $this->_optionArray[] = array(
                    'value' => $method,
                    'label' => $method,
                );
            }
        }
        return $this->_optionArray;
    }
}
