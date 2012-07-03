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

class BL_CustomGrid_Model_Custom_Column_Simple_Duplicate
    extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    public function getDuplicatedField()
    {
        return $this->getModelParam('duplicated_field');
    }
    
    public function addFieldToGridCollection($alias, $params, $block, $collection)
    {
        $helper    = $this->_getCollectionHelper();
        $mainAlias = $helper->getCollectionMainTableAlias($collection);
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $field     = $this->getDuplicatedField();
        $collection->getSelect()->columns(array($alias => $mainAlias.'.'.$field));
        $helper->addFilterToCollectionMap($collection, $qi($mainAlias.'.'.$field), $alias);
        return $this;
    }
}