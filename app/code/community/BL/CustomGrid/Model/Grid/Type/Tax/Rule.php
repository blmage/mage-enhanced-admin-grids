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

class BL_CustomGrid_Model_Grid_Type_Tax_Rule extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/tax_rule_grid');
    }
    
    /**
     * Return the currently edited value from the given tax rule, according to the given edit config
     * 
     * @param string $blockType Grid block type
     * @param BL_CustomGrid_Object $config Edit config
     * @param array $params Edit parameters
     * @param Mage_Tax_Model_Calculation_Rule $entity Edited tax rule
     * @return mixed
     */
    public function getTaxRuleValue($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        $value = null;
        
        switch ($config->getValueId()) {
            case 'customer_tax_classes':
                $value = $entity->getCustomerTaxClasses();
                break;
            case 'product_tax_classes':
                $value = $entity->getProductTaxClasses();
                break;
            case 'tax_rates':
                $value = $entity->getRates();
                break;
            case 'priority':
                $value = (int) $entity->getPriority();
                break;
            case 'position':
                $value = (int) $entity->getPosition();
                break;
        }
        
        return $value;
    }
    
    protected function _getBaseEditableFields($blockType)
    {
        /** @var $helper Mage_Tax_Helper_Data */
        $helper = Mage::helper('tax');
        /** @var $taxClassModel Mage_Tax_Model_Class */
        $taxClassModel = Mage::getModel('tax/class');
        /** @var $taxRateModel Mage_Tax_Model_Calculation_Rate */
        $taxRateModel  = Mage::getModel('tax/calculation_rate');
        
        $productClasses = $taxClassModel->getCollection()
            ->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT)
            ->toOptionArray();
        
        $customerClasses = $taxClassModel->getCollection()
            ->setClassTypeFilter(Mage_Tax_Model_Class::TAX_CLASS_TYPE_CUSTOMER)
            ->toOptionArray();
        
        $rates = $taxRateModel->getCollection()->toOptionArray();
        
        $fields = array(
            'code' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'required-entry',
            ),
            'customer_tax_classes' => array(
                'type'        => 'multiselect',
                'field_name'  => 'tax_customer_class',
                'required'    => true,
                'form_class'  => 'required-entry',
                'form_values' => $customerClasses,
                'entity_value_callback' => array($this, 'getTaxRuleValue'),
            ),
            'product_tax_classes' => array(
                'type'        => 'multiselect',
                'field_name'  => 'tax_product_class',
                'required'    => true,
                'form_class'  => 'required-entry',
                'form_values' => $productClasses,
                'entity_value_callback' => array($this, 'getTaxRuleValue'),
            ),
            'tax_rates' => array(
                'type'        => 'multiselect',
                'field_name'  => 'tax_rate',
                'required'    => true,
                'form_class'  => 'required-entry',
                'form_values' => $rates,
                'entity_value_callback' => array($this, 'getTaxRuleValue'),
            ),
            'priority' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'validate-not-negative-number',
                'form_note'  => $helper->__('Tax rates at the same priority are added, others are compounded.'),
                'entity_value_callback' => array($this, 'getTaxRuleValue'),
            ),
            'position' => array(
                'type'       => 'text',
                'required'   => true,
                'form_class' => 'validate-not-negative-number',
                'entity_value_callback' => array($this, 'getTaxRuleValue'),
            ),
        );
        
        return $fields;
    }
    
    protected function _getEntityRowIdentifiersKeys($blockType)
    {
        return array('tax_calculation_rule_id');
    }
    
    protected function _loadEditedEntity($blockType, BL_CustomGrid_Object $config, array $params, $entityId)
    {
        /** @var $taxRule Mage_Tax_Model_Calculation_Rule */
        $taxRule = Mage::getModel('tax/calculation_rule');
        $taxRule->load($entityId);
        return $taxRule;
    }
    
    protected function _getLoadedEntityName($blockType, BL_CustomGrid_Object $config, array $params, $entity)
    {
        /** @var $entity Mage_Tax_Model_Calculation_Rule */
        return $entity->getCode();
    }
    
    protected function _getEditRequiredAclPermissions($blockType)
    {
        return 'sales/tax/rules';
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
