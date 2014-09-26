<?php

class BL_CustomGrid_Model_System_Config_Source_Sql_Logical_Operator
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => BL_CustomGrid_Helper_Collection::SQL_AND,
                'label' => 'AND',
            ),
            array(
                'value' => BL_CustomGrid_Helper_Collection::SQL_OR,
                'label' => 'OR',
            ),
            array(
                'value' => BL_CustomGrid_Helper_Collection::SQL_XOR,
                'label' => 'XOR',
            ),
        );
    }
}