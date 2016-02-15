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

abstract class BL_CustomGrid_Model_Custom_Column_Order_Address_Abstract extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    protected function _prepareConfig()
    {
        $this->setExcludedVersions('1.4.0.*');
        return parent::_prepareConfig();
    }
    
    abstract public function getAddressType();
    
    protected function _getAppliedFlagKey(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $tableName
    ) {
        return $tableName . '/' . $this->getAddressType();
    }
    
    public function getTableName()
    {
        return 'sales/order_address';
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
        return $this->getConfigParam('address_field_name');
    }
    
    protected function _getAdditionalJoinConditions(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $mainAlias,
        $tableAlias
    ) {
        /** @var $adapter Zend_Db_Adapter_Abstract */
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        return array($adapter->quoteInto($qi($tableAlias . '.address_type') . ' = ?', $this->getAddressType()));
    }
}
