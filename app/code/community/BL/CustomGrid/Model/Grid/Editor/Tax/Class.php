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

class BL_CustomGrid_Model_Grid_Editor_Tax_Class extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('class_id'),
            'entity_model_class_code'     => 'tax/class',
            'entity_name_data_key'        => 'class_name',
        );
    }
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
            $callbackManager->getCallbackFromCallable(
                array($this, 'checkBaseUserEditPermissions'),
                self::WORKER_TYPE_SENTRY,
                BL_CustomGrid_Model_Grid_Editor_Sentry::ACTION_TYPE_CHECK_BASE_USER_EDIT_PERMISSIONS,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_LOW
            ),
            $callbackManager->getCallbackFromCallable(
                array($this, 'checkEditedEntityLoadedState'),
                self::WORKER_TYPE_ENTITY_LOADER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Loader::ACTION_TYPE_CHECK_EDITED_ENTITY_LOADED_STATE,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_LOW
            ),
        );
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        return array(
            'class_name' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'required-entry',
                    'required' => true,
                ),
            ),
        );
    }
    
    public function getAdditionalEditParams($blockType, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return array('class_type' => $gridBlock->getClassType());
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Sentry::checkBaseUserEditPermissions()
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock Grid block (if available)
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if available)
     * @return bool
     */
    public function checkBaseUserEditPermissions(
        BL_CustomGrid_Model_Grid $gridModel,
        $previousReturnedValue,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock = null,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null
    ) {
        $result = ($previousReturnedValue === true);
        
        if ($result) {
            /** @var Mage_Admin_Model_Session $session */
            $session = Mage::getSingleton('admin/session');
            $result  = false;
            $classType = null;
            
            if (!is_null($gridBlock)) {
                $classType = $gridBlock->getClassType();
            } elseif (!is_null($context)) {
                $classType = $context->getRequestParams()->getData('additional/class_type');
            }
            
            if ($classType == Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER) {
                $result = $session->isAllowed('sales/tax/classes_customer');
            } elseif ($classType == Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT) {
                $result = $session->isAllowed('sales/tax/classes_product');
            }
        }
        
        return $result;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::checkEditedEntityLoadedState()
     * 
     * @param Mage_Tax_Model_Class $editedEntity Edited tax class
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool
     */
    public function checkEditedEntityLoadedState(
        Mage_Tax_Model_Class $editedEntity,
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        return ($previousReturnedValue === true)
            && ($editedEntity->getClassType() == $context->getRequestParams()->getData('additional/class_type'));
    }
}
