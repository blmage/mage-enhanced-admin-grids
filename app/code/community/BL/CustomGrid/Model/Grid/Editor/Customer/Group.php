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

class BL_CustomGrid_Model_Grid_Editor_Customer_Group extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('customer_group_id'),
            'entity_model_class_code'     => 'customer/group',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'customer/group',
            ),
        );
    }
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'checkEditedEntityLoadedState'),
                self::WORKER_TYPE_ENTITY_LOADER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Loader::ACTION_TYPE_CHECK_EDITED_ENTITY_LOADED_STATE
            ),
            $callbackManager->getInternalMainCallbackFromCallable(
                array($this, 'checkContextValueEditability'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_CHECK_CONTEXT_VALUE_EDITABILITY
            ),
            $callbackManager->getCallbackFromCallable(
                array($this, 'beforeSaveContextEditedEntity'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_BEFORE,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH
            ),
        );
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var Mage_Tax_Model_Class_Source_Customer $taxClassSource */
        $taxClassSource = Mage::getSingleton('tax/class_source_customer');
        
        $taxClassField = array(
            'global' => array(
                'entity_value_key' => 'tax_class_id',
            ),
            'form_field' => array(
                'type'        => 'select',
                'name'        => 'tax_class_id',
                'class'       => 'required-entry',
                'values'      => $taxClassSource->toOptionArray(),
                'required'    => true,
            ),
        );
        
        $fields = array(
            'type' => array(
                'global' => array(
                    'entity_value_key' => 'customer_group_code',
                ),
                'form_field' => array(
                    'type'     => 'text',
                    'name'     => 'code',
                    'class'    => 'required-entry',
                    'required' => true,
                ),
            ),
            'class_id'     => $taxClassField,
            'class_name'   => $taxClassField,
            'tax_class_id' => $taxClassField,
        );
        
        return $fields;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::checkEditedEntityLoadedState()
     * 
     * @param Mage_Customer_Model_Group $editedEntity Edited customer group
     * @return bool
     */
    public function checkEditedEntityLoadedState(Mage_Customer_Model_Group $editedEntity)
    {
        return (is_object($editedEntity) ? !is_null($editedEntity->getId()) : false);
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::isContextValueEditable()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool|string
     */
    public function checkContextValueEditability(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        if (($context->getValueId() == 'type')
            && ($context->getEditedEntity()->getId() == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)) {
            return $this->getBaseHelper()->__('The name is not editable for this customer group');
        }
        return true;
    }
    
    /**
     * Before callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param Mage_Customer_Model_Group $editedEntity Edited customer group
     */
    public function beforeSaveContextEditedEntity(Mage_Customer_Model_Group $editedEntity)
    {
        if ($this->getBaseHelper()->isMageVersionLesserThan(1, 7)
            && ($editedEntity->getId() == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)) {
            // Prevent unicity check (also done in original form, because code input is disabled and setCode is forced)
            $editedEntity->setCode(null);
        }
    }
}
