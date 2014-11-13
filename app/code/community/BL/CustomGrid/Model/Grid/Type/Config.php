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

class BL_CustomGrid_Model_Grid_Type_Config extends BL_CustomGrid_Model_Config_Abstract
{
    public function getConfigType()
    {
        return BL_CustomGrid_Model_Config_Manager::TYPE_GRID_TYPES;
    }
    
    public function getTypesInstances()
    {
        $types = array();
        
        foreach ($this->getElementsCodes() as $code) {
            if ($instance = $this->getElementInstanceByCode($code)) {
                $types[$code] = $instance;
            }
        }
        
        return $types;
    }
    
    public function getTypeInstanceByCode($typeCode)
    {
        return parent::getElementInstanceByCode($typeCode);
    }
    
    public function getTypesAsOptionHash($sorted = false, $withEmpty = false)
    {
        $types = array();
        
        foreach ($this->getElementsArray() as $type) {
            $types[$type->getCode()] = $type->getName();
        }
        if ($sorted) {
            uasort($types, 'strcmp');
        }
        if ($withEmpty) {
            $types = array('' => Mage::helper('customgrid')->__('None')) + $types;
        }
        
        return $types;
    }
    
    protected function _loadXmlElementCustomColumns(Varien_Simplexml_Element $xmlElement)
    {
        $customColumnModels = array();
        
        if ($columnsXmlElement = $xmlElement->descend('custom_columns')) {
            foreach ($columnsXmlElement->children() as $customColumnId => $columnXmlElement) {
                $columnXmlValues = $columnXmlElement->asArray();
                
                if (!isset($columnXmlValues['@']) || !isset($columnXmlValues['@']['model'])) {
                    continue;
                }
                
                $customColumnModels[$customColumnId] = Mage::getModel(
                    $columnXmlValues['@']['model'],
                    array(
                        'id' => $customColumnId,
                        'xml_element' => $columnXmlElement,
                        'xml_values'  => $columnXmlValues,
                    )
                );
            }
        }
        
        return $customColumnModels;
    }
    
    public function getCustomColumnsByTypeCode($typeCode)
    {
        return ($xmlElement = $this->getXmlElementByCode($typeCode))
            ? $this->_loadXmlElementCustomColumns($xmlElement)
            : array();
    }
}
