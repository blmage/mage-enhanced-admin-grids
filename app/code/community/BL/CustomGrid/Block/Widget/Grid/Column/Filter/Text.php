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
    
    public function getCondition()
    {
        // @todo add a mode where filter mode and negative flag would be implied by the given value ?
        // (eg: re-(test) for a negative regex filter on "test") - how to call it ?
        
        $columnBlock = $this->getColumn();
        $collection  = $columnBlock->getGrid()->getCollection();
        $filterMode  = $columnBlock->getFilterMode();
        $filterIndex = (($filterIndex = $columnBlock->getFilterIndex()) ? $filterIndex : $columnBlock->getIndex());
        $isNegative  = (bool) $columnBlock->getNegativeFilter();
        $value = $this->getValue();
        $condition = null;
        
        if ($filterMode == self::MODE_WITH_WITHOUT) {
            $condition = ($value ? array('neq' => '') : array(array('eq' => ''), array('isnull' => true)));
            
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
            
            // A condition is needed, so let's use one that does nothing
            $condition = array(array('null' => true), array('notnull' => true));
            
        } else {
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
            
            if ($filterMode == self::MODE_INSIDE_LIKE) {
                $searchedValue = '%' . $searchedValue . '%';
            }
            
            $condition = array(($isNegative ? 'nlike' : 'like') => $searchedValue);
        }
        
        return $condition;
    }
}