<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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

class BL_CustomGrid_Block_Widget_Form_Element_Dependence extends Mage_Adminhtml_Block_Abstract
{
    protected $_fieldsMap  = array();
    protected $_dependences = array();
    protected $_configOptions = array();
    
    protected function _toHtml()
    {
        if (empty($this->_dependences)) {
            return '';
        }
        return '<script type="text/javascript">'
            . "\n" . '//<![CDATA[' . "\n"
            . 'new blcg.Form.Element.DependenceController('
            . $this->_getDependencesJsonConfig()
            . (!empty($this->_configOptions) ? ', ' . $this->helper('core')->jsonEncode($this->_configOptions) : '')
            . ');'
            . "\n" . '//<![CDATA[' . "\n"
            . '</script>';
    }
    
    public function addFieldMap($fieldId, $fieldName=null)
    {
        if (is_array($fieldId)) {
            foreach ($fieldId as $subId => $subName) {
                $this->addFieldMap($subId, $subName);
            }
        } else {
            $this->_fieldsMap[$fieldName] = $fieldId;
        }
        return $this;
    }
    
    public function addFieldDependence($fieldName, $fieldNameFrom, $fromValues)
    {
        if (is_array($fieldName)) {
            foreach ($fieldName as $subName) {
                $this->addFieldDependence($subName, $fieldNameFrom, $fromValues);
            }
        } else {
            if (!is_array($fromValues)) {
                $fromValues = array($fromValues);
            }
            $this->_dependences[$fieldName][$fieldNameFrom] = $fromValues;
        }
        return $this;
    }
    
    public function addConfigOptions(array $options)
    {
        $this->_configOptions = array_merge($this->_configOptions, $options);
        return $this;
    }
    
    protected function _getDependencesJsonConfig()
    {
        $result = array();
        
        foreach ($this->_dependences as $to => $row) {
            foreach ($row as $from => $values) {
                if (isset($this->_fieldsMap[$from]) && isset($this->_fieldsMap[$to])) {
                    $result[$this->_fieldsMap[$to]][$this->_fieldsMap[$from]] = $values;
                }
            }
        }
        
        return $this->helper('core')->jsonEncode($result);
    }
}
