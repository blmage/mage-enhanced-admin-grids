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

class BL_CustomGrid_Model_Grid_Edit_Config extends BL_CustomGrid_Object
{
    /**
     * Data keys of the values used by the editor block
     * 
     * @var array
     */
    static protected $_editorBlockDataKeys = array(
        'in_grid'        => true,
        'ids_key'        => true,
        'additional_key' => true,
        'edit_url'       => true, 
        'save_url'       => true,
        'window'         => true,
        'column_params'  => true,
    );
    
    /**
     * Return the necessary data for the editor block (no more no less)
     * 
     * @return array
     */
    public function getEditorBlockData()
    {
        return array_intersect_key($this->getData(), self::$_editorBlockDataKeys);
    }
}
