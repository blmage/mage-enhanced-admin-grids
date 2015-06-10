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

class BL_CustomGrid_Block_Grid_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('BLCG_CustomGridGrid');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customgrid/grid_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    /**
     * Add the "Actions" colum to the columns list
     * 
     * @return BL_CustomGrid_Block_Grid_Grid
     */
    protected function _prepareActionColumn()
    {
        return $this->addColumn(
            'action',
            array(
                'header'   => $this->__('Actions'),
                'index'    => 'id',
                'renderer' => 'customgrid/widget_grid_column_renderer_grid_action',
                'filter'   => false,
                'sortable' => false,
                'getter'   => 'getId',
                'width'    => '120px',
                'actions'  => array(
                    array(
                        'caption' => $this->__('Edit'),
                        'field'   => 'grid_id',
                        'url'     => array(
                            'base' => '*/*/edit',
                        ),
                        'permissions' => array(
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_CUSTOMIZATION_PARAMS,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_ROLES_PERMISSIONS,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES,
                            BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES,
                        ),
                    ),
                    array(
                        'caption'     => $this->__('Enable'),
                        'url'         => array('base' => '*/*/enable'),
                        'field'       => 'grid_id',
                        'permissions' => array(BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE),
                    ),
                    array(
                        'caption'     => $this->__('Disable'),
                        'url'         => array('base' => '*/*/disable'),
                        'field'       => 'grid_id',
                        'permissions' => array(BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE),
                    ),
                    array(
                        'caption'     => $this->__('Delete'),
                        'confirm'     => $this->__('Are you sure?'),
                        'url'         => array('base' => '*/*/delete'),
                        'field'       => 'grid_id',
                        'permissions' => array(BL_CustomGrid_Model_Grid_Sentry::ACTION_DELETE),
                    )
                ),
            )
        );
    }
    
    protected function _prepareColumns()
    {
        /** @var $gridTypeConfig BL_CustomGrid_Model_Grid_Type_Config */
        $gridTypeConfig = Mage::getSingleton('customgrid/grid_type_config');
        $gridTypesHash  = $gridTypeConfig->getTypesAsOptionHash(true);
        
        $this->addColumn(
            'grid_id',
            array(
                'header' => $this->__('ID'),
                'index'  => 'grid_id',
            )
        );
        
        $this->addColumn(
            'block_type',
            array(
                'header' => $this->__('Block Type'),
                'index'  => 'block_type',
            )
        );
            
        $this->addColumn(
            'type_code',
            array(
                'header'  => $this->__('Type'),
                'index'   => 'type_code',
                'type'    => 'options',
                'options' => $gridTypesHash,
            )
        );
        
        $this->addColumn(
            'forced_type_code',
            array(
                'header'  => $this->__('Forced Type'),
                'index'   => 'forced_type_code',
                'type'    => 'options',
                'options' => $gridTypesHash,
            )
        );
        
        $this->addColumn(
            'rewriting_class_name',
            array(
                'header' => $this->__('Rewriting Class'),
                'index'  => 'rewriting_class_name',
            )
        );
        
        $this->addColumn(
            'module_name',
            array(
                'header' => $this->__('Module Name'),
                'index'  => 'module_name',
            )
        );
        
        $this->addColumn(
            'controller_name',
            array(
                'header' => $this->__('Controller Name'),
                'index'  => 'controller_name',
            )
        );
        
        $this->addColumn(
            'block_id',
            array(
                'header' => $this->__('Block ID'),
                'index'  => 'block_id',
            )
        );
        
        $this->addColumn(
            'disabled',
            array(
                'header'  => $this->__('Disabled'),
                'index'   => 'disabled',
                'type'    => 'options',
                'options' => array(
                    1 => $this->__('Yes'),
                    0 => $this->__('No'),
                ),
            )
        );
        
        $this->_prepareActionColumn();
        return parent::_prepareColumns();
    }
    
    /**
     * Return whether the given grid model can be edited by the current user
     * 
     * @param BL_CustomGrid_Model_Grid $gridModel Grid model
     * @return bool
     */
    protected function _isRowEditAllowed(BL_CustomGrid_Model_Grid $gridModel)
    {
        return $gridModel->checkUserPermissions(
            array(
                BL_CustomGrid_Model_Grid_Sentry::ACTION_CUSTOMIZE_COLUMNS,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_ENABLE_DISABLE,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_FORCED_TYPE,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_CUSTOMIZATION_PARAMS,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_DEFAULT_PARAMS_BEHAVIOURS,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_ROLES_PERMISSIONS,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_ASSIGN_PROFILES,
                BL_CustomGrid_Model_Grid_Sentry::ACTION_EDIT_PROFILES,
            )
        );
    }
    
    public function getRowUrl($item)
    {
        if (($item instanceof BL_CustomGrid_Model_Grid) && $this->_isRowEditAllowed($item)) {
            return $this->getUrl(
                '*/*/edit',
                array(
                    'grid_id'    => $item->getId(),
                    'profile_id' => $item->getBaseProfileId(),
                )
            );
        }
        return '#';
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('grid_id');
        
        $this->getMassactionBlock()
            ->setFormFieldName('grid')
            ->addItem(
                'mass_enable',
                array(
                    'label'   => $this->__('Enable'),
                    'url'     => $this->getUrl('*/*/massEnable', array('_current' => true)),
                    'confirm' => $this->__('Are you sure?'),
                )
            )
            ->addItem(
                'mass_disable',
                array(
                    'label'   => $this->__('Disable'),
                    'url'     => $this->getUrl('*/*/massDisable', array('_current' => true)),
                    'confirm' => $this->__('Are you sure?'),
                )
            )
            ->addItem(
                'mass_delete',
                array(
                    'label'   => $this->__('Delete'),
                    'url'     => $this->getUrl('*/*/massDelete', array('_current' => true)),
                    'confirm' => $this->__('Are you sure?'),
                )
            );
        
        return $this;
    }
}
