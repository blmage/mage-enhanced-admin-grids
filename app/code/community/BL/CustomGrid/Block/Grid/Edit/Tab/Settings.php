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

class BL_CustomGrid_Block_Grid_Edit_Tab_Settings extends Mage_Adminhtml_Block_Widget_Form implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
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
    
    public function getFormFieldNote($noteKey)
    {
        if (!$this->hasData('form_field_notes')) {
            $this->setData(
                'form_field_notes',
                array(
                    'profiles_default_restricted' => 'Will be used for profiles created by users who do not have the '
                        . 'permission to assign profiles to roles',
                    'profiles_default_assigned_to' => 'Will be used for profiles created by users who do not have the '
                        . 'permission to assign profiles to roles',
                    'pagination_values' => 'Numeric values separated by commas. If none is set, base pagination values '
                        . 'will be used (ie 20, 30, 50, 100, 200)',
                    'default_pagination_value' => 'This value will replace the original default value from the grids. '
                        . 'If the custom default value can not be found in the pagination values used for the current '
                        . 'grid, nor the original one (by fallback), the smallest available pagination value will be '
                        . 'used',
                    'merge_base_pagination' => 'Choose "<strong>Yes</strong>" to make the base pagination values be '
                        . 'added to the custom ones',
                    'default_scalar_behaviour' => 'Forcing a value only applies when it does exist',
                    'default_array_behaviour' => 'Forcing or merging a value only applies when it does exist',
                )
            );
        }
        return $this->getData('form_field_notes/' . $noteKey);
    }
    
    protected function _addAssignProfilesFieldsToForm(Varien_Data_Form $form)
    {
        $fieldset = $form->addFieldset(
            'profiles',
            array(
                'legend' => $this->__('Profiles - Default Values'),
                'class'  => 'fielset-wide'
            )
        );
        
        $fieldset->addField(
            'profiles_default_restricted',
            'select',
            array(
                'name'   => 'profiles_defaults[restricted]',
                'label'  => $this->__('Restricted'),
                'values' => Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray(),
                'note'   => $this->__($this->getFormFieldNote('profiles_default_restricted')),
            )
        );
        
        $fieldset->addField(
            'profiles_default_assigned_to',
            'multiselect',
            array(
                'name'   => 'profiles_defaults[assigned_to]',
                'label'  => $this->__('Assigned To'),
                'values' => Mage::getSingleton('customgrid/system_config_source_admin_role')->toOptionArray(),
                'note'   => $this->__($this->getFormFieldNote('profiles_default_assigned_to')),
            )
        );
        
        return $fieldset;
    }
    
    protected function _addCustomizationParamsFieldsToForm(Varien_Data_Form $form)
    {
        $yesNoValues = Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray();
        
        $fieldset = $form->addFieldset(
            'customization_parameters',
            array(
                'legend' => $this->__('Customization Parameters'),
                'class'  => 'fielset-wide'
            )
        );
        
        $fieldset->addField(
            'display_system_part',
            'select',
            array(
                'name'   => 'customization_params[display_system_part]',
                'label'  => $this->__('Display "System" Column'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'ignore_custom_headers',
            'select',
            array(
                'name'   => 'customization_params[ignore_custom_headers]',
                'label'  => $this->__('Ignore Custom Headers (Base Grid Columns)'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'ignore_custom_widths',
            'select',
            array(
                'name'   => 'customization_params[ignore_custom_widths]',
                'label'  => $this->__('Ignore Custom Widths (Base Grid Columns)'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'ignore_custom_alignments',
            'select',
            array(
                'name'   => 'customization_params[ignore_custom_alignments]',
                'label'  => $this->__('Ignore Custom Alignments (Base Grid Columns)'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'pagination_values',
            'text',
             array(
                'name'  => 'customization_params[pagination_values]',
                'label' => $this->__('Custom Pagination Values'),
                'note'  => $this->__($this->getFormFieldNote('pagination_values')),
             )
        );
        
        $fieldset->addField(
            'default_pagination_value',
            'text',
            array(
                'name'  => 'customization_params[default_pagination_value]',
                'label' => $this->__('Default Pagination Value'),
                'note'  => $this->__($this->getFormFieldNote('default_pagination_value')),
            )
        );
        
        $fieldset->addField(
            'merge_base_pagination',
            'select',
            array(
                'name'   => 'customization_params[merge_base_pagination]',
                'label'  => $this->__('Merge Base Pagination Values'),
                'values' => $yesNoValues,
                'note'   => $this->__($this->getFormFieldNote('merge_base_pagination')),
            )
        );
        
        $fieldset->addField(
            'pin_header',
            'select',
            array(
                'name'   => 'customization_params[pin_header]',
                'label'  => $this->__('Pin Pager And Mass-Actions Block'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'rss_links_window',
            'select',
            array(
                'name'   => 'customization_params[rss_links_window]',
                'label'  => $this->__('Move RSS Links in a Dedicated Window'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'hide_original_export_block',
            'select',
            array(
                'name'   => 'customization_params[hide_original_export_block]',
                'label'  => $this->__('Hide Original Export Block'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'hide_filter_reset_button',
            'select',
            array(
                'name'   => 'customization_params[hide_filter_reset_button]',
                'label'  => $this->__('Hide Original Filter Reset Button'),
                'values' => $yesNoValues,
            )
        );
        
        return $fieldset;
    }
    
    protected function _addDefaultParamsFieldsToForm(Varien_Data_Form $form)
    {
        $arrayOptions  = Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_array')
            ->toOptionArray();
        $scalarOptions = Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_scalar')
            ->toOptionArray();
        
        $fieldset = $form->addFieldset(
            'default_params_behaviours',
            array(
                'legend' => $this->__('Default Parameters Behaviours'),
                'class'  => 'fielset-wide'
            )
        );
        
        $fieldset->addField(
            'default_page_behaviour',
            'select',
            array(
                'name'   => 'default_params_behaviours[page]',
                'label'  => $this->__('Page Number'),
                'values' => $scalarOptions,
                'note'   => $this->__($this->getFormFieldNote('default_scalar_behaviour')),
            )
        );
        
        $fieldset->addField(
            'default_limit_behaviour',
            'select',
            array(
                'name'   => 'default_params_behaviours[limit]',
                'label'  => $this->__('Page Size'),
                'values' => $scalarOptions,
                'note'   => $this->__($this->getFormFieldNote('default_scalar_behaviour')),
            )
        );
        
        $fieldset->addField(
            'default_sort_behaviour',
            'select',
            array(
                'name'   => 'default_params_behaviours[sort]',
                'label'  => $this->__('Sort'),
                'values' => $scalarOptions,
                'note'   => $this->__($this->getFormFieldNote('default_scalar_behaviour')),
            )
        );
        
        $fieldset->addField(
            'default_dir_behaviour',
            'select',
            array(
                'name'   => 'default_params_behaviours[dir]',
                'label'  => $this->__('Sort Direction'),
                'values' => $scalarOptions,
                'note'   => $this->__($this->getFormFieldNote('default_scalar_behaviour')),
            )
        );
        
        $fieldset->addField(
            'default_filter_behaviour',
            'select',
            array(
                'name'   => 'default_params_behaviours[filter]',
                'label'  => $this->__('Filters'),
                'values' => $arrayOptions,
                'note'   => $this->__($this->getFormFieldNote('default_array_behaviour')),
            )
        );
        
        return $fieldset;
    }
    
    protected function _prepareForm()
    {
        $gridModel = Mage::registry('blcg_grid');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('blcg_grid_' . $gridModel->getId() . '_settings_');
        $useConfigFieldsets = array();
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
            $useConfigFieldsets[] = $this->_addAssignProfilesFieldsToForm($form);
        }
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_CUSTOMIZATION_PARAMS)) {
            $useConfigFieldsets[] = $this->_addCustomizationParamsFieldsToForm($form);
        }
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS)) {
            $useConfigFieldsets[] = $this->_addDefaultParamsFieldsToForm($form);
        }
        
        foreach ($useConfigFieldsets as $fieldset) {
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
            . '<label for="' . $htmlId . '">' . $this->__('Use Config') . '</label>'
            . '</div>';
    }
}
