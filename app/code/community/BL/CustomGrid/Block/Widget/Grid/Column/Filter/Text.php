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

class BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Filter_Text
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
    
    public function getCondition()
    {
        $columnBlock = $this->getColumn();
        $collection  = $this->helper('customgrid/grid')->getColumnBlockCollection($columnBlock);
        $condition   = null;
        $value = $this->getValue();
        
        $filterModeShortcut = (bool) $columnBlock->getFilterModeShortcut();
        $negativeFilterShortcut = (bool) $columnBlock->getNegativeFilterShortcut();
        $filterIndex = $this->helper('customgrid/grid')->getColumnBlockFilterIndex($columnBlock);
        $filterMode  = $columnBlock->getFilterMode();
        $isNegative  = (!$negativeFilterShortcut && $columnBlock->getNegativeFilter());
        
        if ($filterMode == self::MODE_WITH_WITHOUT) {
            $filterModeShortcut = false;
            $negativeFilterShortcut = false;
        }
        
        if ($filterModeShortcut || $negativeFilterShortcut) {
            $modesCodesRegex = implode('|', array_keys(self::$_modesShortcuts)); 
            
            if (preg_match('#^\\[(!)?(' . $modesCodesRegex . ')?\\](.*)$#', $value, $matches)) {
                $value = $matches[3];
                
                if ($negativeFilterShortcut) {
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
        }
        
        if ($filterMode == self::MODE_WITH_WITHOUT) {
            $condition = ($value ? array('neq' => '') : array(array('eq' => ''), array('null' => true)));
            
        } elseif ($filterMode == self::MODE_REGEX) {
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
            
            $condition = $this->helper('customgrid/collection')->getIdentityCondition();
            
        } else {
            $stringHelper   = $this->helper('core/string');
            $valueLength    = $stringHelper->strlen($value);
            $searchedValue  = '';
            $singleWildcard = $columnBlock->getSingleWildcard();
            $multipleWildcard = $columnBlock->getMultipleWildcard();
            
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
            
            if ($filterMode == self::MODE_INSIDE_LIKE) {
                $searchedValue = '%' . $searchedValue . '%';
            }
            
            $condition = array(($isNegative ? 'nlike' : 'like') => $searchedValue);
        }
        
        return $condition;
    }
}