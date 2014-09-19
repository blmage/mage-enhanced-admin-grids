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

class BL_CustomGrid_Model_Grid_Column
    extends BL_CustomGrid_Object
{
    public function getId()
    {
        return $this->_getData('column_id');
    }
    
    public function getGridModel($graceful=false)
    {
        if (($gridModel = $this->_getData('grid_model')) instanceof BL_CustomGrid_Model_Grid) {
            return $gridModel;
        } elseif (!$graceful) {
            Mage::throwException(Mage::helper('customgrid')->__('Invalid grid model'));
        }
        return null;
    }
    
    public function isGrid()
    {
        return ($this->getOrigin() == BL_CustomGrid_Model_Grid::COLUMN_ORIGIN_GRID);
    }
    
    public function isCollection()
    {
        return ($this->getOrigin() == BL_CustomGrid_Model_Grid::COLUMN_ORIGIN_COLLECTION);
    }
    
    public function isAttribute()
    {
        return ($this->getOrigin() == BL_CustomGrid_Model_Grid::COLUMN_ORIGIN_ATTRIBUTE);
    }
    
    public function isCustom()
    {
        return ($this->getOrigin() == BL_CustomGrid_Model_Grid::COLUMN_ORIGIN_CUSTOM);
    }
    
    public function isEditable()
    {
        return ($this->_getData('edit_config') instanceof BL_CustomGrid_Object);
    }
    
    public function getCustomColumnModel($graceful=true)
    {
        $customColumn = null;
        
        if ($this->isCustom()) {
            $customColumn = $this->getData('custom_column_model');
        }
        if (!$customColumn instanceof BL_CustomGrid_Model_Custom_Column_Abstract) {
            if (!$graceful) {
                Mage::throwException(Mage::helper('customgrid')->__('Invalid custom column model'));
            }
            $customColumn = null;
        }
        
        return $customColumn;
    }
    
    public function compareOrderTo(BL_CustomGrid_Model_Grid_Column $column)
    {
        return $this->compareIntDataTo('order', $column);
    }
}
