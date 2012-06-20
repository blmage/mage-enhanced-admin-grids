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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Mysql4_Grid extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customgrid/grid', 'grid_id');
    }
    
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        $gridId = $object->getId();
        $write  = $this->_getWriteAdapter();
        $columnsTable = $this->getTable('customgrid/grid_column');
        $columnsIds = array();
        
        foreach ($object->getColumns() as $column) {
            if (isset($column['column_id']) && ($column['column_id'] > 0)) {
                // Update existing columns
                $write->update($columnsTable, $column, $write->quoteInto('column_id = ?', $column['column_id']));
                $columnsIds[] = $column['column_id'];
            } else {
                // Insert new columns
                $column['grid_id'] = $gridId;
                $write->insert($columnsTable, $column);
                $columnsIds[] = $write->lastInsertId();
            }
        }
        
        // Delete obsolete columns (all not inserted / updated)
        $write->delete(
            $columnsTable,
            $write->quoteInto('grid_id = ' . $gridId  . ' AND column_id NOT IN (?)', $columnsIds)
        );
        
        return $this;
    }
    
    public function getGridColumns($gridId)
    {
        $read = $this->_getReadAdapter();
        $columnsTable = $this->getTable('customgrid/grid_column');
        
        return $read->fetchAll($read->select()
            ->from($columnsTable)
            ->where('grid_id = ?', $gridId));
    }
}