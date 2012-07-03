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

class BL_CustomGrid_Block_System_Config_Form_Field_Grid_Exceptions
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('block_type', array(
            'label' => $this->__('Block Type'),
            'style' => 'width:200px',
        ));
        $this->addColumn('rewriting_class_name', array(
            'label' => $this->__('Rewriting Class'),
            'style' => 'width:200px',
        ));
        
        $this->_addAfter = false;
        $this->_addButtonLabel = $this->__('Add Exception');
        parent::__construct();
    }
}