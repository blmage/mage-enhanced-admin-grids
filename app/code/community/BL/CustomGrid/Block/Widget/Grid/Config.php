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

class BL_CustomGrid_Block_Widget_Grid_Config extends Mage_Adminhtml_Block_Widget
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
            && (($gridBlock = $this->getGridBlock()) || $gridModel->getId())) {
            if (!$gridModel->getId()) {
                $gridModel->initWithGridBlock($gridBlock)->save();
                $this->setIsNewGridModel(true);
            } elseif ($gridBlock && !$this->helper('customgrid')->isRewritedGridBlock($gridBlock)) {
                return '';
            } else {
                $this->setIsNewGridModel(false);
            }
            return parent::_toHtml();
        }
        return '';
    }
    
    public function getGridModel()
    {
        return $this->getDataSetDefault('grid_model', Mage::registry('blcg_grid'));
    }
    
    public function getRewritedGridBlock()
    {
        if (!$this->hasData('rewrited_grid_block')) {
            if (($gridBlock = $this->getGridBlock())
                && $this->helper('customgrid')->isRewritedGridBlock($gridBlock)) {
                $this->setData('rewrited_grid_block', $gridBlock);
            } else {
                $this->setData('rewrited_grid_block', false);
            }
        }
        return $this->_getData('rewrited_grid_block');
    }
    
    public function getGridBlockJsObjectName()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->getJsObjectName() : null);
    }
    
    public function getProfilesJsObjectName()
    {
        return $this->getDataSetDefault('profiles_js_object_name', $this->helper('core')->uniqHash('blcgProfilesBar'));
    }
    
    public function getBaseUrlParams()
    {
        return $this->getDataSetDefault(
            'base_url_params',
            array(
                'grid_id' => $this->getGridModel()->getId(),
                'profile_id' => $this->getGridModel()->getProfileId(),
                'grid_js_object_name' => $this->getGridBlockJsObjectName(),
            )
        );
    }
    
    public function getCustomColumnsFormUrl()
    {
        return $this->getUrl('customgrid/grid/customColumnsForm', $this->getBaseUrlParams());
    }
    
    public function getDefaultParamsFormUrl()
    {
        return $this->getUrl('customgrid/grid/defaultParamsForm', $this->getBaseUrlParams());
    }
    
    public function getExportFormUrl()
    {
        return $this->getUrl('customgrid/grid/exportForm', $this->getBaseUrlParams());
    }
    
    public function getGridInfosUrl()
    {
        return $this->getUrl('customgrid/grid/gridInfos', $this->getBaseUrlParams());
    }
    
    public function getGridEditUrl()
    {
        return $this->getUrl('customgrid/grid/edit', $this->getBaseUrlParams());
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
    
    protected function _getProfilesActionsJsonConfig(BL_CustomGrid_Model_Grid $gridModel)
    {
        $actions   = array();
        $actionsRoute  = 'customgrid/grid_profile/';
        $actionsParams = $this->getBaseUrlParams();
        $actionsParams['profiles_js_object_name'] = $this->getProfilesJsObjectName();
        
        $actions['go_to'] = array(
            'label'   => $this->__('Go To'),
            'url'     => $this->getUrl($actionsRoute . 'goTo', $actionsParams),
            'mode'    => 'direct',
            'confirm' => false,
            'appliesToBase'    => true,
            'appliesToCurrent' => false,
            'leftClickable'    => true,
        );
        
        if ($gridModel->checkUserPermissions(array(
                BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_USER_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_USERS_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OWN_ROLE_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid::ACTION_CHOOSE_OTHER_ROLES_DEFAULT_PROFILE,
                BL_CustomGrid_Model_Grid::ACTION_CHOOSE_GLOBAL_DEFAULT_PROFILE,
            ))) {
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
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_NEW)) {
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
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_COPY_PROFILES_TO_EXISTING)) {
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
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_PROFILES)) {
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
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
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
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_DELETE_PROFILES)) {
            $actions['delete'] = array(
                'label'   => $this->__('Delete'),
                'url'     => $this->getUrl($actionsRoute . 'delete', $actionsParams),
                'mode'    => 'direct',
                'confirm' => $this->__('Are you sure you want to delete this profile?'),
                'appliesToBase'    => false,
                'appliesToCurrent' => true,
            );
        }
        
        return $actions;
    }
    
    public function getProfilesActionsJsonConfig()
    {
        if (!$this->hasData('profiles_actions_json_config')) {
            $actions = ($gridModel = $this->getGridModel())
                ? $this->_getProfilesActionsJsonConfig($gridModel)
                : array();
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
    
    protected function _getGridFormWindowJsonConfig(BL_CustomGrid_Model_Grid $gridModel, $title = null, $height = null)
    {
        $config = array('title' => $gridModel->getProfile()->getName() . (is_null($title) ? '' : ' - ' . $title));
        
        if (!is_null($height)) {
            $config['height'] = (int) $height;
        }
        
        return $this->helper('core')->jsonEncode($config);
    }
    
    protected function _prepareButtonScript($buttonCode, $scriptBody)
    {
        $functionName = $this->getId() . 'Button' . $buttonCode;
        $function  = $functionName . ' = function() {' . "\n" . $scriptBody . "\n" . '}';
        $scripts   = (array) $this->getDataSetDefault('buttons_scripts', array());
        $scripts[] = $function;
        $this->setData('buttons_scripts', $scripts);
        return $functionName;
    }
    
    public function getButtonsScripts()
    {
        $scripts = (array) $this->getDataSetDefault('buttons_scripts', array());
        return (!empty($scripts) ? $this->helper('adminhtml/js')->getScript(implode("\n", $scripts)) : '');
    }
    
    public function getColumnsListButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('columns_list_button_html')) {
            $buttonHtml = '';
            
            if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)) {
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Columns List'),
                    '$(\'' . $this->getColumnsListBlock()->getHtmlId() . '\').toggle(); '
                        . '$(this).toggleClassName(\'blcg-on\');',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-columns-list'
                );
            }
            
            $this->setData('columns_list_button_html', $buttonHtml);
        }
        return $this->_getData('columns_list_button_html');
    }
    
    public function getCustomColumnsFormButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('custom_columns_form_button_html')) {
            $buttonHtml = '';
            
            if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS)
                && $gridModel->canHaveCustomColumns()) {
                $functionName = $this->_prepareButtonScript(
                    'CC',
                    'blcg.Tools.openDialogFromPost('
                        . '\''. $this->getCustomColumnsFormUrl() . '\','
                        . '{},'
                        . $this->_getGridFormWindowJsonConfig($gridModel, $this->__('Custom Columns'))
                        . ');'
                );
                
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Custom Columns'),
                    $functionName . '();',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-custom-columns'
                );
            }
            
            $this->setData('custom_columns_form_button_html', $buttonHtml);
        }
        return $this->_getData('custom_columns_form_button_html');
    }
    
    public function getDefaultParamsFormButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('default_params_form_button_html')) {
            $buttonHtml = '';
           
            if (($gridBlock = $this->getRewritedGridBlock())
                && $gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS)) {
                $defaultParams = serialize(array(
                    'page'   => $gridBlock->blcg_getPage(),
                    'limit'  => $gridBlock->blcg_getLimit(),
                    'sort'   => $gridBlock->blcg_getSort(),
                    'dir'    => $gridBlock->blcg_getDir(),
                    'filter' => $gridBlock->blcg_getFilterParam(),
                ));
                
                $functionName = $this->_prepareButtonScript(
                    'DP',
                    'blcg.Tools.openDialogFromPost('
                        . '\''. $this->getDefaultParamsFormUrl() . '\','
                        . $this->helper('core')->jsonEncode(array('default_params' => $defaultParams)) . ','
                        . $this->_getGridFormWindowJsonConfig($gridModel, $this->__('Default Parameters'))
                        . ');'
                );
                
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Default Parameters'),
                    $functionName . '();',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-default-params'
                );
            }
            
            $this->setData('default_params_form_button_html', $buttonHtml);
        }
        return $this->_getData('default_params_form_button_html');
    }
    
    public function getExportFormButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('export_form_button_html')) {
            $buttonHtml = '';
            
            if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EXPORT_RESULTS)
                && ($gridBlock = $this->getRewritedGridBlock())) {
                $params = array(
                    'total_size'  => $gridBlock->blcg_getCollectionSize(),
                    'first_index' => (($gridBlock->blcg_getPage() -1) * $gridBlock->blcg_getLimit() +1),
                );
                
                $functionName = $this->_prepareButtonScript(
                    'Export',
                    'blcg.Tools.openDialogFromPost('
                        . '\''. $this->getExportFormUrl() . '\','
                        . $this->helper('core')->jsonEncode($params) . ','
                        . $this->_getGridFormWindowJsonConfig($gridModel, $this->__('Export'), 240)
                        . ');'
                );
                
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Export'),
                    $functionName . '();',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-export'
                );
            }
            
            $this->setData('export_form_button_html', $buttonHtml);
        }
        return $this->_getData('export_form_button_html');
    }
    
    public function getGridInfosFormButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('grid_infos_form_button_html')) {
            $buttonHtml = '';
            $hasPermissions = $gridModel->checkUserPermissions(array(
                BL_CustomGrid_Model_Grid::ACTION_EDIT_FORCED_TYPE,
                BL_CustomGrid_Model_Grid::ACTION_ENABLE_DISABLE,
                BL_CustomGrid_Model_Grid::ACTION_VIEW_GRID_INFOS,
            ));
            
            if (!$this->getIsNewGridModel() && $this->getGridBlock() && $hasPermissions) {
                $windowHeight = 120;
                
                if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_FORCED_TYPE)) {
                    $windowHeight += 120;
                }
                if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ENABLE_DISABLE)) {
                    $windowHeight += 30;
                }
                if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_VIEW_GRID_INFOS)) {
                    $windowHeight += 170;
                }
                
                $functionName = $this->_prepareButtonScript(
                    'GI',
                    'blcg.Tools.openDialogFromPost('
                        . '\''. $this->getGridInfosUrl() . '\','
                        . '{},'
                        . $this->_getGridFormWindowJsonConfig($gridModel, $this->__('Grid Infos'), $windowHeight)
                        . ');'
                );
                
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Grid Infos'),
                    $functionName . '();',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-grid-infos'
                );
            }
            
            $this->setData('grid_infos_form_button_html', $buttonHtml);
        }
        return $this->_getData('grid_infos_form_button_html');
    }
    
    public function getGridEditLinkButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('grid_edit_link_button_html')) {
            $buttonHtml = '';
            
            if ($gridModel->checkUserPermissions(array(
                    BL_CustomGrid_Model_Grid::ACTION_CUSTOMIZE_COLUMNS,
                    BL_CustomGrid_Model_Grid::ACTION_ENABLE_DISABLE,
                    BL_CustomGrid_Model_Grid::ACTION_EDIT_FORCED_TYPE,
                    BL_CustomGrid_Model_Grid::ACTION_EDIT_CUSTOMIZATION_PARAMS,
                    BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
                    BL_CustomGrid_Model_Grid::ACTION_EDIT_ROLES_PERMISSIONS,
                    BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES,
                ))) {
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Edit Grid'),
                    'window.open(\'' . $this->getGridEditUrl() . '\', \'_blank\');',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-edit-link'
                );
            }
            
            $this->setData('grid_edit_link_button_html', $buttonHtml);
        }
        return $this->_getData('grid_edit_link_button_html');
    }
    
    public function getRssLinksListButtonHtml(BL_CustomGrid_Model_Grid $gridModel)
    {
        if (!$this->hasData('rss_links_list_button_html')) {
            $buttonHtml = '';
            
            if ($gridModel->getUseRssLinksWindow()
                && ($gridBlock = $this->getGridBlock())
                && is_array($gridBlock->getRssLists())) {
                $functionName = $this->_prepareButtonScript(
                    'RSS',
                    'blcg.Tools.openDialogFromElement('
                        . '\''. $this->getRssLinksBlock()->getHtmlId() . '\','
                        . $this->_getGridFormWindowJsonConfig($gridModel, $this->__('RSS Links'))
                        . ');'
                );
                
                $buttonHtml = $this->getButtonHtml(
                    $this->__('RSS Links'),
                    $functionName . '();',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-rss'
                );
            }
            
            $this->setData('rss_links_list_button_html', $buttonHtml);
        }
        return $this->_getData('rss_links_list_button_html');
    }
    
    public function getButtonsHtml()
    {
        if (!$this->hasData('buttons_html')) {
            $gridModel = $this->getGridModel();
            
            $this->setData(
                'buttons_html',
                $this->getColumnsListButtonHtml($gridModel)
                    . "\n" . $this->getCustomColumnsFormButtonHtml($gridModel)
                    . "\n" . $this->getDefaultParamsFormButtonHtml($gridModel)
                    . "\n" . $this->getExportFormButtonHtml($gridModel)
                    . "\n" . $this->getGridInfosFormButtonHtml($gridModel)
                    . "\n" . $this->getGridEditLinkButtonHtml($gridModel)
                    . "\n" . $this->getRssLinksListButtonHtml($gridModel)
            );
        }
        return $this->_getData('buttons_html');
    }
    
    public function getColumnsListBlock()
    {
        if (!$this->getChild('columns_list')) {
            $this->setChild(
                'columns_list',
                $this->getLayout()->createBlock('customgrid/widget_grid_config_columns_list')
                    ->setId($this->getId())
                    ->setGridModel($this->getGridModel())
                    ->setIsNewGridModel($this->getIsNewGridModel())
                    ->setGridBlock($this->getGridBlock())
            );
        }
        return $this->getChild('columns_list');
    }
    
    public function getColumnsListHtml()
    {
        return ($this->getColumnsListButtonHtml($this->getGridModel()) != '')
            ? $this->getColumnsListBlock()->toHtml()
            : '';
    }
    
    public function getRssLinksBlock()
    {
        if (!$this->getChild('rss_links')) {
            $this->setChild(
                'rss_links',
                $this->getLayout()->createBlock('customgrid/widget_grid_config_rss_links')
                    ->setGridBlock($this->getGridBlock())
            );
        }
        return $this->getChild('rss_links');
    }
    
    public function getRssLinksHtml()
    {
        return ($this->getRssLinksListButtonHtml($this->getGridModel()) != '')
            ? $this->getRssLinksBlock()->toHtml()
            : '';
    }
}
