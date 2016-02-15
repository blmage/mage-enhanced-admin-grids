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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Custom_Column_Simple_Duplicate extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    /**
     * Return the name of the duplicated field
     * 
     * @return string
     */
    public function getDuplicatedFieldName()
    {
        return $this->getConfigParam('duplicated_field_name');
    }
    
    public function getDuplicatedFieldTableAlias(Varien_Data_Collection_Db $collection)
    {
        return $this->getConfigParam(
            'duplicated_field_table_alias',
            $this->_getCollectionHelper()->getCollectionMainTableAlias($collection)
        );
    }
    
    public function addFieldToGridCollection(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        return $this->_addFieldToSelect(
            $collection->getSelect(),
            $columnIndex,
            $this->getDuplicatedFieldName(),
            $this->getDuplicatedFieldTableAlias($collection),
            $params,
            $gridBlock,
            $collection
        );
    }
}
