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

class BL_CustomGrid_Model_Grid_Type_Customer_Group extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/customer_group_grid');
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $taxClassSource Mage_Tax_Model_Class_Source_Customer */
        $taxClassSource = Mage::getSingleton('tax/class_source_customer');
        
        $taxClassField = array(
            'type'        => 'select',
            'required'    => true,
            'field_name'  => 'tax_class_id',
            'form_name'   => 'tax_class_id',
            'form_class'  => 'required-entry',
            'form_values' => $taxClassSource->toOptionArray(),
        );
        
        $fields = array(
            'type' => array(
                'type'       => 'text',
                'required'   => true,
                'field_name' => 'customer_group_code',
                'form_name'  => 'code',
                'form_class' => 'required-entry',
            ),
            'class_name'   => $taxClassField,
            'tax_class_id' => $taxClassField,
            'class_id'     => $taxClassField,
        );
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('customer_group_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        /** @var $group Mage_Customer_Model_Group */
        $group = Mage::getModel('customer/group');
        $group->load($entityId);
        return $group;
    }
    
    protected function _isEditedEntityLoaded(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $entityId
    ) {
        return (is_object($entity) ? !is_null($entity->getId()) : false);
    }
    
    protected function _checkEntityEditableField($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Customer_Model_Group */
        if (parent::_checkEntityEditableField($blockType, $config, $params, $entity)) {
            if (($config->getValueId() == 'type')
                && ($entity->getId() == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)) {
                Mage::throwException($this->_getBaseHelper()->__('The name is not editable for this customer group'));
            }
            return true;
        }
        return false;
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'customer/group';
    }
    
    protected function _beforeSaveEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $value
    ) {
        /** @var $entity Mage_Customer_Model_Group */
        if ($entity->getId() == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID) {
            // Prevent unicity check (also done in original form, because code input is disabled and setCode is forced)
            $entity->setCode(null);
        }
        return parent::_beforeSaveEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
}
