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

class BL_CustomGrid_Block_Grid_Form_Custom_Columns extends BL_CustomGrid_Block_Grid_Form_Abstract
{
    public function getFormAction()
    {
        return $this->getUrl('customgrid/grid/saveCustomColumns');
    }
    
    public function getDefaultFieldsetCollapseState()
    {
        return false;
    }
    
    /**
     * Return the field note usable for the given custom column
     * 
     * @param BL_CustomGrid_Model_Custom_Column_Abstract $customColumn Custom column
     * @return string
     */
    protected function _getCustomColumnFieldNote(BL_CustomGrid_Model_Custom_Column_Abstract $customColumn)
    {
        $note = $customColumn->getDescription();
        
        if ($warning = $customColumn->getWarning()) {
            $note .= ($note ? '<br />' : '') . '<strong>' . $this->__('Warning:') . '</strong> ' . $warning;
        }
        
        return $note;
    }
    
    protected function _addFieldsToForm(Varien_Data_Form $form)
    {
        parent::_addFieldsToForm($form);
        
        $groups = $this->getGridModel()->getCustomColumnsGroups();
        $customColumns = $this->getGridModel()->getAvailableCustomColumns(true);
        
        foreach ($groups as $groupId => $groupLabel) {
            $totalCount = 0;
            $displayedCount = 0;
            
            $fieldset = $form->addFieldset(
                'group_' . md5($groupLabel),
                array(
                    'legend' => $groupLabel,
                    'class'  => 'fielset-wide',
                )
            );
            
            foreach ($customColumns[$groupId] as $customColumn) {
                $id = $customColumn->getId();
                $totalCount++;
                
                if ($customColumn->isSelected()) {
                    $displayedCount++;
                }
                
                $fieldset->addField(
                    $id,
                    'select',
                    array(
                        'name'   => 'custom_columns[]',
                        'label'  => $customColumn->getName(),
                        'note'   => $this->_getCustomColumnFieldNote($customColumn),
                        'values' => array(
                                $id => $this->__('Yes'),
                                ''  => $this->__('No'),
                            ),
                        'value'  => ($customColumn->isSelected() ? $id : ''),
                    )
                );
            }
            
            $fieldset->setLegend($fieldset->getLegend() . ' ' . $this->__('(%s/%s)', $displayedCount, $totalCount));
        }
        
        return $this;
    }
}
