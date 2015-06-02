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

class BL_CustomGrid_Block_Widget_Grid_Column_Renderer_Text extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    const CMS_TEMPLATE_PROCESSOR_NONE  = 'none';
    const CMS_TEMPLATE_PROCESSOR_BLOCK = 'block';
    const CMS_TEMPLATE_PROCESSOR_PAGE  = 'page';
    
    const TRUNCATION_MODE_NONE = 'none';
    const TRUNCATION_MODE_TEXT = 'text';
    const TRUNCATION_MODE_HTML = 'html';
    
    /**
     * Parse the given text value with the configured CMS template processor (if any)
     * 
     * @param string $textValue Parsable text value
     * @return string
     */
    protected function _parseTextValue($textValue)
    {
        if ($cmsProcessorType = $this->getColumn()->getCmsTemplateProcessor()) {
            /** @var $cmsHelper Mage_Cms_Helper_Data */
            $cmsHelper = $this->helper('cms');
            $cmsProcessor = null;
            
            if ($cmsProcessorType == self::CMS_TEMPLATE_PROCESSOR_BLOCK) {
                $cmsProcessor = $cmsHelper->getBlockTemplateProcessor();
            } elseif ($cmsProcessorType == self::CMS_TEMPLATE_PROCESSOR_PAGE) {
                $cmsProcessor = $cmsHelper->getPageTemplateProcessor();
            }
            if (!is_null($cmsProcessor) && method_exists($cmsProcessor, 'filter')) {
                $textValue = $cmsProcessor->filter($textValue);
            }
        }
        return $textValue;
    }
    
    public function render(Varien_Object $row)
    {
        $textValue = $this->_parseTextValue(parent::_getValue($row));
        
        if (($truncationMode = $this->getColumn()->getTruncationMode())
            && ($truncationMode != self::TRUNCATION_MODE_NONE)) {
            /** @var $stringHelper BL_CustomGrid_Helper_String */
            $stringHelper = $this->helper('customgrid/string');
            $truncationLength = (int) $this->getColumn()->getTruncationAt();
            $truncationEnding = $this->getColumn()->getTruncationEnding();
            $exactTruncation  = (bool) $this->getColumn()->getExactTruncation();
            $remainder = '';
            
            if ($truncationMode == self::TRUNCATION_MODE_HTML) {
                $textValue = $stringHelper->truncateHtml(
                    $textValue,
                    $truncationLength,
                    $truncationEnding,
                    !$exactTruncation
                );
            } elseif ($truncationMode == self::TRUNCATION_MODE_TEXT) {
                $textValue = $stringHelper->truncateText(
                    $textValue,
                    $truncationLength,
                    $truncationEnding,
                    $remainder,
                    !$exactTruncation
                );
            }
        }
        if ($this->getColumn()->getEscapeHtml()) {
            $textValue = $this->htmlEscape($textValue);
        }
        if ($this->getColumn()->getNl2br()) {
            $textValue = nl2br($textValue);
        }
        
        return $textValue;
    }
}
