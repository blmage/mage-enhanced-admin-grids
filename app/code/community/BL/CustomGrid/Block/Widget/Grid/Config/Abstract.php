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

abstract class BL_CustomGrid_Block_Widget_Grid_Config_Abstract
    extends Mage_Adminhtml_Block_Widget
{
    protected function _toHtml()
    {
        // To be able to display, we need :
        // a grid model
        if (($model = $this->getGridModel())
            // which is not new if we don't support it
            && ((!$this->getRequireExistingModel() || !$this->getIsNewGridModel())
                && ($this->getDisplayableWithoutBlock()
                    // and a rewrited grid block if we need it
                    || (($block = $this->getGridBlock())
                        && $this->helper('customgrid')->isRewritedGridBlock($block))))) {
            return parent::_toHtml();
        }
        return '';
    }
    
    public function getGridJsObjectName()
    {
        return (($gridBlock = $this->getGridBlock()) ? $gridBlock->getJsObjectName() : null);
    }
    
    public function getJsGridFormJsonConfig()
    {
        if (!$this->hasData('js_grid_form_json_config')) {
            $this->setData(
                'js_grid_form_json_config',
                Mage::helper('core')->jsonEncode(array(
                    'gridObjectName' => $this->getGridJsObjectName(),
                    'additionalParams' => array(
                        'grid_id'    => $this->getGridModel()->getId(),
                        'profile_id' => $this->getGridModel()->getProfileId(),
                        'form_key'   => $this->getFormKey(),
                    ),
                ))
            );
        }
        return $this->_getData('js_grid_form_json_config');
    }
}
