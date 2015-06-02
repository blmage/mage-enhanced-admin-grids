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

class BL_CustomGrid_Model_Grid_Type_Catalog_Search extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/catalog_search_grid');
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $helper Mage_Catalog_Helper_Data */
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
                'form_values'       => $this->_getEditorHelper()->getStoreValuesForForm(true, false),
                'required'          => true,
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('query_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        /** @var $query Mage_Catalogsearch_Model_Query */
        $query = Mage::getModel('catalogsearch/query');
        $query->load($entityId);
        return $query;
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Catalogsearch_Model_Query */
        return $entity->getQueryText();
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'catalog/search';
    }
    
    protected function _beforeSaveEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        /**
         * @var $entity Mage_Catalogsearch_Model_Query
         * @var $duplicate Mage_Catalogsearch_Model_Query
         */
        $duplicate = Mage::getModel('catalogsearch/query')
            ->setStoreId($entity->getStoreId())
            ->loadByQueryText($entity->getQueryText());
        
        if ($duplicate->getId() && ($duplicate->getId() != $entity->getId())) {
            Mage::throwException(Mage::helper('catalog')->__('Search Term with such search query already exists.'));
        }
        
        $entity->setIsProcessed(0);
        return parent::_beforeSaveEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
}
