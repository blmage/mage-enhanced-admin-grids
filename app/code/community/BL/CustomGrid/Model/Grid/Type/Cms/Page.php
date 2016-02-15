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

class BL_CustomGrid_Model_Grid_Type_Cms_Page extends BL_CustomGrid_Model_Grid_Type_Abstract
{
    protected function _getSupportedBlockTypes()
    {
        return array('adminhtml/cms_page_grid');
    }
    
    protected function _getColumnsLockedValues($blockType)
    {
        return array(
            'store_code' => array(
                'renderer' => '',
                'config_values' => array(
                    'filter' => false,
                    'sortable' => false
                ),
            ),
            '_first_store_id' => array(
                'renderer' => '',
                'config_values' => array(
                    'filter' => false,
                    'sortable' => false
                ),
            ),
        );
    }
    
    protected function _getEditorModelClassCode()
    {
        return 'customgrid/grid_editor_cms_page';
    }
}
