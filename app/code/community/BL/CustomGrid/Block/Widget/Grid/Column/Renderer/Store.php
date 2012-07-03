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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Store
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    protected function _getStoreModel()
    {
        return Mage::getSingleton('adminhtml/system_store');
    }
    
    protected function _renderRow(Varien_Object $row, $space, $break)
    {
        $out = '';
        $origStores    = $row->getData($this->getColumn()->getIndex());
        $skipWebsite   = (bool) $this->getColumn()->getSkipWebsite();
        $skipStore     = (bool) $this->getColumn()->getSkipStore();
        $skipStoreView = (bool) $this->getColumn()->getSkipStoreView();
        $skipAllViews  = (bool) $this->getColumn()->getSkipAllViews();
        $flatValueKey  = ($flatValueKey = $this->getColumn()->getFlatValueKey() ? $flatValueKey : 'store_name');
        
        if (is_null($origStores) && ($flatValue = $row->getData($flatValueKey))) {
            $scopes = array();
            
            foreach (explode("\n", $flatValue) as $k => $label) {
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
            
            $i = 0;
            foreach ($scopes as $scope) {
                $out .= str_repeat($space, 3*$i++) . $scope . $break;
            }
            
            $out .= Mage::helper('adminhtml')->__(' [deleted]');
            return $out;
        }
        
        if (!is_array($origStores)) {
            $origStores = array($origStores);
        }
        if (empty($origStores)) {
            return '';
        } elseif (in_array(0, $origStores) && (count($origStores) == 1) && !$skipAllViews) {
            $out .= Mage::helper('adminhtml')->__('All Store Views');
        }
        
        $data = $this->_getStoreModel()->getStoresStructure(false, $origStores);
        
        foreach ($data as $website) {
            $i = 0;
            
            if (!$skipWebsite) {
                $out .= $website['label'] . $break;
                $i = 1;
            }
            
            foreach ($website['children'] as $group) {
                if (!$skipStore) {
                    $out .= str_repeat($space, 3*$i) . $group['label'] . $break;
                    $j = $i+1;
                } else {
                    $j = $i;
                }
                if (!$skipStoreView) {
                    foreach ($group['children'] as $store) {
                        $out .= str_repeat($space, 3*$j) . $store['label'] . $break;
                    }
                }
            }
        }
        
        return $out;
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