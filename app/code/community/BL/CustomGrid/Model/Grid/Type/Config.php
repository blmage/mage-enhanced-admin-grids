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

class BL_CustomGrid_Model_Grid_Type_Config extends BL_CustomGrid_Model_Config_Abstract
{
    public function getConfigType()
    {
        return BL_CustomGrid_Model_Config_Manager::TYPE_GRID_TYPES;
    }
    
    protected function _checkElementModelCompliance($model)
    {
        return ($model instanceof BL_CustomGrid_Model_Grid_Type_Abstract);
    }
    
    /**
     * Return the type model corresponding to the given code
     * 
     * @param string $code Grid type code
     * @return BL_CustomGrid_Model_Grid_Type_Abstract|null
     */
    public function getTypeModelByCode($code)
    {
        return parent::getElementModelByCode($code);
    }
    
    /**
     * Return all the available type models
     * 
     * @param bool $sorted Whether the types models should be sorted
     * @return BL_CustomGrid_Model_Grid_Type_Abstract[]
     */
    public function getTypesModels($sorted = false)
    {
        return parent::getElementsModels($sorted);
    }
    
    /**
     * Return the available types as an option hash
     * 
     * @param bool $sorted Whether the types should be sorted
     * @param bool $withEmpty Whether an empty option should be included
     * @return array
     */
    public function getTypesAsOptionHash($sorted = false, $withEmpty = false)
    {
        $optionHash = array();
        
        foreach ($this->getTypesModels() as $model) {
            $optionHash[$model->getCode()] = $model->getName();
        }
        if ($sorted) {
            asort($optionHash, SORT_LOCALE_STRING);
        }
        if ($withEmpty) {
            /** @var $helper BL_CustomGrid_Helper_Data */
            $helper = Mage::helper('customgrid');
            $optionHash = array('' => $helper->__('None')) + $optionHash;
        }
        
        return $optionHash;
    }
    
    /**
     * Load and return the custom columns models from the given XML element
     * 
     * @param Varien_Simplexml_Element $xmlElement XML element
     * @return array
     */
    protected function _loadXmlElementCustomColumns(Varien_Simplexml_Element $xmlElement)
    {
        $models = array();
        
        if ($columnsXmlElement = $xmlElement->descend('custom_columns')) {
            foreach ($columnsXmlElement->children() as $customColumnId => $columnXmlElement) {
                $columnXmlValues = $columnXmlElement->asArray();
                
                if (!isset($columnXmlValues['@']) || !isset($columnXmlValues['@']['model'])) {
                    continue;
                }
                
                $model = Mage::getModel(
                    $columnXmlValues['@']['model'],
                    array(
                        'id' => $customColumnId,
                        'xml_element' => $columnXmlElement,
                        'xml_values'  => $columnXmlValues,
                    )
                );
                
                if ($model instanceof BL_CustomGrid_Model_Custom_Column_Abstract) {
                    $models[$customColumnId] = $model;
                }
            }
        }
        
        return $models;
    }
    
    /**
     * Return the custom columns models corresponding to the given grid type code
     * 
     * @param string $typeCode Grid type code
     * @return array
     */
    public function getCustomColumnsByTypeCode($typeCode)
    {
        return ($xmlElement = $this->getXmlElementByCode($typeCode))
            ? $this->_loadXmlElementCustomColumns($xmlElement)
            : array();
    }
}
