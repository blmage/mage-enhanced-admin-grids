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

class BL_CustomGrid_Block_Grid_Edit_Tab_Settings extends BL_CustomGrid_Block_Widget_Form implements
    Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel()
    {
        return $this->__('Settings (Grid)');
    }
    
    public function getTabTitle()
    {
        return $this->__('Settings (Grid)');
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
                    'profiles_remembered_session_params' => 'Session parameters that will be restored upon returning '
                        . 'to a profile previously used during the same session.<br /><i>Only applies to the grids '
                        . 'having their parameters saved in session</i>',
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
    
    protected function _addProfilesFieldsToForm(Varien_Data_Form $form)
    {
        $gridModel = $this->getGridModel();
        
        $fieldset = $form->addFieldset(
            'profiles',
            array(
                'legend' => $this->__('Profiles - Default Values'),
                'class'  => 'fielset-wide'
            )
        );
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)) {
            $fieldset->addField(
                'profiles_default_restricted',
                'select',
                array(
                    'name'   => 'restricted',
                    'label'  => $this->__('Restricted'),
                    'values' => Mage::getSingleton('customgrid/system_config_source_yesno')->toOptionArray(),
                    'note'   => $this->__($this->getFormFieldNote('profiles_default_restricted')),
                )
            );
            
            $fieldset->addField(
                'profiles_default_assigned_to',
                'multiselect',
                array(
                    'name'   => 'assigned_to',
                    'label'  => $this->__('Assigned To'),
                    'values' => Mage::getSingleton('customgrid/system_config_source_admin_role')->toOptionArray(),
                    'note'   => $this->__($this->getFormFieldNote('profiles_default_assigned_to')),
                )
            );
        }
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_PROFILES)) {
            $fieldset->addField(
                'profiles_remembered_session_params',
                'multiselect',
                array(
                    'name'   => 'remembered_session_params',
                    'label'  => $this->__('Remembered Session Parameters'),
                    'values' => Mage::getSingleton('customgrid/system_config_source_grid_param')->toOptionArray(),
                    'note'   => $this->__($this->getFormFieldNote('profiles_remembered_session_params')),
                )
            );
        }
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'profiles_defaults');
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
                'name'   => 'display_system_part',
                'label'  => $this->__('Display "System" Column'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'ignore_custom_headers',
            'select',
            array(
                'name'   => 'ignore_custom_headers',
                'label'  => $this->__('Ignore Custom Headers (Base Grid Columns)'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'ignore_custom_widths',
            'select',
            array(
                'name'   => 'ignore_custom_widths',
                'label'  => $this->__('Ignore Custom Widths (Base Grid Columns)'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'ignore_custom_alignments',
            'select',
            array(
                'name'   => 'ignore_custom_alignments',
                'label'  => $this->__('Ignore Custom Alignments (Base Grid Columns)'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'pagination_values',
            'text',
            array(
                'name'  => 'pagination_values',
                'label' => $this->__('Custom Pagination Values'),
                'note'  => $this->__($this->getFormFieldNote('pagination_values')),
             )
        );
        
        $fieldset->addField(
            'default_pagination_value',
            'text',
            array(
                'name'  => 'default_pagination_value',
                'label' => $this->__('Default Pagination Value'),
                'note'  => $this->__($this->getFormFieldNote('default_pagination_value')),
            )
        );
        
        $fieldset->addField(
            'merge_base_pagination',
            'select',
            array(
                'name'   => 'merge_base_pagination',
                'label'  => $this->__('Merge Base Pagination Values'),
                'values' => $yesNoValues,
                'note'   => $this->__($this->getFormFieldNote('merge_base_pagination')),
            )
        );
        
        $fieldset->addField(
            'pin_header',
            'select',
            array(
                'name'   => 'pin_header',
                'label'  => $this->__('Pin Pager And Mass-Actions Block'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'rss_links_window',
            'select',
            array(
                'name'   => 'rss_links_window',
                'label'  => $this->__('Move RSS Links in a Dedicated Window'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'hide_original_export_block',
            'select',
            array(
                'name'   => 'hide_original_export_block',
                'label'  => $this->__('Hide Original Export Block'),
                'values' => $yesNoValues,
            )
        );
        
        $fieldset->addField(
            'hide_filter_reset_button',
            'select',
            array(
                'name'   => 'hide_filter_reset_button',
                'label'  => $this->__('Hide Original Filter Reset Button'),
                'values' => $yesNoValues,
            )
        );
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'customization_params');
        return $fieldset;
    }
    
    protected function _addDefaultParamsFieldsToForm(Varien_Data_Form $form)
    {
        $gridParams = Mage::getSingleton('customgrid/system_config_source_grid_param')
            ->toOptionArray(false);
        $arrayOptions   = Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_array')
            ->toOptionArray();
        $scalarOptions  = Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_scalar')
            ->toOptionArray();
        
        $fieldset = $form->addFieldset(
            'default_params_behaviours',
            array(
                'legend' => $this->__('Default Parameters Behaviours'),
                'class'  => 'fielset-wide'
            )
        );
        
        foreach ($gridParams as $gridParam) {
            if ($isFilterGridParam = ($gridParam['value'] == BL_CustomGrid_Model_Grid::GRID_PARAM_FILTER)) {
                $formFieldNoteKey = 'default_array_behaviour';
            } else {
                $formFieldNoteKey = 'default_scalar_behaviour';
            }
            
            $fieldset->addField(
                'default_' . $gridParam['value'] . '_behaviour',
                'select',
                array(
                    'name'   => $gridParam['value'],
                    'label'  => $gridParam['label'],
                    'values' => ($isFilterGridParam ? $arrayOptions : $scalarOptions),
                    'note'   => $this->__($this->getFormFieldNote($formFieldNoteKey)),
                )
            );
        }
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'default_params_behaviours');
        return $fieldset;
    }
    
    protected function _prepareForm()
    {
        $gridModel = $this->getGridModel();
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('blcg_grid_' . $gridModel->getId() . '_settings_');
        $useConfigFieldsets = array();
        
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_ASSIGN_PROFILES)
            || $gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_PROFILES)) {
            $useConfigFieldsets[] = $this->_addProfilesFieldsToForm($form);
        }
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_CUSTOMIZATION_PARAMS)) {
            $useConfigFieldsets[] = $this->_addCustomizationParamsFieldsToForm($form);
        }
        if ($gridModel->checkUserPermissions(BL_CustomGrid_Model_Grid::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS)) {
            $useConfigFieldsets[] = $this->_addDefaultParamsFieldsToForm($form);
        }
        
        foreach ($useConfigFieldsets as $fieldset) {
            foreach ($fieldset->getElements() as $field) {
                $this->applyUseConfigCheckboxToElement($field, is_null($gridModel->getData($field->getId())));
            }
        }
        
        $form->setValues($gridModel->getData());
        $this->setForm($form);
        
        return parent::_prepareForm();
    }
}
