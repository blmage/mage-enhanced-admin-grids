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
 * @copyright  Copyright (c) 2016 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class BL_CustomGrid_Block_Grid_Edit_Tab_Settings extends BL_CustomGrid_Block_Grid_Form_Abstract implements
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
    
    protected function _getFormHtmlIdPrefix()
    {
        return 'blcg_grid_' . $this->getGridModel()->getId() . '_settings_';
    }
    
    /**
     * Return the field note corresponding to the given key
     * 
     * @param string $noteKey Note key
     * @return string|null
     */
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
    
    /**
     * Return default array parameters behaviours as an option array
     * 
     * @return array
     */
    protected function _getDefaultArrayBehavioursOptionArray()
    {
        /** @var $source BL_CustomGrid_Model_System_Config_Source_Default_Param_Behaviour_Array */
        $source = Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_array');
        return $source->toOptionArray();
    }
    
    /**
     * Return default scalar parameters behaviours as an option array
     * 
     * @return array
     */
    protected function _getDefaultScalarBehavioursOptionArray()
    {
        /** @var $source BL_CustomGrid_Model_System_Config_Source_Default_Param_Behaviour_Scalar */
        $source = Mage::getSingleton('customgrid/system_config_source_default_param_behaviour_scalar');
        return $source->toOptionArray();
    }
    
    /**
     * Add profiles-related fields to the given form, return the corresponding fieldset
     * 
     * @param Varien_Data_Form $form Form
     * @return Varien_Data_Form_Element_Fieldset
     */
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
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES)) {
            $fieldset->addField(
                'profiles_default_restricted',
                'select',
                array(
                    'name'   => 'restricted',
                    'label'  => $this->__('Restricted'),
                    'values' => $this->_getYesNoOptionArray(),
                    'note'   => $this->__($this->getFormFieldNote('profiles_default_restricted')),
                )
            );
            
            $fieldset->addField(
                'profiles_default_assigned_to',
                'multiselect',
                array(
                    'name'   => 'assigned_to',
                    'label'  => $this->__('Assigned To'),
                    'values' => $this->_getAdminRolesOptionArray(),
                    'note'   => $this->__($this->getFormFieldNote('profiles_default_assigned_to')),
                )
            );
        }
        
        if ($gridModel->checkUserActionPermission(BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES)) {
            $fieldset->addField(
                'profiles_remembered_session_params',
                'multiselect',
                array(
                    'name'   => 'remembered_session_params',
                    'label'  => $this->__('Remembered Session Parameters'),
                    'values' => $this->_getGridParamsOptionArray(),
                    'note'   => $this->__($this->getFormFieldNote('profiles_remembered_session_params')),
                )
            );
        }
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'profiles_defaults');
        return $fieldset;
    }
    
    /**
     * Add customization params-related fields to the given form, return the corresponding fieldset
     * 
     * @param Varien_Data_Form $form Form
     * @return Varien_Data_Form_Element_Fieldset
     */
    protected function _addCustomizationParamsFieldsToForm(Varien_Data_Form $form)
    {
        $yesNoOptions = $this->_getYesNoOptionArray();
        
        $fieldset = $form->addFieldset(
            'customization_parameters',
            array(
                'legend' => $this->__('Customization Parameters'),
                'class'  => 'fielset-wide'
            )
        );
        
        $fieldTypes = array(
            'display_system_part'        => 'Display "System" Column',
            'ignore_custom_headers'      => 'Ignore Custom Headers (Base Grid Columns)',
            'ignore_custom_widths'       => 'Ignore Custom Widths (Base Grid Columns)',
            'ignore_custom_alignments'   => 'Ignore Custom Alignments (Base Grid Columns)',
            'pagination_values'          => 'Custom Pagination Values',
            'default_pagination_value'   => 'Default Pagination Value',
            'merge_base_pagination'      => 'Merge Base Pagination Values',
            'pin_header'                 => 'Pin Pager And Mass-Actions Block',
            'rss_links_window'           => 'Move RSS Links in a Dedicated Window',
            'hide_original_export_block' => 'Hide Original Export Block',
            'hide_filter_reset_button'   => 'Hide Original Filter Reset Button',
        );
        
        foreach ($fieldTypes as $fieldName => $fieldLabel) {
            $field = $fieldset->addField(
                $fieldName,
                'select',
                array(
                    'name'   => $fieldName,
                    'label'  => $this->__($fieldLabel),
                    'values' => $yesNoOptions,
                )
            );
            
            if ($note = $this->getFormFieldNote($fieldName)) {
                $field->setNote($note);
            }
        }
        
        $this->_addSuffixToFieldsetFieldNames($fieldset, 'customization_params');
        return $fieldset;
    }
    
    /**
     * Add default params-related fields to the given form, return the corresponding fieldset
     * 
     * @param Varien_Data_Form $form Form
     * @return Varien_Data_Form_Element_Fieldset
     */
    protected function _addDefaultParamsFieldsToForm(Varien_Data_Form $form)
    {
        $arrayOptions  = $this->_getDefaultArrayBehavioursOptionArray();
        $scalarOptions = $this->_getDefaultScalarBehavioursOptionArray();
        
        $fieldset = $form->addFieldset(
            'default_params_behaviours',
            array(
                'legend' => $this->__('Default Parameters Behaviours'),
                'class'  => 'fielset-wide'
            )
        );
        
        foreach ($this->_getGridParamsOptionArray(false) as $gridParam) {
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
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        
        $gridModel   = $this->getGridModel();
        $useConfigFieldsets = array();
        $actionCodes = array(
            'assign_profiles' => BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES,
            'edit_profiles'   => BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES,
            'edit_customization_params' => BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_CUSTOMIZATION_PARAMS,
            'edit_default_params_behaviours' => BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
        );
        
        if ($gridModel->checkUserActionPermission($actionCodes['assign_profiles'])
            || $gridModel->checkUserActionPermission($actionCodes['edit_profiles'])) {
            $useConfigFieldsets[] = $this->_addProfilesFieldsToForm($form);
        }
        if ($gridModel->checkUserActionPermission($actionCodes['edit_customization_params'])) {
            $useConfigFieldsets[] = $this->_addCustomizationParamsFieldsToForm($form);
        }
        if ($gridModel->checkUserActionPermission($actionCodes['edit_default_params_behaviours'])) {
            $useConfigFieldsets[] = $this->_addDefaultParamsFieldsToForm($form);
        }
        
        foreach ($useConfigFieldsets as $fieldset) {
            foreach ($fieldset->getElements() as $field) {
                $this->applyUseConfigCheckboxToElement($field, is_null($gridModel->getData($field->getId())));
            }
        }
        
        return $this;
    }
    
    protected function _getFormValues()
    {
        return $this->getGridModel()->getData();
    }
}
