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

class BL_CustomGrid_Model_Custom_Column_Order_Status_Color
    extends BL_CustomGrid_Model_Custom_Column_Simple_Duplicate
{
    public function initConfig()
    {
        parent::initConfig();
        $helper = Mage::helper('customgrid');
        
        $this->addCustomParam('only_cell', array(
            'label'        => $helper->__('Only Colorize Status Cell'),
            'type'         => 'select',
            'source_model' => 'adminhtml/system_config_source_yesno',
            'value'        => 0,
        ), 10);
        
        // Add two colors params for each status
        $order    = 20;
        $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        
        foreach ($statuses as $id => $status) {
            $this->addCustomParam($id.'_background', array(
                'label'       => $helper->__('"%s" Background Color', $status),
                'description' => $helper->__('Must be a valid CSS color'),
                'type'        => 'text',
                'value'       => '',
            ), $order);
            
            $this->addCustomParam($id.'_text', array(
                'label'       => $helper->__('"%s" Text Color', $status),
                'description' => $helper->__('Must be a valid CSS color'),
                'type'        => 'text',
                'value'       => '',
            ), $order+10);
            
            $order += 20;
        }
        
        $this->setCustomParamsWindowConfig(array('height' => 450));
        
        return $this;
    }
    
    public function getDuplicatedField()
    {
        return 'status';
    }
    
    protected function _getForcedGridValues($block, $model, $id, $alias, $params, $store, $renderer=null)
    {
        $statuses = Mage::getSingleton('sales/order_config')->getStatuses();
        $colors   = array();
        
        foreach ($statuses as $id => $status) {
            $bkgColor  = $this->_extractStringParam($params, $id.'_background', '', true);
            $textColor = $this->_extractStringParam($params, $id.'_text', '', true);
            
            if (($bkgColor !== '') || ($textColor !== '')) {
                $colors[$id] = array(
                    'background' => $bkgColor,
                    'text' => $textColor,
                );
            }
        }
        
        return array(
            'renderer'  => 'customgrid/widget_grid_column_renderer_order_status_color',
            'filter'    => 'adminhtml/widget_grid_column_filter_select',
            'only_cell' => $this->_extractBoolParam($params, 'only_cell'),
            'options'   => $statuses,
            'options_colors' => $colors,
        );
    }
}