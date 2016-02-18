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

class BL_CustomGrid_Model_Grid_Editor_Tax_Rule extends BL_CustomGrid_Model_Grid_Editor_Abstract
{
    protected function _getBaseConfigData()
    {
        return array(
            'entity_row_identifiers_keys' => array('tax_calculation_rule_id'),
            'entity_model_class_code'     => 'tax/calculation_rule',
            'entity_name_data_key'        => 'code',
            'grid_block_edit_permissions' => array(
                BL_CustomGrid_Model_Grid_Editor_Sentry::BLOCK_TYPE_ALL => 'sales/tax/rules',
            ),
        );
    }
    
    public function getDefaultBaseCallbacks(BL_CustomGrid_Model_Grid_Editor_Callback_Manager $callbackManager)
    {
        return array(
            $callbackManager->getCallbackFromCallable(
                array($this, 'getContextUserEditedValue'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_GET_CONTEXT_USER_EDITED_VALUE,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_MAIN,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_LOW
            ),
            $callbackManager->getCallbackFromCallable(
                array($this, 'beforeApplyUserEditedValueToEditedEntity'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_APPLY_USER_EDITED_VALUE_TO_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_BEFORE,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH
            ),
            $callbackManager->getCallbackFromCallable(
                array($this, 'beforeSaveContextEditedEntity'),
                self::WORKER_TYPE_ENTITY_UPDATER,
                BL_CustomGrid_Model_Grid_Editor_Entity_Updater::ACTION_TYPE_SAVE_CONTEXT_EDITED_ENTITY,
                BL_CustomGrid_Model_Grid_Editor_Callback::POSITION_BEFORE,
                BL_CustomGrid_Model_Grid_Editor_Callback::PRIORITY_EDITOR_INTERNAL_HIGH
            ),
        );
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var Mage_Tax_Helper_Data $helper */
        $helper = Mage::helper('tax');
        /** @var Mage_Tax_Model_Class $taxClassModel */
        $taxClassModel = Mage::getModel('tax/class');
        /** @var Mage_Tax_Model_Calculation_Rate $taxRateModel */
        $taxRateModel  = Mage::getModel('tax/calculation_rate');
        
        $productClasses = $taxClassModel->getCollection()
            ->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)
            ->toOptionArray();
        
        $customerClasses = $taxClassModel->getCollection()
            ->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER)
            ->toOptionArray();
        
        $taxRates = $taxRateModel->getCollection()->toOptionArray();
        
        $fields = array(
            'code' => array(
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'required-entry',
                    'required' => true,
                ),
            ),
            'customer_tax_classes' => array(
                'global' => array(
                    'entity_value_callback' => array($this, 'getContextTaxRuleFieldValue'),
                    'entity_value_key' => 'tax_customer_class',
                ),
                'form_field' => array(
                    'type'     => 'multiselect',
                    'name'     => 'tax_customer_class',
                    'class'    => 'required-entry',
                    'values'   => $customerClasses,
                    'required' => true,
                ),
            ),
            'product_tax_classes' => array(
                'global' => array(
                    'entity_value_callback' => array($this, 'getContextTaxRuleFieldValue'),
                    'entity_value_key' => 'tax_product_class',
                ),
                'form_field' => array(
                    'type'     => 'multiselect',
                    'name'     => 'tax_product_class',
                    'class'    => 'required-entry',
                    'values'   => $productClasses,
                    'required' => true,
                ),
            ),
            'tax_rates' => array(
                'global' => array(
                    'entity_value_callback' => array($this, 'getContextTaxRuleFieldValue'),
                    'entity_value_key' => 'tax_rate',
                ),
                'form_field' => array(
                    'type'     => 'multiselect',
                    'name'     => 'tax_rate',
                    'class'    => 'required-entry',
                    'values'   => $taxRates,
                    'required' => true,
                ),
            ),
            'priority' => array(
                'global' => array(
                    'entity_value_callback' => array($this, 'getContextTaxRuleFieldValue'),
                ),
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'validate-not-negative-number',
                    'note'     => $helper->__('Tax rates at the same priority are added, others are compounded.'),
                    'required' => true,
                ),
            ),
            'position' => array(
                'global' => array(
                    'entity_value_callback' => array($this, 'getContextTaxRuleFieldValue'),
                ),
                'form_field' => array(
                    'type'     => 'text',
                    'class'    => 'validate-not-negative-number',
                    'required' => true,
                ),
            ),
        );
        
        if ($this->getBaseHelper()->isMageVersionGreaterThan(1, 7)) {
            $fields['calculate_subtotal'] = array(
                'form_field' => array(
                    'type'    => 'checkbox',
                    'onclick' => 'this.value = this.checked ? 1 : 0;',
                    'checked_callback' => array($this, 'isCalculateSubtotalCheckboxChecked'),
                ),
            );
        }
        
        return $fields;
    }
    
    /**
     * Return whether the "Calculate off subtotal only" checkbox should be checked
     * according to the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return bool
     */
    public function isCalculateSubtotalCheckboxChecked(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        return (bool) $context->getEditedEntity()->getCalculateSubtotal();
    }
    
    /**
     * Return the value of the edited tax rule field from the given editor context
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @return mixed
     */
    public function getContextTaxRuleFieldValue(BL_CustomGrid_Model_Grid_Editor_Context $context)
    {
        /** @var Mage_Tax_Model_Calculation_Rule $editedTaxRule */
        $editedTaxRule = $context->getEditedEntity();
        $fieldValue = null;
        
        switch ($context->getValueId()) {
            case 'customer_tax_classes':
                $fieldValue = $editedTaxRule->getCustomerTaxClasses();
                break;
            case 'product_tax_classes':
                $fieldValue = $editedTaxRule->getProductTaxClasses();
                break;
            case 'tax_rates':
                $fieldValue = $editedTaxRule->getRates();
                break;
            case 'priority':
                $fieldValue = (int) $editedTaxRule->getPriority();
                break;
            case 'position':
                $fieldValue = (int) $editedTaxRule->getPosition();
                break;
        }
        
        return $fieldValue;
    }
    
    /**
     * Callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::getContextUserEditedValue()
     * 
     * @param BL_CustomGrid_Model_Grid_Editor_Context $context Editor context
     * @param BL_CustomGrid_Object $transport Transport object used to hold the user value
     */
    public function getContextUserEditedValue(
        BL_CustomGrid_Model_Grid_Editor_Context $context,
        BL_CustomGrid_Object $transport
    ) {
        if ($context->getValueId() == 'calculate_subtotal') {
            $transport->setData('value', $transport->getData('value') ? 1 : 0);
        }
    }
    
    /**
     * Before callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::applyUserEditedValueToEditedEntity()
     * 
     * @param Mage_Tax_Model_Calculation_Rule $editedEntity Edited tax rule
     */
    public function beforeApplyUserEditedValueToEditedEntity(Mage_Tax_Model_Calculation_Rule $editedEntity)
    {
        $editedEntity->addData(
            array(
                'blcg_original_code' => $editedEntity->getCode(),
                'tax_customer_class' => array_unique($editedEntity->getCustomerTaxClasses()),
                'tax_product_class'  => array_unique($editedEntity->getProductTaxClasses()),
                'tax_rate'           => array_unique($editedEntity->getRates()),
            )
        );
    }
    
    /**
     * Before callback for @see BL_CustomGrid_Model_Grid_Editor_Entity_Updater::saveContextEditedEntity()
     * 
     * @param Mage_Tax_Model_Calculation_Rule $editedEntity Edited tax rule
     */
    public function beforeSaveContextEditedEntity(Mage_Tax_Model_Calculation_Rule $editedEntity)
    {
        if ($this->getBaseHelper()->isMageVersionGreaterThan(1, 7)) {
            $existingRules = array_diff(
                $editedEntity->fetchRuleCodes(
                    $editedEntity->getTaxRate(),
                    $editedEntity->getTaxCustomerClass(),
                    $editedEntity->getTaxProductClass()
                ),
                array($editedEntity->getData('blcg_original_code'))
            );
            
            if (count($existingRules) > 0) {
                /** @var Mage_Tax_Helper_Data $taxHelper */
                $taxHelper = Mage::helper('tax');
                $ruleCodes = implode(',', $existingRules);
                
                Mage::throwException(
                    $taxHelper->__(
                        'Rules (%s) already exist for the specified Tax Rate, Customer Tax Class '
                        . 'and Product Tax Class combinations',
                        $ruleCodes
                    )
                );
            }
        }
    }
}
