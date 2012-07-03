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

class BL_CustomGrid_Model_Grid_Type_Catalog_Search
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/catalog_search_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('catalog/search');
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('catalog');
        
        $fields = array(
            'search_query' => array(
                'type'       => 'text',
                'required'   => true,
                'field_name' => 'query_text',
            ),
            'num_results' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'popularity' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'synonym_for' => array(
                'type'      => 'text',
                'required'  => true,
                'form_note' => $helper->__('(Will make search for the query above return results for this search.)'),
            ),
            'redirect' => array(
                'type'       => 'text',
                'form_class' => 'validate-url',
                'form_note'  => $helper->__('ex. http://domain.com'),
            ),
            'display_in_terms' => array(
                'type'         => 'select',
                'required'     => true,
                'form_options' => array(
                    '1' => $helper->__('Yes'),
                    '0' => $helper->__('No'),
                ),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = array(
                'type'              => 'select',
                'form_values'       => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(true, false),
                'required'          => true,
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('query_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['query_id'])) {
            return Mage::getModel('catalogsearch/query')->load($params['ids']['query_id']);
        }
        return null;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getQueryText();
    }
    
    protected function _beforeSaveEditedFieldValue($type, $config, $params, $entity, $value)
    {
        $model = Mage::getModel('catalogsearch/query');
        $model->setStoreId($entity->getStoreId());
        $model->loadByQueryText($entity->getQueryText());
        
        if ($model->getId() && ($model->getId() != $entity->getId())) {
            Mage::throwException(Mage::helper('catalog')->__('Search Term with such search query already exists.'));
        }
        
        $entity->setIsProcessed(0);
        return parent::_beforeSaveEditedFieldValue($type, $config, $params, $entity, $value);
    }
}