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

class BL_CustomGrid_Model_Mysql4_Grid_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid');
    }
    
    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->walk('afterLoad');
    }
    
    public function addColumnsToResult()
    {
        $gridIds = array();
        foreach ($this as $grid) {
            $gridIds[] = $grid->getId();
        }
        
        if (!empty($gridIds)) {
            $read = $this->getResource()->getReadConnection();
            $columns = $read->fetchAll($read->select()
                ->from($this->getTable('customgrid/grid_column')
                ->columns('*')
                ->columns(array('is_visible'  => new Zend_Db_Expr('IF(is_visible=2, 1, is_visible)')))
                ->columns(array('filter_only' => new Zend_Db_Expr('IF(is_visible=2, 1, 0)')))
                ->where('grid_id IN ?', $gridIds))
                ->order(array('order', 'asc')));
            
            foreach ($columns as $column) {
                if ($this->getItemById($column['grid_id'])) {
                    $this->getItemById($column['grid_id'])->addColumn($column);
                }
            }
        }
        
        return $this;
    }
}