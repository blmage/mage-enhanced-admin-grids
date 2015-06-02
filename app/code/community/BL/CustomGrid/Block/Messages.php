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

class BL_CustomGrid_Block_Messages extends Mage_Adminhtml_Block_Messages
{
    /**
     * Having our own messages list allows to :
     * - display as much messages as possible, even ones that are added in session after the call to
     *   _initLayoutMessages() by the current controller
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
        /** @var $session BL_CustomGrid_Model_Session */
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
    
    /**
     * Return the format usable to render messages dates
     * 
     * @return string
     */
    public function getDateFormat()
    {
        if (!$this->hasData('date_format')) {
            $format = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
            $this->setData('date_format', $format);
        }
        return $this->_getData('date_format');
    }
    
    /**
     * Format the given message date
     * 
     * @param string $date Message date
     * @return string
     */
    protected function _formatDate($date)
    {
        return Mage::app()->getLocale()
            ->date($date, Varien_Date::DATETIME_INTERNAL_FORMAT)
            ->toString($this->getDateFormat());
    }
    
    /**
     * Return the current number of messages, either globally or for the given type
     * 
     * @param string|null $type Message type
     * @return int
     */
    public function getMessagesCount($type = null)
    {
        if (!is_null($type)) {
            return count($this->getMessageCollection()->getItems($type));
        }
        return $this->getMessageCollection()->count();
    }
    
    /**
     * Return whether there currently are messages, either globally or for the given type
     * 
     * @param string|null $type Message type
     * @return bool
     */
    public function hasMessages($type = null)
    {
        return ($this->getMessagesCount($type) > 0);
    }
    
    /**
     * Return current messages, arranged by date
     * 
     * @param string|null $type Message type
     * @return array
     */
    public function getDatedMessages($type = null)
    {
        $datedMessages = array();
        
        if ($messages = $this->getMessages($type)) {
            foreach ($messages as $message) {
                /** @var $message Mage_Core_Model_Message_Abstract */
                if ($messageId = $message->getIdentifier()) {
                    list(, $date) = explode('|', $messageId);
                    
                    if (!empty($date)) {
                        if (isset($datedMessages[$date])) {
                            $datedMessages[$date][] = $message;
                        } else {
                            $datedMessages[$date] = array($message);
                        }
                    }
                }
            }
        }
        
        return $datedMessages;
    }
    
    /**
     * Return the text from the given message
     * 
     * @param Mage_Core_Model_Message_Abstract $message Message
     * @return string
     */
    protected function _getMessageText(Mage_Core_Model_Message_Abstract $message)
    {
        return ($this->_escapeMessageFlag ? $this->htmlEscape($message->getText()) : $message->getText());
    }
    
    /**
     * Return current messages as HTML
     * 
     * @param string|null $type Message type
     * @return string
     */
    public function getMessagesHtml($type = null)
    {
        if (!is_null($type)) {
            $html = '';
            
            if (isset($this->_messagesContentWrapperTagName)) {
                $contentWrapperStartTag = '<' . $this->_messagesContentWrapperTagName . '>';
                $contentWrapperEndTag   = '</' . $this->_messagesContentWrapperTagName . '>';
            } else {
                $contentWrapperStartTag = '';
                $contentWrapperEndTag   = '';
            }
            
            foreach ($this->getDatedMessages($type) as $date => $messages) {
                $html .= '<div class="blcg-messages-list">';
                $html .= '<div class="blcg-messages-content-date">' . $this->_formatDate($date) . '</div>';
                $html .= '<' . $this->_messagesFirstLevelTagName . ' class="messages">';
                $html .= '<' . $this->_messagesSecondLevelTagName . ' class="' . $type . '-msg">';
                $html .= '<' . $this->_messagesFirstLevelTagName . '>';
                
                foreach ($messages as $message) {
                    /** @var $message Mage_Core_Model_Message_Abstract */
                    $html .= '<' . $this->_messagesSecondLevelTagName . '>';
                    $html .= $contentWrapperStartTag;
                    $html .= $this->_getMessageText($message);
                    $html .= $contentWrapperEndTag;
                    $html .= '</' . $this->_messagesSecondLevelTagName . '>';
                }
                
                $html .= '</' . $this->_messagesFirstLevelTagName . '>';
                $html .= '</' . $this->_messagesSecondLevelTagName . '>';
                $html .= '</' . $this->_messagesFirstLevelTagName . '>';
                $html .= '</div>';
            }
            
            return $html;
        }
        return parent::getGroupedHtml();
    }
    
    /**
     * Return messages types as option hash
     * 
     * @return array
     */
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
     * Return whether the initialization JS script should be included in this block output
     * 
     * @return bool
     */
    public function getIncludeJsScript()
    {
        return $this->getDataSetDefault('include_js_script', true);
    }
    
    /**
     * Return the wrapper ID to use when on Ajax mode
     * 
     * @return string
     */
    public function getAjaxModeWrapperId()
    {
        if (!$this->hasData('ajax_mode_wrapper_id')) {
            /** @var $helper Mage_Helper_Core_Data */
            $helper = $this->helper('core');
            $this->setData('ajax_mode_wrapper_id', $helper->uniqHash('blcg-ajax-messages-wrapper-'));
        }
        return $this->_getData('ajax_mode_wrapper_id');
    }
}
