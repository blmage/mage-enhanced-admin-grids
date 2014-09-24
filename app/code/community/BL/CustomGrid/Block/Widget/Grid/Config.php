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

class BL_CustomGrid_Block_Widget_Grid_Config
    extends Mage_Adminhtml_Block_Widget
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId($this->helper('core')->uniqHash('blcgConfig'));
        $this->setTemplate('bl/customgrid/widget/grid/config.phtml');
    }
    
    protected function _toHtml()
    {
        if (($gridModel = $this->getGridModel())
            && ($gridModel->getId() || $this->getGridBlock())) {
            if (!$gridModel->getId()) {
                // Init and save custom grid model if new one
                $gridModel->initWithGridBlock($this->getGridBlock())->save();
                $this->setIsNewGridModel(true);
            } elseif (($gridBlock = $this->getGridBlock())
                && !$this->helper('customgrid')->isRewritedGridBlock($gridBlock)) {
                // Do not display if a not rewrited grid is given
                return '';
            } else {
                $this->setIsNewGridModel(false);
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
    
    public function getGridModel()
    {
        return $this->getDataSetDefault('grid_model', Mage::registry('blcg_grid'));
    }
    
    public function getJsObjectName()
    {
        return $this->getId() . 'Config';
    }
    
    public function getGridBlockJsObjectName()
    {
        return (($block = $this->getGridBlock()) ? $block->getJsObjectName() : null);
    }
    
    public function getProfilesJsObjectName()
    {
        return $this->getDataSetDefault('profiles_js_object_name', $this->helper('core')->uniqHash('blcgProfilesBar'));
    }
    
    public function getFromAjax()
    {
        return ($this->getRequest()->getQuery('ajax') ? true : false);
    }
    
    public function getSaveUrl()
    {
        return $this->getUrl('customgrid/grid/save');
    }
    
    public function getDeleteUrl()
    {
        return $this->getUrl('customgrid/grid/delete', array('grid_id' => $this->getGridModel()->getId()));
    }
    
    public function getCustomizeButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Grid Customization'),
            '$(\'' . $this->getHtmlId() . '\').toggle();',
            'scalable blcg-customize'
        );
    }
    
    public function getBackButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Back'),
            'setLocation(\'' . $this->getUrl('*/*/') . '\');',
            'back'
        );
    }
    
    public function getDeleteButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Delete'),
            'confirmSetLocation(\''
                . $this->jsQuoteEscape($this->__('Are you sure?')) . '\', \'' . $this->getDeleteUrl() . '\');',
            'scalable delete'
        );
    }
    
    public function getSaveButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Save'),
            $this->getJsObjectName() . '.saveColumns();',
            'scalable save'
        );
    }
    
    public function getToggleGridInfosButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Grid Infos'),
            '$(\'' . $this->getHtmlId() . '-grid-infos\').toggle();',
            'scalable blcg-grid-infos'
        );
    }
    
    public function getToggleAdditionalButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('More Options'),
            '$(\'' . $this->getHtmlId() . '-additional\').toggle();',
            'scalable blcg-additional'
        );
    }
    
    public function getAddColumnButtonHtml()
    {
        return $this->getButtonHtml(
            $this->__('Add Attribute Column'),
            $this->getJsObjectName() . '.addColumn();',
            'scalable add'
        );
    }
    
    public function getGridFilterParamName()
    {
        return (($grid = $this->getGridBlock()) ? $grid->getVarNameFilter() : null);
    }
    
    public function getGridFilterParamValue()
    {
        if (($grid = $this->getGridBlock())
            && !$grid->getUseAjax()
            && !is_null($grid->getRequest()->getParam($grid->getVarNameFilter(), null))
            && ($param = $grid->blcg_getFilterParam())) {
            return $param;
        }
        return null;
    }
    
    public function getProfileIdPlaceholder()
    {
        return '{{profile_id}}';
    }
    
    public function getProfiles()
    {
        if (!$this->hasData('profiles')) {
            $profiles = array();
            
            if ($gridModel = $this->getGridModel()) {
                $profiles = $gridModel->getProfiles(true, true, true);
            }
            
            foreach ($profiles as $key => $profile) {
                $profiles[$key] = array(
                    'id'        => $profile->getId(),
                    'name'      => $profile->getName(),
                    'isBase'    => $profile->isBase(),
                    'isCurrent' => $profile->isCurrent(),
                );
            }
            
            $this->setData('profiles', $profiles);
        }
        return $this->_getData('profiles');
    }
    
    public function getProfilesJsonConfig()
    {
        return $this->getDataSetDefault(
            'profiles_json_config',
            $this->helper('core')->jsonEncode((object) $this->getProfiles())
        );
    }
    
    public function getSortedProfilesIdsJson()
    {
        return $this->getDataSetDefault(
            'sorted_profiles_ids_json',
            $this->helper('core')->jsonEncode(array_keys($this->getProfiles()))
        );
    }
    
    public function getProfilesActionsJsonConfig()
    {
        if (!$this->hasData('profiles_actions_json_config')) {
            $actions = array();
            
            if ($gridModel = $this->getGridModel()) {
                $actionsRoute  = 'customgrid/grid_profile/';
                $actionsParams = array(
                    'grid_id' => $gridModel->getId(),
                    'profile_id' => $this->getProfileIdPlaceholder(),
                    'js_object_name' => $this->getProfilesJsObjectName(),
                );
                
                $actions['go_to'] = array(
                    'label'   => $this->__('Go To'),
                    'url'     => $this->getUrl($actionsRoute . 'goTo', $actionsParams),
                    'mode'    => 'direct',
                    'confirm' => false,
                    'appliesToBase'    => true,
                    'appliesToCurrent' => false,
                    'leftClickable'    => true,
                );
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE)
                    || $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE)
                    || $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE)
                    || $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE)
                    || $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE)) {
                    $actions['default'] = array(
                        'label'     => $this->__('Choose As Default'),
                        'mode'      => 'window',
                        'confirm'   => false,
                        'url'       => $this->getUrl($actionsRoute . 'default', $actionsParams),
                        'windowUrl'    => $this->getUrl($actionsRoute . 'defaultForm', $actionsParams),
                        'windowConfig' => array('height' => 600),
                        'appliesToBase'    => true,
                        'appliesToCurrent' => true,
                    );
                }
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_NEW)) {
                    $actions['copy_new'] = array(
                        'label'     => $this->__('Copy To New Profile'),
                        'mode'      => 'window',
                        'confirm'   => false,
                        'url'       => $this->getUrl($actionsRoute . 'copyToNew', $actionsParams),
                        'windowUrl' => $this->getUrl($actionsRoute . 'copyToNewForm', $actionsParams),
                        'appliesToBase'    => true,
                        'appliesToCurrent' => true,
                    );
                }
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_EXISTING)) {
                    $actions['copy_existing'] = array(
                        'label'     => $this->__('Copy To Existing Profile'),
                        'mode'      => 'window',
                        'confirm'   => false,
                        'url'       => $this->getUrl($actionsRoute . 'copyToExisting', $actionsParams),
                        'windowUrl' => $this->getUrl($actionsRoute . 'copyToExistingForm', $actionsParams),
                        'appliesToBase'    => true,
                        'appliesToCurrent' => true,
                    );
                }
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_PROFILES)) {
                    $actions['edit'] = array(
                        'label'     => $this->__('Edit'),
                        'mode'      => 'window',
                        'confirm'   => false,
                        'url'       => $this->getUrl($actionsRoute . 'edit', $actionsParams),
                        'windowUrl' => $this->getUrl($actionsRoute . 'editForm', $actionsParams),
                        'appliesToBase'    => false,
                        'appliesToCurrent' => true,
                    );
                }
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
                    $actions['assign'] = array(
                        'label'     => $this->__('Assign'),
                        'mode'      => 'window',
                        'confirm'   => false,
                        'url'       => $this->getUrl($actionsRoute . 'assign', $actionsParams),
                        'windowUrl' => $this->getUrl($actionsRoute . 'assignForm', $actionsParams),
                        'appliesToBase'    => false,
                        'appliesToCurrent' => true,
                    );
                }
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_DELETE_PROFILES)) {
                    $actions['delete'] = array(
                        'label'   => $this->__('Delete'),
                        'url'     => $this->getUrl($actionsRoute . 'delete', $actionsParams),
                        'mode'    => 'direct',
                        'confirm' => $this->__('Are you sure you want to delete this profile?'),
                        'appliesToBase'    => false,
                        'appliesToCurrent' => true,
                    );
                }
            }
            
            $this->setData('profiles_actions_json_config', $this->helper('core')->jsonEncode($actions));
        }
        return $this->_getData('profiles_actions_json_config');
    }
    
    public function getProfilesBarJsonConfig()
    {
        if (!$this->hasData('profiles_bar_json_config')) {
            $config = array(
                'profileIdPlaceholder' => $this->getProfileIdPlaceholder(),
                'profileItemIdPrefix'  => $this->helper('core')->uniqHash('blcg-grid-profile-item-'),
            );
            
            if ($gridModel = $this->getGridModel()) {
                $config['removableUrlParams'] = array_values($gridModel->getBlockVarNames());
            }
            
            $this->setData('profiles_bar_json_config', $this->helper('core')->jsonEncode((object) $config));
        }
        return $this->_getData('profiles_bar_json_config');
    }
    
    public function canDisplayColumnsConfig()
    {
        return $this->getDataSetDefault(
            'can_display_columns_config',
            $this->getGridModel()
                ->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)
        );
    }
    
    public function getDefaultColumnsConfig()
    {
        return $this->canDisplayColumnsConfig();
    }
    
    public function getColumnsConfigHtml()
    {
        if ($this->canDisplayColumnsConfig()) {
            return $this->getLayout()->createBlock('customgrid/widget_grid_config_columns_list')
                ->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setIsNewGridModel($this->getIsNewGridModel())
                ->setGridBlock($this->getGridBlock())
                ->toHtml();
        }
        return '';
    }
    
    public function canDisplayAdditional()
    {
        return $this->getDataSetDefault(
            'can_display_additional',
            ($this->getGridBlock()
             && !$this->getIsNewGridModel()
             && (($this->getGridModel()->canHaveCustomColumns() && $this->canDisplayColumnsConfig())
                 || $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS)
                 || $this->getGridModel()->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_EXPORT_RESULTS)))
        );
    }
    
    public function getDefaultAdditional()
    {
        return (!$this->getDefaultColumnsConfig() && $this->canDisplayAdditional());
    }
    
    public function getAdditionalHtml()
    {
        if ($this->canDisplayAdditional()) {
            return $this->getLayout()->createBlock('customgrid/widget_grid_config_additional')
                ->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setIsNewGridModel($this->getIsNewGridModel())
                ->setGridBlock($this->getGridBlock())
                ->setStartDisplayed($this->getDefaultAdditional())
                ->toHtml();
        }
        return '';
    }
    
    public function canDisplayGridInfos()
    {
        return $this->getDataSetDefault(
            'can_display_grid_infos',
            (!$this->getIsNewGridModel()
             && $this->getGridBlock()
             && $this->getGridModel()
                    ->checkUserActionPermission(BL_CustomGrid_Model_Grid::ACTION_VIEW_GRID_INFOS))
        );
    }
    
    public function getDefaultGridInfos()
    {
        return !$this->getDefaultColumnsConfig()
            && !$this->getDefaultAdditional()
            && $this->canDisplayGridInfos();
    }
    
    public function getGridInfosHtml()
    {
        if ($this->canDisplayGridInfos()) {
            return $this->getLayout()->createBlock('customgrid/widget_grid_config_infos')
                ->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setIsNewGridModel($this->getIsNewGridModel())
                ->setGridBlock($this->getGridBlock())
                ->setStartDisplayed($this->getDefaultGridInfos())
                ->toHtml();
        }
        return '';
    }
    
    public function isStandAlone()
    {
        return false;
    }
}
