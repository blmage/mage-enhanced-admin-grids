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

class BL_CustomGrid_Block_Widget_Grid_Columns_Config_Columns
    extends BL_CustomGrid_Block_Widget_Grid_Columns_Config_Abstract
{
    const CHILD_VALUE_HTML_KEY = '_html';
    
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bl/customgrid/widget/grid/columns/config/columns.phtml');
    }
    
    public function getDisplayableWithoutBlock()
    {
        return true;
    }
    
    public function getJsObjectName()
    {
        return $this->getId() . 'JsObject';
    }
    
    public function getUseDragNDrop()
    {
        return Mage::helper('customgrid/config')->getSortWithDnd();
    }
    
    public function canDisplayEditablePart()
    {
        if (!$this->hasData('can_display_editable_part')) {
            $this->setData(
                'can_display_editable_part',
                $this->getGridModel()->hasEditableColumns()
            );
        }
        return $this->_getData('can_display_editable_part');
    }
    
    public function canChooseEditableColumns()
    {
        if (!$this->hasData('can_choose_editable_columns')) {
            $this->setData(
                'can_choose_editable_columns',
                $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_CHOOSE_EDITABLE_COLUMNS)
            );
        }
        return $this->_getData('can_choose_editable_columns');
    }
    
    protected function _getStoreSelect()
    {
        if (!$this->getChild('store_select')) {
            $this->setChild(
                'store_select',
                $this->getLayout()
                    ->createBlock('customgrid/store_select')
            );
        }
        return $this->getChild('store_select');
    }
    
    protected function getStoreSelectHtml($selectName, $storeId=null, $jsOutput=false)
    {
        return $this->_getStoreSelect()
            ->hasUseGridOption(true)
            ->hasDefaultOption(true)
            ->setStoreId($storeId)
            ->setSelectName($selectName)
            ->setSelectClassNames('select')
            ->setOutputAsJs($jsOutput)
            ->toHtml();
    }
    
    protected function _getCollectionRendererSelect($id)
    {
        if (!$this->getChild('collection_renderer_select_'.$id)) {
            $this->setChild(
                'collection_renderer_select_'.$id,
                $this->getLayout()
                    ->createBlock('customgrid/column_renderer_collection_select')
                    ->setId($id)
            );
        }
        return $this->getChild('collection_renderer_select_'.$id);
    }
    
    public function getCollectionRendererSelectHtml($id, $selectName='', $paramsTargetId=null, $code=null,
        $forced=false, $forcedLabel='')
    {
        return $this->_getCollectionRendererSelect($id)
            ->setRendererCode($code)
            ->setIsForcedRenderer($forced)
            ->setForcedRendererLabel($forcedLabel)
            ->setParamsTargetId($paramsTargetId)
            ->setSelectName($selectName)
            ->setSelectClassNames('select')
            ->toHtml();
    }
    
    protected function _getAttributesSelect($id)
    {
        if (!$this->getChild('attribute_renderer_select_'.$id)) {
            $this->setChild(
                'attribute_renderer_select_'.$id,
                $this->getLayout()
                    ->createBlock('customgrid/column_renderer_attribute_select')
                    ->setId($id)
            );
        }
        return $this->getChild('attribute_renderer_select_'.$id);
    }
    
    public function getAttributesSelectHtml($id, $selectName='', $paramsTargetId=null,
        $editableContainerId=null, $editableCheckboxId=null, $code=null, $jsOutput=false)
    {
        return $this->_getAttributesSelect($id)
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
    
    public function getAttributesSelectJsObjectName($id)
    {
        return $this->_getAttributesSelect($id)->getJsObjectName();
    }
    
    public function getColumnLockedValues($columnId)
    {
        return $this->getGridModel()->getColumnLockedValues($columnId);
    }
    
    public function getSaveUrl()
    {
        return $this->getUrl('adminhtml/blcg_custom_grid/save');
    }
    
    public function getCustomColumnConfigUrl()
    {
        return $this->getUrl('adminhtml/blcg_custom_column_config/index');
    }
    
    // @todo restore getErrorText() from where it was lost
}