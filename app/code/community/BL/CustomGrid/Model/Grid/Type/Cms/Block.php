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

class BL_CustomGrid_Model_Grid_Type_Cms_Block
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/cms_block_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('cms/block');
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
                'form_class' => 'validate-xml-identifier',
            ),
            'is_active' => array(
                'type'         => 'select',
                'required'     => true,
                'form_options' => array(
                    '1' => $helper->__('Enabled'),
                    '0' => $helper->__('Disabled'),
                ),
            ),
            'content' => array(
                'type'         => 'editor',
                'required'     => true,
                'in_grid'      => false,
                'form_label'   => $helper->__('Content'),
                'form_wysiwyg' => true,
                'form_style'   => 'height:36em;',
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['store_id'] = array(
                'type'              => 'multiselect',
                'required'          => true,
                'form_values'       => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('block_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['block_id'])) {
            return Mage::getModel('cms/block')->load($params['ids']['block_id']);
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