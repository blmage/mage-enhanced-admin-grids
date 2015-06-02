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

class BL_CustomGrid_Model_Custom_Column_Order_Payment extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    protected function _prepareConfig()
    {
        $this->setExcludedVersions('1.4.0.*'); // those versions don't have the sales_flat_order_grid table
        return parent::_prepareConfig();
    }
    
    public function getTableName()
    {
        return 'sales/order_payment';
    }
    
    public function getJoinConditionMainFieldName()
    {
        return (($field = parent::getJoinConditionMainFieldName()) ? $field : 'entity_id');
    }
    
    public function getJoinConditionTableFieldName()
    {
        return (($field = parent::getJoinConditionTableFieldName()) ? $field : 'parent_id');
    }
    
    public function getTableFieldName()
    {
        return $this->getConfigParam('payment_field_name');
    }
}
