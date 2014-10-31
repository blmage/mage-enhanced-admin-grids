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
 * @copyright  Copyright (c) 2014 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Text extends
    Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    const CMS_TEMPLATE_PROCESSOR_NONE  = 'none';
    const CMS_TEMPLATE_PROCESSOR_BLOCK = 'block';
    const CMS_TEMPLATE_PROCESSOR_PAGE  = 'page';
    
    const TRUNCATION_MODE_NONE = 'none';
    const TRUNCATION_MODE_TEXT = 'text';
    const TRUNCATION_MODE_HTML = 'html';
    
    protected function _parseText($text)
    {
        if ($cmsProcessorType = $this->getColumn()->getCmsTemplateProcessor()) {
            $cmsProcessor = null;
            
            if ($cmsProcessorType == self::CMS_TEMPLATE_PROCESSOR_BLOCK) {
                $cmsProcessor = $this->helper('cms')->getBlockTemplateProcessor();
            } elseif ($cmsProcessorType == self::CMS_TEMPLATE_PROCESSOR_PAGE) {
                $cmsProcessor = $this->helper('cms')->getPageTemplateProcessor();
            }
            if (!is_null($cmsProcessor) && method_exists($cmsProcessor, 'filter')) {
                $text = $cmsProcessor->filter($text);
            }
        }
        return $text;
    }
    
    public function render(Varien_Object $row)
    {
        $text = $this->_parseText(parent::_getValue($row));
        
        if (($truncationMode = $this->getColumn()->getTruncationMode())
            && ($truncationMode != self::TRUNCATION_MODE_NONE)) {
            $stringHelper = $this->helper('customgrid/string');
            $truncationLength = (int) $this->getColumn()->getTruncationAt();
            $truncationEnding = $this->getColumn()->getTruncationEnding();
            $exactTruncation  = (bool) $this->getColumn()->getExactTruncation();
            $remainder = '';
            
            if ($truncationMode == self::TRUNCATION_MODE_HTML) {
                $text = $stringHelper->truncateHtml(
                    $text,
                    $truncationLength,
                    $truncationEnding,
                    !$exactTruncation
                );
            } elseif ($truncationMode == self::TRUNCATION_MODE_TEXT) {
                $text = $stringHelper->truncateText(
                    $text,
                    $truncationLength,
                    $truncationEnding,
                    $remainder,
                    !$exactTruncation
                );
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
