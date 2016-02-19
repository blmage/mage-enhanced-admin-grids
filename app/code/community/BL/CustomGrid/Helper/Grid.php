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

class BL_CustomGrid_Helper_Grid extends Mage_Core_Helper_Abstract
{
    /**
     * Whether the current Magento version is greater or equal to 1.6
     * 
     * @var bool|null
     */
    protected $_checkFromOneDotSix = null;
    
    /**
     * Base verification callbacks by verification type and block type
     * 
     * @var array
     */
    protected $_baseVerificationCallbacks = array(
        'block' => array(
            'adminhtml/catalog_product_grid'  => '_verifyCatalogProductGridBlock',
            'adminhtml/sales_order_grid'      => '_verifySalesOrderGridBlock',
            'adminhtml/sales_invoice_grid'    => '_verifySalesInvoiceGridBlock',
            'adminhtml/sales_shipment_grid'   => '_verifySalesShipmentGridBlock',
            'adminhtml/sales_creditmemo_grid' => '_verifySalesCreditmemoGridBlock',
        ),
        'collection' => array(
            'adminhtml/catalog_product_grid'  => '_verifyCatalogProductGridCollection',
            'adminhtml/sales_order_grid'      => '_verifySalesOrderGridCollection',
            'adminhtml/sales_invoice_grid'    => '_verifySalesInvoiceGridCollection',
            'adminhtml/sales_shipment_grid'   => '_verifySalesShipmentGridCollection',
            'adminhtml/sales_creditmemo_grid' => '_verifySalesCreditmemoGridCollection',
        ),
    );
    
    /**
     * Additional verification callbacks by verification type and block type
     * 
     * @var array
     */
    protected $_additionalVerificationCallbacks = array(
        'block' => array(),
        'collection' => array(),
    );
    
