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

class BL_CustomGrid_Model_Grid_Editor_Cms_Page extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('page_id'),
            'entity_model_class_code'     => 'cms/page',
            'entity_name_data_key'        => 'title',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'cms/page/save',
            ),
        );
    }
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'applyUserEditedValueToEditedEntity'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_APPLY_USER_EDITED_VALUE_TO_EDITED_ENTITY
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'getRenderableContextEditedValue'),
                self::WORKER_TYPE_VALUE_RENDERER,
                BL_CustomGrid_Model_Grid_Editor_Value_Renderer::ACTION_TYPE_GET_RENDERABLE_CONTEXT_EDITED_VALUE
            ),
        );
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var Mage_Cms_Helper_Data $helper */
        $helper = Mage::helper('cms');
        /** @var Mage_Core_Model_Design_Source_Design $designSource */
        $designSource = Mage::getSingleton('core/design_source_design');
        /** @var Mage_Page_Model_Source_Layout $layoutSource */
        $layoutSource = Mage::getSingleton('page/source_layout');
        /** @var Mage_Cms_Model_Page $pageModel */
        $pageModel = Mage::getSingleton('cms/page');
        
        $fields = array(
            'title' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                )
            ),
            'identifier' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'validate-identifier',
                    'note'     => $helper->__('Relative to Website Base URL'),
                    'required' => true,
                ),
            ),
            'root_template' => array(
                'form_field' => array(
                    'type'     => 'select',
                    'values'   => $layoutSource->toOptionArray(),
                    'required' => true,
                ),
            ),
            'is_active' => array(
                'form_field' => array(
                    'type'     => 'select',
                    'options'  => $pageModel->getAvailableStatuses(),
                    'required' => true,
                ),
            ),
            'meta_keywords' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'  => 'textarea',
                    'label' => $helper->__('Meta Keywords'),
                ),
                'window' => array(
                    'height' => 310,
                ),
            ),
            'meta_description' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'  => 'textarea',
                    'label' => $helper->__('Meta Description'),
                ),
                'window' => array(
                    'height' => 310,
                ),
            ),
            'content' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'     => 'editor',
                    'wysiwyg'  => true,
                    'label'    => $helper->__('Content'),
                    'style'    => 'height:36em;',
                    'required' => true,
                ),
            ),
            'layout_update_xml' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'  => 'textarea',
                    'label' => $helper->__('Layout Update XML'),
                    'style' => 'height:24em;',
                ),
            ),
            'custom_theme' => array(
                'form_field' => array(
                    'type'   => 'select',
                    'values' => $designSource->getAllOptions(),
                ),
            ),
            'custom_root_template' => array(
                'form_field' => array(
                    'type'   => 'select',
                    'values' => $layoutSource->toOptionArray(true),
                ),
            ),
            'custom_layout_update_xml' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'  => 'textarea',
                    'label' => $helper->__('Custom Layout Update XML'),
                    'style' => 'height:24em;',
                ),
            ),
            // Date validation classes are purposely missing from the two date fields below:
            // depending on the current locale and/or browser and/or chosen date, it may reject valid dates
            'custom_theme_from' => array(
                'form_field' => array(
                    'type'  => 'date',
                ),
            ),
            'custom_theme_to' => array(
                'form_field' => array(
                    'type'  => 'date',
                ),
            ),
            'content_heading' => array(),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = $this->getEditorHelper()->getStoreFieldBaseConfig(true);
        }
        
        return $fields;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param Mage_Cms_Model_Page $editedEntity Edited CMS page
     * @param mixed $userValue User-edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function applyUserEditedValueToEditedEntity(
        Mage_Cms_Model_Page $editedEntity,
        $userValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        if ($context->getValueId() == 'store_id') {
            $editedEntity->setStores($userValue);
            return true;
        }
        $editedEntity->setStores($editedEntity->getStoreId());
        return false;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Value_Renderer::getRenderableContextEditedValue()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the renderable value
     */
    public function getRenderableContextEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        if ($context->getValueId() == 'store_id') {
            /** @var Mage_Cms_Model_Page $editedEntity */
            $editedEntity = $context->getEditedEntity();
            $transport->setData('value', $editedEntity->getStores());
        }
    }
}
