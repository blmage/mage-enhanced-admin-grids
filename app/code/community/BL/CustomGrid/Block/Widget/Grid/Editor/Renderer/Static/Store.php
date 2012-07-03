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

class BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Static_Store
    extends BL_CustomGrid_Block_Widget_Grid_Editor_Renderer_Abstract
{
    protected function _getStoreModel()
    {
        return Mage::getSingleton('adminhtml/system_store');
    }
    
    protected function _getRenderedValue()
    {
        $editedConfig         = $this->getEditedConfig();
        $renderOptions        = $editedConfig['renderer'];
        $renderableValue      = $this->getRenderableValue();
        
        if (empty($renderableValue)
            && isset($renderOptions['without_empty_store'])
            && (bool)$renderOptions['without_empty_store']) {
            return '';
        }
        if (!is_array($renderableValue)) {
            $renderableValue = array($renderableValue);
        }
        if (empty($renderableValue)) {
            return '';
        } elseif (in_array(0, $renderableValue) && (count($renderableValue) == 1)) {
            if (isset($renderOptions['without_all_store'])
                && (bool)$renderOptions['without_all_store']) {
                return '';
            } else {
                return Mage::helper('adminhtml')->__('All Store Views');
            }
        }
        
        $data = $this->_getStoreModel()->getStoresStructure(false, $renderableValue);
        
        $renderedValue = '';
        $spacesCount   = (isset($renderOptions['spaces_count']) ? $renderOptions['spaces_count'] : 3);
        $spacesCount   = ($spacesCount > 0 ? $spacesCount : 3);
        
        foreach ($data as $website) {
            $renderedValue .= $website['label'] . '<br/>';
            foreach ($website['children'] as $group) {
                $renderedValue .= str_repeat('&nbsp;', $spacesCount) . $group['label'] . '<br/>';
                foreach ($group['children'] as $store) {
                    $renderedValue .= str_repeat('&nbsp;', 2*$spacesCount) . $store['label'] . '<br/>';
                }
            }
        }
        
        return $renderedValue;
    }
}