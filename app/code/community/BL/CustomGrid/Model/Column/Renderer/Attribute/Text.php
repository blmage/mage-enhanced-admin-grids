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

class BL_CustomGrid_Model_Column_Renderer_Attribute_Text
    extends BL_CustomGrid_Model_Column_Renderer_Attribute_Abstract
{
    protected $_backwardsMap = array(
        'truncate'        => 'truncation_mode',
        'truncate_at'     => 'truncation_at',
        'truncate_ending' => 'truncation_ending',
        'truncate_exact'  => 'exact_truncation',
        'parse_tags'      => 'cms_template_processor',
    );
    
    public function isAppliableToAttribute(Mage_Eav_Model_Entity_Attribute $attribute,
        BL_CustomGrid_Model_Grid $gridModel)
    {
        return true;
    }
    
    public function getColumnBlockValues(Mage_Eav_Model_Entity_Attribute $attribute,
        Mage_Core_Model_Store $store, BL_CustomGrid_Model_Grid $gridModel)
    {
        $values = array(
            'renderer'                 => 'customgrid/widget_grid_column_renderer_text',
            'filter'                   => 'customgrid/widget_grid_column_filter_text',
            'filter_mode_shortcut'     => (bool) $this->getDataSetDefault('values/filter_mode_shortcut', true),
            'negative_filter_shortcut' => (bool) $this->getDataSetDefault('values/negative_filter_shortcut', true),
            'truncation_mode'          => $this->getData('values/truncation_mode'),
            'truncation_at'            => (int) $this->getData('values/truncation_at'),
            'truncation_ending'        => $this->getData('values/truncation_ending'),
            'exact_truncation'         => (bool) $this->getData('values/exact_truncation'),
            'escape_html'              => (bool) $this->getData('values/escape_html'),
            'nl2br'                    => (bool) $this->getData('values/nl2br'),
            'cms_template_processor'   => $this->getData('values/cms_template_processor'),
        );
        
        if ($this->hasData('values/filter_mode')) {
            $values['filter_mode'] = $this->getData('values/filter_mode');
            $values['negative_filter'] = (bool) $this->getData('values/negative_filter');
        } else {
            $values['filter_mode'] = $this->getData('values/exact_filter')
                ? BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_EXACT_LIKE
                : BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_INSIDE_LIKE;
            $values['negative_filter'] = false;
        }
        
        if ($values['filter_mode_shortcut']
            || ($values['filter_mode'] == BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_EXACT_LIKE)
            || ($values['filter_mode'] == BL_CustomGrid_Block_Widget_Grid_Column_Filter_Text::MODE_INSIDE_LIKE)) {
            $stringHelper = Mage::helper('core/string');
            $singleWildcard = strval($this->getData('values/single_wildcard'));
            $multipleWildcard = strval($this->getData('values/multiple_wildcard'));
            
            if ($stringHelper->strlen($singleWildcard) === 1) {
                $values['single_wildcard'] = $singleWildcard;
            }
            if (($stringHelper->strlen($multipleWildcard) === 1)
                && ($multipleWildcard !== $singleWildcard)) {
                $values['multiple_wildcard'] = $multipleWildcard;
            }
        }
        
        return $values;
    }
}