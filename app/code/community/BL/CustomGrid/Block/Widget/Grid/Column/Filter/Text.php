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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Text
{
    const MODE_WITH_WITHOUT = 'with_without';
    const MODE_EXACT_LIKE   = 'exact_like';
    const MODE_INSIDE_LIKE  = 'inside_like';
    const MODE_REGEX = 'regex';
    
    /**
     * Shortcuts for filter modes
     * 
     * @var array
     */
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
    
    /**
     * Search for filter shortcuts in the given value, adapt it so that it can directly be used for filtering,
     * and return the applicable filter mode and negative filter flag
     * 
     * @param string $value Filter value
     * @param string $filterMode Default filter mode
     * @param bool $filterModeShortcut Whether shortcuts for filter modes are enabled
     * @param bool $isNegative Default negative filter flag
     * @param bool $negativeShortcut Whether a shortcut for negative filter flag is enabled
     * @return array Filter mode + negative filter flag
     */
    public function parseFilterShorcutsForValue(
        &$value,
        $filterMode,
        $filterModeShortcut,
        $isNegative,
        $negativeShortcut
    ) {
        $modesCodesRegex = implode('|', array_keys(self::$_modesShortcuts)); 
        
        if (preg_match('#^\\[(!)?(' . $modesCodesRegex . ')?\\](.*)$#', $value, $matches)) {
            $value = $matches[3];
            /** @var $session BL_CustomGrid_Model_Session */
            $session = Mage::getSingleton('customgrid/session');
            
            if ($negativeShortcut) {
                $isNegative = !empty($matches[1]);
            } elseif (!empty($matches[1])) {
                $session->addNotice($this->__('Negative filter shortcut is not enabled (used in "%s")', $matches[0]));
            }
            if ($filterModeShortcut) {
                if (!empty($matches[2])) {
                    $filterMode = self::$_modesShortcuts[$matches[2]];
                }
            } elseif (!empty($matches[2])) {
                $session->addNotice($this->__('Filter mode shortcut is not enabled (used in "%s")', $matches[0]));
            }
        }
        
        return array($filterMode, $isNegative);
    }
    
    /**
     * Return the collection condition(s) usable to filter on whether the corresponding field has a value or not,
     * depending on the given flag value
     * 
     * @param bool $value Flag value (true if the field must have a value, false otherwise)
     * @return array
     */
    public function getBooleanCondition($value)
    {
        return ($value ? array('neq' => '') : array(array('eq' => ''), array('null' => true)));
    }
    
    /**
     * Return the collection condition(s) usable to filter on the given value with the LIKE function
     * 
     * @param string $value Filter value
     * @param string $filterMode Filter mode
     * @param bool $isNegative Whether negative filter is enabled
     * @return array
     */
    public function getLikeCondition($value, $filterMode, $isNegative)
    {
        /** @var $stringHelper Mage_Core_Helper_String */
        $stringHelper  = $this->helper('core/string');
        $valueLength   = $stringHelper->strlen($value);
        $searchedValue = '';
        $singleWildcard   = $this->getColumn()->getSingleWildcard();
        $multipleWildcard = $this->getColumn()->getMultipleWildcard();
        
        $charsMap = array(
            '%'  => '\\%',
            '_'  => '\\_',
            $singleWildcard   => '_',
            $multipleWildcard => '%',
            '\\' => '\\\\',
        );
        
        for ($i=0; $i<$valueLength; $i++) {
            $char = $stringHelper->substr($value, $i, 1);
            
            if (isset($charsMap[$char])) {
                $searchedValue .= $charsMap[$char];
            } else {
                $searchedValue .= $char;
            }
        }
        
        if (($filterMode == self::MODE_INSIDE_LIKE) && ($searchedValue !== '')) {
            $searchedValue = '%' . $searchedValue . '%';
        }
        
        return array(($isNegative ? 'nlike' : 'like') => $searchedValue);
    }
    
    /**
     * Directly apply on the collection the necessary conditions to filter on the given value with the REGEX function,
     * and return a valid (but "useless") collection condition
     * 
     * @param string $value Filter value
     * @param Varien_Data_Collection_Db $collection Grid collection
     * @param string $filterIndex Filter index
     * @param bool $isNegative Whether negative filter is enabled
     * @return array
     */
    public function getRegexCondition($value, Varien_Data_Collection_Db $collection, $filterIndex, $isNegative)
    {
        /** @var $collectionHelper BL_CustomGrid_Helper_Collection */
        $collectionHelper = $this->helper('customgrid/collection');
        /** @var $session BL_CustomGrid_Model_Session */
        $session = Mage::getSingleton('customgrid/session');
        
        try {
            $collectionHelper->addRegexFilterToCollection($collection, $filterIndex, $value, $isNegative);
        } catch (Mage_Core_Exception $e) {
            $session->addError($this->__('Could not apply the regex filter : "%s"', $e->getMessage()));
        } catch (Exception $e) {
            Mage::logException($e);
            $session->addError($this->__('Could not apply the regex filter'));
        }
        
        return $collectionHelper->getIdentityCondition();
    }
    
    public function getCondition()
    {
        $columnBlock = $this->getColumn();
        /** @var $gridHelper BL_CustomGrid_Helper_Grid */
        $gridHelper  = $this->helper('customgrid/grid');
        $collection  = $gridHelper->getColumnBlockGridCollection($columnBlock);
        $value = $this->getValue();
        
        $filterModeShortcut = (bool) $columnBlock->getFilterModeShortcut();
        $negativeShortcut   = (bool) $columnBlock->getNegativeFilterShortcut();
        $filterIndex = $gridHelper->getColumnBlockFilterIndex($columnBlock);
        $filterMode  = $columnBlock->getFilterMode();
        $isNegative  = (!$negativeShortcut && $columnBlock->getNegativeFilter());
        
        if ($filterMode != self::MODE_WITH_WITHOUT) {
            list($filterMode, $isNegative) = $this->parseFilterShorcutsForValue(
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
            $condition = $this->getLikeCondition($value, $filterMode, $isNegative);
        }
        
        return $condition;
    }
}
