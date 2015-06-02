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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Static_Store extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    /**
     * Return the system store model
     * 
     * @return Mage_Adminhtml_Model_System_Store
     */
    protected function _getStoreModel()
    {
        return Mage::getSingleton('adminhtml/system_store');
    }
    
    /**
     * Render the given stores structure
     * 
     * @param array $storesStructure Stores structure
     * @param int $spacesCount Base number of spaces to prepend on sub-levels labels
     * @return string
     */
    protected function _renderStoresStructure(array $storesStructure, $spacesCount)
    {
        $renderedStructure = '';
        
        foreach ($storesStructure as $website) {
            $renderedStructure .= $website['label'] . '<br/>';
            
            foreach ($website['children'] as $group) {
                $renderedStructure .= str_repeat('&nbsp;', $spacesCount) . $group['label'] . '<br/>';
                
                foreach ($group['children'] as $store) {
                    $renderedStructure .= str_repeat('&nbsp;', 2*$spacesCount) . $store['label'] . '<br/>';
                }
            }
        }
        
        return $renderedStructure;
    }
    
    protected function _getRenderedValue()
    {
        $editConfig = $this->getEditConfig();
        $renderableValue = $this->getRenderableValue();
        $withoutAllStore = (bool) $editConfig->getData('renderer/without_all_store');
        $withoutEmptyStore = (bool) $editConfig->getData('renderer/without_empty_store');
        
        if (empty($renderableValue) && (is_array($renderableValue) || $withoutEmptyStore)) { 
            return '';
        }
        
        $renderableValue = (array) $renderableValue;
        
        if (in_array(0, $renderableValue) && (count($renderableValue) == 1)) {
            if ($withoutAllStore) {
                return '';
            } else {
                return $this->__('All Store Views');
            }
        }
        
        $storesStructure = $this->_getStoreModel()->getStoresStructure(false, $renderableValue);
        $spacesCount = (int) $editConfig->getDataSetDefault('renderer/spaces_count', 3);
        return $this->_renderStoresStructure($storesStructure, $spacesCount);
    }
}
