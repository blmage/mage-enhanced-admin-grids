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

class BL_CustomGrid_Block_Custom_Grid_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('BLCG_CustomGridGrid')
            ->setSaveParametersInSession(true)
            ->setUseAjax(false);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customgrid/grid_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('block_type', array(
            'header' => $this->__('Block Type'),
            'index'  => 'block_type',
        ));
        
        $this->addColumn('type', array(
            'header'  => $this->__('Type'),
            'index'   => 'type',
            'type'    => 'options',
            'options' => Mage::getModel('customgrid/grid_type')->getTypesAsOptionHash(true),
        ));
        
        $this->addColumn('rewriting_class_name', array(
            'header' => $this->__('Rewriting Class'),
            'index'  => 'rewriting_class_name',
        ));
        
        $this->addColumn('module_name', array(
            'header' => $this->__('Module Name'),
            'index'  => 'module_name',
        ));
        
        $this->addColumn('controller_name', array(
            'header' => $this->__('Controller Name'),
            'index'  => 'controller_name',
        ));
        
        $this->addColumn('block_id', array(
            'header' => $this->__('Block ID'),
            'index'  => 'block_id',
        ));
        
        $this->addColumn('disabled', array(
            'header'  => $this->__('Disabled'),
            'index'   => 'disabled',
            'type'    => 'options',
            'options' => array(
                1 => $this->__('Yes'),
                0 => $this->__('No'),
            ),
        ));
        
        $this->addColumn('action',
            array(
                'header'  => $this->__('Actions'),
                'width'   => '120px',
                'type'    => 'action',
                'getter'  => 'getId',
                'actions' => array(
                    array(
                        'caption' => $this->__('Edit'),
                        'url'     => array(
                            'base' => '*/*/edit',
                        ),
                        'field'   => 'grid_id',
                    ),
                    array(
                        'caption' => $this->__('Enable'),
                        'url'     => array(
                            'base' => '*/*/enable',
                        ),
                        'field'   => 'grid_id',
                    ),
                    array(
                        'caption' => $this->__('Disable'),
                        'url'     => array(
                            'base' => '*/*/disable',
                        ),
                        'field'   => 'grid_id',
                    ),
                    array(
                        'caption' => $this->__('Delete'),
                        'confirm' => $this->__('Are you sure?'),
                        'url'     => array(
                            'base' => '*/*/delete',
                        ),
                        'field'   => 'grid_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'id',
        ));
        
        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('grid_id' => $row->getGridId()));
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('grid_id');
        $this->getMassactionBlock()->setFormFieldName('grid');
        
        $this->getMassactionBlock()->addItem('mass_enable', array(
            'label'   => $this->__('Enable'),
            'url'     => $this->getUrl('*/*/massEnable', array('_current' => true)),
            'confirm' => $this->__('Are you sure?'),
        ));
        
        $this->getMassactionBlock()->addItem('mass_disable', array(
            'label'   => $this->__('Disable'),
            'url'     => $this->getUrl('*/*/massDisable', array('_current' => true)),
            'confirm' => $this->__('Are you sure?'),
        ));
        
        $this->getMassactionBlock()->addItem('mass_delete', array(
            'label'   => $this->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete', array('_current' => true)),
            'confirm' => $this->__('Are you sure?'),
        ));
        
        return $this;
    }
}