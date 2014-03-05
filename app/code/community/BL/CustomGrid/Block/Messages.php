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

class BL_CustomGrid_Block_Messages
    extends Mage_Adminhtml_Block_Messages
{
    /**
     * Having our own messages list allows to :
     * - display as much messages as possible, even ones that are added in session after the call to _initLayoutMessages() by the current controller
     * - display lots of messages without wasting screen space (rewriting errors, eg)
     * - separate our (sometimes really) specific messages from the other messages
     */
    
    public function _prepareLayout()
    {
        $this->setTemplate('bl/customgrid/messages.phtml');
        return Mage_Core_Block_Template::_prepareLayout();
    }
    
    protected function _beforeToHtml()
    {
        $session = Mage::getSingleton('customgrid/session');
        $this->addMessages($session->getMessages(true));
        $this->setEscapeMessageFlag($session->getEscapeMessages(true));
        
        if (method_exists($this, 'addStorageType')) {
            $this->addStorageType('customgrid/session');
        }
        
        return parent::_beforeToHtml();
    }
    
    protected function _toHtml()
    {
        return Mage_Core_Block_Template::_toHtml();
    }
    
    public function getDateFormat()
    {
        if (!$this->hasData('date_format')) {
            $format = Mage::app()->getLocale()
                ->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $this->setData('date_format', $format);
        }
        return $this->_getData('date_format');
    }
    
    protected function _formatDate($date)
    {
        return Mage::app()->getLocale()
            ->date($date, Varien_Date::DATETIME_INTERNAL_FORMAT)
            ->toString($this->getDateFormat());
    }
    
    public function getMessagesCount($type=null)
    {
        if (!is_null($type)) {
            return count($this->getMessageCollection()->getItems($type));
        }
        return $this->getMessageCollection()->count();
    }
    
    public function hasMessages($type=null)
    {
        return ($this->getMessagesCount($type) > 0);
    }
    
    public function getMessagesHtml($type=null)
    {
        if (!is_null($type)) {
            $html = '';
            
            if ($messages = $this->getMessages($type)) {
                $datedMessages = array();
                
                foreach ($messages as $message) {
                    if ($messageId = $message->getIdentifier()) {
                        list($messageId, $date) = explode('|', $messageId);
                        
                        if (!empty($date)) {
                            if (isset($datedMessages[$date])) {
                                $datedMessages[$date][] = $message;
                            } else {
                                $datedMessages[$date] = array($message);
                            }
                        }
                    }
                }
                
                foreach ($datedMessages as $date => $messages) {
                    $html .= '<div class="blcg-messages-content-date">' . $this->_formatDate($date) . '</div>';
                    $html .= '<' . $this->_messagesFirstLevelTagName . ' class="messages">';
                    $html .= '<' . $this->_messagesSecondLevelTagName . ' class="' . $type . '-msg">';
                    $html .= '<' . $this->_messagesFirstLevelTagName . '>';
                    
                    foreach ($messages as $message) {
                        $html.= '<' . $this->_messagesSecondLevelTagName . '>';
                        
                        if (isset($this->_messagesContentWrapperTagName)) {
                            $html.= '<' . $this->_messagesContentWrapperTagName . '>';
                        }
                        $html.= ($this->_escapeMessageFlag) ? $this->htmlEscape($message->getText()) : $message->getText();
                        
                        if (isset($this->_messagesContentWrapperTagName)) {
                            $html.= '</' . $this->_messagesContentWrapperTagName . '>';
                        }
                        $html.= '</' . $this->_messagesSecondLevelTagName . '>';
                    }
                    
                    $html .= '</' . $this->_messagesFirstLevelTagName . '>';
                    $html .= '</' . $this->_messagesSecondLevelTagName . '>';
                    $html .= '</' . $this->_messagesFirstLevelTagName . '>';
                }
            }
            
            return $html;
        }
        return parent::getGroupedHtml();
    }
    
    public function getMessagesTypes()
    {
        return array(
            Mage_Core_Model_Message::NOTICE  => $this->__('EAG - Messages'),
            Mage_Core_Model_Message::SUCCESS => $this->__('EAG - Successes'),
            Mage_Core_Model_Message::WARNING => $this->__('EAG - Warnings'),
            Mage_Core_Model_Message::ERROR   => $this->__('EAG - Errors'),
        );
    }
    
    /**
     * @todo
     * Possibly expected for messages system :
     * - fully customizable
     * - messages persistence
     * - ability to remove messages (single / by date / by type)
     * - ability to ignore specific types of messages (or make use of simple/complex rules)
     * - ACL
     */
}