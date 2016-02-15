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

class BL_CustomGrid_Model_Grid_Editor_Tax_Rate extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('tax_calculation_rate_id'),
            'entity_model_class_code'     => 'tax/calculation_rate',
            'entity_name_data_key'        => 'code',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'sales/tax/rates',
            ),
        );
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        return array(
            'code' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'required-entry',
                    'required' => true,
                ),
            ),
            'rate' => array(
                'global' => array(
                    'entity_value_callback' => array($this, 'getContextTaxRateRateValue'),
                ),
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'validate-not-negative-number',
                    'required' => true,
                ),
            ),
            // All the other fields use dependencies
        );
    }
    
    /**
     * Return the actual tax rate value of the edited tax rate from the given editor context
     * 1
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return int|float
     */
    public function getContextTaxRateRateValue(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        /** @var Mage_Tax_Model_Calculation_Rate $editedTaxRate */
        $editedTaxRate = $context->getEditedEntity();
        return ($editedTaxRate->getRate() ? $editedTaxRate->getRate()*1 : 0);
    }
}
