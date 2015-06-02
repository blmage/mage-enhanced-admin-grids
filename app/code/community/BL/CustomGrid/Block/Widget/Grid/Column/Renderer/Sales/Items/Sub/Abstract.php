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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Abstract extends Mage_Adminhtml_Block_Template implements BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface
{
    public function canRender(Varien_Object $value)
    {
        return true;
    }
    
    public function render(Varien_Object $value)
    {
        $this->setValue($value);
        return parent::toHtml();
    }
    
    /**
     * Return the CSS classes appliable for the rendering of the given value
     * 
     * @param Varien_Object $value Renderable value
     * @param string $alignmentKey Data key used to store the alignment in the given renderable value object
     * @return string
     */
    public function getItemValueCssClasses(Varien_Object $value, $alignmentKey = null)
    {
        $classes = array();
        
        if ($value->getLast()) {
            $classes[] = 'last';
        }
        if (!empty($alignmentKey) && ($alignment = $value->getData($alignmentKey))) {
            if (in_array($alignment, array('left', 'center', 'right'))) {
                $classes[] = 'a-' . $alignment;
            }
        }
        
        return implode(' ', $classes);
    }
}
