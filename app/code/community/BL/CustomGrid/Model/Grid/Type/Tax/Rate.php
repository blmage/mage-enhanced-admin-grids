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

class BL_CustomGrid_Model_Grid_Type_Tax_Rate
    extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    public function isAppliableToGrid($type, $rewritingClassName)
    {
        return ($type == 'adminhtml/tax_rate_grid');
    }
    
    public function checkUserEditPermissions($type, $model, $block=null, $params=array())
    {
        if (parent::checkUserEditPermissions($type, $model, $block, $params)) {
            return Mage::getSingleton('admin/session')->isAllowed('sales/tax/rates');
        }
        return false;
    }
    
    public function getTaxRateRateNumber($type, $config, $params, $entity)
    {
        if ($entity->getRate()) {
            $value = 1*$entity->getRate();
        } else {
            $value = 0;
        }
        return $value;
    }
    
    protected function _getBaseEditableFields($type)
    {
        $helper = Mage::helper('cms');
        
        // All the other fields are using dependences
        $fields = array(
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
        );
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($type)
    {
        return array('tax_calculation_rate_id');
    }
    
    protected function _loadEditedEntity($type, $config, $params)
    {
        if (isset($params['ids']['tax_calculation_rate_id'])) {
            return Mage::getSingleton('tax/calculation_rate')
                ->load($params['ids']['tax_calculation_rate_id']);
        }
        return null;
    }
    
    protected function _getLoadedEntityName($type, $config, $params, $entity)
    {
        return $entity->getCode();
    }
}
