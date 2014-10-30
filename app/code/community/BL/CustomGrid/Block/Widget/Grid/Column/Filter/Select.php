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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
{
    const MODE_SINGLE = 'single';
    const MODE_MULTIPLE = 'multiple';
    const MODE_WITH_WITHOUT = 'with_without';
    
    const BOOLEAN_FILTER_VALUE_WITH = 'with';
    const BOOLEAN_FILTER_VALUE_WITHOUT = 'without';
    
    public function getValue()
    {
        $value = parent::getValue();
        
        if ($this->getColumn()->getFilterMode() == self::MODE_MULTIPLE) {
            if (!is_array($value) && !is_null($value) && ($value !== '')) {
                // The multiple select has likely been imploded by Prototype
                $value = explode(',', $value);
            }
        }
        
        return $value;
    }
    
    protected function _getOptions()
    {
        if ($this->getColumn()->getFilterMode() == self::MODE_WITH_WITHOUT) {
            $options = array(
                array(
                    'value' => self::BOOLEAN_FILTER_VALUE_WITH,
                    'label' => $this->helper('customgrid')->__('With'),
                ),
                array(
                    'value' => self::BOOLEAN_FILTER_VALUE_WITHOUT,
                    'label' => $this->helper('customgrid')->__('Without'),
                ),
            );
        } else {
            $options = $this->getColumn()->getOptions();
        }
        return (is_array($options) ? $options : array());
    }
    
    protected function _isValueSelected($value, $selected)
    {
        return (!is_null($selected) && (is_array($selected) ? in_array($value, $selected) : ($value == $selected)));
    }
    
    protected function _renderOption($option, $selectedValue, $removeEmpty = true)
    {
        $html = '';
        
        if (is_array($option['value'])) {
            $html = '<optgroup label="' . $this->htmlEscape($option['label']) . '">';
            
            foreach ($option['value'] as $subOption) {
                $html .= $this->_renderOption($subOption, $selectedValue);
            }
            
            $html .= '</optgroup>';
        } else {
            if (!$removeEmpty || (!is_null($option['value']) && ($option['value'] !== ''))) {
                $html = '<option value="' . $this->htmlEscape($option['value']) . '"' 
                    . ($this->_isValueSelected($option['value'], $selectedValue) ? ' selected="selected"' : '' ) . '>'
                    . $this->htmlEscape($option['label'])
                    . '</option>';
            }
        }
        
        return $html;
    }
    
    public function getHtml()
    {
        $options  = $this->_getOptions();
        $value    = $this->getValue();
        $multiple = ($this->getColumn()->getFilterMode() == self::MODE_MULTIPLE ? ' multiple="multiple" ' : '');
        $htmlName = $this->_getHtmlName();
        $htmlId   = $this->_getHtmlId();
        $html     = '<select name="' . $htmlName . '" id="' . $htmlId . '"' . $multiple . 'class="no-changes">';
        
        if (!empty($options)) {
            if (is_null($emptyLabel = $this->getColumn()->getEmptyOptionLabel())) {
                $emptyLabel = '';
            }
            
            $html .= $this->_renderOption(
                array('value' => '', 'label' => $emptyLabel),
                $value,
                false
            );
            
            foreach ($this->_getOptions() as $option) {
                $html .= $this->_renderOption($option, $value);
            }
        }
        
        $html .= '</select>';
        return $html;
    }
    
    public function getMultipleCondition($value, Varien_Data_Collection_Db $collection, $filterIndex, $isNegative)
    {
        $columnBlock = $this->getColumn();
        
        if ($columnBlock->getImplodedValues()) {
            try {
                $this->helper('customgrid/collection')->addFindInSetFiltersToCollection(
                    $collection,
                    $filterIndex,
                    $value,
                    $columnBlock->getImplodedSeparator(),
                    $columnBlock->getLogicalOperator(),
                    $isNegative
                );
            } catch (Mage_Core_Exception $e) {
                Mage::getSingleton('customgrid/session')
                    ->addError($this->__('Could not apply the multiple filter : "%s"', $e->getMessage()));
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('customgrid/session')
                    ->addError($this->__('Could not apply the multiple filter'));
            }
            
            $condition = $this->helper('customgrid/collection')->getIdentityCondition();
        } elseif ($isNegative) {
            $lastValue = array_pop($value);
            $condition = array('neq' => $lastValue);
            
            foreach ($value as $subValue) {
                $collection->addFieldToFilter($filterIndex, array('neq' => $subValue));
            }
        } else {
            $condition = array();
            
            foreach ($value as $subValue) {
                $condition[] = array('eq' => $subValue);
            }
        }
        
        return $condition;
    }
    
    public function getBooleanCondition($value)
    {
        return ($value == self::BOOLEAN_FILTER_VALUE_WITHOUT)
            ? array(array('null' => true), array('eq' => ''))
            : array('neq' => '');
    }
    
    protected function _getSingleSubConditions($value, $separator, $isNegative)
    {
        $eqCode   = ($isNegative ? 'neq' : 'eq');
        $likeCode = ($isNegative ? 'nlike' : 'like');
        
        return array(
            array($eqCode   => $value),
            array($likeCode => $value . $separator . '%'),
            array($likeCode => '%' . $separator . $value . $separator . '%'),
            array($likeCode => '%' . $separator . $value)
        );
    }
    
    public function getSingleCondition($value, Varien_Data_Collection_Db $collection, $filterIndex, $isNegative)
    {
        $columnBlock = $this->getColumn();
        
        if ($columnBlock->getImplodedValues()) {
            $separator = $columnBlock->getImplodedSeparator();
            
            if (($separator == '%') || ($separator == '_')) {
                $separator = '\\' . $separator;
            }
            
            $conditions = $this->_getSingleSubConditions($value, $separator, $isNegative);
            
            if ($isNegative) {
                $condition = array_pop($conditions);
                
                foreach ($conditions as $subCondition) {
                    $collection->addFieldToFilter($filterIndex, $subCondition);
                }
            } else {
                $condition = $conditions;
            }
        } else {
            $condition = array(($isNegative ? 'neq' : 'eq') => $value);
        }
        
        return $condition;
    }
    
    public function getCondition()
    {
        $columnBlock = $this->getColumn();
        $collection  = $this->helper('customgrid/grid')->getColumnBlockGridCollection($columnBlock);
        $filterIndex = $this->helper('customgrid/grid')->getColumnBlockFilterIndex($columnBlock);
        $filterMode  = $this->getColumn()->getFilterMode();
        $isNegative  = (bool) $this->getColumn()->getNegativeFilter();
        $value = $this->getValue();
        
        if ($filterMode == self::MODE_MULTIPLE) {
            $condition = $this->getMultipleCondition($value, $collection, $filterIndex, $isNegative);
        } elseif ($filterMode == self::MODE_WITH_WITHOUT) {
            $condition = $this->getBooleanCondition($value);
        } else {
            $condition = $this->getSingleCondition($value, $collection, $filterIndex, $isNegative);
        }
        
        return $condition;
    }
}
