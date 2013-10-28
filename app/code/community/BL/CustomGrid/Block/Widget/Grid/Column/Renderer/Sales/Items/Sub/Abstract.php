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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Abstract
    extends Mage_Adminhtml_Block_Template
    implements BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Sales_Items_Sub_Interface
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
    
    public function getItemValueCssClasses(array $value, $alignmentKey=null)
    {
        $classes = array();
        
        if (isset($value['last']) && $value['last']) {
            $classes[] = 'last';
        }
        if (!empty($alignmentKey) && isset($value[$alignmentKey])) {
            if (in_array($value[$alignmentKey], array('left', 'center', 'right'))) {
                $classes[] = 'a-'.$value[$alignmentKey];
            }
        }
        
        return implode(' ', $classes);
    }
}