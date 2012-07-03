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

class BL_CustomGrid_Model_Grid_Type_Cms_Widget
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'widget/adminhtml_widget_instance_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('cms/widget_instance');
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
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
                'form_values'       => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
                'required'          => true,
                'render_block_type' => 'customgrid/widget_grid_editor_renderer_static_store',
            );
        }
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('instance_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['instance_id'])) {
            return Mage::getModel('widget/widget_instance')->load($params['ids']['instance_id']);
        }
        return null;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getTitle();
    }
    
    protected function _prepareWidgetPageGroups($widget)
    {
        /**
        * Groups coming from edit form have not the same values as loaded ones,
        * so prepare existing ones to make as if they had been edited
        */
        if (is_array($pageGroups = $widget->getData('page_groups'))) {
            $newGroups = array();
            
            foreach ($pageGroups as $pageGroup) {
                $newGroups[] = array(
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
            
            $widget->setData('page_groups', $newGroups);
        }
        return $this;
    }
    
    protected function _applyEditedFieldValue($type, $config, $params, $entity, $value)
    {
        if ($config['id'] == 'sort_order') {
            $entity->setSortOrder(empty($value) ? '0' : $value);
            return $this;
        } else {
            return parent::_applyEditedFieldValue($type, $config, $params, $entity, $value);
        }
    }
    
    protected function _beforeSaveEditedFieldValue($type, $config, $params, $entity, $value)
    {
        if ((($result = $entity->validate()) !== true) && is_string($result)) {
            Mage::throwException($result);
        }
        // Ensure page groups will be succesfully saved, and not resetted
        $this->_prepareWidgetPageGroups($entity);
        return parent::_beforeSaveEditedFieldValue($type, $config, $params, $entity, $value);
    }
    
    protected function _getSavedFieldValueForRender($type, $config, $params, $entity)
    {
        if ($config['id'] == 'store_ids') {
            $storesIds = $entity->getStoreIds();
            return (is_array($storesIds) ? $storesIds : explode(',', $storesIds));
        } else {
            return parent::_getSavedFieldValueForRender($type, $config, $params, $entity);
        }
    }
}