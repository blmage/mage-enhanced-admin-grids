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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Column_Renderer_Collection_Text
    extends BL_CustomGrid_Model_Column_Renderer_Collection_Abstract
{
    public function getColumnGridValues($index, $store, $grid)
    {
        return array(
            'filter'          => 'adminhtml/widget_grid_column_filter_text',
            'renderer'        => 'customgrid/widget_grid_column_renderer_text',
            'truncate'        => $this->_getData('truncate'),
            'truncate_at'     => $this->_getData('truncate_at'),
            'truncate_ending' => $this->_getData('truncate_ending'),
            'truncate_exact'  => $this->_getData('truncate_exact'),
            'truncate_at'     => $this->_getData('truncate_at'),
            'escape_html'     => $this->_getData('escape_html'),
            'nl2br'           => $this->_getData('nl2br'),
            'parse_tags'      => $this->_getData('parse_tags'),
        );
    }
}