    /**
     * Return whether the given grid block has been rewrited by this extension
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return bool
     */
    public function isRewritedGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = Mage::helper('customgrid');
        return $helper->isRewritedGridBlock($gridBlock);
    }
    
    /**
     * Return the columns that are actually displayable for the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return Mage_Adminhtml_Block_Widget_Grid_Column[]
     */
    public function getGridBlockDisplayableColumns(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $columns = $gridBlock->getColumns();
        
        foreach ($columns as $key => $column) {
            if ($column->getBlcgFilterOnly()) {
                unset($columns[$key]);
            }
        }
        
        return $columns;
    }
    
    /**
     * Return the grid model from the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @return BL_CustomGrid_Model_Grid|null
     */
    public function getGridModelFromBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return Mage::helper('customgrid')->isRewritedGridBlock($gridBlock)
            ? $gridBlock->blcg_getGridModel()
            : null;
    }
    
    /**
     * Return whether the given grid block is based on EAV entities
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    public function isEavEntityGrid(Mage_Adminhtml_Block_Widget_Grid $gridBlock, BL_CustomGrid_Model_Grid $gridModel)
    {
        return ($gridBlock->getCollection() instanceof Mage_Eav_Model_Entity_Collection_Abstract);
    }
    
    /**
     * Return the collection on which is based the grid block to whom belongs the given column block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock Column block
     * @return Varien_Data_Collection_Db
     */
    public function getColumnBlockGridCollection(Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock)
    {
        return $columnBlock->getGrid()->getCollection();
    }
    
    /**
     * Return the filter index usable by the given column block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock Column block
     * @return string
     */
    public function getColumnBlockFilterIndex(Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock)
    {
        return (($filterIndex = $columnBlock->getFilterIndex()) ? $filterIndex : $columnBlock->getIndex());
    }
    
    /**
     * Return whether the given column filter value is empty
     *
     * @param mixed $filterValue Filter value
     * @return bool
     */
    public function isColumnFilterValueEmpty($filterValue)
    {
        return (!empty($filterValue) || (strlen($filterValue) > 0));
    }
    
    /**
     * Return the indexes on which are based the active filters from the given list applied to the given grid block
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param array $filters Applied filters
     * @return array
     */
    public function getGridBlockActiveFiltersIndexes(Mage_Adminhtml_Block_Widget_Grid $gridBlock, array $filters)
    {
        $filtersIndexes = array();
        
        foreach ($gridBlock->getColumns() as $columnBlockId => $columnBlock) {
            if (isset($filters[$columnBlockId])
                && !$this->isColumnFilterValueEmpty($filters[$columnBlockId])
                && $columnBlock->getFilter()) {
                $filtersIndexes[] = $this->getColumnBlockFilterIndex($columnBlock);
            }
        }
        
        return $filtersIndexes;
    }
    
    /**
     * Return whether the given block ID has been generated via Mage_Core_Helper_Abstract::uniqHash()
     *
     * @param string $gridBlockId Grid block ID
     * @return bool
     */
    public function isVaryingGridBlockId($gridBlockId)
    {
        return (strlen($gridBlockId) >= 32)
            && preg_match('#([0-9a-f]){32}#', substr($gridBlockId, -32));
    }
    
    /**
     * Return whether the two given varying grid block IDs actually correspond to the same base block ID
     * 
     * @param string $gridBlockIdA One grid block ID
     * @param string $gridBlockIdB Another grid block ID
     * @return bool
     */
    public function checkVaryingGridBlockIdsEquality($gridBlockIdA, $gridBlockIdB)
    {
        return (substr($gridBlockIdA, 0, -32) == substr($gridBlockIdB, 0, -32));
    }
    
    /**
     * Register an additional verification callback for the given block type
     * 
     * @param string $type Verification type (either "block" or "collection")
     * @param string $blockType Grid block type
     * @param callable $callback Verification callback
     * @param array $params Callback parameters
     * @param bool $addNative Whether the native callback parameters should be appended to the callback call
     * @return BL_CustomGrid_Helper_Grid
     */
    public function addVerificationCallback($type, $blockType, $callback, array $params = array(), $addNative = true)
    {
        if (!isset($this->_additionalVerificationCallbacks[$type][$blockType])) {
            $this->_additionalVerificationCallbacks[$type][$blockType] = array();
        }
        
        $this->_additionalVerificationCallbacks[$type][$blockType][] = array(
            'callback'   => $callback,
            'params'     => $params,
            'add_native' => (bool) $addNative,
        );
        
        return $this;
    }
    
    /**
     * Whether grid block and collection verifications should be based on Magento 1.6+
     * 
     * @return bool
     */
    public function shouldCheckFrom16()
    {
        if (is_null($this->_checkFromOneDotSix)) {
            $this->_checkFromOneDotSix = Mage::helper('customgrid')->isMageVersionGreaterThan(1, 5);
        }
        return $this->_checkFromOneDotSix;
    }
    
    /**
     * Verify that the given grid element (either block or collection) corresponds to what is expected in order to
     * safely apply further treatments (such as applying custom columns)
     * By default, it is verified that both the block and collection inherit from their corresponding core classes
     * 
     * @param string $type Verification type
     * @param string $blockType Grid block type
     * @param mixed $element Verified element (either block or collection)
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    protected function _verifyGridElement($type, $blockType, $element, BL_CustomGrid_Model_Grid $gridModel)
    {
        $checkFromOneDotSix = $this->shouldCheckFrom16();
        $isVerified = true;
        
        if (isset($this->_baseVerificationCallbacks[$type][$blockType])) {
            $isVerified = (bool) call_user_func(
                array($this, $this->_baseVerificationCallbacks[$type][$blockType]),
                $element,
                $gridModel,
                $checkFromOneDotSix
            );
        }
        if ($isVerified && isset($this->_additionalVerificationCallbacks[$type][$blockType])) {
            foreach ($this->_additionalVerificationCallbacks[$type][$blockType] as $callback) {
                $isVerified = (bool) call_user_func_array(
                    $callback['callback'],
                    array_merge(
                        array_values($callback['params']),
                        ($callback['add_native'] ? array($element, $gridModel, $checkFromOneDotSix) : array())
                    )
                );
                
                if (!$isVerified) {
                    break;
                }
            }
        }
        
        return $isVerified;
    }
    
    /**
     * Verify that the given grid block corresponds to what is expected in order to safely apply further treatments
     * (such as applying custom columns)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    public function verifyGridBlock(Mage_Adminhtml_Block_Widget_Grid $gridBlock, BL_CustomGrid_Model_Grid $gridModel)
    {
        return (($gridBlock instanceof Mage_Adminhtml_Block_Widget_Grid) && $this->isRewritedGridBlock($gridBlock))
            ? $this->_verifyGridElement('block', $gridModel->getBlockType(), $gridBlock, $gridModel)
            : false;
    }
    
    /**
     * Verify that the collection from the given grid block corresponds to what is expected in order to safely apply
     * further treatments (such as applying custom columns)
     * 
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    public function verifyGridCollection(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel
    ) {
        return (($collection = $gridBlock->getCollection()) && ($collection instanceof Varien_Data_Collection_Db))
            ? $this->_verifyGridElement('collection', $gridModel->getBlockType(), $collection, $gridModel)
            : false;
    }
    
    /**
     * Base block verification callback for catalog product grids
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifyCatalogProductGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return ($gridBlock instanceof Mage_Adminhtml_Block_Catalog_Product_Grid);
    }
    
    /**
     * Base collection verification callback for catalog product grids
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifyCatalogProductGridCollection(
        Varien_Data_Collection_Db $collection,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return $checkFromOneDotSix
            ? ($collection instanceof Mage_Catalog_Model_Resource_Product_Collection)
            : ($collection instanceof Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection);
    }
    
    /**
     * Base block verification callback for sales order grids
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesOrderGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return ($gridBlock instanceof Mage_Adminhtml_Block_Sales_Order_Grid);
    }
    
    /**
     * Base collection verification callback for sales order grids
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesOrderGridCollection(
        Varien_Data_Collection_Db $collection,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return $checkFromOneDotSix
            ? ($collection instanceof Mage_Sales_Model_Resource_Order_Grid_Collection)
            : ($collection instanceof Mage_Sales_Model_Mysql4_Order_Grid_Collection);
    }
    
    /**
     * Base block verification callback for sales invoice grids
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesInvoiceGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return ($gridBlock instanceof Mage_Adminhtml_Block_Sales_Invoice_Grid);
    }
    
    /**
     * Base collection verification callback for sales invoice grids
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesInvoiceGridCollection(
        Varien_Data_Collection_Db $collection,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return $checkFromOneDotSix
            ? ($collection instanceof Mage_Sales_Model_Resource_Order_Invoice_Grid_Collection)
            : ($collection instanceof Mage_Sales_Model_Mysql4_Order_Invoice_Grid_Collection);
    }
    
    /**
     * Base block verification callback for sales shipment grids
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesShipmentGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return ($gridBlock instanceof Mage_Adminhtml_Block_Sales_Shipment_Grid);
    }
    
    /**
     * Base collection verification callback for sales shipment grids
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesShipmentGridCollection(
        Varien_Data_Collection_Db $collection,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return $checkFromOneDotSix
            ? ($collection instanceof Mage_Sales_Model_Resource_Order_Shipment_Grid_Collection)
            : ($collection instanceof Mage_Sales_Model_Mysql4_Order_Shipment_Grid_Collection);
    }
    
    /**
     * Base block verification callback for sales creditmemo grids
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesCreditmemoGridBlock(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return ($gridBlock instanceof Mage_Adminhtml_Block_Sales_Creditmemo_Grid);
    }
    
    /**
     * Base collection verification callback for sales creditmemo grids
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param bool $checkFromOneDotSix Whether verification should be based on Magento 1.6+
     * @return bool
     */
    protected function _verifySalesCreditmemoGridCollection(
        Varien_Data_Collection_Db $collection,
        BL_CustomGrid_Model_Grid $gridModel,
        $checkFromOneDotSix
    ) {
        return $checkFromOneDotSix
            ? ($collection instanceof Mage_Sales_Model_Resource_Order_Creditmemo_Grid_Collection)
            : ($collection instanceof Mage_Sales_Model_Mysql4_Order_Creditmemo_Grid_Collection);
    }
}
