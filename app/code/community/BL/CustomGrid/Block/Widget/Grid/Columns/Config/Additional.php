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

class BL_CustomGrid_Block_Widget_Grid_Columns_Config_Additional
    extends BL_CustomGrid_Block_Widget_Grid_Columns_Config_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/columns/config/additional.phtml');
    }
    
    public function getNeedExistingModel()
    {
        return true;
    }
    
    public function getExportJsObjectName()
    {
        return $this->getId() . 'ExportJsObject';
    }
    
    protected function _getCommonActionButtonHtml($htmlId, $applyUrl)
    {
        $model    = $this->getGridModel();
        $onClick  = 'blcg.Tools.submitContainerValues(\'' . $this->jsQuoteEscape($htmlId) . '\', '
            . '\''. $applyUrl . '\', {\'grid_id\': \'' . $model->getId() . '\', '
            . '\'form_key\': \'' . $this->getFormKey() . '\'})';
        return parent::getButtonHtml($this->__('Apply'), $onClick, 'scalable save');
    }
    
    public function getCustomColumnsActionButtonHtml($htmlId)
    {
        return $this->_getCommonActionButtonHtml(
            $htmlId,
            $this->getUrl('adminhtml/blcg_custom_grid/saveCustomColumns')
        );
    }
    
    public function getDefaultParametersActionButtonHtml($htmlId)
    {
        return $this->_getCommonActionButtonHtml(
            $htmlId,
            $this->getUrl('adminhtml/blcg_custom_grid/saveDefault')
        );
    }
    
    public function getExportActionButtonHtml()
    {
        return parent::getButtonHtml($this->__('Export'), $this->getExportJsObjectName().'.doExport()', 'scalable blcg-export');
    }
    
    public function getGridPageNumber()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->blcg_getPage();
        }
        return null;
    }
    
    public function getGridPageSize()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->blcg_getLimit();
        }
        return null;
    }
    
    public function getGridSort()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->blcg_getSort();
        }
        return null;
    }
    
    public function getGridSortDirection()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->blcg_getDir();
        }
        return null;
    }
    
    public function getGridFilters()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->blcg_getFilterParam();
        }
        return null;
    }
    
    public function getGridSize()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->blcg_getCollectionSize();
        }
        return null;
    }
    
    public function canDisplayDefaultParamsBlocks()
    {
        return $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_DEFAULT_PARAMS);
    }
    
    public function canDisplayCustomColumnsBlock()
    {
        return ($this->getGridModel()->canHaveCustomColumns()
            && $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_CUSTOMIZE_COLUMNS));
    }
    
    public function getCustomColumns()
    {
        return $this->getGridModel()->getAvailableCustomColumns(true);
    }
    
    public function getCustomColumnsGroups()
    {
        return $this->getGridModel()->getCustomColumnsGroups();
    }
    
    public function canDisplayExportBlock()
    {
        return ($this->getGridModel()->canExport()
            && $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EXPORT_RESULTS));
    }
    
    public function getExportTypes()
    {
        return $this->getGridModel()->getExportTypes();
    }
    
    public function getExportAdditionalParams()
    {
        $params = array();
        
        if (($block = $this->getGridBlock())
            && ($massactionBlock = $block->getMassactionBlock())) {
            $params[$massactionBlock->getFormFieldNameInternal()] = $massactionBlock->getSelectedJson();
        }
        
        return $params;
    }
}