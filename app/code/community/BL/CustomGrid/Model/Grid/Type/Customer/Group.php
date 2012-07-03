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

class BL_CustomGrid_Model_Grid_Type_Customer_Group
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/customer_group_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('customer/group');
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('cms');
        
        $taxClassField = array(
            'type'        => 'select',
            'required'    => true,
            'field_name'  => 'tax_class_id',
            'form_name'   => 'tax_class_id',
            'form_class'  => 'required-entry',
            'form_values' => Mage::getSingleton('tax/class_source_customer')->toOptionArray()
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
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('customer_group_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['customer_group_id'])) {
            return Mage::getModel('customer/group')->load($params['ids']['customer_group_id']);
        }
        return null;
    }
    
    protected function _isEditedEntityLoaded($type, $config, $params, $entity)
    {
        return (is_object($entity) ? !is_null($entity->getId()) : false);
    }
    
    
    protected function _checkEntityEditableField($type, $config, $params, $entity)
    {
        if (parent::_checkEntityEditableField($type, $config, $params, $entity)) {
            if (($config['id'] == 'type')
                || ($entity->getId() == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID)) {
                Mage::throwException(Mage::helper('customgrid')->__('The name is not editable for this customer group'));
            }
            return true;
        }
        return false;
    }
    
    protected function _beforeSaveEditedFieldValue($type, $config, $params, $entity, $value)
    {
        if ($entity->getId() == Mage_Customer_Model_Group::NOT_LOGGED_IN_ID) {
            // Prevent from unique check (also made in original form, because code input is disabled and setCode is forced)
            $entity->setCode(null);
        }
        return parent::_beforeSaveEditedFieldValue($type, $config, $params, $entity, $value);
    }
}