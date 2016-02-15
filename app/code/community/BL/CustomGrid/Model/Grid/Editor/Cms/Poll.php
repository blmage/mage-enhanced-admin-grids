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

class BL_CustomGrid_Model_Grid_Editor_Cms_Poll extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('poll_id'),
            'entity_model_class_code'     => 'poll/poll',
            'entity_name_data_key'        => 'poll_title',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'cms/poll',
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
        );
    }
    
    /**
     * Return the IDs of the stores to which the edited poll from the given editor context is associated
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return array
     */
    public function getContextPollStoreIds(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        /** @var Mage_Poll_Model_Poll $editorPoll */
        $editedPoll = $context->getEditedEntity();
        return ($editedPoll && $editedPoll->getId() ? $editedPoll->getStoreIds() : array());
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var Mage_Poll_Helper_Data $helper */
        $helper = Mage::helper('poll');
        
        $fields = array(
            'poll_title' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'required-entry',
                    'required' => true,
                ),
            ),
            'closed' => array(
                'form_field' => array(
                    'type'   => 'select',
                    'values' => array(
                        array(
                            'value' => 1,
                            'label' => $helper->__('Closed'),
                        ),
                        array(
                            'value' => 0,
                            'label' => $helper->__('Open'),
                        ),
                    ),
                ),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['visible_in'] = array(
                'global'     => array(
                    'entity_value_key' => 'store_ids',
                    'entity_value_callback' => array($this, 'getContextPollStoreIds'),
                ),
                'form_field' => array(
                    'type'     => 'multiselect',
                    'name'     => 'store_ids',
                    'values'   => $this->getEditorHelper()->getStoreValuesForForm(false, false),
                    'required' => true,
                ),
                'renderer'   => array( 
                    'block_type' => 'customgrid/widget_grid_editor_renderer_field_store',
                ),
            );
        }
        
        return $fields;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param Mage_Poll_Model_Poll $editedEntity Edited poll
     * @param mixed $userValue User-edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function applyUserEditedValueToEditedEntity(
        Mage_Poll_Model_Poll $editedEntity,
        $userValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context
    ) {
        $isStoresField = false;
        
        if ($context->getValueId() == 'store_ids') {
            $editedEntity->setStoreIds($userValue);
            $isStoresField = true;
        } else {
            // Force the lazy-loading of the current store IDs
            $editedEntity->setStoreIds($editedEntity->getStoreIds());
        }
        
        return $isStoresField;
    }
}
