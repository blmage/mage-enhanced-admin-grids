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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Columns_Config
    extends Mage_Adminhtml_Block_Widget
{
    static protected $_instancesNumber = 0; 
    protected $_instanceId = null;
    
    protected function _construct()
    {
        parent::_construct();
        $this->_instanceId = ++self::$_instancesNumber;
        $this->setId(Mage::helper('core')->uniqHash('customGridConfig_'.$this->_instanceId));
        $this->setErrorText(Mage::helper('core')->jsQuoteEscape($this->__('Please select items.')));
        $this->setTemplate('bl/customgrid/widget/grid/columns/config.phtml');
    }
    
    public function getGridModel()
    {
        if (!$this->hasData('grid_model')) {
            if ($model = Mage::registry('current_custom_grid')) {
                $this->setData('grid_model', $model);
            } else {
                $this->setData('grid_model', null);
            }
        }
        return $this->getData('grid_model');
    }
    
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }
    
    public function getJsObjectName()
    {
        return $this->getId() . 'JsObject';
    }
    
    public function getExportJsObjectName()
    {
        return $this->getId() . 'ExportJsObject';
    }
    
    public function getSaveUrl()
    {
        return $this->getUrl('customgrid/custom_grid/save');
    }
    
    public function getDeleteUrl()
    {
        if ($model = $this->getGridModel()) {
            return $this->getUrl('customgrid/custom_grid/delete', array('grid_id' => $model->getId()));
        }
        return null;
    }
    
    public function getCustomizeButtonHtml()
    {
        return parent::getButtonHtml($this->__('Grid Customization'), '$(\''.$this->getHtmlId().'\').toggle()', 'scalable blcg-customize');
    }
    
    public function getBackButtonHtml()
    {
        return parent::getButtonHtml($this->__('Back'), 'setLocation(\''.$this->getUrl('*/*/').'\')', 'back');
    }
    
    public function getDeleteButtonHtml()
    {
        return parent::getButtonHtml(
            $this->__('Delete'), 
            'confirmSetLocation(\''.$this->__('Are you sure?').'\', \''.$this->getDeleteUrl().'\')', 
            'scalable delete'
        );
    }
    
    public function getSaveButtonHtml()
    {
        return parent::getButtonHtml($this->__('Save'), $this->getJsObjectName().'.saveGrid();', 'scalable save');
    }
    
    public function getToggleAdditionalButtonHtml()
    {
        return parent::getButtonHtml($this->__('More Options'), '$(\''.$this->getHtmlId().'-additional\').toggle();', 'scalable blcg-additional');
    }
    
    public function getAddColumnButtonHtml()
    {
        return parent::getButtonHtml($this->__('Add Attribute Column'), $this->getJsObjectName().'.addColumn();', 'scalable add');
    }
    
    public function getDefaultParametersActionButtonHtml($htmlId)
    {
        if ($model = $this->getGridModel()) {
            $applyUrl = $this->getUrl('customgrid/custom_grid/saveDefault');
            $onClick  = 'blcg.Tools.submitContainerValues(\'' . $this->jsQuoteEscape($htmlId) . '\', '
                        . '\''. $applyUrl . '\', {\'grid_id\': \'' . $model->getId() . '\', '
                        . '\'form_key\': \'' . $this->getFormKey() . '\'})';
            return parent::getButtonHtml($this->__('Apply'), $onClick, 'scalable save');
        }
        return '';
    }
    
    public function getExportActionButtonHtml()
    {
        return parent::getButtonHtml($this->__('Export'), $this->getExportJsObjectName().'.doExport()', 'scalable blcg-export');
    }
    
    public function getGridFilterParamName()
    {
        if ($grid = $this->getGridBlock()) {
            return $grid->getVarNameFilter();
        }
        return null;
    }
    
    public function getGridFilterParamValue()
    {
        if (($grid = $this->getGridBlock()) && !$grid->getUseAjax()
            && !is_null($grid->getRequest()->getParam($grid->getVarNameFilter(), null))
            && ($param = $grid->blcg_getFilterParam())) {
            return $param;
        }
        return null;
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
    
    public function canDisplayExportBlock()
    {
        if ($model = $this->getGridModel()) {
            return $model->canExport();
        }
        return false;
    }
    
    public function canDisplayEditablePart()
    {
        if (!$this->hasData('can_display_editable_part')) {
            $flag = false;
            if ($model = $this->getGridModel()) {
                $flag = $model->hasEditableColumns();
            }
            $this->setData('can_display_editable_part', $flag);
        }
        return $this->getData('can_display_editable_part');
    }
    
    public function canChooseEditableColumns()
    {
        if (!$this->hasData('can_choose_editable_columns')) {
            $this->setData(
                'can_choose_editable_columns',
                Mage::getModel('admin/session')
                    ->isAllowed('system/customgrid/editor/choose_columns')
            );
        }
        return $this->getData('can_choose_editable_columns');
    }
    
    public function getExportTypes()
    {
        if ($model = $this->getGridModel()) {
            return $model->getExportTypes();
        }
        return array();
    }
    
    public function getUseDragNDrop()
    {
        return Mage::helper('customgrid/config')->getSortWithDnd();
    }
    
    public function getFromAjax()
    {
        return ($this->getRequest()->getQuery('ajax') ? true : false);
    }
    
    public function getStoreSelectHtml($selectName, $storeId=null, $jsOutput=false)
    {
        return $this->getLayout()->createBlock('customgrid/store_select')
            ->hasUseGridOption(true)
            ->hasDefaultOption(true)
            ->setStoreId($storeId)
            ->setSelectName($selectName)
            ->setSelectClassNames('select')
            ->setOutputAsJs($jsOutput)
            ->toHtml();
    }
    
    public function getCollectionRendererSelectHtml($selectName, $paramsTargetId=null, $code=null, $forced=false, $forcedLabel='')
    {
        return $this->getLayout()->createBlock('customgrid/column_renderer_collection_select')
            ->setRendererCode($code)
            ->setIsForcedRenderer($forced)
            ->setForcedRendererLabel($forcedLabel)
            ->setParamsTargetId($paramsTargetId)
            ->setSelectName($selectName)
            ->setSelectClassNames('select')
            ->toHtml();
    }
    
    public function getAttributesSelectHtml($id, $selectName, $paramsTargetId=null, $editableContainerId=null,
        $editableCheckboxId=null, $code=null, $jsOutput=false)
    {
        return $this->getLayout()->createBlock('customgrid/column_renderer_attribute_select')
            ->setId($id)
            ->setGridModel($this->getGridModel())
            ->setAttributeCode($code)
            ->setOutputAsJs($jsOutput)
            ->setParamsTargetId($paramsTargetId)
            ->setEditableContainerId($editableContainerId)
            ->setEditableCheckboxId($editableCheckboxId)
            ->setSelectName($selectName)
            ->setSelectClassNames('select')
            ->toHtml();
    }
    
    public function getColumnLockedValues($columnId)
    {
        if ($model = $this->getGridModel()) {
            return $model->getColumnLockedValues($columnId);
        }
        return array();
    }
    
    protected function _toHtml()
    {
        if (($model = $this->getGridModel())
            && ($model->getId() || $this->getGridBlock())) {
            if (!$model->getId()) {
                // Init and save custom grid model if new one
                $model->initWithGridBlock($this->getGridBlock())->save();
                $this->setIsNewModel(true);
            } elseif (($block = $this->getGridBlock())
                      && !Mage::helper('customgrid')->isRewritedGrid($block)) {
                // Do not display if a not rewrited grid is given
                return '';
            } else {
                $this->setIsNewModel(false);
            }
            return parent::_toHtml();
        }
        return '';
    }
}