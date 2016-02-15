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

class BL_CustomGrid_Model_Grid_Editor_Product_Review extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('review_id'),
            'entity_model_class_code'     => 'review/review',
            'entity_name_data_key'        => 'title',
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
            $callbackManager->getCallbackFromCallable(
                array($this, 'afterSaveContextEditedEntity'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_AFTER,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH
            ),
        );
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var Mage_Review_Helper_Data $helper */
        $helper = Mage::helper('review');
        /** @var Mage_Review_Model_Review $reviewModel */
        $reviewModel = Mage::getModel('review/review');
        
        $statuses = $reviewModel->getStatusCollection()
            ->load()
            ->toOptionArray();
        
        $fields = array(
            'status' => array(
                'global' => array(
                    'entity_value_key' => 'status_id',
                ),
                'form_field' => array(
                    'type'     => 'select',
                    'name'     => 'status_id',
                    'values'   => $helper->translateArray($statuses),
                    'required' => true,
                ),
            ),
            'nickname' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'title' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'detail' => array(
                'form' => array(
                    'is_in_grid' => false,
                ),
                'form_field' => array(
                    'type'     => 'textarea',
                    'label'    => $helper->__('Review'),
                    'style'    => 'height:24em;',
                    'required' => true,
                )
            ),
        );
        
        return $fields;
    }
    
    public function getAdditionalEditParams($blockType, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        $params = array();
        
        if (Mage::registry('use_pending_filter') === true) {
            $params['use_pending_filter'] = 1;
        }
        
        return $params;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Sentry::checkBaseUserEditPermissions()
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context (if available)
     * @return bool
     */
    public function checkBaseUserEditPermissions(
        BL_CustomGrid_Model_Grid $gridModel,
        $previousReturnedValue,
        BL_CustomGrid_Model_Grid_Editor_Context $context = null
    ) {
        $result = ($previousReturnedValue === true);
        
        if ($result) {
            /** @var Mage_Admin_Model_Session $session */
            $session = Mage::getSingleton('admin/session');
            
            if ((!is_null($context) && $context->getRequestParams()->getData('additional/use_pending_filter'))
                || (Mage::registry('use_pending_filter') === true)) {
                $result = $session->isAllowed('catalog/reviews_ratings/reviews/pending');
            } else {
                $result = $session->isAllowed('catalog/reviews_ratings/reviews/all');
            }
        }
        
        return $result;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Loader::checkEditedEntityLoadedState()
     * 
     * @param Mage_Review_Model_Review $editedEntity Edited review
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param mixed $previousReturnedValue Value returned by the previous callback
     * @return bool
     */
    public function checkEditedEntityLoadedState(
        Mage_Review_Model_Review $editedEntity,
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        $previousReturnedValue
    ) {
        $result = ($previousReturnedValue === true);
        
        if ($result) {
            $usePendingFilter = $context->getRequestParams()->getData('additional/use_pending_filter');
            $result = ($editedEntity->getStatus() == $editedEntity->getPendingStatus())
                ? $usePendingFilter
                : !$usePendingFilter;
        }
        
        return $result;
    }
    
    /**
     * After callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param Mage_Review_Model_Review $editedEntity Edited review
     */
    public function afterSaveContextEditedEntity(Mage_Review_Model_Review $editedEntity)
    {
        $editedEntity->aggregate();
    }
}
