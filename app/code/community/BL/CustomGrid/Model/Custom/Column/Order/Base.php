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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Custom_Column_Order_Base
    extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    public function initConfig()
    {
        parent::initConfig();
        
        // Those versions don't have the sales_flat_order_grid table
        $this->addExcludedVersions('1.4.0.*');
        
        return $this;
    }
    
    public function getTableName()
    {
        return 'sales/order';
    }
    
    public function getJoinConditionMainField()
    {
        return (($field = parent::getJoinConditionMainField()) ? $field : 'entity_id');
    }
    
    public function getJoinConditionTableField()
    {
        return (($field = parent::getJoinConditionTableField()) ? $field : 'entity_id');
    }
    
    public function getTableFieldName()
    {
        return $this->getModelParam('order_field');
    }
}