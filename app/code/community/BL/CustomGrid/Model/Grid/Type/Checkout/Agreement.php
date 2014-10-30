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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Checkout_Agreement extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/checkout_agreement_grid');
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        $helper = Mage::helper('checkout');
        
        $fields = array(
            'name' => array(
                'type'     => 'text',
                'required' => true,
            ),
            'is_active' => array(
                'type'         => 'select',
                'required'     => true,
                'form_options' => array(
                    '1' => $helper->__('Enabled'),
                    '0' => $helper->__('Disabled'),
                ),
            ),
            'is_html' => array(
                'type'         => 'select',
                'required'     => true,
                'form_options' => array(
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
            $stores = Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true);
            
            $fields['store_id'] = array(
                'type'              => 'multiselect',
                'required'          => true,
                'form_values'       => $stores,
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _prepareEditableFieldCommonConfig(
        $blockType,
        $fieldId,
        BL_CustomGrid_Model_Grid_Edit_Config $config
    ) {
        parent::_prepareEditableFieldCommonConfig($blockType, $fieldId, $config);
        
        // Remove editor handle, as it is not used/needed in original edit form
        if (($config->getType() == 'editor') && is_array($handles = $config->getData('layout_handles'))) {
            $config->setData(
                'layout_handles',
                array_filter($handles, create_function('$v', 'return ($v != "blcg_grid_editor_handle_editor");'))
            );
        }
        
        return $this;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('agreement_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        return Mage::getModel('checkout/agreement')->load($entityId);
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'sales/checkoutagreement';
    }
    
    protected function _applyEditedFieldValue($blockType, BL_CustomGrid_Object $config, array $params, $entity, $value)
    {
        if ($config->getValueId() == 'store_id') {
            $entity->setStores($value);
            return $this;
        }
        $entity->setStores($entity->getStoreId());
        return parent::_applyEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
    
    protected function _getSavedFieldValueForRender($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return ($config->getValueId() == 'store_id')
            ? $entity->getStores()
            : parent::_getSavedFieldValueForRender($blockType, $config, $params, $entity);
    }
}
