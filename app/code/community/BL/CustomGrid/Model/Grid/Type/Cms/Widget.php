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

class BL_CustomGrid_Model_Grid_Type_Cms_Widget extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('widget/adminhtml_widget_instance_grid');
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $helper Mage_Widget_Helper_Data */
        $helper = Mage::helper('widget');
        
        $fields = array(
            'title' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'sort_order' => array(
                'type' => 'text',
                'note' => $helper->__('Sort Order of widget instances in the same block reference'),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_ids'] = array(
                'type'              => 'multiselect',
                'required'          => true,
                'form_values'       => $this->_getEditorHelper()->getStoreValuesForForm(),
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('instance_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        /** @var $widget Mage_Widget_Model_Widget_Instance */
        $widget = Mage::getModel('widget/widget_instance');
        $widget->load($entityId);
        return $widget;
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Widget_Model_Widget_Instance */
        return $entity->getTitle();
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'cms/widget_instance';
    }
    
    protected function _prepareWidgetPageGroups($entity)
    {
        /** @var $entity Mage_Widget_Model_Widget_Instance */
        /**
         * Groups coming from the edit form do not have the same values as if they were loaded,
         * so prepare the given (loaded) ones to make them look like they have just been edited
         */
        if (is_array($pageGroups = $entity->getData('page_groups'))) {
            $editedPageGroups = array();
            
            foreach ($pageGroups as $pageGroup) {
                $editedPageGroups[] = array(
                    'page_group' => $pageGroup['group'],
                    
                    $pageGroup['group'] => array(
                        'page_id'       => $pageGroup['page_id'],
                        'page_group'    => $pageGroup['group'],
                        'layout_handle' => $pageGroup['layout_handle'],
                        'for'           => $pageGroup['for'],
                        'block'         => $pageGroup['block_reference'],
                        'entities'      => $pageGroup['entities'],
                        'template'      => $pageGroup['template'],
                    ),
                );
            }
            
            $entity->setData('page_groups', $editedPageGroups);
        }
        return $this;
    }
    
    protected function _applyEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        /** @var $entity Mage_Widget_Model_Widget_Instance */
        if ($config->getValueId() == 'sort_order') {
            $entity->setSortOrder(empty($value) ? '0' : $value);
            return $this;
        }
        return parent::_applyEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _beforeSaveEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        /** @var $entity Mage_Widget_Model_Widget_Instance */
        if (is_string($result = $entity->validate())) {
            Mage::throwException($result);
        }
        $this->_prepareWidgetPageGroups($entity);
        return parent::_beforeSaveEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _getSavedFieldValueForRender($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Widget_Model_Widget_Instance */
        if ($config->getValueId() == 'store_ids') {
            $storesIds = $entity->getStoreIds();
            return (is_array($storesIds) ? $storesIds : explode(',', $storesIds));
        }
        return parent::_getSavedFieldValueForRender($blockType, $config, $params, $entity);
    }
}
