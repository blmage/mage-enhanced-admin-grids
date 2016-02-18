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
 * @copyright  Copyright (c) 2015 Matthew Gamble (https://github.com/mwgamble)
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Model_Custom_Column_Action extends BL_CustomGrid_Model_Custom_Column_Abstract
{
    /**
     * @var string
     */
    protected $_getter = 'getId';
    
    /**
     * @var string
     */
    protected $_field = 'id';
    
    /**
     * Apply the custom column to the given grid collection
     *
     * @param Varien_Data_Collection_Db $collection
     * @param Mage_Adminhtml_Block_Widget_Grid $gridBlock
     * @param BL_CustomGrid_Model_Grid $gridModel
     * @param string $columnBlockId
     * @param string $columnIndex
     * @param array $params
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    public function applyToGridCollection(
        Varien_Data_Collection_Db $collection,
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        return $this;
    }
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $customBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $values = array(
            'type'      => 'action',
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true,
        );
        
        $editSettings = $this->getCurrentBlockValues();
        
        if (isset($editSettings['actions'])) {
            $values['actions'] = $editSettings['actions'];
            $values['actions']['edit']['caption'] = $this->getBaseHelper()->__('Edit');
            $values['actions']['edit']['url'] = array('base' => '*/*/edit');
        }
        
        return $values;
    }
}
