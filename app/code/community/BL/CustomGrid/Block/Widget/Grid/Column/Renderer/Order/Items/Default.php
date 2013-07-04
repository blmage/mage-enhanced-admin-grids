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
 * @copyright  Copyright (c) 2013 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Items_Default
    extends BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Items_Abstract
{
    public function setColumn($column)
    {
        try {
            // Copy as much as possible the behaviour from the "order_items" block of the sales order view page
            $layout = $this->getLayout();
            $update = Mage::getModel('core/layout_update')
                ->load('adminhtml_sales_order_view')
                ->asSimplexml();
            
            if (is_array($blockNodes = $update->xpath("//block[@name='order_items']"))
                && ($blockNode = array_shift($blockNodes))
                && ($template = (string)$blockNode['template'])) {
                $this->setTemplate($template);
            } else {
                // Use base template as default
                $this->setTemplate('sales/order/view/items.phtml');
            }
            
            // Retrieve and use all actions applied to the base block (most commonly, "addColumnRender" and "addItemRender")
            $parent = new Varien_Object(array('block_name' => $this->getNameInLayout()));
            $actionNodes = $update->xpath("//block[@name='order_items']//action|//reference[@name='order_items']//action");
            
            $layoutReflection = new ReflectionClass($layout);
            $generateAction   = $layoutReflection->getMethod('_generateAction');
            $generateAction->setAccessible(true);
            
            foreach ($actionNodes as $actionNode) {
                $generateAction->invoke($layout, $actionNode, $parent);
            }
            
            $this->setOrderItemsCopySuccess(true);
            
        } catch (Exception $e) {
            // Remember to display nothing if something failed
            Mage::logException($e);
            $this->setOrderItemsCopySuccess(false);
        }
        
        $this->_column = $column;
        return $this;
    }
}