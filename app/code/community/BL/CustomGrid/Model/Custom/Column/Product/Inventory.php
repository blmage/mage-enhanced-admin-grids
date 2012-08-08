<?php

class BL_CustomGrid_Model_Custom_Column_Product_Inventory
    extends BL_CustomGrid_Model_Custom_Column_Simple_Table
{
    protected $_useConfigMode = false;
    
    public function finalizeConfig()
    {
        parent::finalizeConfig();
        $helper = Mage::helper('customgrid');
        
        if ($this->getIsUseConfigField()) {
            $this->addCustomParam('use_config_filter', array(
                'label'        => $helper->__('Filter on "Use Config"'),
                'description'  => $helper->__('Choose "Yes" to filter values that either use config or not. Else, the filter type will depend on the type of the field'),
                'type'         => 'select',
                'source_model' => 'adminhtml/system_config_source_yesno',
                'value'        => 0,
            ), 10);
            
            $this->addCustomParam('use_config_prefix', array(
                'label'       => $helper->__('Config Values Prefix'),
                'description' => $helper->__('Prefix that will be prepended to the values coming from config'),
                'type'        => 'text',
                'value'       => '',
            ), 20);
            
            $this->addCustomParam('use_config_suffix', array(
                'label'       => $helper->__('Config Values Suffix'),
                'description' => $helper->__('Suffix that will be appended to the values coming from config'),
                'type'        => 'text',
                'value'       => '',
            ), 30);
            
            if (!$this->getWarning()) {
                $this->setWarning($helper->__('Sorting does not take config values into account, so that products that use config may not be sorted consistently'));
            }
            
            $this->setCustomParamsWindowConfig(array('height' => 280), true);
        }
        
        return $this;
    }
    
    public function getTableName()
    {
        return 'cataloginventory/stock_item';
    }
    
    public function getJoinConditionMainField()
    {
        return 'entity_id';
    }
    
    public function getJoinConditionTableField()
    {
        return 'product_id';
    }
    
    public function getTableFieldName($forceBase=false)
    {
        return (!$this->_useConfigMode || $forceBase
            ? $this->getModelParam('table_field_name')
            : $this->getUseConfigFieldName());
    }
    
    public function getIsUseConfigField()
    {
        return (bool) $this->getModelParam('is_use_config_field');
    }
    
    public function getUseConfigFieldName()
    {
        if (!$name = $this->getModelParam('use_config_field_name')) {
            $name = 'use_config_' . $this->getTableFieldName(true);
        }
        return $name;
    }
    
    public function getUseConfigSystemPath()
    {
        if (!$path = $this->getModelParam('system_config_path')) {
            return 'cataloginventory/item_options/' . $this->getTableFieldName();
        }
        return $path;
    }
    
    public function getFieldType()
    {
        return $this->getModelParam('field_type');
    }
    
    public function getAdditionalJoinConditions($alias, $params, $block, $collection, $mainAlias, $tableAlias)
    {
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        // @todo stock_id is usually hard-coded, should we provide it as a customization parameter anyway ?
        return array($adapter->quoteInto($qi($tableAlias.'.stock_id').' = ?', 1));
    }
    
    public function addFieldToGridCollection($alias, $params, $block, $collection)
    {
        // Add main field in all cases
        parent::addFieldToGridCollection($alias, $params, $block, $collection);
        
        // Add use_config field if needed
        if ($this->getIsUseConfigField()) {
            $this->_useConfigMode = true;
            parent::addFieldToGridCollection($alias.'_cpi_uc', $params, $block, $collection);
            $this->_useConfigMode = false;
        }
    }
    
    public function addFilterToGridCollection($collection, $column)
    {
        $field   = ($column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex());
        $cond    = $column->getFilter()->getCondition();
        $flagKey = $column->getBlcgTableFlagKey();
        $params  = $column->getBlcgFilterParams();
        
        if ($field && is_array($cond) && $flagKey
            && isset(self::$_tablesAppliedFlags[$flagKey])
            && ($tableAlias = $collection->getFlag(self::$_tablesAppliedFlags[$flagKey]))) {
            list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
            
            if (is_array($params) && $this->_extractBoolParam($params, 'use_config_filter', false)) {
                if (isset($cond['eq'])) {
                    $collection->getSelect()
                        ->where($qi($tableAlias.'.'.$this->getUseConfigFieldName()).' = '.$adapter->quoteInto('?', (bool) $cond['eq']));
                }
            } else {
                if ($column->getCanUseConfig()) {
                    $conditionBase = new Zend_Db_Expr(
                        'IF('
                        .$qi($tableAlias.'.'.$this->getUseConfigFieldName()).','
                        .$adapter->quoteInto('?', Mage::getStoreConfig($this->getUseConfigSystemPath())).','
                        .$qi($tableAlias.'.'.$this->getTableFieldName())
                        .')'
                    );
                } else {
                    $conditionBase = $qi($tableAlias.'.'.$this->getTableFieldName());
                }
                
                if ($this->getFieldType() == 'decimal') {
                    if (isset($cond['from']) && isset($cond['to'])) {
                        $collection->getSelect()
                            ->where(
                                $conditionBase
                                .' BETWEEN '.$adapter->quoteInto('?', floatval($cond['from']))
                                .' AND '.$adapter->quoteInto('?', floatval($cond['to']))
                            );
                    }
                } elseif (isset($cond['eq'])) {
                    $collection->getSelect()->where($conditionBase.' = '.$adapter->quoteInto('?', $cond['eq']));
                }
            }
        }
        
        return $this;
    }
    
    public function shouldInvalidateFilters($grid, $column, $params, $rendererTypes)
    {
        if (!parent::shouldInvalidateFilters($grid, $column, $params, $rendererTypes)) {
            if ($this->getIsUseConfigField()) {
                return ($this->_extractBoolParam($params['old'], 'use_config_filter')
                    XOR $this->_extractBoolParam($params['new'], 'use_config_filter'));
            }
            return false;
        }
        return true;
    }
    
    protected function _getConditionalGridValues($params)
    {
        $values = array();
        $fieldType = $this->getFieldType();
        
        if (($fieldType == 'options')
            && ($sourceModel = Mage::getModel($this->getModelParam('source_model')))
            && is_callable(array($sourceModel, 'toOptionArray'))) {
            $optionsArray = $sourceModel->toOptionArray();
            $optionsHash  = Mage::helper('customgrid')->getOptionsHashFromOptionsArray($optionsArray);
            
            $values = array(
                'filter'  => 'customgrid/widget_grid_column_filter_select',
                'options' => $optionsArray,
                'options_hash' => $optionsHash,
            );
        }
        if ($this->_extractBoolParam($params, 'use_config_filter', false)) {
            $values['filter']  = 'customgrid/widget_grid_column_filter_select';
            $values['options'] = array(
                array('value' => 1, 'label' => Mage::helper('customgrid')->__('Use Config')),
                array('value' => 0, 'label' => Mage::helper('customgrid')->__('Do Not Use Config')),
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
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        return array_merge(
            array(
                'renderer'   => 'customgrid/widget_grid_column_renderer_product_inventory',
                'field_name' => $this->getTableFieldName(),
                'field_type' => $this->getFieldType(),
                'can_use_config'      => $this->getIsUseConfigField(),
                'use_config_index'    => $alias.'_cpi_uc',
                'use_config_prefix'   => $this->_extractStringParam($params, 'use_config_prefix', ''),
                'use_config_suffix'   => $this->_extractStringParam($params, 'use_config_suffix', ''),
                'system_config_path'  => $this->getUseConfigSystemPath(),
                'blcg_table_flag_key' => $this->getAppliedFlagKey($alias, $params, $block, $block->getCollection(), $this->getTableName()),
                'blcg_filter_params'  => $params,
                'filter_condition_callback' => array($this, 'addFilterToGridCollection'),
            ),
            $this->_getConditionalGridValues($params)
        );
    }
}