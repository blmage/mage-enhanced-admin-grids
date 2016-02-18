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

class BL_CustomGrid_Model_Custom_Column_Collection_Handler extends BL_CustomGrid_Model_Custom_Column_Worker_Abstract
{
    /**
     * Generated collection flags
     *
     * @var string[]
     */
    static protected $_uniqueFlags = array();
    
    /**
     * Generated collection aliases
     *
     * @var string[]
     */
    static protected $_uniqueAliases = array();
    
    public function getType()
    {
        return BL_CustomGrid_Model_Custom_Column_Abstract::WORKER_TYPE_COLLECTION_HANDLER;
    }
    
    /**
     * Return grid collection helper
     *
     * @return BL_CustomGrid_Helper_Collection
     */
    public function getHelper()
    {
        return Mage::helper('customgrid/collection');
    }
    
    /**
     * Return the alias used for the main table in the given collection
     *
     * @param Varien_Data_Collection_Db $collection Database collection
     * @return string
     */
    public function getCollectionMainTableAlias(Varien_Data_Collection_Db $collection)
    {
        return $this->getHelper()->getCollectionMainTableAlias($collection);
    }
    
    /**
     * Return the adapter model used by the given collection
     * If requested, also return a shortcut callback to the adapter's quoteIdentifier() method
     *
     * @param Varien_Data_Collection_Db $collection Database collection
     * @param bool $withQiCallback Whether a callback to the adapter's quoteIdentifier() method should also be returned
     * @return mixed Adapter model or an array with the adapter model and the callback
     */
    public function getCollectionAdapter(Varien_Data_Collection_Db $collection, $withQiCallback = false)
    {
        $helper  = $this->getHelper();
        $adapter = $helper->getCollectionAdapter($collection);
        return (!$withQiCallback ? $adapter : array($adapter, $helper->getQuoteIdentifierCallback($adapter)));
    }
    
    /**
     * Generate a string that is unique across all custom columns, basing on the values that already exist
     *
     * @param string $suffix String suffix
     * @param string[] $existing Already existing strings
     * @return string
     */
    protected function _generateUniqueString($suffix, array $existing)
    {
        $class = get_class($this->getCustomColumn());
        $alias = '_' . strtolower(preg_replace('#[^A-Z]#', '', $class) . $suffix);
        $base  = $alias;
        $index = 1;
        
        while (in_array($alias, $existing)) {
            $alias = $base . '_' . $index++;
        }
        
        return $alias;
    }
    
    /**
     * Generate an unique string, suitable for collection flags (for consistency and safe uniqueness)
     *
     * @param string $suffix String suffix
     * @return string
     */
    public function getUniqueCollectionFlag($suffix = '')
    {
        $flag = $this->_generateUniqueString($suffix . '_applied', self::$_uniqueFlags);
        self::$_uniqueFlags[] = $flag;
        return $flag;
    }
    
    /**
     * Generate an unique string, suitable for table aliases (for consistency and safe uniqueness)
     *
     * @param string $suffix String suffix
     * @return string
     */
    public function getUniqueTableAlias($suffix = '')
    {
        $alias = $this->_generateUniqueString($suffix, self::$_uniqueAliases);
        self::$_uniqueAliases[] = $alias;
        return $alias;
    }
    
    /**
     * Prepare the filters map for the given grid collection
     * Used to prevent ambiguous filters and other problems of the same kind
     *
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param array $filters Current filters
     * @return BL_CustomGrid_Model_Custom_Column_Collection_Handler
     */
    public function prepareGridCollectionFiltersMap(
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        array $filters
    ) {
        $this->getHelper()->prepareGridCollectionFiltersMap($collection, $gridBlock, $gridModel, $filters);
        return $this;
    }
    
    /**
     * Restore the original filters map for the given grid collection, after it was previously prepared
     * Used to prevent undesired side effects from the filters map preparation
     *
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param array $filters Current filters
     * @return BL_CustomGrid_Model_Custom_Column_Collection_Handler
     */
    public function restoreGridCollectionFiltersMap(
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        array $filters
    ) {
        $this->getHelper()->restoreGridCollectionFiltersMap($collection, $gridBlock, $gridModel, $filters);
        return $this;
    }
    
    /**
     * Prepare the given grid collection to prevent any potential problem that could occur within it
     * after the custom column will have been applied to it (such as ambiguous filters)
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param string $columnBlockId Grid column block ID
     * @param string $columnIndex Grid column index
     * @param array $params Customization parameters values
     * @param Mage_Core_Model_Store $store Column store
     * @return BL_CustomGrid_Model_Custom_Column_Collection_Handler
     */
    public function prepareGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        if (!$gridBlock->getData('_blcg_added_collection_prepare_callbacks')) {
            $gridBlock->blcg_addCollectionCallback(
                BL_CustomGrid_Model_Custom_Column_Abstract::GC_EVENT_BEFORE_SET_FILTERS,
                array($this, 'prepareGridCollectionFiltersMap'),
                array($gridModel),
                true
            );
            
            $customColumn = $this->getCustomColumn();
            
            if ($customColumn->getBaseHelper()->isMageVersionGreaterThan(1, 6)
                && $customColumn->getGridHelper()->isEavEntityGrid($gridBlock, $gridModel)) {
                /**
                 * Fix for Mage_Eav_Model_Entity_Collection_Abstract::_renderOrders() on 1.7+,
                 * which fails to handle qualified field names, as it forces the use of addAttributeToSort() :
                 * when this method is applied on mapped fields,
                 * the fact that they are qualified makes them unrecognizable as attributes or static fields
                 * Note that this does not affect filters applied on custom columns derived from
                 * BL_CustomGrid_Model_Custom_Column_Simple_Abstract, as it forces field orders on EAV entity grids
                 */
                $gridBlock->blcg_addCollectionCallback(
                    BL_CustomGrid_Model_Custom_Column_Abstract::GC_EVENT_AFTER_SET_FILTERS,
                    array($this, 'restoreGridCollectionFiltersMap'),
                    array($gridModel),
                    true
                );
            }
            
            $gridBlock->setData('_blcg_added_collection_prepare_callbacks', true);
        }
        return $this;
    }
}
