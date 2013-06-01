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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Model_Custom_Column_Customer_Address
    extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    abstract public function getAddressType();
    
    public function getAttributeCode()
    {
        return $this->getModelParam('attribute_code');
    }
    
    public function addFieldToGridCollection($alias, $params, $block, $collection)
    {
        $collection->joinAttribute($alias, 'customer_address/'.$this->getAttributeCode(), 'default_'.$this->getAddressType(), null, 'left');
        return $this;
    }
}