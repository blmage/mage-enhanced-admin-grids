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

class BL_CustomGrid_Model_Grid_Type_Checkout_Agreement
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/checkout_agreement_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('sales/checkoutagreement');
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('checkout');
        
        $fields = array(
            'name' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'is_active' => array(
                'type'          => 'select',
                'required'      => true,
                'form_options'  => array(
                    '1' => $helper->__('Enabled'),
                    '0' => $helper->__('Disabled'),
                ),
            ),
            'is_html' => array(
                'type'          => 'select',
                'required'      => true,
                'form_options'  => array(
                    '0' => $helper->__('Text'),
                    '1' => $helper->__('HTML'),
                ),
            ),
            'checkbox_text' => array(
                'type'         => 'editor',
                'required'     => true,
                'in_grid'      => false,
                'form_rows'    => '5',
                'form_cols'    => '30',
                'form_wysiwyg' => false,
                'form_label'   => $helper->__('Checkbox Text'),
            ),
            'content' => array(
                'type'         => 'editor',
                'required'     => true,
                'in_grid'      => false,
                'form_label'   => $helper->__('Content'),
                'form_wysiwyg' => false,
                'form_style'   => 'height:24em;',
            ),
            'content_height' => array(
                'type'           => 'text',
                'form_maxlength' => 25,
                'form_class'     => 'validate-css-length',
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
        return array('agreement_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['agreement_id'])) {
            return Mage::getModel('checkout/agreement')->load($params['ids']['agreement_id']);
        }
        return null;
    }
    
    protected function _prepareEditableFieldCommonConfig($type, $id, $config)
    {
        // Remove editor handle, as it is not used/needed in original edit form
        $config = parent::_prepareEditableFieldCommonConfig($type, $id, $config);
        
        if (($config['type'] == 'editor') && isset($config['layout_handles'])) {
            array_filter($config['layout_handles'], create_function('$a', 'return ($a != \'custom_grid_editor_handle_editor\');'));
        }
        
        return $config;
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