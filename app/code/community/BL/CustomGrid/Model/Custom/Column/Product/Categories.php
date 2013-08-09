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

class BL_CustomGrid_Model_Custom_Column_Product_Categories
    extends BL_CustomGrid_Model_Custom_Column_Simple_Abstract
{
    const FILTER_MODE_ONE_CHOOSEN  = 'one_choosen';
    const FILTER_MODE_ALL_CHOOSEN  = 'all_choosen';
    const FILTER_MODE_NONE_CHOOSEN = 'none_choosen';
    const FILTER_MODE_CUSTOM       = 'custom';
    
    protected $_customFilterOperators = null;
    
    protected function _getCustomFilterOperators($asOptionArray=false)
    {
        $helper = Mage::helper('customgrid');
        
        if (is_null($this->_customFilterOperators)) {
            $this->_customFilterOperators = array(
                '>'  => $helper->__('Greater than'),
                '>=' => $helper->__('Greater than or equal to'),
                '='  => $helper->__('Equal'),
                '!=' => $helper->__('Not equal'),
                '<=' => $helper->__('Lesser than or equal to'),
                '<'  => $helper->__('Lesser than'),
            );
        }
        
        return ($asOptionArray
            ? $helper->getOptionsArrayFromOptionsHash($this->_customFilterOperators)
            : $this->_customFilterOperators);
    }
    
    public function initConfig()
    {
        parent::initConfig();
        $helper = Mage::helper('customgrid');
        
        $this->addCustomParam('separator', array(
            'label'       => $helper->__('Categories Separator'),
            'description' => $helper->__('Indicate here the string that will be used each product\'s category values. If none is set, ", " will be used'),
            'type'        => 'text',
            'value'       => '',
        ), 10);
        
        $this->addCustomParam('full_paths', array(
            'label'        => $helper->__('Display Full Paths'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 20);
        
        $this->addCustomParam('level_separator', array(
            'label'       => $helper->__('Levels Separator'),
            'description' => $helper->__('Indicate here the string that will be used to separate categories in each full path. If none is set, " > " will be used'),
            'type'        => 'text',
            'value'       => '',
            'depends'     => array('full_paths' => array('value' => 1)),
        ), 30);
        
        $this->addCustomParam('ascent_limit', array(
            'label'       => $helper->__('Ascent Limit Level'),
            'description' => $helper->__('If needed, indicate here the level on which to stop ascent for the full paths display. One level will be displayed in all cases, even if the concerned category has a lower level'),
            'type'        => 'text',
            'value'       => '',
            'depends'     => array('full_paths' => array('value' => 1)),
        ), 40);
        
        $this->addCustomParam('display_ids', array(
            'label'        => $helper->__('Display IDs'),
            'description'  => $helper->__('Choose "Yes" to display IDs instead of names'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 50);
        
        $this->addCustomParam('boolean_filter', array(
            'label'        => $helper->__('With/Without Filter'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 60);
        
        $this->addCustomParam('filter_mode', array(
            'label'        => $helper->__('Filter Mode'),
            'type'         => 'select',
            'source_model' => 'customgrid/system_config_source_product_categories_filter_mode',
            'value'        => self::FILTER_MODE_ONE_CHOOSEN,
            'depends'      => array('boolean_filter' => array('value' => 0)),
        ), 70);
        
        $this->addCustomParam('custom_filter_operator', array(
            'label'       => $helper->__('Custom Filter - Operator'),
            'description' => $helper->__('Filtered products will have to belong to a number of categories amongst the chosen ones, that can be verified using this value and the one from the "Custom Filter - Number" field'),
            'type'        => 'select',
            'values'      => $this->_getCustomFilterOperators(true),
            'depends'     => array(
                'boolean_filter' => array('value' => 0),
                'filter_mode'    => array('value' => self::FILTER_MODE_CUSTOM),
            ),
        ), 80);
        
        $this->addCustomParam('custom_filter_number', array(
            'label'       => $helper->__('Custom Filter - Number'),
            'description' => $helper->__('Filtered products will have to belong to a number of categories amongst the chosen ones, that can be verified using this value and the one from the "Custom Filter - Operator" field'),
            'type'        => 'text',
            'depends'     => array(
                'boolean_filter' => array('value' => 0),
                'filter_mode'    => array('value' => self::FILTER_MODE_CUSTOM),
            ),
        ), 90);
        
        $this->setCustomParamsWindowConfig(array('height' => 520));
        
        return $this;
    }
    
    public function shouldInvalidateFilters($grid, $column, $params, $rendererTypes)
    {
        if (!parent::shouldInvalidateFilters($grid, $column, $params, $rendererTypes)) {
            return ($this->_extractBoolParam($params['old'], 'boolean_filter')
                XOR $this->_extractBoolParam($params['new'], 'boolean_filter'));
        }
        return true;
    }
    
    protected function _getCategoryIdsSelect($collection, $forFilter=false, $ids=null)
    {
        $helper    = $this->_getCollectionHelper();
        $mainAlias = $this->_getCollectionMainTableAlias($collection);
        list($adapter, $qi) = $this->_getCollectionAdapter($collection, true);
        $cpAlias   = $this->_getUniqueTableAlias($forFilter ? '_filter' : '_select');
        $mainField = ($forFilter ? 'COUNT(*)' : 'GROUP_CONCAT('.$qi($cpAlias.'.category_id').')');
        
        $select = $adapter->select()
            ->from(
                array($cpAlias => $collection->getTable('catalog/category_product')),
                array('value' => new Zend_Db_Expr($mainField))
            )
            ->where($qi($cpAlias.'.product_id').' = '.$qi($mainAlias.'.entity_id'));
        
        if (!$forFilter) {
            $select->group($cpAlias.'.product_id');
        }
        if (is_array($ids)) {
            $select->where($qi($cpAlias.'.category_id').' IN (?)', $ids);
        }
        
        return $select;
    }
    
    public function addFieldToGridCollection($alias, $params, $block, $collection)
    {
        $idsQuery = '('.$this->_getCategoryIdsSelect($collection).')';
        $collection->getSelect()->columns(array($alias => new Zend_Db_Expr($idsQuery)));
        return $this;
    }
    
    public function addIdsFilterToGridCollection($collection, $column)
    {
        $field  = ($column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex());
        $params = $column->getBlcgFilterParams();
        
        if ($field && is_array($params)) {
            if ($this->_extractBoolParam($params, 'boolean_filter')) {
                if (!is_null($filter = $column->getFilter()->getValue())) {
                    $countQuery = '('.$this->_getCategoryIdsSelect($collection, true).')';
                    $collection->getSelect()->where(new Zend_Db_Expr($countQuery).' '.((bool)$filter ? '>' : '=').' 0');
                }
            } else {
                $filteredIds  = array_filter(array_unique(explode(',', $column->getFilter()->getValue())));
                $filterMode   = $this->_extractStringParam($params, 'filter_mode', self::FILTER_MODE_ONE_CHOOSEN, true);
                $filterOpr    = '>=';
                $filterNumber = '1';
                
                if ($filterMode == self::FILTER_MODE_ALL_CHOOSEN) {
                    $filterNumber = count($filteredIds);
                } elseif ($filterMode == self::FILTER_MODE_NONE_CHOOSEN) {
                    $filterOpr    = '=';
                    $filterNumber = '0';
                } elseif ($filterMode == self::FILTER_MODE_CUSTOM) {
                    // Extract and use if consistent
                    if (!is_int($filterNumber = $this->_extractIntParam($params, 'custom_filter_number', null, true))
                        || !($filterOpr = $this->_extractStringParam($params, 'custom_filter_operator', null, true))) {
                        // Don't apply any custom filter if params are missing
                        return $this;
                    }
                }
                
                $countQuery = '('.$this->_getCategoryIdsSelect($collection, true, $filteredIds).')';
                $collection->getSelect()->where(new Zend_Db_Expr($countQuery).' '.$filterOpr.' '.$filterNumber);
            }
        }
        
        return $this;
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $tree = null;
        $hash = null;
        $displayIds = $this->_extractBoolParam($params, 'display_ids');
        $displayFullPaths = $this->_extractBoolParam($params, 'full_paths');
        
        if ($displayFullPaths) {
            $tree = Mage::getModel('catalog/category')
                ->getTreeModel()
                ->setStoreId($store->getId())
                ->load();
        }
        if (!$displayIds) {
            $collection = Mage::getModel('catalog/category')
                ->getCollection()
                ->setStoreId($store->getId())
                ->addAttributeToSelect('name')
                ->load(); 
            
            if (!is_null($tree)) {
                $tree->addCollectionData($collection);
            } else {
                foreach ($collection as $category) {
                    $hash[$category->getId()] = $category;
                }
            }
        }
        
        return array(
            'renderer' => 'customgrid/widget_grid_column_renderer_product_categories',
            'filter'   => 'customgrid/widget_grid_column_filter_product_categories',
            'category_tree'      => $tree,
            'category_hash'      => $hash,
            'result_separator'   => $this->_extractStringParam($params, 'separator', ', ', true),
            'level_separator'    => $this->_extractStringParam($params, 'level_separator', ' > ', true),
            'ascent_limit'       => $this->_extractIntParam($params, 'ascent_limit', -1),
            'boolean_filter'     => $this->_extractBoolParam($params, 'boolean_filter'),
            'display_ids'        => $displayIds,
            'display_full_paths' => $displayFullPaths,
            'blcg_filter_params' => $params,
            'filter_condition_callback' => array($this, 'addIdsFilterToGridCollection'),
        );
    }
}