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

class BL_CustomGrid_Model_Grid_Editor_Cms_Widget extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('instance_id'),
            'entity_model_class_code'     => 'widget/widget_instance',
            'entity_name_data_key'        => 'title',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'cms/widget_instance',
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
            $callbackManager->getCallbackFromCallable(
                array($this, 'beforeSaveContextEditedEntity'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_BEFORE,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH
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
        /** @var Mage_Widget_Helper_Data $helper */
        $helper = Mage::helper('widget');
        
        $fields = array(
            'title' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'sort_order' => array(
                'form_field' => array(
                    'type' => 'text',
                    'note' => $helper->__('Sort Order of widget instances in the same block reference'),
                ),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_ids'] = $this->getEditorHelper()->getStoreFieldBaseConfig(true);
        }
        
        return $fields;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param Mage_Widget_Model_Widget_Instance $editedEntity Edited widget instance
     * @param mixed $userValue User-edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function applyUserEditedValueToEditedEntity(
        Mage_Widget_Model_Widget_Instance $editedEntity,
        $userValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        if ($context->getValueId() == 'sort_order') {
            $editedEntity->setSortOrder(empty($userValue) ? '0' : $userValue);
            return true;
        }
        return false;
    }
    
    /**
     * Prepare the page groups from the given edited widget instance to make them look like they have just been edited
     * (page groups coming from the edit form do not have the same values than when the widget instance is just loaded)
     * 
     * @param Mage_Widget_Model_Widget_Instance $editedEntity Edited widget instance
     * @return BL_CustomGrid_Model_Grid_Editor_Cms_Widget
     */
    protected function _prepareEditedWidgetInstancePageGroups(Mage_Widget_Model_Widget_Instance $editedEntity)
    {
        if (is_array($pageGroups = $editedEntity->getData('page_groups'))) {
            $editedPageGroups = array();
            
            foreach ($pageGroups as $pageGroup) {
                // @see Mage_Widget_Model_Widget_Instance::_beforeSave()
                $editedPageGroups[] = array(
                    'page_group' => $pageGroup['page_group'],
                    $pageGroup['page_group'] => array(
                        'page_id'       => $pageGroup['page_id'],
                        'page_group'    => $pageGroup['page_group'],
                        'layout_handle' => $pageGroup['layout_handle'],
                        'for'           => $pageGroup['page_for'],
                        'block'         => $pageGroup['block_reference'],
                        'entities'      => $pageGroup['entities'],
                        'template'      => $pageGroup['page_template'],
                    ),
                );
            }
            
            $editedEntity->setData('page_groups', $editedPageGroups);
        }
        return $this;
    }
    
    /**
     * Before callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param Mage_Widget_Model_Widget_Instance $editedEntity Edited widget instance
     */
    public function beforeSaveContextEditedEntity(Mage_Widget_Model_Widget_Instance $editedEntity)
    {
        if (is_string($result = $editedEntity->validate())) {
            Mage::throwException($result);
        }
        $this->_prepareEditedWidgetInstancePageGroups($editedEntity);
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
        if ($context->getValueId() == 'store_ids') {
            /** @var Mage_Widget_Model_Widget_Instance $editedEntity */
            $editedEntity = $context->getEditedEntity();
            $storesIds = $editedEntity->getStoreIds();
            $transport->setData('value', (is_array($storesIds) ? $storesIds : explode(',', $storesIds)));
        }
    }
}
