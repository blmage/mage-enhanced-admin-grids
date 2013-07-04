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

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

abstract class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Order_Items_Abstract
    extends Mage_Adminhtml_Block_Sales_Order_View_Items
    implements Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Interface
{
    protected $_column;
    protected $_defaultWidth = null;
    
    protected function _beforeToHtml()
    {
        return Mage_Adminhtml_Block_Sales_Items_Abstract::_beforeToHtml();
    }
    
    public function getColumn()
    {
        return $this->_column;
    }
    
    public function render(Varien_Object $row)
    {
        if ($this->getOrderItemsCopySuccess()) {
            $this->setOrder($row);
            return parent::toHtml();
        }
        return $this->helper('customgrid')->__('An error occured while initializing order items');
    }
    
    public function renderExport(Varien_Object $row)
    {
        return '';
    }
    
    public function renderHeader()
    {
        if ((false !== $this->getColumn()->getGrid()->getSortable())
            && (false !== $this->getColumn()->getSortable())) {
            $className = 'not-sort';
            $dir = strtolower($this->getColumn()->getDir());
            $nDir= ($dir=='asc') ? 'desc' : 'asc';
            
            if ($this->getColumn()->getDir()) {
                $className = 'sort-arrow-' . $dir;
            }
            
            $out = '<a href="#" name="' . $this->getColumn()->getId() . '" title="' . $nDir
                   . '" class="' . $className . '"><span class="sort-title">'
                   . $this->getColumn()->getHeader() . '</span></a>';
        } else {
            $out = $this->getColumn()->getHeader();
        }
        return $out;
    }
    
    public function renderProperty()
    {
        $out = '';
        $width = $this->_defaultWidth;
        
        if ($this->getColumn()->hasData('width')) {
            $customWidth = $this->getColumn()->getData('width');
            
            if ((null === $customWidth) || (preg_match('/^[0-9]+%?$/', $customWidth))) {
                $width = $customWidth;
            } elseif (preg_match('/^([0-9]+)px$/', $customWidth, $matches)) {
                $width = (int)$matches[1];
            }
        }
        if (null !== $width) {
            $out .= ' width="' . $width . '"';
        }
        
        return $out;
    }
    
    public function renderCss()
    {
        return $this->getColumn()->getCssClass();
    }
}