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

class BL_CustomGrid_Model_Grid_Type_Product_Review extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/review_grid');
    }
    
    public function canExport($blockType)
    {
        return !$this->isSupportedBlockType($blockType);
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $helper Mage_Review_Helper_Data */
        $helper = Mage::helper('review');
        /** @var $reviewModel Mage_Review_Model_Review */
        $reviewModel = Mage::getModel('review/review');
        
        $statuses = $reviewModel->getStatusCollection()
            ->load()
            ->toOptionArray();
        
        $fields = array(
            'status' => array(
                'type'        => 'select',
                'required'    => true,
                'field_name'  => 'status_id',
                'form_values' => $helper->translateArray($statuses),
            ),
            'nickname' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'title' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'detail' => array(
                'type'       => 'textarea',
                'required'   => true,
                'in_grid'    => false,
                'form_label' => $helper->__('Review'),
                'form_style' => 'height:24em;',
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
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('review_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        /** @var $review Mage_Review_Model_Review */
        $review = Mage::getModel('review/review');
        $review->load($entityId);
        return $review;
    }
    
    protected function _isEditedEntityLoaded(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $entityId
    ) {
        if (parent::_isEditedEntityLoaded($blockType, $config, $params, $entity, $entityId)) {
            /** @var $entity Mage_Review_Model_Review */
            $usePendingFilter = (isset($params['additional']) && isset($params['additional']['use_pending_filter']));
            return ($entity->getStatus() == $entity->getPendingStatus() ? $usePendingFilter : !$usePendingFilter);
        }
        return false;
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Review_Model_Review */
        return $entity->getTitle();
    }
    
    public function checkUserEditPermissions(
        $blockType,
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock = null,
        array $params = array()
    ) {
        if (parent::checkUserEditPermissions($blockType, $gridModel, $gridBlock, $params)) {
            /** @var $session Mage_Admin_Model_Session */
            $session = Mage::getSingleton('admin/session');
            
            if ((Mage::registry('use_pending_filter') === true)
                || (isset($params['additional']) && isset($params['additional']['use_pending_filter']))) {
                return $session->isAllowed('catalog/reviews_ratings/reviews/pending');
            } else {
                return $session->isAllowed('catalog/reviews_ratings/reviews/all');
            }
        }
        return false;
    }
}
