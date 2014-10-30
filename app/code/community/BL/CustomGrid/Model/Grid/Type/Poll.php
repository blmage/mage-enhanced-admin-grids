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

class BL_CustomGrid_Model_Grid_Type_Poll extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/poll_grid');
    }
    
    public function getPollStoreIds($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return ($entity && $entity->getId() ? $entity->getStoreIds() : array());
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        $helper = Mage::helper('poll');
        
        $fields = array(
            'poll_title' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'required-entry',
            ),
            'closed' => array(
                'type'        => 'select',
                'form_values' => array(
                    array(
                        'value' => 1,
                        'label' => $helper->__('Closed'),
                    ),
                    array(
                        'value' => 0,
                        'label' => $helper->__('Open'),
                    ),
                ),
            ),
        );
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fields['visible_in'] = array(
                'type'        => 'multiselect',
                'field_name'  => 'store_ids',
                'required'    => true,
                'form_values' => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
                'render_block_type'     => 'customgrid/widget_grid_editor_renderer_static_store',
                'entity_value_callback' => array($this, 'getPollStoreIds'),
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('poll_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        return Mage::getModel('poll/poll')->load($entityId);
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $entity->getPollTitle();
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'cms/poll';
    }
}
