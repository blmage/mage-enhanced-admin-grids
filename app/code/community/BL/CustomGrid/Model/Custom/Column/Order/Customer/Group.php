<?php

class BL_CustomGrid_Model_Custom_Column_Order_Customer_Group extends
    BL_CustomGrid_Model_Custom_Column_Order_Base
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
            $values['type'] = 'options'; 
            $values['options'] = Mage::getModel('customer/group')
                ->getResourceCollection()
                ->toOptionHash();
        }
        
        return $values;
    }
}