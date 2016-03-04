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

class BL_CustomGrid_Model_Custom_Column_Product_Inventory extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    /**
     * Validation class names for the different inventory fields
     *
     * @var array
     */
    protected $_fieldValidationClassNames = array(
        'min_qty'          => 'validate-number',
        'min_sale_qty'     => 'validate-digits',
        'max_sale_qty'     => 'validate-number',
        'notify_stock_qty' => 'validate-number',
        'qty'              => 'validate-number',
        'qty_increments'   => 'validate-digits',
    );
    
    protected function _prepareConfig()
    {
        $helper = $this->getBaseHelper();
        
        $notes = array(
            'use_config_filter' => 'Choose "<strong>Yes</strong>" to filter on products that use system configuration '
                . 'values or not. Else, the filter type will depend on the type of the field',
            'use_config_prefix' => 'Prefix that will be prepended to the values coming from the system configuration',
            'use_config_suffix' => 'Suffix that will be appended to the values coming from the system configuration',
            'warning' => 'Sorting does not take system configuration values into account, so that products may not be '
                . 'sorted consistently',
        );
        
        if ($this->getIsUseConfigField()) {
            $this->addCustomizationParam(
                'use_config_filter',
                array(
                    'label'        => $helper->__('Filter on "Use Config Settings"'),
                    'description'  => $helper->__($notes['use_config_filter']),
                    'type'         => 'select',
                    'source_model' => 'adminhtml/system_config_source_yesno',
                    'value'        => 0,
                ),
                10
            );
            
            $this->addCustomizationParam(
                'use_config_prefix',
                array(
                    'label'       => $helper->__('Config Values Prefix'),
                    'description' => $helper->__($notes['use_config_prefix']),
                    'type'        => 'text',
                    'value'       => '',
                ),
                20
            );
            
            $this->addCustomizationParam(
                'use_config_suffix',
                array(
                    'label'       => $helper->__('Config Values Suffix'),
                    'description' => $helper->__($notes['use_config_suffix']),
                    'type'        => 'text',
                    'value'       => '',
                ),
                30
            );
            
            $this->setWarning($helper->__($notes['warning']));
            $this->setCustomizationWindowConfig(array('height' => 350), true);
        }
        
        return parent::_prepareConfig();
    }
    
    public function getTableName()
    {
        return 'cataloginventory/stock_item';
    }
    
    public function getJoinConditionMainFieldName()
    {
        return 'entity_id';
    }
    
    public function getJoinConditionTableFieldName()
    {
        return 'product_id';
    }
    
    /**
     * Return the inventory helper
     *
     * @return BL_CustomGrid_Helper_Catalog_Inventory
     */
    public function getInventoryHelper()
    {
        return Mage::helper('customgrid/catalog_inventory');
    }
    
    /**
     * Return the name of the "Use Config Settings" field corresponding to the current inventory field
     * 
     * @return string|false
     */
    public function getUseConfigFieldName()
    {
        return $this->getInventoryHelper()->getBaseFieldUseConfigFieldName($this->getTableFieldName());
    }
    
    /**
     * Return whether the current inventory field can use configuration values
     * 
     * @return bool
     */
    public function getIsUseConfigField()
    {
        return ($this->getUseConfigFieldName() !== false);
    }
    
    /**
     * Return the type of the current inventory field ("boolean", "decimal" or "options")
     * 
     * @return string
     */
    public function getFieldType()
    {
        return $this->getConfigParam('field_type');
    }
    
    protected function _getAdditionalJoinConditions(
        $columnIndex,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection,
        $mainAlias,
        $tableAlias
    ) {
        /** @var Zend_Db_Adapter_Abstract $adapter */
        list($adapter, $qi) = $this->getCollectionHandler()->getCollectionAdapter($collection, true);
        return array($adapter->quoteInto($qi($tableAlias . '.stock_id') . ' = ?', 1));
    }
    
    protected function _addFieldToSelect(
        Varien_Db_Select $select,
        $columnIndex,
        $fieldName,
        $tableAlias,
        array $params,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        Varien_Data_Collection_Db $collection
    ) {
        $collectionHandler = $this->getCollectionHandler();
        $helper = $collectionHandler->getHelper();
        list(, $qi) = $collectionHandler->getCollectionAdapter($collection, true);
        
        $select->columns(array($columnIndex => $tableAlias . '.' . $fieldName), $tableAlias);
        $helper->addFilterToCollectionMap($collection, $qi($tableAlias . '.' . $fieldName), $columnIndex);
        
        if ($useConfigFieldName = $this->getUseConfigFieldName()) {
            $columnIndex .= '_cpi_uc';
            $select->columns(array($columnIndex => $tableAlias . '.' . $useConfigFieldName), $tableAlias);
            $helper->addFilterToCollectionMap($collection, $qi($tableAlias . '.' . $useConfigFieldName), $columnIndex);
        }
        
        return $this;
    }
    
    /**
     * Add a filter based on the "Use Config Settings" field state to the given grid collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string $tableAlias Inventory table alias
     * @param array $condition Condition values
     */
    protected function _addUseConfigFilterToGridCollection(
        Varien_Data_Collection_Db $collection,
        $tableAlias,
        array $condition
    ) {
        if (isset($condition['eq']) && ($useConfigFieldName = $this->getUseConfigFieldName())) {
            /** @var Zend_Db_Adapter_Abstract $adapter */
            list($adapter, $qi) = $this->getCollectionHandler()->getCollectionAdapter($collection, true);
            
            $collection->getSelect()
                ->where(
                    $qi($tableAlias . '.' . $useConfigFieldName)
                    . ' = '
                    . $adapter->quote((bool) $condition['eq'])
                );
        }
    }
    
    /**
     * Return the base expression on which to apply filters based on actual values
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string $tableAlias Inventory table alias
     * @return Zend_Db_Expr
     */
    protected function _getValueFilterConditionBase(Varien_Data_Collection_Db $collection, $tableAlias)
    {
        /** @var Zend_Db_Adapter_Abstract $adapter */
        list($adapter, $qi) = $this->getCollectionHandler()->getCollectionAdapter($collection, true);
        $tableFieldName = $this->getTableFieldName();
        
        if ($useConfigFieldName = $this->getUseConfigFieldName()) {
            $conditionBase = new Zend_Db_Expr(
                'IF('
                . $qi($tableAlias . '.' . $useConfigFieldName) . ','
                . $adapter->quote($this->getInventoryHelper()->getDefaultConfigInventoryValue($tableFieldName)) . ','
                . $qi($tableAlias . '.' . $tableFieldName)
                . ')'
            );
        } else {
            $conditionBase = new Zend_Db_Expr($qi($tableAlias . '.' . $tableFieldName));
        }
        
        return $conditionBase;
    }
    
    /**
     * Add a filter based on decimal values to the given grid collection
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Zend_Db_Expr $conditionBase Base expression on which to apply the conditions
     * @param array $condition Condition values
     */
    protected function _addDecimalFilterToGridCollection(
        Varien_Data_Collection_Db $collection,
        Zend_Db_Expr $conditionBase,
        array $condition
    ) {
        $adapter = $this->getCollectionHandler()->getCollectionAdapter($collection);
        
        if (isset($condition['from'])) {
            $collection->getSelect()
                ->where($conditionBase . ' >= ' . $adapter->quote(floatval($condition['from'])));
        }
        if (isset($condition['to'])) {
            $collection->getSelect()
                ->where($conditionBase . ' <= ' . $adapter->quote(floatval($condition['to'])));
        }
    }
    
    /**
     * Add a filter based on raw values to the given grid collection
     *
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Zend_Db_Expr $conditionBase Base expression on which to apply the conditions
     * @param array $condition Condition values
     */
    protected function _addBaseFilterToGridCollection(
        Varien_Data_Collection_Db $collection,
        Zend_Db_Expr $conditionBase,
        array $condition
    ) {
        if (isset($condition['eq'])) {
            $adapter = $this->getCollectionHandler()->getCollectionAdapter($collection);
            $collection->getSelect()->where($conditionBase . ' = ' . $adapter->quote($condition['eq']));
        }
    }
    
    /**
     * Add the current filter applied on the given column block to the given grid collection
     * 
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock Grid column block
     * @return BL_CustomGrid_Model_Custom_Column_Product_Inventory
     */
    public function addFilterToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid_Column $columnBlock
    ) {
        $columnIndex  = $columnBlock->getIndex();
        $filterParams = $columnBlock->getBlcgFilterParams();
        $tableAlias   = $this->_getJoinedTableAlias($columnIndex, $filterParams, $columnBlock->getGrid(), $collection);
        $condition    = $columnBlock->getFilter()->getCondition();
        
        if (is_array($condition) && $tableAlias) {
            if (is_array($filterParams) && $this->_extractBoolParam($filterParams, 'use_config_filter', false)) {
                $this->_addUseConfigFilterToGridCollection($collection, $tableAlias, $condition);
            } else {
                $conditionBase = $this->_getValueFilterConditionBase($collection, $tableAlias);
                
                if ($this->getFieldType() == 'decimal') {
                    $this->_addDecimalFilterToGridCollection($collection, $conditionBase, $condition);
                } else {
                    $this->_addBaseFilterToGridCollection($collection, $conditionBase, $condition);
                }
            }
        }
        
        return $this;
    }
    
    public function shouldInvalidateFilters(
        BL_CustomGrid_Model_Grid $gridModel,
        BL_CustomGrid_Model_Grid_Column $columnModel,
        array $params,
        array $renderers
    ) {
        if (!parent::shouldInvalidateFilters($gridModel, $columnModel, $params, $renderers)) {
            if ($this->getIsUseConfigField()) {
                return ($this->_extractBoolParam($params['previous'], 'use_config_filter')
                    XOR $this->_extractBoolParam($params['current'], 'use_config_filter'));
            }
            return false;
        }
        return true;
    }
    
    /**
     * Return the block values that depend on the current field and the given column parameters
     * 
     * @param array $params Column parameters
     * @return array
     */
    protected function _getConditionalBlockValues(array $params)
    {
        $helper = $this->getBaseHelper();
        $values = array();
        $fieldType = $this->getFieldType();
        
        if (($fieldType == 'options')
            && ($sourceModel = Mage::getModel($this->getConfigParam('source_model')))
            && method_exists($sourceModel, 'toOptionArray')) {
            $optionArray = $sourceModel->toOptionArray();
            $optionHash  = $helper->getOptionHashFromOptionArray($optionArray);
            
            $values = array(
                'filter'  => 'customgrid/widget_grid_column_filter_select',
                'options' => $optionArray,
                'option_hash' => $optionHash,
            );
        }
        
        if ($this->_extractBoolParam($params, 'use_config_filter', false)) {
            $values['filter']  = 'customgrid/widget_grid_column_filter_select';
            $values['options'] = array(
                array('value' => 1, 'label' => $helper->__('Config Value')),
                array('value' => 0, 'label' => $helper->__('Own Value')),
            );
        } elseif ($fieldType == 'boolean') {
            $values['filter'] = 'customgrid/widget_grid_column_filter_yesno';
        } elseif ($fieldType == 'decimal') {
            $values['filter'] = 'adminhtml/widget_grid_column_filter_range';
        } elseif ($fieldType != 'options') {
            $values['filter'] = 'adminhtml/widget_grid_column_filter_text';
        }
        
        return $values;
    }
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return array_merge(
            array(
                'renderer'   => 'customgrid/widget_grid_column_renderer_product_inventory',
                'field_name' => $this->getTableFieldName(),
                'field_type' => $this->getFieldType(),
                'can_use_config'     => $this->getIsUseConfigField(),
                'use_config_index'   => $columnIndex . '_cpi_uc',
                'use_config_prefix'  => $this->_extractStringParam($params, 'use_config_prefix', ''),
                'use_config_suffix'  => $this->_extractStringParam($params, 'use_config_suffix', ''),
                'blcg_filter_params' => $params,
                'filter_condition_callback' => array($this, 'addFilterToGridCollection'),
            ),
            $this->_getConditionalBlockValues($params)
        );
    }
    
    public function getGridColumnEditorConfig(
        BL_CustomGrid_Model_Grid_Column $gridColumn,
        BL_CustomGrid_Model_Grid_Editor_Value_Config_Builder $configBuilder
    ) {
        $fieldName = $this->getTableFieldName();
        
        if (in_array($fieldName, array('enable_qty_increments', 'is_qty_decimal', 'manage_stock'))) {
            // Every handled inventory field except the fields with dependent values
            return false;
        }
        
        $formFieldConfig =  array(
            'name' => $fieldName,
            'required' => in_array($fieldName, array('qty')),
            'inventory_field' => $fieldName,
        );
        
        if (in_array($fieldName, array('backorders', 'is_in_stock'))) {
            $formFieldConfig['type'] = 'select';
        
            if ($fieldName == 'backorders') {
                /** @var Mage_CatalogInventory_Model_Source_Backorders $backOrdersSource */
                $backOrdersSource = Mage::getSingleton('cataloginventory/source_backorders');
                $formFieldConfig['values'] = $backOrdersSource->toOptionArray();
            } else {
                /** @var Mage_CatalogInventory_Model_Source_Stock $yesNoSource */
                $yesNoSource = Mage::getSingleton('cataloginventory/source_stock');
                $formFieldConfig['values'] = $yesNoSource->toOptionArray();
            }
        } else {
            $formFieldConfig['type'] = 'text';
        }
        
        if (isset($this->_fieldValidationClassNames[$fieldName])) {
            $formFieldConfig['class'] = $this->_fieldValidationClassNames[$fieldName];
        }
        
        return $this->_buildGridColumnEditableFieldConfig(
            $gridColumn,
            $configBuilder,
            array(
                'form' => array(
                    'block_type' => 'customgrid/widget_grid_editor_form_field_product_inventory',
                    'is_in_grid' => true,
                ),
                'form_field' => $formFieldConfig,
            )
        );
    }
}
