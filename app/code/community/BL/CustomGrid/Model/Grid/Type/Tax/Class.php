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

class BL_CustomGrid_Model_Grid_Type_Tax_Class extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/tax_class_grid');
    }
    
    public function canExport($blockType)
    {
        return false;
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        return array(
            'class_name' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'required-entry',
            ),
        );
    }
    
    public function getAdditionalEditParams($blockType, Mage_Adminhtml_Block_Widget_Grid $gridBlock)
    {
        return array('class_type' => $gridBlock->getClassType());
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('class_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        if (isset($params['addtional']) && isset($params['additional']['class_type'])) {
            return Mage::getModel('tax/class')->load($entityId);
        }
        return null;
    }
    
    protected function _isEditedEntityLoaded(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        $entityId
    ) {
        if (parent::_isEditedEntityLoaded($blockType, $config, $params, $entity, $entityId)
            && isset($params['additional'])
            && isset($params['additional']['class_type'])) {
            return ($entity->getClassType() == $params['additional']['class_type']);
        }
        return false;
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $entity->getClassName();
    }
    
    public function checkUserEditPermissions(
        $blockType,
        BL_CustomGrid_Model_Grid $gridModel,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock = null,
        array $params = array()
    ) {
        if (parent::checkUserEditPermissions($blockType, $gridModel, $gridBlock, $params)) {
            $classType = null;
            
            if (!is_null($gridBlock)) {
                $classType = $gridBlock->getClassType();
            } elseif (isset($params['additional']) && isset($params['additional']['class_type'])) {
                $classType = $params['additional']['class_type'];
            }
            if ($classType == Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER) {
                return Mage::getSingleton('admin/session')->isAllowed('sales/tax/classes_customer');
            } elseif ($classType == Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT) {
                return Mage::getSingleton('admin/session')->isAllowed('sales/tax/classes_product');
            }
        }
        return false;
    }
}
