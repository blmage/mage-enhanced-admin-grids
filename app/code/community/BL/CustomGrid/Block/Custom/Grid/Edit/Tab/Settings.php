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

class BL_CustomGrid_Block_Custom_Grid_Edit_Tab_Settings
    extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $grid   = Mage::registry('custom_grid');
        $form   = new Varien_Data_Form();
        $form->setHtmlIdPrefix('grid_settings_'.$grid->getId().'_');
        
        $fieldset = $form->addFieldset('grid_settings_'.$grid->getId().'_fieldset_params', array(
            'legend' => $this->__('Custom Default Parameters Behaviour'),
            'class'  => 'fielset-wide'
        ));
        
        $options = array(
            'scalar' => Mage::getModel('customgrid/system_config_source_default_param_behaviour_scalar')->toOptionArray(),
            'array'  => Mage::getModel('customgrid/system_config_source_default_param_behaviour_array')->toOptionArray(),
        );
        foreach ($options as $type => $values) {
            array_unshift($options[$type], array(
                'value' => '',
                'label' => $this->__('Use Config')
            ));
        }
        
        // @todo some notes about how it's working
        
        $fieldset->addField('default_page_behaviour', 'select', array(
            'name'   => 'default_page_behaviour',
            'label'  => $this->__('Page Number'),
            'label'  => $this->__('Page Number'),
            'values' => $options['scalar'],
        ));
        
        $fieldset->addField('default_limit_behaviour', 'select', array(
            'name'   => 'default_limit_behaviour',
            'label'  => $this->__('Page Size'),
            'label'  => $this->__('Page Size'),
            'values' => $options['scalar'],
        ));
        
        $fieldset->addField('default_sort_behaviour', 'select', array(
            'name'   => 'default_sort_behaviour',
            'label'  => $this->__('Sort'),
            'label'  => $this->__('Sort'),
            'values' => $options['scalar'],
        ));
        
        $fieldset->addField('default_direction_behaviour', 'select', array(
            'name'   => 'default_direction_behaviour',
            'label'  => $this->__('Sort Direction'),
            'label'  => $this->__('Sort Direction'),
            'values' => $options['scalar'],
        ));
        
        $fieldset->addField('default_filters_behaviour', 'select', array(
            'name'   => 'default_filters_behaviour',
            'label'  => $this->__('Filters'),
            'label'  => $this->__('Filters'),
            'values' => $options['array'],
        ));
        
        $form->setValues($grid->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}