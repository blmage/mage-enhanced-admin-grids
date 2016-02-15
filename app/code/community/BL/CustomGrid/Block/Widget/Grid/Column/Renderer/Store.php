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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Store extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected function _getStoreModel()
    {
        return Mage::getSingleton('adminhtml/system_store');
    }
    
    protected function _renderFlatValue($flatValue, $space, $break, $skipWebsite, $skipStore, $skipStoreView)
    {
        $result = '';
        $scopes = array();
        
        foreach (explode("\n", $flatValue) as $label) {
            $scopes[] = $label;
        }
        
        if (count($scopes) == 3) {
            // Assume those are website / store / store view values
            if ($skipWebsite) {
                unset($scopes[0]);
            }
            if ($skipStore) {
                unset($scopes[1]);
            }
            if ($skipStoreView) {
                unset($scopes[2]);
            }
        }
        
        $scopeIndex = 0;
        
        foreach ($scopes as $scope) {
            $result .= str_repeat($space, 3*$scopeIndex++) . $scope . $break;
        }
        
        $result .= $this->helper('adminhtml')->__(' [deleted]');
        return $result;
    }
    
    protected function _renderStoresStructure(
        array $storesStructure,
        $space,
        $break,
        $skipWebsite,
        $skipStore,
        $skipStoreView
    ) {
        $result = '';
        
        foreach ($storesStructure as $website) {
            $i = 0;
            
            if (!$skipWebsite) {
                $result .= $website['label'] . $break;
                $i = 1;
            }
            
            foreach ($website['children'] as $group) {
                if (!$skipStore) {
                    $result .= str_repeat($space, 3*$i) . $group['label'] . $break;
                    $j = $i+1;
                } else {
                    $j = $i;
                }
                if (!$skipStoreView) {
                    foreach ($group['children'] as $store) {
                        $result .= str_repeat($space, 3*$j) . $store['label'] . $break;
                    }
                }
            }
        }
        
        return $result;
    }
    
    protected function _renderRow(Varien_Object $row, $space, $break)
    {
        $result = '';
        $originalStores = $row->getData($this->getColumn()->getIndex());
        $skipWebsite    = (bool) $this->getColumn()->getSkipWebsite();
        $skipStore      = (bool) $this->getColumn()->getSkipStore();
        $skipStoreView  = (bool) $this->getColumn()->getSkipStoreView();
        $skipAllViews   = (bool) $this->getColumn()->getSkipAllViews();
        $flatValueKey   = (($flatValueKey = $this->getColumn()->getFlatValueKey()) ? $flatValueKey : 'store_name');
        
        if (is_null($originalStores) && ($flatValue = $row->getData($flatValueKey))) {
            return $this->_renderFlatValue($flatValue, $space, $break, $skipWebsite, $skipStore, $skipStoreView);
        } elseif (!is_array($originalStores)) {
            $originalStores = array($originalStores);
        }
        if (empty($originalStores)) {
            return '';
        } elseif (in_array(0, $originalStores) && (count($originalStores) == 1) && !$skipAllViews) {
            $result .= $this->helper('adminhtml')->__('All Store Views');
        }
        
        $result .= $this->_renderStoresStructure(
            $this->_getStoreModel()->getStoresStructure(false, $originalStores),
            $space,
            $break,
            $skipWebsite,
            $skipStore,
            $skipStoreView
        );
        
        return $result;
    }
    
    public function render(Varien_Object $row)
    {
        return $this->_renderRow($row, '&nbsp;', '<br />');
    }
    
    public function renderExport(Varien_Object $row)
    {
        return $this->_renderRow($row, ' ', "\r\n");
    }
}
