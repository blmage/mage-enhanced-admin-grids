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

class BL_CustomGrid_Block_Grid_Edit_Tab_Settings
    extends Mage_Adminhtml_Block_Widget_Form
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected function _prepareForm()
    {
        $gridModel = Mage::registry('blcg_grid');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('blcg_grid_' . $gridModel->getId() . '_settings_');
        
        $options = array(
            'boolean'    => Mage::getSingleton('customgrid/system_config_source_yesno')
                ->toOptionArray(),
            'dpb_array'  => Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_array')
                ->toOptionArray(),
            'dpb_scalar' => Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_scalar')
                ->toOptionArray(),
        );
        
        $gridActions = array(
            'assign_profiles' => BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES,
            'customization_params' => BL_CustomGrid_Model_Grid::ACTION_EDIT_CUSTOMIZATION_PARAMS,
            'default_params_behaviours' => BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
        );
        
        if ($gridModel->checkUserActionPermission($gridActions['assign_profiles'])) {
            $fieldset = $form->addFieldset('fieldset_profiles', array(
                'legend' => $this->__('Profiles - Default Values'),
                'class'  => 'fielset-wide'
            ));
            
            $fieldset->addField('profiles_default_restricted', 'select', array(
                'name'   => 'profiles_defaults[restricted]',
                'label'  => $this->__('Restricted'),
                'values' => $options['boolean'],
                'note'   => $this->__('Will be used for profiles created by users who do not have the permission '
                    . 'to assign profiles to roles'),
            ));
            
            $fieldset->addField('profiles_default_assigned_to', 'multiselect', array(
                'name'   => 'profiles_defaults[assigned_to]',
                'label'  => $this->__('Assigned To'),
                'values' => Mage::getSingleton('customgrid/system_config_source_admin_role')->toOptionArray(),
                'note'   => $this->__('Will be used for profiles created by users who do not have the permission '
                    . 'to assign profiles to roles'),
            ));
        }
        
        if ($gridModel->checkUserActionPermission($gridActions['customization_params'])) {
            $fieldset = $form->addFieldset('fieldset_customization_parameters', array(
                'legend' => $this->__('Customization Parameters'),
                'class'  => 'fielset-wide'
            ));
            
            $fieldset->addField('ignore_custom_headers', 'select', array(
                'name'   => 'customization_params[ignore_custom_headers]',
                'label'  => $this->__('Ignore Custom Headers (Base Grid Columns)'),
                'values' => $options['boolean'],
            ));
            
            $fieldset->addField('ignore_custom_widths', 'select', array(
                'name'   => 'customization_params[ignore_custom_widths]',
                'label'  => $this->__('Ignore Custom Widths (Base Grid Columns)'),
                'values' => $options['boolean'],
            ));
            
            $fieldset->addField('ignore_custom_alignments', 'select', array(
                'name'   => 'customization_params[ignore_custom_alignments]',
                'label'  => $this->__('Ignore Custom Alignments (Base Grid Columns)'),
                'values' => $options['boolean'],
            ));
            
            $fieldset->addField('pagination_values', 'text', array(
                'name'  => 'customization_params[pagination_values]',
                'label' => $this->__('Custom Pagination Values'),
                'note'  => $this->__('Numeric values separated by commas. '
                    . 'If none is set, base pagination values will be used (ie 20, 30, 50, 100, 200)'),
            ));
            
             $fieldset->addField('default_pagination_value', 'text', array(
                'name'  => 'customization_params[default_pagination_value]',
                'label' => $this->__('Default Pagination Value'),
                'note'  => $this->__('This value will replace the original default value from the grids.  If the '
                    . 'custom default value can not be found in the pagination values used for the current grid , nor '
                    . 'the original one (by fallback), then the smallest available pagination value will be used'),
            ));
            
            $fieldset->addField('merge_base_pagination', 'select', array(
                'name'   => 'customization_params[merge_base_pagination]',
                'label'  => $this->__('Merge Base Pagination Values'),
                'values' => $options['boolean'],
                'note'   => $this->__('Choose "Yes" to make the base pagination values be added to the custom ones'),
            ));
            
            $fieldset->addField('pin_header', 'select', array(
                'name'   => 'customization_params[pin_header]',
                'label'  => $this->__('Pin Pager And Mass-Actions Block'),
                'values' => $options['boolean'],
            ));
        }
        
        if ($gridModel->checkUserActionPermission($gridActions['default_params_behaviours'])) {
            $fieldset = $form->addFieldset('fieldset_default_params_behaviours', array(
                'legend' => $this->__('Default Parameters Behaviours'),
                'class'  => 'fielset-wide'
            ));
            
            $fieldset->addField('default_page_behaviour', 'select', array(
                'name'   => 'default_params_behaviours[page]',
                'label'  => $this->__('Page Number'),
                'values' => $options['dpb_scalar'],
                'note'   => $this->__('Forcing a value only applies when it does exist'),
            ));
            
            $fieldset->addField('default_limit_behaviour', 'select', array(
                'name'   => 'default_params_behaviours[limit]',
                'label'  => $this->__('Page Size'),
                'values' => $options['dpb_scalar'],
                'note'   => $this->__('Forcing a value only applies when it does exist'),
            ));
            
            $fieldset->addField('default_sort_behaviour', 'select', array(
                'name'   => 'default_params_behaviours[sort]',
                'label'  => $this->__('Sort'),
                'values' => $options['dpb_scalar'],
                'note'   => $this->__('Forcing a value only applies when it does exist'),
            ));
            
            $fieldset->addField('default_dir_behaviour', 'select', array(
                'name'   => 'default_params_behaviours[dir]',
                'label'  => $this->__('Sort Direction'),
                'values' => $options['dpb_scalar'],
                'note'   => $this->__('Forcing a value only applies when it does exist'),
            ));
            
            $fieldset->addField('default_filter_behaviour', 'select', array(
                'name'   => 'default_params_behaviours[filter]',
                'label'  => $this->__('Filters'),
                'values' => $options['dpb_array'],
                'note'   => $this->__('Forcing or merging a value only applies when it does exist'),
            ));
        }
        
        foreach ($form->getElements() as $fieldset) {
            foreach ($fieldset->getElements() as $field) {
                if (is_null($gridModel->getData($field->getId()))) {
                    $field->setDisabled(true);
                    $field->addClass('disabled');
                }
                $field->setAfterElementHtml($this->_getUseConfigCheckboxHtml($field));
            }
        }
        
        $form->setValues($gridModel->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
    
    protected function _getUseConfigCheckboxHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $htmlId  = $element->getHtmlId() . '-uc-checkbox';
        $checked = $element->getDisabled();
        
        if (preg_match('#^([a-zA-Z_]+)(\[([a-zA-Z_]+)\])?(\[\])?$#', $element->getName(), $nameParts)) {
            $name = 'use_config[' . $nameParts[1] . ']';
            
            if ($nameParts[3] !== '') {
                $name .= '[' . $nameParts[3] . ']';
            }
        } else {
            $name = 'use_config[' . $element->getName() . ']';
        }
        
        return '<div class="blcg-use-config-wrapper">'
            . '<input type="checkbox" class="checkbox" id="' . $htmlId . '" ' . 'name="' . $name . '" value="1" '
            . ($checked ? 'checked="checked" ' : '')
            . 'onclick="toggleValueElements(this, Element.up(this.parentNode));" />'
            . '<label for="' . $htmlId . '">' . $this->__('Use Config') . '</label></div>';
    }
    
    public function getTabLabel()
    {
        return $this->__('Settings');
    }
    
    public function getTabTitle()
    {
        return $this->__('Settings');
    }
    
    public function canShowTab()
    {
        return true;
    }
    
    public function isHidden()
    {
        return false;
    }
}