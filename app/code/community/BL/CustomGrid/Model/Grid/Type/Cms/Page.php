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

class BL_CustomGrid_Model_Grid_Type_Cms_Page
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/cms_page_grid');
    }
    
    protected function _getColumnsLockedValues($type)
    {
        return array(
            'store_code' => array(
                'renderer'      => '',
                'config_values' => array(
                    'filter'   => false,
                    'sortable' => false,
                ),
            ),
            '_first_store_id' => array(
                'renderer'      => '',
                'config_values' => array(
                    'filter'   => false,
                    'sortable' => false,
                ),
            ),
        );
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('cms/page/save');
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('cms');
        
        $fields = array(
            'title' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'identifier' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'validate-identifier',
                'form_note'  => $helper->__('Relative to Website Base URL'),
            ),
            'root_template' => array(
                'type'        => 'select',
                'form_values' => Mage::getSingleton('page/source_layout')->toOptionArray(),
                'required'    => true,
            ),
            'is_active' => array(
                'type'          => 'select',
                'form_options'  => Mage::getModel('cms/page')->getAvailableStatuses(),
                'required'      => true,
            ),
            'meta_keywords' => array(
                'type'          => 'textarea',
                'in_grid'       => false,
                'form_label'    => $helper->__('Meta Keywords'),
                'window_height' => 310,
            ),
            'meta_description' => array(
                'type'          => 'textarea',
                'in_grid'       => false,
                'form_label'    => $helper->__('Meta Description'),
                'window_height' => 310,
            ),
            'content_heading' => true,
            'content' => array(
                'type'         => 'editor',
                'required'     => true,
                'in_grid'      => false,
                'form_wysiwyg' => true,
                'form_label'   => $helper->__('Content'),
                'form_style'   => 'height:36em;',
            ),
            'layout_update_xml' => array(
                'type'       => 'textarea',
                'in_grid'    => false,
                'form_label' => $helper->__('Layout Update XML'),
                'form_style' => 'height:24em;',
            ),
            'custom_theme' => array(
                'type'        => 'select',
                'form_values' => Mage::getModel('core/design_source_design')->getAllOptions(),
            ),
            'custom_root_template' => array(
                'type'        => 'select',
                'form_values' => Mage::getSingleton('page/source_layout')->toOptionArray(true),
            ),
            'custom_layout_update_xml' => array(
                'type'       => 'textarea',
                'in_grid'    => false,
                'form_label' => $helper->__('Custom Layout Update XML'),
                'form_style' => 'height:24em;',
            ),
            'custom_theme_from' => array(
                'type' => 'date',
            ),
            'custom_theme_to' => array(
                'type' => 'date',
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = array(
                'type'              => 'multiselect',
                'form_values'       => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'required'          => true,
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('page_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['page_id'])) {
            return Mage::getModel('cms/page')->load($params['ids']['page_id']);
        }
        return null;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getTitle();
    }
    
    protected function _applyEditedFieldValue($type, $config, $params, $entity, $value)
    {
        if ($config['id'] != 'store_id') {
            $entity->setStores($entity->getStoreId());
            return parent::_applyEditedFieldValue($type, $config, $params, $entity, $value);
        } else {
            $entity->setStores($value);
            return $this;
        }
    }
    
    protected function _getSavedFieldValueForRender($type, $config, $params, $entity)
    {
        if ($config['id'] == 'store_id') {
            return $entity->getStores();
        } else {
            return parent::_getSavedFieldValueForRender($type, $config, $params, $entity);
        }
    }
}
