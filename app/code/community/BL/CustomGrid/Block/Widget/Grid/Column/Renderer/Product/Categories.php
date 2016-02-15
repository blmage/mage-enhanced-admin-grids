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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Product_Categories extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Text
{
    protected function _getCategoryTreeResult(
        $tree,
        array $categoryIds,
        $displayIds,
        $minimumLevel
    ) {
        $result = array();
        
        foreach ($categoryIds as $categoryId) {
            $subResult = array();
            
            if ($node = $tree->getNodeById($categoryId)) {
                $subResult[] = ($displayIds ? $categoryId : $node->getName());
                
                while (($node = $node->getParent()) && ($node->getLevel() >= $minimumLevel)) {
                    $subResult[] = ($displayIds ? $node->getId() : $node->getName());
                }
                
                $result[] = array_reverse($subResult);
            }
        }
        
        return $result;
    }
    
    protected function _getCategoryHashResult(array $hash, array $categoryIds)
    {
        $result = array();
        
        foreach ($categoryIds as $categoryId) {
            if (isset($hash[$categoryId])) {
                $result[] = array($hash[$categoryId]->getName());
            }
        }
        
        return $result;
    }
    
    protected function _getRowResult(Varien_Object $row)
    {
        $result = array();
        $columnBlock  = $this->getColumn();
        $displayIds   = (bool) $columnBlock->getDisplayIds();
        $categoryIds  = explode(',', $row->getData($columnBlock->getIndex()));
        $minimumLevel = intval($columnBlock->getAscentLimit());
        
        if (empty($categoryIds)) {
            return $result;
        }
        
        if ($tree = $columnBlock->getCategoryTree()) {
            $result = $this->_getCategoryTreeResult($tree, $categoryIds, $displayIds, $minimumLevel);
        } elseif ($hash = $columnBlock->getCategoryHash()) {
            $result = $this->_getCategoryHashResult($hash, $categoryIds);
        } else {
            $result = array_map(create_function('$v', 'return array($v);'), $categoryIds);
        }
        
        return $result;
    }
    
    protected function _renderRow(Varien_Object $row, $levelSeparator, $resultSeparator)
    {
        $result = $this->_getRowResult($row);
        array_walk($result, create_function('&$v, $k, $s', '$v = implode($v, $s);'), $levelSeparator);
        return implode($resultSeparator, $result);
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
