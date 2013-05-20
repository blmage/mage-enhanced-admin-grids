<?php

class BL_CustomGrid_Model_Custom_Column_Order_Customer_Group
    extends BL_CustomGrid_Model_Custom_Column_Order_Base
{
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        if ($this->_extractBoolParam($params, 'use_default_behaviour')) {
            return array(
                'type'    => 'options',
                'options' => Mage::getModel('customer/group')->getResourceCollection()->toOptionHash(),
            );
        }
        return array();
    }
}