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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Text
{
    const MODE_WITH_WITHOUT = 'with_without';
    const MODE_EXACT_LIKE   = 'exact_like';
    const MODE_INSIDE_LIKE  = 'inside_like';
    const MODE_REGEX = 'regex';
    
    static protected $_modesShortcuts = array(
        'el' => self::MODE_EXACT_LIKE,
        'il' => self::MODE_INSIDE_LIKE,
        're' => self::MODE_REGEX,
    );
    
    public function getHtml()
    {
        if ($this->getColumn()->getFilterMode() == self::MODE_WITH_WITHOUT) {
            $value = (!is_null($value = $this->getValue()) ? (bool) $value : null);
            $withSelected = ($value === true ? ' selected="selected"' : '');
            $withoutSelected = ($value === false ? ' selected="selected"' : '');
            
            return '<select name="' . $this->_getHtmlName() . '" id="' . $this->_getHtmlId() . '" class="no-changes">'
                . '<option value=""></option>'
                . '<option value="1"' . $withSelected . '>' . $this->__('With') . '</option>'
                . '<option value="0"' . $withoutSelected . '>' . $this->__('Without') . '</option>'
                . '</select>';
        }
        return parent::getHtml();
    }
    
    public function applyFilterShortcutsToValue(
        &$value,
        $filterMode,
        $filterModeShortcut,
        $isNegative,
        $negativeShortcut
    ) {
        $modesCodesRegex = implode('|', array_keys(self::$_modesShortcuts)); 
        
        if (preg_match('#^\\[(!)?(' . $modesCodesRegex . ')?\\](.*)$#', $value, $matches)) {
            $value = $matches[3];
            
            if ($negativeShortcut) {
                $isNegative = !empty($matches[1]);
            } elseif (!empty($matches[1])) {
                Mage::getSingleton('customgrid/session')
                    ->addNotice($this->__('Negative filter shortcut is not enabled (used in "%s")', $matches[0]));
            }
            if ($filterModeShortcut) {
                if (!empty($matches[2])) {
                    $filterMode = self::$_modesShortcuts[$matches[2]];
                }
            } elseif (!empty($matches[2])) {
                Mage::getSingleton('customgrid/session')
                    ->addNotice($this->__('Filter mode shortcut is not enabled (used in "%s")', $matches[0]));
            }
        }
        
        return array($filterMode, $isNegative);
    }
    
    public function getBooleanCondition($value)
    {
        return ($value ? array('neq' => '') : array(array('eq' => ''), array('null' => true)));
    }
    
    public function getRegexCondition($value, Varien_Data_Collection_Db $collection, $filterIndex, $isNegative)
    {
        try {
            $this->helper('customgrid/collection')
                ->addRegexFilterToCollection($collection, $filterIndex, $value, $isNegative);
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('customgrid/session')
                ->addError($this->__('Could not apply the regex filter : "%s"', $e->getMessage()));
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('customgrid/session')->addError($this->__('Could not apply the regex filter'));
        }
        return $this->helper('customgrid/collection')->getIdentityCondition();
    }
    
    public function getLikeCondition($value, Varien_Data_Collection_Db $collection, $filterMode, $isNegative)
    {
        $stringHelper   = $this->helper('core/string');
        $valueLength    = $stringHelper->strlen($value);
        $searchedValue  = '';
        $singleWildcard = $this->getColumn()->getSingleWildcard();
        $multipleWildcard = $this->getColumn()->getMultipleWildcard();
        
        for ($i=0; $i<$valueLength; $i++) {
            $char = $stringHelper->substr($value, $i, 1);
            
            if ($char === $singleWildcard) {
                $searchedValue .= '_';
            } elseif ($char === $multipleWildcard) {
                $searchedValue .= '%';
            } elseif (($char === '%') || ($char === '_')) {
                $searchedValue .= '\\' . $char;
            } elseif ($char == '\\') {
                $searchedValue .= '\\\\';
            } else {
                $searchedValue .= $char;
            }
        }
        
        if (($filterMode == self::MODE_INSIDE_LIKE) && ($searchedValue !== '')) {
            $searchedValue = '%' . $searchedValue . '%';
        }
        
        return array(($isNegative ? 'nlike' : 'like') => $searchedValue);
    }
    
    public function getCondition()
    {
        $columnBlock = $this->getColumn();
        $collection  = $this->helper('customgrid/grid')->getColumnBlockGridCollection($columnBlock);
        $value = $this->getValue();
        
        $filterModeShortcut = (bool) $columnBlock->getFilterModeShortcut();
        $negativeShortcut   = (bool) $columnBlock->getNegativeFilterShortcut();
        $filterIndex = $this->helper('customgrid/grid')->getColumnBlockFilterIndex($columnBlock);
        $filterMode  = $columnBlock->getFilterMode();
        $isNegative  = (!$negativeShortcut && $columnBlock->getNegativeFilter());
        
        if ($filterMode != self::MODE_WITH_WITHOUT) {
            list($filterMode, $isNegative) = $this->applyFilterShortcutsToValue(
                $value,
                $filterMode,
                $filterModeShortcut,
                $isNegative,
                $negativeShortcut
            );
        }
        
        if ($filterMode == self::MODE_WITH_WITHOUT) {
            $condition = $this->getBooleanCondition($value);
        } elseif ($filterMode == self::MODE_REGEX) {
            $condition = $this->getRegexCondition($value, $collection, $filterIndex, $isNegative);
        } else {
            $condition = $this->getLikeCondition($value, $collection, $filterMode, $isNegative);
        }
        
        return $condition;
    }
}
