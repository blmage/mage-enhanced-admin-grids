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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Select extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Abstract
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
    
    /**
     * Return the available filter options
     * 
     * @return array
     */
    protected function _getOptions()
    {
        if ($this->getColumn()->getFilterMode() == self::MODE_WITH_WITHOUT) {
            $options = array(
                array(
                    'value' => self::BOOLEAN_FILTER_VALUE_WITH,
                    'label' => $this->__('With'),
                ),
                array(
                    'value' => self::BOOLEAN_FILTER_VALUE_WITHOUT,
                    'label' => $this->__('Without'),
                ),
            );
        } else {
            $options = $this->getColumn()->getOptions();
        }
        return (is_array($options) ? $options : array());
    }
    
    /**
     * Return whether the given option value is currently selected
     * 
     * @param mixed $value Option value to check
     * @param mixed $selectedValue Currently selected value(s)
     * @return bool
     */
    protected function _isValueSelected($value, $selectedValue)
    {
        return is_array($selectedValue)
            ? in_array($value, $selectedValue)
            : (!is_null($selectedValue) && ($value == $selectedValue));
    }
    
    /**
     * Return the HTML content for the given option or group of options
     * 
     * @param array $option Option or group of options
     * @param mixed $selectedValue Currently selected value(s)
     * @param bool $removeEmpty Whether the empty options should be ignored
     * @return string
     */
    protected function _getOptionHtml($option, $selectedValue, $removeEmpty = true)
    {
        $html = '';
        
        if (is_array($option['value'])) {
            $html = '<optgroup label="' . $this->htmlEscape($option['label']) . '">';
            
            foreach ($option['value'] as $subOption) {
                $html .= $this->_getOptionHtml($subOption, $selectedValue);
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
        $multiple = ($this->getColumn()->getFilterMode() == self::MODE_MULTIPLE ? ' multiple="multiple" ' : ' ');
        $htmlName = $this->_getHtmlName();
        $htmlId   = $this->_getHtmlId();
        $html     = '<select name="' . $htmlName . '" id="' . $htmlId . '"' . $multiple . 'class="no-changes">';
        
        if (!empty($options)) {
            if (is_null($emptyLabel = $this->getColumn()->getEmptyOptionLabel())) {
                $emptyLabel = '';
            }
            
            $html .= $this->_getOptionHtml(
                array('value' => '', 'label' => $emptyLabel),
                $value,
                false
            );
            
            foreach ($this->_getOptions() as $option) {
                $html .= $this->_getOptionHtml($option, $value);
            }
        }
        
        $html .= '</select>';
        return $html;
    }
    
    /**
     * Return the collection condition(s) usable to filter on the given multiple selected values
     * If it is not possible to do otherwise, the condition(s) may directly be applied to the given collection,
     * but in all cases a valid collection condition will be returned
     * 
     * @param array $values Selected values
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string $filterIndex Column filter index
     * @param bool $isNegative Whether negative filter is enabled
     * @return array
     */
    public function getMultipleCondition(array $values, Varien_Data_Collection_Db $collection, $filterIndex, $isNegative)
    {
        $columnBlock = $this->getColumn();
        
        if ($columnBlock->getImplodedValues()) {
            /** @var $helper BL_CustomGrid_Helper_Collection */
            $helper  = $this->helper('customgrid/collection');
            /** @var $session BL_CustomGrid_Model_Session */
            $session = Mage::getSingleton('customgrid/session');
            
            try {
                $helper->addFindInSetFiltersToCollection(
                    $collection,
                    $filterIndex,
                    $values,
                    $columnBlock->getImplodedSeparator(),
                    $columnBlock->getLogicalOperator(),
                    $isNegative
                );
            } catch (Mage_Core_Exception $e) {
                $session->addError($this->__('Could not apply the multiple filter : "%s"', $e->getMessage()));
            } catch (Exception $e) {
                Mage::logException($e);
                $session->addError($this->__('Could not apply the multiple filter'));
            }
            
            $condition = $helper->getIdentityCondition();
        } elseif ($isNegative) {
            $lastValue = array_pop($values);
            $condition = array('neq' => $lastValue);
            
            foreach ($values as $subValue) {
                $collection->addFieldToFilter($filterIndex, array('neq' => $subValue));
            }
        } else {
            $condition = array();
            
            foreach ($values as $subValue) {
                $condition[] = array('eq' => $subValue);
            }
        }
        
        return $condition;
    }
    
    /**
     * Return the collection condition corresponding to the given selected boolean flag
     * 
     * @param string $value Selected boolean flag
     * @return array
     */
    public function getBooleanCondition($value)
    {
        return ($value == self::BOOLEAN_FILTER_VALUE_WITHOUT)
            ? array(array('null' => true), array('eq' => ''))
            : array('neq' => '');
    }
    
    /**
     *  Return the collection condition(s) usable to filter on the given selected value,
     * in case the corresponding field consists of imploded values
     * 
     * @param string $value Selected value
     * @param string $separator Separator used for the imploded values
     * @param bool $isNegative Whether the filter is on negative mode
     * @return array
     */
    protected function _getSingleImplodedSubConditions($value, $separator, $isNegative)
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
    
    /**
     * Return the collection condition(s) usable to filter on the given selected value
     * If it is not possible to do otherwise, the condition(s) may directly be applied to the given collection,
     * but in all cases a valid collection condition will be returned
     * 
     * @param string $value Selected value
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string $filterIndex Column filter index
     * @param bool $isNegative Whether negative filter is enabled
     * @return array
     */
    public function getSingleCondition($value, Varien_Data_Collection_Db $collection, $filterIndex, $isNegative)
    {
        $columnBlock = $this->getColumn();
        
        if ($columnBlock->getImplodedValues()) {
            $separator = $columnBlock->getImplodedSeparator();
            
            if (($separator == '%') || ($separator == '_')) {
                $separator = '\\' . $separator;
            }
            
            $conditions = $this->_getSingleImplodedSubConditions($value, $separator, $isNegative);
            
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
        /** @var $gridHelper BL_CustomGrid_Helper_Grid */
        $gridHelper  = $this->helper('customgrid/grid');
        $collection  = $gridHelper->getColumnBlockGridCollection($columnBlock);
        $filterIndex = $gridHelper->getColumnBlockFilterIndex($columnBlock);
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
