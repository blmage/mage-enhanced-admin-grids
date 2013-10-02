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
 * @copyright  Copyright (c) 2012 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_BLock_Widget_Grid_Column_Renderer_Text
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $text = parent::_getValue($row);
        
        if ($parseTags = $this->getColumn()->getParseTags()) {
            $processor = null;
            if ($parseTags == 'block') {
                $processor = Mage::helper('cms')->getBlockTemplateProcessor();
            } elseif ($parseTags == 'page') {
                $processor = Mage::helper('cms')->getPageTemplateProcessor();
            }
            if (!is_null($processor)
                && is_callable(array($processor, 'filter'))) {
                $text = $processor->filter($text);
            }
        }
        
        if (($truncate = $this->getColumn()->getTruncate())
            && ($truncate != 'no')) {
            $truncateHelper = $this->helper('customgrid/string');
            $truncateLength = intval($this->getColumn()->getTruncateAt());
            $truncateEnding = $this->getColumn()->getTruncateEnding();
            $truncateExact  = (bool)$this->getColumn()->getTruncateExact();
            $remainder = '';
            
            if ($truncate == 'html') {
                $text = $truncateHelper->truncateHtml($text, $truncateLength, $truncateEnding, $remainder, !$truncateExact);
            } else {
                $text = $truncateHelper->truncateText($text, $truncateLength, $truncateEnding, $remainder, !$truncateExact);
            }
        }
        if ($this->getColumn()->getEscapeHtml()) {
            $text = $this->htmlEscape($text);
        }
        if ($this->getColumn()->getNl2br()) {
            $text = nl2br($text);
        }
        
        return $text;
    }
}