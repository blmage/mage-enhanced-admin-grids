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

class BL_CustomGrid_Model_Grid_Type_Tax_Rate extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/tax_rate_grid');
    }
    
    public function getTaxRateRateNumber($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return ($entity->getRate() ? 1*$entity->getRate() : 0);
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        return array(
            'code' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'required-entry',
            ),
            'rate' => array(
                'type'         => 'text',
                'required'     => true,
                'form_class'   => 'validate-not-negative-number',
                'entity_value' => array($this, 'getTaxRateRateNumber'),
            ),
            // All the other fields are using dependences
        );
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('tax_calculation_rate_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        return Mage::getSingleton('tax/calculation_rate')->load($entityId);
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        return $entity->getCode();
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'sales/tax/rates';
    }
}
