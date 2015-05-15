<?php

class BL_CustomGrid_Model_System_Config_Source_Payment_Allmethods
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return Mage::helper('payment')->getPaymentMethodList(true, true);
    }
}
