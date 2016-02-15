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

class BL_CustomGrid_Model_Grid_Type_Tax_Rule extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/tax_rule_grid');
    }
    
    protected function _getEditorModelClassCode()
    {
        return 'customgrid/grid_editor_tax_rule';
    }
    
    protected function _beforeApplyEditedFieldValue(
        $blockType,
        BL_CustomGrid_Object $config,
        array $params,
        $entity,
        &$value
    ) {
        /** @var $entity Mage_Tax_Model_Calculation_Rule */
        $entity->addData(
            array(
                'tax_rate' => array_unique($entity->getRates()),
                'tax_product_class'  => array_unique($entity->getProductTaxClasses()),
                'tax_customer_class' => array_unique($entity->getCustomerTaxClasses()),
            )
        );
        return parent::_beforeApplyEditedFieldValue($blockType, $config, $params, $entity, $value);
    }
}
