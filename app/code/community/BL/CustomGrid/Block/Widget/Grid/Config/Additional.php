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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Config_Additional
    extends BL_CustomGrid_Block_Widget_Grid_Config_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/config/additional.phtml');
    }
    
    public function getRequireExistingModel()
    {
        return true;
    }
    
    public function getApplyButtonHtml($onClick, $label=null)
    {
        return $this->getButtonHtml((empty($label) ? $this->__('Apply') : $label), $onClick, 'scalable save');
    }
    
    public function canDisplayDefaultParamsBlocks()
    {
        return $this->getGridModel()
            ->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS);
    }
    
    public function getGridPageNumber()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->blcg_getPage() : null);
    }
    
    public function getGridPageSize()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->blcg_getLimit() : null);
    }
    
    public function getGridSort()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->blcg_getSort() : null);
    }
    
    public function getGridSortDirection()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->blcg_getDir() : null);
    }
    
    public function getGridFilters()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->blcg_getFilterParam() : null);
    }
    
    public function getGridSize()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->blcg_getCollectionSize() : null);
    }
    
    public function getDefaultParamsSaveJsObjectName()
    {
        return $this->getId() . 'DPSForm';
    }
    
    public function getDefaultParamsRemoveJsObjectName()
    {
        return $this->getId() . 'DPRForm';
    }
    
    public function getDefaultParamsSaveUrl()
    {
        return $this->getUrl('customgrid/grid/saveDefaultParams');
    }
    
    public function canDisplayCustomColumnsBlock()
    {
        return $this->getGridModel()->canHaveCustomColumns()
            && $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS);
    }
    
    public function getCustomColumns()
    {
        return $this->getGridModel()->getAvailableCustomColumns(true);
    }
    
    public function getCustomColumnsGroups()
    {
        return $this->getGridModel()->getCustomColumnsGroups();
    }
    
    public function getCustomColumnsListJsObjectName()
    {
        return $this->getId() . 'CCList';
    }
    
    public function getCustomColumnsSaveJsObjectName()
    {
        return $this->getId() . 'CCList';
    }
    
    public function getCustomColumnsSaveUrl()
    {
        return $this->getUrl('customgrid/grid/saveCustomColumns');
    }
    
    public function canDisplayExportBlock()
    {
        return $this->getGridModel()->canExport()
            && $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EXPORT_RESULTS);
    }
    
    public function getExportTypes()
    {
        return $this->getGridModel()->getExportTypes();
    }
    
    public function getExportJsObjectName()
    {
        return $this->getId() . 'ExportForm';
    }
}