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

abstract class BL_CustomGrid_Model_Custom_Column_Simple_Abstract
    extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    abstract public function addFieldToGridCollection($alias, $params, $block, $collection);
    
    public function shouldForceFieldOrder($collection, $block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return (($block->blcg_getSort(false) === $id)
            && $this->_getGridHelper()->isEavEntityGrid($block, $model));
    }
    
    public function addSortToGridCollection($id, $alias, $block, $collection)
    {
        $collection->getSelect()->order(new Zend_Db_Expr($alias.' '.$block->blcg_getDir()));
        return $this;
    }
    
    protected function _applyToGridCollection($collection, $block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $block->blcg_addCollectionCallback(
            self::GC_EVENT_AFTER_SET,
            array($this, 'addFieldToGridCollection'),
            array($alias, $params),
            true
        );
        
        if ($this->shouldForceFieldOrder($collection, $block, $model, $id, $alias, $params, $store, $renderer)) {
            $block->blcg_addCollectionCallback(
                self::GC_EVENT_AFTER_SET,
                array($this, 'addSortToGridCollection'),
                array($id, $alias),
                true
            );
        }
        
        return $this;
    }
}