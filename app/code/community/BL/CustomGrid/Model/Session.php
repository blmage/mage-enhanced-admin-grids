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

class BL_CustomGrid_Model_Session
    extends Mage_Core_Model_Session_Abstract
{
    protected $_now;
    
    protected function _construct()
    {
        parent::_construct();
        $this->_now = date('Y-m-d H:i:s');
    }
    
    public function addMessage(Mage_Core_Model_Message_Abstract $message)
    {
        $message->setIdentifier(uniqid().'|'.$this->_now);
        return parent::addMessage($message);
    }
}