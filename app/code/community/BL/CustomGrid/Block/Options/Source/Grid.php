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

class BL_CustomGrid_Block_Options_Source_Grid
    extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('BLCG_OptionsSourceGrid')
            ->setSaveParametersInSession(true)
            ->setUseAjax(false);
    }
    
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customgrid/options_source_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns()
    {
        $this->addColumn('source_id', array(
            'header' => $this->__('ID'),
            'index'  => 'source_id',
            'type'   => 'number',
            'width'  => '50px',
        ));
        
        $this->addColumn('name', array(
            'header' => $this->__('Name'),
            'index'  => 'name',
        ));
        
        $this->addColumn('type', array(
            'header'  => $this->__('Type'),
            'index'   => 'type',
            'type'    => 'options',
            'options' => Mage::getModel('customgrid/options_source')->getTypesAsOptionHash(),
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
                        'field'   => 'id',
                    ),
                    array(
                        'caption' => $this->__('Delete'),
                        'confirm' => $this->__('Are you sure?'),
                        'url'     => array(
                            'base' => '*/*/delete',
                        ),
                        'field'   => 'id'
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
        return $this->getUrl('*/*/edit', array('id' => $row->getSourceId()));
    }
    
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('options_source');
        
        $this->getMassactionBlock()->addItem('mass_delete', array(
            'label'   => $this->__('Delete'),
            'url'     => $this->getUrl('*/*/massDelete', array('_current' => true)),
            'confirm' => $this->__('Are you sure?'),
        ));
        
        return $this;
    }
}