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

abstract class BL_CustomGrid_Block_Config_Container_Abstract extends BL_CustomGrid_Block_Widget_Form_Container
{
    /**
    * Return the controller value usable to create the form block
    * 
    * @return string
    */
    abstract protected function _getController();
    
    /**
     * Return the header text
     * 
     * @return string
     */
    abstract protected function _getHeaderText();
    
    /**
     * Return the ID of the save button
     * 
     * @return string
     */
    abstract protected function _getSaveButtonId();
    
    /**
     * Return the name of the form JS object
     * 
     * @return string
     */
    abstract public function getJsObjectName(); 
    
    public function __construct()
    {
        parent::__construct();
        
        $this->_controller = $this->_getController();
        $this->_headerText = $this->_getHeaderText();
        $this->_removeButtons(array('back', 'delete', 'reset'));
        
        $this->_updateButton(
            'save',
            null,
            array(
                'id'         => $this->_getSaveButtonId(),
                'label'      => $this->__('Apply Configuration'),
                'onclick'    => $this->getJsObjectName() . '.insertParams();',
                'sort_order' => 0,
            )
        );
    }
}
