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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Product_Review
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        // @todo apply also to product's tabs corresponding grid
        return ($type == 'adminhtml/review_grid');
    }
    
    public function canExport($type)
    {
        // @todo allow it when new export system is done (look at tax class type)
        return false;
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            if ((Mage::registry('use_pending_filter') === true)
                || isset($params['additional']['use_pending_filter'])) {
                return Mage::getSingleton('admin/session')->isAllowed('catalog/reviews_ratings/reviews/pending');
            } else {
                return Mage::getSingleton('admin/session')->isAllowed('catalog/reviews_ratings/reviews/all');
            }
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('review');
        
        $statuses = Mage::getModel('review/review')
            ->getStatusCollection()
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
    
    public function getAdditionalEditParams($type, $grid)
    {
        if (Mage::registry('use_pending_filter') === true) {
            return array('use_pending_filter' => 1);
        }
        return array();
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('review_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['review_id'])) {
            return Mage::getModel('review/review')->load($params['ids']['review_id']);
        }
        return null;
    }
    
    protected function _isEditedEntityLoaded($type, $config, $params, $entity)
    {
        if (parent::_isEditedEntityLoaded($type, $config, $params, $entity)) {
            if ($entity->getStatus() == $entity->getPendingStatus()) {
                return isset($params['additional']['use_pending_filter']);
            } else {
                return !isset($params['additional']['use_pending_filter']);
            }
        }
        return false;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getTitle();
    }
}