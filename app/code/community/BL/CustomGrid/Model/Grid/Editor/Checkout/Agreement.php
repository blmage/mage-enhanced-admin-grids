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

class BL_CustomGrid_Model_Grid_Editor_Checkout_Agreement extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('agreement_id'),
            'entity_model_class_code'     => 'checkout/agreement',
             'entity_name_data_key'       => 'name',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'milou',//'sales/checkoutagreement',
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
        /** @var $helper Mage_Checkout_Helper_Data */
        $helper = Mage::helper('checkout');
        
        $fields = array(
            'name' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'is_active' => array(
                'form_field' => array(
                    'type'     => 'select',
                    'options'  => array(
                        '1' => $helper->__('Enabled'),
                        '0' => $helper->__('Disabled'),
                    ),
                    'required' => true,
                ),
            ),
            'is_html' => array(
                'form_field' => array(
                    'type'     => 'select',
                    'options'  => array(
                        '0' => $helper->__('Text'),
                        '1' => $helper->__('HTML'),
                    ),
                    'required' => true,
                ),
            ),
            'checkbox_text' => array(
                'form' => array(
                    'is_in_grid'       => false,
                    'no_editor_handle' => true,
                ),
                'form_field' => array(
                    'type'     => 'editor',
                    'label'    => $helper->__('Checkbox Text'),
                    'cols'     => '30',
                    'rows'     => '5',
                    'wysiwyg'  => false,
                    'required' => true,
                ),
            ),
            'content' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'     => 'editor',
                    'label'    => $helper->__('Content'),
                    'style'    => 'height:24em;',
                    'wysiwyg'  => false,
                    'required' => true,
                ),
            ),
            'content_height' => array(
                'form_field' => array(
                    'type'      => 'text',
                    'class'     => 'validate-css-length',
                    'maxlength' => 25,
                ),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = $this->getEditorHelper()->getStoreFieldBaseConfig(true);
        }
        
        return $fields;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param Mage_Checkout_Model_Agreement $editedEntity Edited checkout agreement
     * @param mixed $userValue User-edited value
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function applyUserEditedValueToEditedEntity(
        Mage_Checkout_Model_Agreement $editedEntity,
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
            /** @var $editedEntity Mage_Checkout_Model_Agreement */
            $editedEntity = $context->getEditedEntity();
            $transport->setData('value', $editedEntity->getStores());
        }
    }
}
