<?php

class BL_CustomGrid_Model_Custom_Column_Order_Customer_Group extends BL_CustomGrid_Model_Custom_Column_Order_Base
{
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $values = array();
        
        if ($this->_extractBoolParam($params, 'use_default_behaviour')) {
            /** @var $collection Mage_Customer_Model_Entity_Group_Collection */
            $collection = Mage::getResourceModel('customer/group_collection');
            $values['type'] = 'options'; 
            $values['options'] = $collection->toOptionHash();
        }
        
        return $values;
    }
}