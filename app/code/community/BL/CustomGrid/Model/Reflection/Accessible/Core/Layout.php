<?php

class BL_CustomGrid_Model_Reflection_Accessible_Core_Layout extends Mage_Core_Model_Layout
{
    /**
     * Invoke the _generateAction() method on the given layout object, and return the corresponding result
     * 
     * @param Mage_Core_Model_Layout $layout Layout object
     * @param Varien_Simplexml_Element $actionNode Action node
     * @param Varien_Simplexml_Element $parent Parent node
     * @return Mage_Core_Model_Layout
     */
    public function blcgInvokeGenerateAction(Mage_Core_Model_Layout $layout, $actionNode, $parent)
    {
        return $layout->_generateAction($actionNode, $parent);
    }
}
