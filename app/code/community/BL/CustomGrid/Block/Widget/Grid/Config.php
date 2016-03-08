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

class BL_CustomGrid_Block_Widget_Grid_Config extends Mage_Adminhtml_Block_Widget
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId($this->getCoreHelper()->uniqHash('blcgConfig'));
        $this->setTemplate('bl/customgrid/widget/grid/config.phtml');
    }
    
    protected function _toHtml()
    {
        /** @var $helper BL_CustomGrid_Helper_Data */
        $helper = $this->helper('customgrid');
        
        if (($gridModel = $this->getGridModel())
            && (($gridBlock = $this->getGridBlock()) || $gridModel->getId())) {
            if (!$gridModel->getId()) {
                $gridModel->getAbsorber()->initGridModelFromGridBlock($gridBlock);
                $this->setIsNewGridModel(true);
            } elseif ($gridBlock && !$helper->isRewritedGridBlock($gridBlock)) {
                return '';
            } else {
                $this->setIsNewGridModel(false);
            }
            return parent::_toHtml();
        }
        
        return '';
    }
    
    /**
     * Return the core helper
     * 
     * @return Mage_Core_Helper_Data
     */
    public function getCoreHelper()
    {
        return $this->helper('core');
    }
    
    /**
     * Return the current grid model
     * 
     * @return BL_CustomGrid_Model_Grid
     */
    public function getGridModel()
    {
        return $this->getDataSetDefault('grid_model', Mage::registry('blcg_grid'));
    }
    
    /**
     * Return the current grid block
     * 
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    public function getGridBlock()
    {
        return ($gridBlock = $this->_getData('grid_block'))
            && ($gridBlock instanceof Mage_Adminhtml_Block_Widget_Grid)
            ? $gridBlock
            : null;
    }
    
    /**
     * Return the current grid block only if it has been rewrited by the extension
     * 
     * @return Mage_Adminhtml_Block_Widget_Grid|false
     */
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
    
    /**
     * Return the name of the main JS object from the current grid block
     * 
     * @return string
     */
    public function getGridBlockJsObjectName()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->getJsObjectName() : null);
    }
    
    /**
     * Return the base URL parameters, that should be common to most requests
     * 
     * @return array
     */
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
    
    /**
     * Return the URL of the columns list form
     *
     * @return string
     */
    public function getColumnsListFormUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid/columnsListForm', $this->getBaseUrlParams());
    }
    
    /**
     * Return the URL of the custom columns form
     * 
     * @return string
     */
    public function getCustomColumnsFormUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid/customColumnsForm', $this->getBaseUrlParams());
    }
    
    /**
     * Return the URL of the default parameters form
     * 
     * @return string
     */
    public function getDefaultParamsFormUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid/defaultParamsForm', $this->getBaseUrlParams());
    }
    
    /**
     * Return the URL of the export form
     * 
     * @return string
     */
    public function getExportFormUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid_export/form', $this->getBaseUrlParams());
    }
    
    /**
     * Return the URL of the grid informations form
     * 
     * @return string
     */
    public function getGridInfosUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid/gridInfos', $this->getBaseUrlParams());
    }
    
    /**
     * Return the URL of the grid edit page
     * 
     * @return string
     */
    public function getGridEditUrl()
    {
        return $this->getUrl('adminhtml/blcg_grid/edit', $this->getBaseUrlParams());
    }
    
    /**
     * Return the JSON config for the given form window values
     * 
     * @param string $title Window title
     * @param int $height Window height
     * @param array $additional Additional config data
     * @return string
     */
    protected function _getGridFormWindowJsonConfig($title = null, $height = null, array $additional = array())
    {
        $config = array(
            'title' => $this->getGridModel()->getProfile()->getName() . (is_null($title) ? '' : ' - ' . $title)
        );
        
        if (!is_null($height)) {
            $config['height'] = (int) $height;
        }
        
        $config = array_merge($additional, $config);
        return $this->getCoreHelper()->jsonEncode($config);
    }
    
    /**
     * Prepare and register a new button script for the given code and JS function body
     * 
     * @param string $buttonCode Button code
     * @param string $scriptBody JS function body
     * @return string Corresponding JS function name
     */
    protected function _prepareButtonScript($buttonCode, $scriptBody)
    {
        $functionName = $this->getId() . 'Button' . $buttonCode;
        $function  = $functionName . ' = function() {' . "\n" . $scriptBody . "\n" . '}';
        $scripts   = (array) $this->getDataSetDefault('buttons_scripts', array());
        $scripts[] = $function;
        $this->setData('buttons_scripts', $scripts);
        return $functionName;
    }
    
    /**
     * Return the imploded button JS scripts
     * 
     * @return string
     */
    public function getButtonsScripts()
    {
        /** @var $helper Mage_Adminhtml_Helper_Js */
        $helper  = $this->helper('adminhtml/js');
        $scripts = (array) $this->getDataSetDefault('buttons_scripts', array());
        return (!empty($scripts) ? $helper->getScript(implode("\n", $scripts)) : '');
    }
    
    /**
     * Return the HTML content of the columns list button
     * 
     * @return string
     */
    public function getColumnsListButtonHtml()
    {
        if (!$this->hasData('columns_list_button_html')) {
            $buttonHtml = '';
            $hasUserPermissions = $this->getGridModel()
                ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS);
            
            if ($hasUserPermissions) {
                $functionName = $this->_prepareButtonScript(
                    'CL',
                    'blcg.Tools.openIframeDialog('
                    . '\''. $this->getColumnsListFormUrl() . '\','
                    . $this->_getGridFormWindowJsonConfig(
                        $this->__('Columns List'),
                        null,
                        array('alwaysMaximized' => true)
                    )
                    . ');'
                );
                
                $buttonHtml = $this->getButtonHtml(
                    $this->__('Columns List'),
                    $functionName . '();',
                    'blcg-grid-profiles-bar-button blcg-grid-profiles-bar-button-columns-list'
                );
            }
            
            $this->setData('columns_list_button_html', $buttonHtml);
        }
        return $this->_getData('columns_list_button_html');
    }
    
    /**
     * Return the HTML content of the custom columns form button
     * 
     * @return string
     */
    public function getCustomColumnsFormButtonHtml()
    {
        if (!$this->hasData('custom_columns_form_button_html')) {
            $gridModel  = $this->getGridModel();
            $buttonHtml = '';
            
            if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS)
                && $gridModel->canHaveCustomColumns()) {
                $functionName = $this->_prepareButtonScript(
                    'CC',
                    'blcg.Tools.openDialogFromPost('
                    . '\''. $this->getCustomColumnsFormUrl() . '\','
                    . '{},'
                    . $this->_getGridFormWindowJsonConfig($this->__('Custom Columns'))
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
    
    /**
     * Return the HTML content of the default parameters form button
     * 
     * @return string
     */
    public function getDefaultParamsFormButtonHtml()
    {
        if (!$this->hasData('default_params_form_button_html')) {
            $buttonHtml = '';
            $hasUserPermission = $this->getGridModel()
                ->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS);
           
            if ($hasUserPermission
                && ($gridBlock = $this->getRewritedGridBlock())
                && $gridBlock->getPagerVisibility()) {
                $defaultParams = serialize(
                    array(
                        BL_CustomGrid_Model_Grid::GRID_PARAM_PAGE   => $gridBlock->blcg_getPage(),
                        BL_CustomGrid_Model_Grid::GRID_PARAM_LIMIT  => $gridBlock->blcg_getLimit(),
                        BL_CustomGrid_Model_Grid::GRID_PARAM_SORT   => $gridBlock->blcg_getSort(),
                        BL_CustomGrid_Model_Grid::GRID_PARAM_DIR    => $gridBlock->blcg_getDir(),
                        BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER => $gridBlock->blcg_getFilterParam(),
                    )
                );
                
                $functionName = $this->_prepareButtonScript(
                    'DP',
                    'blcg.Tools.openDialogFromPost('
                    . '\''. $this->getDefaultParamsFormUrl() . '\','
                    . $this->getCoreHelper()->jsonEncode(array('default_params' => $defaultParams)) . ','
                    . $this->_getGridFormWindowJsonConfig($this->__('Default Parameters'))
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
    
    /**
     * Return the HTML content of the export form button
     * 
     * @return string
     */
    public function getExportFormButtonHtml()
    {
        if (!$this->hasData('export_form_button_html')) {
            $gridModel    = $this->getGridModel();
            $gridExporter = $gridModel->getExporter();
            $buttonHtml   = '';
            
            if ($gridExporter->canExport()
                && $gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EXPORT_RESULTS)
                && ($gridBlock = $this->getRewritedGridBlock())) {
                $params = array(
                    'total_size'  => $gridBlock->blcg_getCollectionSize(),
                    'first_index' => (($gridBlock->blcg_getPage() - 1) * $gridBlock->blcg_getLimit() + 1),
                    'additional_params' => $gridExporter->getAdditionalFormParams($gridBlock),
                );
                
                $functionName = $this->_prepareButtonScript(
                    'Export',
                    'blcg.Tools.openDialogFromPost('
                    . '\''. $this->getExportFormUrl() . '\','
                    . $this->getCoreHelper()->jsonEncode($params) . ','
                    . $this->_getGridFormWindowJsonConfig($this->__('Export'), 240)
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
    
    /**
     * Return the HTML content of the grid informations form button
     * 
     * @return string
     */
    public function getGridInfosFormButtonHtml()
    {
        if (!$this->hasData('grid_infos_form_button_html')) {
            $gridModel  = $this->getGridModel();
            $buttonHtml = '';
            $hasUserPermissions = $gridModel->checkUserPermissions(
                array(
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE,
                    BL_CustomGrid_Model_Grid_Sentry::ACTION_VIEW_GRID_INFOS,
                )
            );
            
            if (!$this->getIsNewGridModel() && $this->getGridBlock() && $hasUserPermissions) {
                $windowHeight = 120;
                
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE)) {
                    $windowHeight += 120;
                }
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE)) {
                    $windowHeight += 30;
                }
                if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_VIEW_GRID_INFOS)) {
                    $windowHeight += 170;
                }
                
                $functionName = $this->_prepareButtonScript(
                    'GI',
                    'blcg.Tools.openDialogFromPost('
                    . '\''. $this->getGridInfosUrl() . '\','
                    . '{},'
                    . $this->_getGridFormWindowJsonConfig($this->__('Grid Infos'), $windowHeight)
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
    
    /**
     * Return the HTML content of the grid edit link button
     * 
     * @return string
     */
    public function getGridEditLinkButtonHtml()
    {
        if (!$this->hasData('grid_edit_link_button_html')) {
            $buttonHtml = '';
            $hasUserPermissions = $this->getGridModel()
                ->checkUserPermissions(
                    array(
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_CUSTOMIZATION_PARAMS,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_ROLES_PERMISSIONS,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES,
                        BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES,
                    )
                );
            
            if ($hasUserPermissions) {
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
    
    /**
     * Return the HTML content of the RSS links list button
     * 
     * @return string
     */
    public function getRssLinksListButtonHtml()
    {
        if (!$this->hasData('rss_links_list_button_html')) {
            $buttonHtml = '';
            
            if ($this->getGridModel()->getUseRssLinksWindow()
                && ($gridBlock = $this->getGridBlock())
                && is_array($gridBlock->getRssLists())) {
                $functionName = $this->_prepareButtonScript(
                    'RSS',
                    'blcg.Tools.openDialogFromElement('
                    . '\''. $this->getRssLinksBlock()->getHtmlId() . '\','
                    . $this->_getGridFormWindowJsonConfig($this->__('RSS Links'))
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
    
    /**
     * Return the HTML content of all the buttons
     * 
     * @return string
     */
    public function getButtonsHtml()
    {
        if (!$this->hasData('buttons_html')) {
            $this->setData(
                'buttons_html',
                $this->getColumnsListButtonHtml()
                . $this->getCustomColumnsFormButtonHtml()
                . $this->getDefaultParamsFormButtonHtml()
                . $this->getExportFormButtonHtml()
                . $this->getGridInfosFormButtonHtml()
                . $this->getGridEditLinkButtonHtml()
                . $this->getRssLinksListButtonHtml()
            );
        }
        return $this->_getData('buttons_html');
    }
    
    /**
     * Return the profiles bar block
     * 
     * @return BL_CustomGrid_Block_Widget_Grid_Config_Profiles_Bar
     */
    public function getProfilesBarBlock()
    {
        if (!$this->getChild('profiles_bar')) {
            /** @var $profilesBar BL_CustomGrid_Block_Widget_Grid_Config_Profiles_Bar */
            $profilesBar = $this->getLayout()->createBlock('customgrid/widget_grid_config_profiles_bar');
            
            $profilesBar->setId($this->getId())
                ->setGridModel($this->getGridModel())
                ->setGridConfigButtonsHtml($this->getButtonsHtml())
                ->setGridBlockJsObjectName($this->getGridBlockJsObjectName());
            
            $this->setChild('profiles_bar', $profilesBar);
        }
        return $this->getChild('profiles_bar');
    }
    
    /**
     * Return the HTML content of the profiles bar block
     *
     * @return string
     */
    public function getProfilesBarHtml()
    {
        return $this->getProfilesBarBlock()->toHtml(); 
    }
    
    /**
     * Return the RSS links block
     * 
     * @return BL_CustomGrid_Block_Widget_Grid_Config_Rss_Links
     */
    public function getRssLinksBlock()
    {
        if (!$this->getChild('rss_links')) {
            /** @var $rssLinksBlock BL_CustomGrid_Block_Widget_Grid_Config_Rss_Links */
            $rssLinksBlock = $this->getLayout()->createBlock('customgrid/widget_grid_config_rss_links');
            $rssLinksBlock->setGridBlock($this->getGridBlock());
            $this->setChild('rss_links', $rssLinksBlock);
        }
        return $this->getChild('rss_links');
    }
    
    /**
     * Return the HTML content of the RSS links block
     * 
     * @return string
     */
    public function getRssLinksHtml()
    {
        return (($this->getRssLinksListButtonHtml() != '') ? $this->getRssLinksBlock()->toHtml() : '');
    }
}
