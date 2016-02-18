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

class BL_CustomGrid_Model_Custom_Column_Order_Status_Color extends BL_CustomGrid_Model_Custom_Column_Simple_Duplicate
{
    protected function _prepareConfig()
    {
        $helper = $this->getBaseHelper();
        $sortOrder = 20;
        
        $this->addCustomizationParam(
            'only_cell',
            array(
                'label'        => $helper->__('Only Colorize Status Cell'),
                'group'        => $helper->__('Rendering'),
                'type'         => 'select',
                'source_model' => 'adminhtml/system_config_source_yesno',
                'value'        => 0,
            ),
            10
        );
        
        foreach ($this->getOrderStatuses() as $key => $status) {
            $this->addCustomizationParam(
                $key . '_background',
                array(
                    'label'       => $helper->__('"%s" Background Color', $status),
                    'group'       => $helper->__('Background Colors'),
                    'description' => $helper->__('Must be a valid CSS color'),
                    'type'        => 'text',
                    'value'       => '',
                ),
                $sortOrder += 10
            );
            
            $this->addCustomizationParam(
                $key . '_text',
                array(
                    'label'       => $helper->__('"%s" Text Color', $status),
                    'group'       => $helper->__('Text Colors'),
                    'description' => $helper->__('Must be a valid CSS color'),
                    'type'        => 'text',
                    'value'       => '',
                ),
                $sortOrder += 10
            );
        }
        
        $this->setCustomizationWindowConfig(array('height' => 450), true);
        return parent::_prepareConfig();
    }
    
    public function getDuplicatedFieldName()
    {
        return 'status';
    }
    
    public function getForcedBlockValues(
        Mage_Adminhtml_Block_Widget_Grid $gridBlock,
        BL_CustomGrid_Model_Grid $gridModel,
        $columnBlockId,
        $columnIndex,
        array $params,
        Mage_Core_Model_Store $store
    ) {
        $colors = array();
        
        foreach ($this->getOrderStatuses() as $key => $status) {
            $textColor = $this->_extractStringParam($params, $key . '_text', '', true);
            $backgroundColor = $this->_extractStringParam($params, $key . '_background', '', true);
            
            if (($textColor !== '') || ($backgroundColor !== '')) {
                $colors[$key] = array(
                    'text' => $textColor,
                    'background' => $backgroundColor,
                );
            }
        }
        
        return array(
            'filter'    => 'customgrid/widget_grid_column_filter_select',
            'renderer'  => 'customgrid/widget_grid_column_renderer_order_status_color',
            'only_cell' => $this->_extractBoolParam($params, 'only_cell'),
            'options'   => $this->getBaseHelper()->getOptionArrayFromOptionHash($this->getOrderStatuses()),
            'options_colors'  => $colors,
            'imploded_values' => false,
        );
    }
    
    public function getLockedRenderer()
    {
        return 'options';
    }
    
    public function getOrderStatuses()
    {
        if (!$this->hasData('order_statuses')) {
            /** @var $orderConfig Mage_Sales_Model_Order_Config */
            $orderConfig = Mage::getSingleton('sales/order_config');
            $this->setData('order_statuses', $orderConfig->getStatuses());
        }
        return $this->_getData('order_statuses');
    }
}
