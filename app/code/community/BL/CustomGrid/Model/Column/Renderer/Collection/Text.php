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

class BL_CustomGrid_Model_Column_Renderer_Collection_Text
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnGridValues($index, $store, $grid)
    {
        $values = array(
            'filter'          => 'customgrid/widget_grid_column_filter_text',
            'renderer'        => 'customgrid/widget_grid_column_renderer_text',
            'exact_filter'    => (bool) $this->_getData('exact_filter'),
            'truncate'        => $this->_getData('truncate'),
            'truncate_at'     => intval($this->_getData('truncate_at')),
            'truncate_ending' => $this->_getData('truncate_ending'),
            'truncate_exact'  => (bool) $this->_getData('truncate_exact'),
            'escape_html'     => (bool) $this->_getData('escape_html'),
            'nl2br'           => (bool) $this->_getData('nl2br'),
            'parse_tags'      => $this->_getData('parse_tags'),
        );
        
        $strHelper = Mage::helper('core/string');
        
        if ($strHelper->strlen($singleWc = strval($this->_getData('single_wildcard'))) === 1) {
            $values['single_wildcard'] = $singleWc;
        }
        if (($strHelper->strlen($multipleWc = strval($this->_getData('multiple_wildcard'))) === 1)
            && ($multipleWc !== $singleWc)) {
            $values['multiple_wildcard'] = $multipleWc;
        }
        
        return $values;
    }
}