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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Grid_Type_Poll extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/poll_grid');
    }
    
    /**
     * Return the IDs of the stores to which the given poll is associated
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edited field config
     * @param array $params Edit parameters
     * @param Mage_Poll_Model_Poll $entity Edited poll
     * @return array
     */
    public function getPollStoreIds($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return ($entity && $entity->getId() ? $entity->getStoreIds() : array());
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $helper Mage_Poll_Helper_Data */
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
                'form_values' => $this->_getEditorHelper()->getStoreValuesForForm(false, false),
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
        /** @var $poll Mage_Poll_Model_Poll */
        $poll = Mage::getModel('poll/poll');
        $poll->load($entityId);
        return $poll;
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Poll_Model_Poll */
        return $entity->getPollTitle();
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'cms/poll';
    }
}
