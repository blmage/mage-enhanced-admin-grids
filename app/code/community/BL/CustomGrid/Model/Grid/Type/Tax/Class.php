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

class BL_CustomGrid_Model_Grid_Type_Tax_Class
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/tax_class_grid');
    }
    
    public function canExport($type)
    {
        /**
        * @todo if wanting to get export running for those grids (and perhaps others) :
        * Add additional export params from method with grid block instance as parameter
        * This way we'll be able to give the class type to a custom controller
        * -> could be done along with the new export system
        */
        return false;
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            if (!is_null($block)) {
                $classType = $block->getClassType();
            } elseif (isset($params['additional']['class_type'])) {
                $classType = $params['additional']['class_type'];
            } else {
                $classType = null;
            }
            
            switch ($classType) {
                case Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER:
                    return Mage::getSingleton('admin/session')->isAllowed('sales/tax/classes_customer');
                case Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT:
                    return Mage::getSingleton('admin/session')->isAllowed('sales/tax/classes_product');
            }
        }
        return false;
    }
    
    protected function _getBaseEditableFields($type)
    {
        return array(
            'class_name' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'required-entry',
            ),
        );
    }
    
    public function getAdditionalEditParams($type, $grid)
    {
        return array('class_type' => $grid->getClassType());
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('class_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['class_id'])
            && isset($params['additional']['class_type'])) {
            return Mage::getModel('tax/class')->load($params['ids']['class_id']);
        }
        return null;
    }
    
    protected function _isEditedEntityLoaded($type, $config, $params, $entity)
    {
        if (parent::_isEditedEntityLoaded($type, $config, $params, $entity)
            && isset($params['additional']['class_type'])) {
            return ($entity->getClassType() == $params['additional']['class_type']);
        }
        return false;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getClassName();
    }
}