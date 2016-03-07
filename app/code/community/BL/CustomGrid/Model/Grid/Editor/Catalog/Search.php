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

class BL_CustomGrid_Model_Grid_Editor_Catalog_Search extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('query_id'),
            'entity_model_class_code'     => 'catalogsearch/query',
            'entity_name_data_key'        => 'query_text',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'catalog/search',
            ),
        );
    }
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
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
        /** @var Mage_Catalog_Helper_Data $helper */
        $helper = Mage::helper('catalog');
        
        $fields = array(
            'search_query' => array(
                'global'     => array(
                    'entity_value_key' => 'query_text',
                ),
                'form_field' => array(
                    'type'     => 'text',
                    'name'     => 'query_text',
                    'required' => true,
                ),
            ),
            'num_results' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'popularity' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'required' => true,
                ),
            ),
            'synonym_for' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'note'     => $helper->__('(Will make search for the query above return results for this search.)'),
                    'required' => true,
                ),
            ),
            'redirect' => array(
                'form_field' => array(
                    'type'  => 'text',
                    'class' => 'validate-url',
                    'note'  => $helper->__('ex. http://domain.com'),
                ),
            ),
            'display_in_terms' => array(
                'form_field' => array(
                    'type'     => 'select',
                    'options'  => array(
                        '1' => $helper->__('Yes'),
                        '0' => $helper->__('No'),
                    ),
                    'required' => true,
                ),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = $this->getEditorHelper()->getStoreFieldBaseConfig(false);
        }
        
        return $fields;
    }
    
    /**
     * Before callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param Mage_CatalogSearch_Model_Query $editedEntity Edited search query
     */
    public function beforeSaveContextEditedEntity(Mage_CatalogSearch_Model_Query $editedEntity)
    {
        /** @var Mage_CatalogSearch_Model_Query $duplicate */
        $duplicate = Mage::getModel('catalogsearch/query');
        $duplicate->setStoreId($editedEntity->getStoreId()); // No chaining : this method does not return $this
        $duplicate->loadByQueryText($editedEntity->getQueryText());
        
        if ($duplicate->getId() && ($duplicate->getId() != $editedEntity->getId())) {
            /** @var Mage_Catalog_Helper_Data $helper */
            $helper = Mage::helper('catalog');
            Mage::throwException($helper->__('Search Term with such search query already exists.'));
        }
        
        $editedEntity->setIsProcessed(0);
    }
}
