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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Product_Categories
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    protected function _getRowResult(Varien_Object $row)
    {
        $displayIds = (bool) $this->getColumn()->getDisplayIds();
        $ids        = explode(',', $row->getData($this->getColumn()->getIndex()));
        $minLevel   = intval($this->getColumn()->getAscentLimit());
        $result     = array();
        
        if (empty($ids)) {
            return $result;
        }
        
        if ($tree = $this->getColumn()->getCategoryTree()) {
            foreach ($ids as $categoryId) {
                $subResult = array();
                
                if ($node = $tree->getNodeById($categoryId)) {
                    $subResult[] = ($displayIds ? $categoryId : $node->getName());
                    
                    while (($node = $node->getParent()) && ($node->getLevel() >= $minLevel)) {
                        $subResult[] = ($displayIds ? $node->getId() : $node->getName());
                    }
                    
                    $result[] = array_reverse($subResult);
                }
            }
        } elseif ($hash = $this->getColumn()->getCategoryHash()) {
            foreach ($ids as $categoryId) {
                if (isset($hash[$categoryId])) {
                    $result[] = array($hash[$categoryId]->getName());
                }
            }
        } else {
            $result = array_map(create_function('$v', 'return array($v);'), $ids);
        }
        
        return $result;
    }
    
    protected function _renderRow($row, $levelSep, $resultSep)
    {
        $result = $this->_getRowResult($row);
        array_walk($result, create_function('&$v, $k, $s', '$v = implode($v, $s);'), $levelSep);
        return implode($resultSep, $result);
    }
    
    public function render(Varien_Object $row)
    {
        return $this->_renderRow(
            $row,
            $this->htmlEscape($this->getColumn()->getLevelSeparator()),
            $this->htmlEscape($this->getColumn()->getResultSeparator())
        );
    }
    
    public function renderExport(Varien_Object $row)
    {
        return $this->_renderRow(
            $row,
            $this->getColumn()->getLevelSeparator(),
            $this->getColumn()->getResultSeparator()
        );
    }
}