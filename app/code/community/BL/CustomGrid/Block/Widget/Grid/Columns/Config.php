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
        return $this->_getData('grid_model');
    }
    
    public function getFormKey()
    {
        return Mage::getSingleton('core/session')->getFormKey();
    }
    
    public function getJsObjectName()
    {
        return $this->getId() . 'JsObject';
    }
    
    public function getSaveUrl()
    {
        return $this->getUrl('adminhtml/blcg_custom_grid/save');
    }
    
    public function getDeleteUrl()
    {
        return $this->getUrl('adminhtml/blcg_custom_grid/delete', array('grid_id' => $this->getGridModel()->getId()));
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
    
    public function getToggleGridInfosButtonHtml()
    {
        return parent::getButtonHtml($this->__('Grid Infos'), '$(\''.$this->getHtmlId().'-grid-infos\').toggle();', 'scalable blcg-grid-infos');
    }
    
    public function getToggleAdditionalButtonHtml()
    {
        return parent::getButtonHtml($this->__('More Options'), '$(\''.$this->getHtmlId().'-additional\').toggle();', 'scalable blcg-additional');
    }
    
    public function getAddColumnButtonHtml()
    {
        return parent::getButtonHtml($this->__('Add Attribute Column'), $this->getJsObjectName().'.addColumn();', 'scalable add');
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
    
    public function getFromAjax()
    {
        return ($this->getRequest()->getQuery('ajax') ? true : false);
    }
    
    public function canDisplayColumnsConfig()
    {
        if (!$this->hasData('can_display_columns_config')) {
            $this->setData(
                'can_display_columns_config',
                $this->getGridModel()
                    ->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_CUSTOMIZE_COLUMNS)
            );
        }
        return $this->_getData('can_display_columns_config');
    }
    
    public function getDefaultColumnsConfig()
    {
        return $this->canDisplayColumnsConfig();
    }
    
    public function getColumnsConfigHtml()
    {
        if ($this->canDisplayColumnsConfig()) {
            return $this->getLayout()->createBlock('customgrid/widget_grid_columns_config_columns')
                ->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setIsNewModel($this->getIsNewModel())
                ->setGridBlock($this->getGridBlock())
                ->toHtml();
        }
        return '';
    }
    
    public function canDisplayAdditional()
    {
        if (!$this->hasData('can_display_additional')) {
            $this->setData(
                'can_display_additional',
                ($this->getGridBlock()
                 && !$this->getIsNewModel()
                 && (($this->getGridModel()->canHaveCustomColumns() && $this->canDisplayColumnsConfig())
                     || $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EDIT_DEFAULT_PARAMS)
                     || $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_EXPORT_RESULTS)))
            );
        }
        return $this->_getData('can_display_additional');
    }
    
    public function getDefaultAdditional()
    {
        return (!$this->getDefaultColumnsConfig()
            && $this->canDisplayAdditional());
    }
    
    public function getAdditionalHtml()
    {
        if ($this->canDisplayAdditional()) {
            return $this->getLayout()->createBlock('customgrid/widget_grid_columns_config_additional')
                ->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setIsNewModel($this->getIsNewModel())
                ->setGridBlock($this->getGridBlock())
                ->setStartDisplayed($this->getDefaultAdditional())
                ->toHtml();
        }
        return '';
    }
    
    public function canDisplayGridInfos()
    {
        if (!$this->hasData('can_display_grid_infos')) {
            $this->setData(
                'can_display_grid_infos',
                (!$this->getIsNewModel()
                 && $this->getGridBlock()
                 && $this->getGridModel()
                        ->checkUserActionPermission(BL_CustomGrid_Model_Grid::GRID_ACTION_VIEW_GRID_INFOS))
            );
        }
        return $this->_getData('can_display_grid_infos');
    }
    
    public function getDefaultGridInfos()
    {
        return (!$this->getDefaultColumnsConfig()
            && !$this->getDefaultAdditional()
            && $this->canDisplayGridInfos());
    }
    
    public function getGridInfosHtml()
    {
        if ($this->canDisplayGridInfos()) {
            return $this->getLayout()->createBlock('customgrid/widget_grid_columns_config_infos')
                ->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setIsNewModel($this->getIsNewModel())
                ->setGridBlock($this->getGridBlock())
                ->setStartDisplayed($this->getDefaultGridInfos())
                ->toHtml();
        }
        return '';
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
                      && !$this->helper('customgrid')->isRewritedGrid($block)) {
                // Do not display if a not rewrited grid is given
                return '';
            } else {
                $this->setIsNewModel(false);
            }
            
            if ($this->canDisplayColumnsConfig()
                || $this->canDisplayAdditional()
                || $this->canDisplayGridInfos()) {
                // Only display if relevant
                return parent::_toHtml();
            }
        }
        return '';
    }
}