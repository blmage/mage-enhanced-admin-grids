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

class BL_CustomGrid_Model_Grid_Type_Poll
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/poll_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('cms/poll');
        }
        return false;
    }
    
    public function getPollStoreIds($type, $config, $params, $entity)
    {
        return ($entity && $entity->getId() ? $entity->getStoreIds() : array());
    }
    
    protected function _getBaseEditableFields($type)
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
                        'value'     => 1,
                        'label'     => Mage::helper('poll')->__('Closed'),
                    ),
                    array(
                        'value'     => 0,
                        'label'     => Mage::helper('poll')->__('Open'),
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
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('poll_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['poll_id'])) {
            return Mage::getModel('poll/poll')->load($params['ids']['poll_id']);
        }
        return null;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getPollTitle();
    }
}