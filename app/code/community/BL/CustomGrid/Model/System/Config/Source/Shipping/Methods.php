<?php

class BL_CustomGrid_Model_System_Config_Source_Shipping_Methods
{
    /**
     * Designed for use in the Sales Order grid.
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var $res Mage_Core_Model_Resource */
        $res = Mage::getSingleton('core/resource');
        $conn = $res->getConnection(Mage_Core_Model_Resource::DEFAULT_READ_RESOURCE);

        $select = $conn->select();
        $select->from($res->getTableName("sales/order"), array("shipping_method"));
        $select->distinct(true);

        $methods = $conn->fetchCol($select);

        $return = array();
        foreach ($methods as $method) {
            // There isn't a sensible way to get a generic label for a method, as it can differ for each order.
            // As a result, just use the method code as the label as well.
            $return[] = array(
                "value" => $method,
                "label" => $method,
            );
        }

        return $return;
    }
}
