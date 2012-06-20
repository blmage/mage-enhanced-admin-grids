/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
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
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
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
 * @copyright  Copyright (c) 2011 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (typeof(blcg) == 'undefined') {
    var blcg = {};
}

blcg.Tools = {
    windowsNumber: 0,
    
    onAjaxSuccess: function(transport)
    {
        if (transport.responseText.isJSON()) {
            var response = transport.responseText.evalJSON();
            if (response.error) {
                throw response;
            } else if (response.ajaxExpired && response.ajaxRedirect) {
                setLocation(response.ajaxRedirect);
            }
        }
    },
    
    _openDialog: function(windowConfig, otherWindow)
    {
        if (!otherWindow && ($('blcg_window') && (typeof(Windows) != 'undefined'))) {
            Windows.focus('blcg_window');
            return;
        }
        
        var windowId = 'blcg_window' + (otherWindow ? '_'+(++this.windowsNumber) : '');
        windowConfig = Object.extend({
            draggable: false,
            resizable: false,
            closable: true,
            className: 'blcg',
            windowClassName: 'popup-window blcg-popup-window',
            title: '',
            width: 800,
            height: 450,
            zIndex: 1000,
            recenterAuto: true,
            hideEffect: Element.hide,
            showEffect: Element.show,
            id: windowId,
            onClose: this.closeDialog.bind(this)
        }, windowConfig || {}); 
        
        if (windowConfig.resizable) {
            windowConfig.windowClassName += ' blcg-resizable-popup-window';
        }
        
        var dialogWindow = Dialog.info(null, windowConfig);
        
        if (!otherWindow) {
            this.dialogWindow = dialogWindow;
            return;
        } else {
            return dialogWindow;
        }
    },
    
    openDialogFromUrl: function(url, windowConfig)
    {
        this._openDialog(windowConfig);
        new Ajax.Updater('modal_dialog_message', url, {evalScripts: true});
    },
    
    openDialogFromPost: function(url, data, windowConfig)
    {
        this._openDialog(windowConfig);
        new Ajax.Updater('modal_dialog_message', url, {
            method: 'post',
            parameters: $H(data).toQueryString(),
            evalScripts: true
        });
    },
    
    openDialogFromElement: function(elementId, windowConfig)
    {
        this._openDialog(windowConfig);
        $('modal_dialog_message').update($(elementId).innerHTML);
    },
    
    openIframeDialog: function(iframeUrl, windowConfig, otherWindow)
    {
        windowConfig.url = iframeUrl;
        return this._openDialog(windowConfig, otherWindow);
    },
    
    closeDialog: function(window)
    {
        if (!window) {
            window = this.dialogWindow;
        }
        if (window) {
            WindowUtilities._showSelect();
            window.close();
        }
    },
    
    execNodeJS: function(node)
    {
        var safari  = (navigator.userAgent.indexOf('Safari') != -1);
        var opera   = (navigator.userAgent.indexOf('Opera') != -1);
        var mozilla = (navigator.appName == 'Netscape');
        
        if (!node) {
            return;
        }
        
        var st = node.getElementsByTagName('SCRIPT');
        var strExec;
        
        for(var i=0; i<st.length; i++) {
            if (safari) {
                strExec = st[i].innerHTML;
                st[i].innerHTML = '';
            } else if (opera) {
                strExec = st[i].text;
                st[i].text = '';
            } else if (mozilla) {
                strExec = st[i].textContent;
                st[i].textContent = '';
            } else {
                strExec = st[i].text;
                st[i].text = '';
            }
            
            try {
                var x  = document.createElement('script');
                x.type = 'text/javascript';
                
                if (safari || opera || mozilla) {
                    x.innerHTML = strExec;
                } else {
                    x.text = strExec;
                }
                
                document.getElementsByTagName('head')[0].appendChild(x);
            } catch(e) {
                return;
            }
        }
    },
    
    submitContainerValues: function(container, url, additional, method)
    {
        container = $(container);
        if (!container) {
            return false;
        }
        
        var elements = [];
        var valid = true;
        
        container.getElementsBySelector('input, select').each(function(input){
            var isInput = (input.tagName.toUpperCase() == 'INPUT');
            var isCheckbox = (isInput && (input.readAttribute('type').toUpperCase() == 'CHECKBOX'));
            
            if (!input.disabled && (!isCheckbox || input.checked)) {
                elements.push(input);
                if (!Validation.validate(input)) {
                    valid = false;
                }
            }
        });
        
        if (valid) {
            var form = document.createElement('form');
            form.writeAttribute({
                'action': url,
                'method': ((method == 'GET') || (method == 'POST') ? method : 'POST')
            });
            document.body.appendChild(form);
            
            $A(elements).each(function(element){
                var input = document.createElement('input');
                input.writeAttribute({
                    'type':  'hidden',
                    'name':  element.readAttribute('name'),
                    'value': $F(element)
                });
                form.appendChild(input);
            });
            
            additional = $H(additional || {});
            additional.each(function(option){
                var input = document.createElement('input');
                input.writeAttribute({
                    'type':  'hidden',
                    'name':  option.key,
                    'value': option.value
                });
                form.appendChild(input);
            });
            
            form.submit();
            return true;
        } else {
            return false;
        }
    }
}

blcg.CollectionRenderer = {};
blcg.CollectionRenderer.Select = Class.create();
blcg.CollectionRenderer.Select.prototype = {
    initialize: function(select, renderersConfig, configButtonId, rendererTargetId, configUrl)
    {
        this.select = $(select);
        this.configUrl = configUrl;
        
        this.renderersConfig = $H({});
        $A(renderersConfig).each(function(renderer){
            if (renderer.code) {
                this.renderersConfig[renderer.code] = Object.extend({
                    code: '',
                    isCustomizable: false
                }, renderer);
            }
        }.bind(this));
        this.renderersParams = $H({});
        
        this.configButton = $(configButtonId);
        this.configButton.hide();
        this.rendererTargetId = rendererTargetId;
        
        var code = $F(this.select);
        if (code && this.renderersConfig[code]) {
            this.currentRenderer = code;
            this.renderersParams[code] = $F(this.rendererTargetId);
        } else {
            this.currentRenderer = null;
            $(this.rendererTargetId).value = '';
        }
        
        this.onRendererChange();
        
        if (this.select.tagName.toUpperCase() == 'SELECT') {
            this.select.observe('change', this.onRendererChange.bind(this));
        }
    },
    
    enableConfigButton: function(code, url, windowConfig)
    {
        if (this.currentRenderer) {
            this.renderersParams[this.currentRenderer] = $F(this.rendererTargetId);
        }
        if (this.renderersParams[code]) {
            $(this.rendererTargetId).value = this.renderersParams[code];
        } else {
            $(this.rendererTargetId).value = '';
        }
        
        this.configButton.show();
        this.configButton.stopObserving('click');
        this.configButton.observe('click', function(){ 
            blcg.Tools.openDialogFromPost(url, {
              'code': code,
              'renderer_target_id':  this.rendererTargetId,
              'params': $F(this.rendererTargetId)
            }, windowConfig);
        }.bind(this));
    },
    
    disableConfigButton: function()
    {
        if (this.currentRenderer) {
            this.renderersParams[this.currentRenderer] = $F(this.rendererTargetId);
        }
        $(this.rendererTargetId).value = '';
        this.configButton.stopObserving('click');
        this.configButton.hide();
    },
    
    onRendererChange: function()
    {
        var code = $F(this.select);
        if (code && this.renderersConfig[code]) {
            var renderer = this.renderersConfig[code];
            if (renderer.isCustomizable) { 
                this.enableConfigButton(renderer.code, this.configUrl, renderer.windowConfig);
            } else {
                this.disableConfigButton();
            }
            this.currentRenderer = code;
        } else {
            this.disableConfigButton();
            this.currentRenderer = '';
        }
    }
}

blcg.Renderer = {};
blcg.Renderer.Config = Class.create();
blcg.Renderer.Config.prototype = {
    initialize: function(formEl, rendererTargetId)
    {
        this.formEl = formEl;
        this.optionValues = new Hash({});
        this.rendererTargetId = rendererTargetId;
    },
    
    insertRenderer: function()
    {
        var rendererOptionsForm = new varienForm(this.formEl);
        if (!rendererOptionsForm.validator
            || (rendererOptionsForm.validator && rendererOptionsForm.validator.validate())) {
            var formElements = [];
            var i = 0;
            Form.getElements($(this.formEl)).each(function(e) {
                if(!e.hasClassName('skip-submit')) {
                    formElements[i] = e;
                    i++;
                }
            });
            
            new Ajax.Request($(this.formEl).action, {
                parameters: Form.serializeElements(formElements),
                onComplete: function(transport){
                    try {
                        blcg.Tools.onAjaxSuccess(transport);
                        this.updateContent(transport.responseText);
                    } catch(e) {
                        alert(e.message);
                    }
                }.bind(this)
            });
            
            blcg.Tools.closeDialog();
        }
    },
    
    updateContent: function(content)
    {   
        var target = $(this.rendererTargetId);
        target.value = content;
    }
}

blcg.Attribute = {};
blcg.Attribute.Select = Class.create();
blcg.Attribute.Select.prototype = {
    initialize: function(select, attributesConfig, renderersConfig, configButtonId, rendererTargetId, configUrl, editableConfig)
    {
        this.select = $(select);
        this.configUrl = configUrl;
        
        this.attributesConfig = $H({});
        $A(attributesConfig).each(function(attribute){
            if (attribute.code) {
                this.attributesConfig[attribute.code] = Object.extend({
                    code: '',
                    rendererCode: '',
                    editableValues: false
                }, attribute);
            }
        }.bind(this));
        
        this.renderersConfig = $H({});
        $A(renderersConfig).each(function(renderer){
            if (renderer.code) {
                this.renderersConfig[renderer.code] = Object.extend({
                    code: '',
                    isCustomizable: false
                }, renderer);
            }
        }.bind(this));
        this.renderersParams = $H({});
        
        this.editableConfig = Object.extend({
            'editableContainerId': false,
            'editableCheckboxId': false,
            'yesMessageText': '',
            'noMessageText': ''
        }, editableConfig);
        
        this.configButton = $(configButtonId);
        this.configButton.hide();
        this.rendererTargetId = rendererTargetId;
        
        var code = $F(this.select);
        if (code && this.attributesConfig[code]) {
            this.currentAttribute = code;
            this.renderersParams[code] = $F(this.rendererTargetId);
        } else {
            this.currentAttribute = null;
            $(this.rendererTargetId).value = '';
        }
        
        this.onAttributeChange();
        this.select.observe('change', this.onAttributeChange.bind(this));
    },
    
    enableConfigButton: function(code, rendererCode, url, windowConfig)
    {
        if (this.currentAttribute) {
            this.renderersParams[this.currentAttribute] = $F(this.rendererTargetId);
        }
        if (this.renderersParams[code]) {
            $(this.rendererTargetId).value = this.renderersParams[code];
        } else {
            $(this.rendererTargetId).value = '';
        }
        
        this.configButton.show();
        this.configButton.stopObserving('click');
        this.configButton.observe('click', function(){ 
            blcg.Tools.openDialogFromPost(url, {
              'code': rendererCode,
              'renderer_target_id':  this.rendererTargetId,
              'params': $F(this.rendererTargetId)
            }, windowConfig);
        }.bind(this));
    },
    
    disableConfigButton: function()
    {
        if (this.currentAttribute) {
            this.renderersParams[this.currentAttribute] = $F(this.rendererTargetId);
        }
        $(this.rendererTargetId).value = '';
        this.configButton.stopObserving('click');
        this.configButton.hide();
    },
    
    updateEditableConfig: function(code)
    {
        var isEditable = false;
        if (this.currentAttribute) {
            isEditable = this.attributesConfig[this.currentAttribute].editableValues;
        }
        if (this.editableConfig.editableContainerId) {
            var container = $(this.editableConfig.editableContainerId);
            if (container) {
                var checkbox = false;
                if (this.editableConfig.editableCheckboxId) {
                    checkbox = $(this.editableConfig.editableCheckboxId);
                }
                if (checkbox) {
                    checkbox.disabled = !isEditable;
                } else {
                    container.innerHTML = (isEditable ? this.editableConfig.yesMessageText : this.editableConfig.noMessageText);
                }
            }
        }
    },
    
    onAttributeChange: function()
    {
        var code = $F(this.select);
        
        if (code && this.attributesConfig[code]) {
            var attribute = this.attributesConfig[code];
            var renderer  = (attribute.rendererCode ? this.renderersConfig[attribute.rendererCode] : null);
            if (renderer && renderer.isCustomizable) { 
                this.enableConfigButton(attribute.code, renderer.code, this.configUrl, renderer.windowConfig);
            } else {
                this.disableConfigButton();
            }
            this.currentAttribute = code;
        } else {
            this.disableConfigButton();
            this.currentAttribute = '';
        }
        
        this.updateEditableConfig();
    }
}

blcg.FormElementDependenceController = Class.create();
blcg.FormElementDependenceController.prototype = {
    initialize : function (elementsMap, config)
    {
        // Elements states (enabled or disabled)
        this.idsStates = $H({});
        // Inverted elements map
        this.invertedMap = $A({});
        
        this.elementsMap = elementsMap;
        this._config = Object.extend({
            levels_up: 1 // How many levels up to travel when toggling element
        }, config || {});
        
        for (var idTo in elementsMap) {
            this.idsStates[idTo] = true;
            for (var idFrom in elementsMap[idTo]) {
                if (!this.invertedMap[idFrom]) {
                    this.invertedMap[idFrom] = $A({});
                }
                this.invertedMap[idFrom].push(idTo);
                this.idsStates[idFrom] = true;
                Event.observe($(idFrom), 'change', this.trackChange.bindAsEventListener(this, idTo));
                this.trackChange(null, idTo);
            }
        }
    },
    
    trackChange : function(e, idTo)
    {
        var valuesFrom = this.elementsMap[idTo];
        
        // Define whether the target should show up
        var shouldShowUp = true;
        for (var idFrom in valuesFrom) {
            if (!this.idsStates[idFrom]
                || (valuesFrom[idFrom].indexOf($(idFrom).value) == -1)) {
                shouldShowUp = false;
            }
        }
        // Toggle target row
        if (shouldShowUp) {
            $(idTo).up(this._config.levels_up).select('input', 'select').each(function (item) {
                if (!item.type || item.type != 'hidden') { // Don't touch hidden inputs, because they may have custom logic
                    item.disabled = false;
                }
            });
            $(idTo).up(this._config.levels_up).show();
            this.idsStates[idTo] = true;
        } else {
            $(idTo).up(this._config.levels_up).select('input', 'select').each(function (item){
                if (!item.type || item.type != 'hidden') { // Don't touch hidden inputs, because they may have custom logic
                    item.disabled = true;
                }
            });
            $(idTo).up(this._config.levels_up).hide();
            this.idsStates[idTo] = false;
        }
        
        // Apply chaining
        if (this.invertedMap[idTo]) {
            this.invertedMap[idTo].each(function(subIdTo){
                this.trackChange(null, subIdTo, this.elementsMap[subIdTo]);
            }.bind(this));
        }
    }
}

/*
* Table drag'n'drop
* jQuery version :
* Copyright (c) Denis Howlett <denish@isocra.com>
* Licensed like jQuery, see http://docs.jquery.com/License
* Prototype version (including various adaptions) :
* Copyright (c) Benoît Leulliette <benoit.leulliette@gmail.com>
* http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*/

blcg.TableDnd = Class.create();
blcg.TableDnd.prototype = {
    initialize: function(table, options)
    {
        // Keep hold of the current table being dragged
        this.currentTable = null,
        // Keep hold of the current drag object if any
        this.dragObject = null,
        // The current mouse offset
        this.mouseOffset = null,
        // Remember the old value of Y so that we don't do too much processing
        this.oldY = 0,
        
        // DnD config values
        this.tableDndConfig = Object.extend({
            onDragStyle: null,
            onDropStyle: null,
            onDragClass: 'dnd_whileDrag',
            onDrop: null,
            onExchange: null,
            onDragStart: null,
            scrollAmount: 5,
            dragHandle: null
        }, options || {});
        
        this.makeDraggable(table);
        
        Event.observe(document, 'mousemove', function(event){ this.onMouseMove(event); }.bind(this));
        Event.observe(document, 'mouseup', function(event){ this.onMouseUp(event); }.bind(this));
    },
    
    makeDraggable: function(table)
    {
        var table = $(table);
        
        if (this.tableDndConfig.dragHandle) {
            // We only need to add the event to the specified cells
            var cells = table.getElementsBySelector('td.' + this.tableDndConfig.dragHandle);
            cells.each(function(cell){
                Event.observe(cell, 'mousedown', function(event){
                    this.dragObject   = cell.parentNode;
                    this.currentTable = table;
                    this.mouseOffset  = this.getMouseOffset(cell, event);
                    if (this.tableDndConfig.onDragStart) {
                        // Call the onDrop method if there is one
                        this.tableDndConfig.onDragStart(table, cell);
                    }
                    return false;
                }.bind(this));
            }.bind(this));
        } else {
            // For backwards compatibility, we add the event to the whole row
            var rows = table.getElementsBySelector('tr'); // Get all the rows as a wrapped set
            rows.each(function(row){
                if (!row.hasClassName('nodrag')) {
                    Event.observe(row, 'mousedown', function(event){
                        if (event.target.tagName.toUpperCase() == 'TD') {
                            this.dragObject   = row;
                            this.currentTable = table;
                            this.mouseOffset  = this.getMouseOffset(row, event);
                            if (this.tableDndConfig.onDragStart) {
                                // Call the onDrop method if there is one
                                this.tableDndConfig.onDragStart(table, row);
                            }
                        }
                    }.bind(this));
                    $(row).setStyle({cursor: 'move'});
                }
            }.bind(this));
        }
    },
    
    registerNewRow: function(row)
    {
        var row   = $(row);
        var table = row.up('table');
        
        if (this.tableDndConfig.dragHandle) {
            // We only need to add the event to the specified cells
            var cells = row.getElementsBySelector('td.' + this.tableDndConfig.dragHandle);
            cells.each(function(cell){
                Event.observe(cell, 'mousedown', function(event){
                    this.dragObject   = cell.parentNode;
                    this.currentTable = table;
                    this.mouseOffset  = this.getMouseOffset(cell, event);
                    if (this.tableDndConfig.onDragStart) {
                        // Call the onDrop method if there is one
                        this.tableDndConfig.onDragStart(table, cell);
                    }
                    return false;
                }.bind(this));
            }.bind(this));
        } else {
            // For backwards compatibility, we add the event to the whole row
            if (!row.hasClassName('nodrag')) {
                Event.observe(row, 'mousedown', function(event){
                    if (event.target.tagName.toUpperCase() == 'TD') {
                        this.dragObject   = row;
                        this.currentTable = table;
                        this.mouseOffset  = this.getMouseOffset(row, event);
                        if (this.tableDndConfig.onDragStart) {
                            // Call the onDrop method if there is one
                            this.tableDndConfig.onDragStart(table, row);
                        }
                    }
                }.bind(this));
                row.setStyle({cursor: 'move'});
            }
        }
    },
    
    getMouseCoords: function(event)
    {
        return Event.pointer(event);
    },
    
    getMouseOffset: function(target, event)
    {
        event = event || window.event;
        var docPos   = this.getPosition(target);
        var mousePos = this.getMouseCoords(event);
        return {x: mousePos.x - docPos.x, y: mousePos.y - docPos.y};
    },
    
    getPosition: function(event)
    {
        var left = 0;
        var top  = 0;
        
        if (event.offsetHeight == 0) {
            event = event.firstChild;
        }
        
        while (event.offsetParent) {
            left += event.offsetLeft;
            top  += event.offsetTop;
            event = event.offsetParent;
        }
        
        left += event.offsetLeft;
        top  += event.offsetTop;
        
        return {x: left, y: top};
    },
    
    onMouseMove: function(event)
    {
        if (this.dragObject == null) {
            return;
        }
        
        var dragObj  = $(this.dragObject);
        var config   = this.tableDndConfig;
        var mousePos = this.getMouseCoords(event);
        var y = mousePos.y - this.mouseOffset.y;
        
        // Auto scroll the window
        var yOffset = window.pageYOffset;
        if (document.all) {
            // Windows version
            if ((typeof document.compatMode != 'undefined')
                && (document.compatMode != 'BackCompat')) {
                yOffset = document.documentElement.scrollTop;
            } else if (typeof document.body != 'undefined') {
                yOffset=document.body.scrollTop;
            }
        }
        
        if (mousePos.y - yOffset < config.scrollAmount) {
            window.scrollBy(0, -config.scrollAmount);
        } else {
            var windowHeight = window.innerHeight ? window.innerHeight
                : document.documentElement.clientHeight ? document.documentElement.clientHeight : document.body.clientHeight;
            if (windowHeight - (mousePos.y - yOffset) < config.scrollAmount) {
                window.scrollBy(0, config.scrollAmount);
            }
        }
        
        if (y != this.oldY) {
            // Update the old value
            this.oldY = y;
            // Update the style to show we're dragging
            if (config.onDragClass) {
                dragObj.addClassName(config.onDragClass);
            } else if (config.onDragStyle) {
                dragObj.setStyle(config.onDragStyle);
            }
            
            // If we're over a row then move the dragged row to there so that the user sees the effect dynamically
            var currentRow = this.findDropTargetRow(dragObj, y);
            if (currentRow) {
                // Work out if we're going up or down...
                var movingDown = true;
                var previousRow, observedRow = dragObj;
                
                while (previousRow = observedRow.previous('tr')) {
                    if (previousRow == currentRow) {
                        movingDown = false;
                        break;
                    }
                    observedRow = previousRow;
                }
                
                if (movingDown && (this.dragObject != currentRow)) {
                    this.dragObject.parentNode.insertBefore(this.dragObject, currentRow.nextSibling);
                    if (config.onExchange) {
                        config.onExchange(this.dragObject, currentRow);
                    }
                } else if (!movingDown && (this.dragObject != currentRow)) {
                    this.dragObject.parentNode.insertBefore(this.dragObject, currentRow);
                    if (config.onExchange) {
                        config.onExchange(this.dragObject, currentRow);
                    }
                }
            }
        }
        
        return false;
    },
    
    findDropTargetRow: function(draggedRow, y)
    {
        var rows = this.currentTable.rows;
        for (var i=0, l=rows.length; i<l; i++) {
            var row  = rows[i];
            var rowY = this.getPosition(row).y;
            var rowH = parseInt(row.offsetHeight) / 2;
            
            if (row.offsetHeight == 0) {
                rowY = this.getPosition(row.firstChild).y;
                rowH = parseInt(row.firstChild.offsetHeight) / 2;
            }
            
            // Because we always have to insert before, we need to offset the height a bit
            if ((y > rowY - rowH) && (y < (rowY + rowH))) { 
                // That's the row we're over
                // If it's the same as the current row, ignore it
                if (row == draggedRow) {
                    return null;
                }
                
                var config = this.tableDndConfig;
                if (config.onAllowDrop) {
                    if (config.onAllowDrop(draggedRow, row)) {
                        return row;
                    } else {
                        return null;
                    }
                } else {
                    // If a row has nodrop class, then don't allow dropping (inspired by John Tarr and Famic)
                    var nodrop = $(row).hasClassName('nodrop');
                    if (!nodrop) {
                        return row;
                    } else {
                        return null;
                    }
                }
                return row;
            }
        }
        return null;
    },
    
    onMouseUp: function(event)
    {
        if (this.currentTable && this.dragObject) {
            var droppedRow = this.dragObject;
            var config = this.tableDndConfig;
            
            // If we have a dragObject, then we need to release it
            // The row will already have been moved to the right place so we just reset stuff
            if (config.onDragClass) {
                $(droppedRow).removeClassName(config.onDragClass);
            } else if (config.onDropStyle) {
                $(droppedRow).setStyle(config.onDropStyle);
            }
            
            this.dragObject   = null;
            if (config.onDrop) {
                // Call the onDrop method if there is one
                config.onDrop(this.currentTable, droppedRow);
            }
            this.currentTable = null; // Let go of the table too
        }
    }
}

blcg.CustomGridExport = Class.create();
blcg.CustomGridExport.prototype = {
    initialize: function (containerId, errorTexts, additional)
    {
        this.containerId  = containerId;
        this.errorTexts   = $H(errorTexts || {});
        this.additional   = additional || {};
        
        this.initElements();
    },
    
    initElements: function()
    {
        this.container    = $(this.containerId);
        this.formatSelect = $(this.containerId + '-format');
        this.sizeSelect   = $(this.containerId + '-size');
        this.customSizeInput = $(this.containerId + '-custom-size');
        this.fromResultInput = $(this.containerId + '-from-result');
        
        this.customSizeInput.hide();
        this.customSizeInput.disabled = true;
        this.customSizeInput.observe('change', function(){
            this.verifyInput(this.customSizeInput, '');
        }.bind(this));
        this.fromResultInput.observe('change', function(){
            this.verifyInput(this.fromResultInput, '1');
        }.bind(this));
        
        this.sizeSelect.observe('change', function(){
            if ($F(this.sizeSelect) != '') {
                this.customSizeInput.hide();
                this.customSizeInput.disabled = true;
            } else {
                this.customSizeInput.show();
                this.customSizeInput.disabled = false;
            }
        }.bind(this));
    },
    
    verifyInput: function(input, defaultValue)
    {
        var value = $F(input);
        if (value != '') {
            var intValue = parseInt(value);
            if (isNaN(intValue) || (value != intValue.toString()) || (intValue <= 0)) {
                input.value = defaultValue;
            }
        }
    },
    
    doExport: function()
    {
        if ($F(this.formatSelect) == '') {
            if (this.errorTexts.get('format')) {
                alert(this.errorTexts.get('format'));
            }
            return false;
        }
        if (($F(this.sizeSelect) == '')
            && ($F(this.customSizeInput) == '')) {
            if (this.errorTexts.get('custom-size')) {
                alert(this.errorTexts.get('custom-size'));
            }
            return false;
        }
        return blcg.Tools.submitContainerValues(this.container, $F(this.formatSelect), this.additional, 'GET');        
    }
}

blcg.CustomGridConfig = Class.create();
blcg.CustomGridConfig.prototype = {
    initialize: function(containerId, saveUrl, rowClassName, orderInputId, newRowId, newRowClassNames, newRowColumns, config)
    {
        // Hash of columns by column ID (store corresponding table rows)
        this.columns = $H({});
        // Array of all columns IDs
        this.columnsIds = $A({});
        // Hash of visibility checkboxes by column ID
        this.checkboxes = $H({});
        // Hash of order inputs by column ID
        this.orderInputs = $H({});
        // Array of columns IDs having their visibility checkbox checked
        this.checkedValues = $A({});
        // Next new column ID
        this.nextId = 0;
        
        // Container ID
        this.containerId = containerId;
        // Save grid URL
        this.saveUrl = saveUrl;
        // Class name used to initialize existing columns rows
        this.rowClassName = rowClassName; 
        // ID of the inputs which contain columns orders
        this.orderInputId = orderInputId;
        // Template for new column's table row's ID
        this.newRowId = newRowId;
        // Templates for new column's table row's class names
        this.newRowClassNames = $A(newRowClassNames);
        // Array of templates for new column's table row's cells
        this.newRowColumns = $A(newRowColumns);
        for (var i=0, l=this.newRowColumns.length; i<l; ++i) {
            // These are hashes with "template" and "class names" for each cell
            this.newRowColumns[i] = $H(this.newRowColumns[i]);
        }
        
        this.config = Object.extend({
            idTemplate: '{{id}}', // String to replace by new IDs in new rows templates
            jsIdTemplate: '{{js_id}}', // String to replace by new JS ids in new rows templates
            orderTemplate: '{{order}}', // String to replace by new orders in new rows templates
            errorText: '', // Error message to display if no checkboxes are checked on submit
            maxOrder: 0, // The  maximum order for existing columns
            orderPitch: 1, // The number to add to maximum order to get new columns orders
            useDnd: false // Flag to tell if we should use drag'n'drop
        }, config || {});
        this.config.originalMaxOrder = this.config.maxOrder;
        
        // Make regexes from ID and order template
        this.config.idRegex = new RegExp(this.config.idTemplate, 'g');
        this.config.jsIdRegex = new RegExp(this.config.jsIdTemplate, 'g');
        this.config.orderRegex = new RegExp(this.config.orderTemplate, 'g');
                
        this.initElements();
        this.initColumns();
        this.initCheckboxes();
        this.updateCount();
    },
    
    makeTableUnselectable: function(unselectable)
    {
        this.rows.setStyle({
            'MozUserSelect': (unselectable ? 'none' : ''), // FF
            'KhtmlUserSelect': (unselectable ? 'none' : ''), // Safari, Chrome
            'userSelect': (unselectable ? 'none' : '') // CSS 3
        });
        
        if (Prototype.Browser.IE) {
            // IE
            this.rows.ondrag = (unselectable ? function(){ return false; } : null)
            this.rows.onselectstart = (unselectable ? function(){ return false; } : null);
        } else if (Prototype.Browser.Opera) {
            // Opera
            this.rows.writeAttribute('unselectable', (unselectable ? 'on' : 'off'));
        }
    },
    
    initElements: function()
    {
        this.container = $(this.containerId);
        this.table     = $(this.containerId + '-table');
        this.rows      = $(this.containerId + '-table-rows');
        this.count     = $(this.containerId + '-count');
        
        if (this.config.useDnd) {
            // Drag'n'drop if needed
            this.tableDnd  = new blcg.TableDnd(this.table, {
                dragHandle: 'blcg-drag-handle',
                onDragStart: function(){  this.makeTableUnselectable(true); }.bind(this),
                onExchange: function(from, to){ this.exchangeRows(from, to); }.bind(this),
                onDrop: function(){ this.makeTableUnselectable(false); }.bind(this)
            });
        }
    },
    
    getColumnIdFromRowId: function(rowId)
    {
        var regex  = new RegExp(this.newRowId.replace(this.config.idTemplate, '(-?[0-9]+)'));
        var result = regex.exec(rowId);
        if (result.length) {
            return result[1];
        } else {
            return -1;
        }
    },
    
    exchangeRows: function(from, to)
    {
        var from = this.columnsIds.indexOf(this.getColumnIdFromRowId(from.id));
        var to   = this.columnsIds.indexOf(this.getColumnIdFromRowId(to.id));
        
        if ((from != -1) && (to != -1)) {
            var add = (from > to ? -1 : 1); 
            
            for (i=from+add; (add>0 ? i<=to : i>=to); i+=add) {
                var toId   = this.columnsIds[i];
                var fromId = this.columnsIds[i-add];
                
                // Exchange decoration
                if (i%2 == 0) {
                    this.columns[fromId].removeClassName('even').addClassName('odd');
                    this.columns[toId].removeClassName('odd').addClassName('even');
                } else {
                    this.columns[fromId].removeClassName('odd').addClassName('even');
                    this.columns[toId].removeClassName('even').addClassName('odd');
                }
                
                // Exchange position in IDs array
                this.columnsIds[i] = fromId;
                this.columnsIds[i-add] = toId;
                
                // Exchange orders values
                buffer = this.orderInputs[fromId].getValue();
                this.orderInputs[fromId].value = this.orderInputs[toId].value;
                this.orderInputs[toId].value = buffer;
            }
        }
    },
    
    getNextNewColumnId: function()
    {
        return '' + --this.nextId;
    },
    
    getNextNewColumnOrder: function()
    {
        this.config.maxOrder += this.config.orderPitch;
        return this.config.maxOrder;
    },
    
    _parseRowTemplate: function(tpl, nextId, nextOrder)
    {
        tpl = tpl.replace(this.config.idRegex, nextId);
        tpl = tpl.replace(this.config.jsIdRegex, (''+nextId).replace('-', '_'));
        tpl = tpl.replace(this.config.orderRegex, nextOrder);
        return tpl;
    },
    
    _addRowCell: function(row, template, cellId, nextId, nextOrder, classNames)
    {
        var cell = $(row.insertCell(-1));
        cell.innerHTML = this._parseRowTemplate(template, nextId, nextOrder);
        blcg.Tools.execNodeJS(cell);
        
        if (cellId) {
            cell.id = this._parseRowTemplate(cellId, nextId, nextOrder);
        }
        for (var i=0, l=classNames.length; i<l; ++i) {
            cell.addClassName(classNames[i]);
        }
        
        return cell;
    },    
    
    addColumn: function()
    {
        var nextId = this.getNextNewColumnId();
        var nextOrder = this.getNextNewColumnOrder();
        var row = $(this.rows.insertRow(-1));
        row.writeAttribute('id', this._parseRowTemplate(this.newRowId, nextId, nextOrder));
        for (var i=0, l=this.newRowClassNames.length; i<l; ++i) {
            row.addClassName(this._parseRowTemplate(this.newRowClassNames[i], nextId, nextOrder));
        }
        for (var i=0, l=this.newRowColumns.length; i<l; ++i) {
            this._addRowCell(row, this.newRowColumns[i].get('template'), this.newRowColumns[i].get('id'), nextId, nextOrder, $A(this.newRowColumns[i].get('classNames')));
        }
        
        this.columns[nextId] = row;
        this.columnsIds.push(nextId);
        
        row.getElementsBySelector('.visible-checkbox').each(function(cb){
            cb.observe('click', function(){
                if (cb.checked) {
                    this.checkedValues.push(nextId);
                } else {
                    var i = this.checkedValues.indexOf(nextId);
                    if (i != -1) {
                        this.checkedValues.splice(i, 1);
                    }
                } 
                this.updateCount(); 
            }.bind(this));
            
            this.checkboxes[nextId] = cb;
            
            if (cb.checked) {
                this.checkedValues.push(nextId);
            }
        }.bind(this));
        this.orderInputs[nextId] = $(this.orderInputId.replace(this.config.idRegex, nextId));
        
        this.updateCount();
        this.redecorateColumns();
        
        if (this.config.useDnd) {
            this.tableDnd.registerNewRow(row);
        }
    },
    
    redecorateColumns: function()
    {
        var i = 0;
        this.columnsIds.each(function(columnId){
            if (i++ % 2 == 1) {
                this.columns[columnId].removeClassName('odd').addClassName('even');
            } else {
                this.columns[columnId].removeClassName('even').addClassName('odd');
            }
        }.bind(this));
    },
    
    initColumns: function()
    {
        this.table.getElementsBySelector('.' + this.rowClassName).each(function(row){
            var columnId = this.getColumnIdFromRowId(row.id);
            this.columns[columnId] = row;
            this.orderInputs[columnId] = $(this.orderInputId.replace(this.config.idRegex, columnId));
            this.columnsIds.push(columnId);
        }.bind(this));
    },
    
    initCheckboxes: function()
    {
        this.columnsIds.each(function(columnId){
            var column = this.columns[columnId];
            column.getElementsBySelector('.visible-checkbox').each(function(cb){
                cb.observe('click', function(){
                    if (cb.checked) {
                        this.checkedValues.push(columnId);
                    } else {
                        var i = this.checkedValues.indexOf(columnId);
                        if (i != -1) {
                            this.checkedValues.splice(i, 1);
                        }
                    } 
                    this.updateCount(); 
                }.bind(this));
                
                this.checkboxes[columnId] = cb;
                
                if (cb.checked) {
                    this.checkedValues.push(columnId);
                }
            }.bind(this));
        }.bind(this));
    },
    
    updateCount: function()
    {
        this.checkedValues = this.checkedValues.uniq();
        this.count.update(this.checkedValues.size());
    },
    
    selectAll: function()
    {
        this.columnsIds.each(function(columnId){ this.checkboxes[columnId].checked = true; }.bind(this));
        this.checkedValues = this.columnsIds;
        this.updateCount();
        return false;
    },
    
    unselectAll: function()
    {
        this.columnsIds.each(function(columnId){ this.checkboxes[columnId].checked = false; }.bind(this));
        this.checkedValues = $A({});
        this.updateCount();
        return false;
    },
    
    deleteColumn: function(columnId)
    {
        columnId = '' + columnId;
        var i = this.columnsIds.indexOf(columnId);
        if (i != -1) {
            this.columns[columnId].remove();
            this.columnsIds.splice(i, 1);
            this.columns.unset(columnId);
            this.checkboxes.unset(columnId);
            var j = this.checkedValues.indexOf(columnId);
            if (j != -1) {
                this.checkedValues.splice(j, 1);
                this.updateCount();
            }
            this.redecorateColumns();
        }
    },
    
    saveGrid: function()
    {
        if (this.checkedValues.size() == 0) {
            if (this.config.errorText != '') {
                alert(this.config.errorText);
            }
            return false;
        }
        
        return blcg.Tools.submitContainerValues(this.container, this.saveUrl);
    }
}

blcg.GridEditor = Class.create();
blcg.GridEditor.prototype = {
    initialize: function(tableId, cells, rowsIds, additionalParams, globalParams, errorMessages)
    {
        this.tableId = tableId;
        this.table   = $(tableId);
        this.cells   = $A(cells);
        this.rowsIds = $A(rowsIds);
        this.additionalParams = (Object.isArray(additionalParams) ? $H({}) : $H(additionalParams));
        this.globalParams     = (Object.isArray(globalParams) ? $H({}) : $H(globalParams));
        this.errorMessages    = $H(errorMessages);
        this.editWindow = null;
        
        if ((!this.table)
            || (this.cells.length == 0) 
            || (this.rowsIds.length == 0)) {
            return false;
        }
        
        this.initCells();
    },
    
    initCells: function()
    {
        this.cellsRowNums  = $H({});
        this.cellsConfigs  = $H({});
        this.editedCell    = null;
        this.previousValue = null;
        this.hasPreviousValue = false;
        
        this.table.up().getElementsBySelector('#' + this.tableId + ' > tbody > tr').each(function(row, rowIndex){
            if (this.rowsIds[rowIndex]) {
                row.childElements('td').each(function(cell, cellIndex){
                    if (this.cells[cellIndex]) {
                        var cellId = cell.identify();
                        this.cellsRowNums[cellId] = rowIndex;
                        this.cellsConfigs[cellId] = this.cells[cellIndex];
                        
                        cell.observe('mouseover', function(){
                            this.onCellMouseOver(cell);
                        }.bind(this)).observe('mouseout', function(){
                            this.onCellMouseOut(cell);
                        }.bind(this));
                    }
                }.bind(this));
            }
        }.bind(this));
        
        this.hoveredCell = null;
        this.mouseCell   = null;
        this.hoverStart  = null;
        this.hoverStop   = null;
    },
    
    compareCells: function(cell1, cell2)
    {
        return (cell1 && cell2 ? cell1.identify() == cell2.identify() : false);
    },
    
    createDiv: function(id, classNames)
    {
        var div = $(document.createElement('DIV'));
        if (id) {
            div.id = id;
        }
        if (classNames) {
            $A(classNames).each(function(className){
                div.addClassName(className);
            });
        }
        return div;
    },
    
    getCellOverlayId: function(cell)
    {
        return 'blcg-column-editor-overlay-' + cell.identify();
    },
    
    createCellOverlay: function(cell)
    {
        var overlay = this.createDiv(this.getCellOverlayId(cell), ['blcg-column-editor-overlay']);
        
        overlay.setStyle({
            'display': 'none',
            'position': 'absolute'
        }).observe('mouseover', function(){
            this.onCellMouseOver(cell);
        }.bind(this)).observe('mouseout', function(){
            this.onCellMouseOut(cell);
        }.bind(this));
        
        document.body.appendChild(overlay);
        return overlay;
    },
    
    getCellOverlay: function(cell)
    {
        var overlay = $(this.getCellOverlayId(cell));
        return (overlay ? overlay : this.createCellOverlay(cell));       
    },
    
    positionCellOverlay: function(cell, overlay, mustShow)
    {
        overlay = (overlay ? overlay : this.getCellOverlay(cell));
        var offset   = cell.cumulativeOffset(),
            csOffset = cell.cumulativeScrollOffset(),
            width;
        offset.left -= csOffset.left;
        
        if (!overlay.visible()) {
            overlay.show();
            width = overlay.getWidth();
            overlay.hide();
        } else {
            width = overlay.getWidth();
        }
        
        overlay.setStyle({
            'position': 'absolute',
            'top': (offset.top + 2) + 'px',
            'left': (offset.left + cell.getWidth() - width-3) + 'px'
        });
        
        if (mustShow) {
            overlay.show();
        }
    },
    
    fillCellOverlay: function(cell, overlay)
    {
        overlay = (overlay ? overlay : this.getCellOverlay(cell));
               
        if (cell.hasClassName('blcg-column-editor-editing')) {
            if (!overlay.hasClassName('blcg-column-editor-overlay-container-editing')) {
                overlay.innerHTML = '';
                var div = this.createDiv(null, ['blcg-column-editor-overlay-validate']);
                div.observe('click', function(){ this.validateEdit(); }.bind(this));
                overlay.appendChild(div);
                div = this.createDiv(null, ['blcg-column-editor-overlay-cancel']);
                div.observe('click', function(){ this.cancelEdit(); }.bind(this));
                overlay.appendChild(div);
                overlay.removeClassName('blcg-column-editor-overlay-container-idle');
                overlay.addClassName('blcg-column-editor-overlay-container-editing');
            }
        } else if (!overlay.hasClassName('blcg-column-editor-overlay-container-idle')) {
            overlay.innerHTML = '';
            var div = this.createDiv(null, ['blcg-column-editor-overlay-edit']);
            div.observe('click', function(){ this.editCell(cell); }.bind(this));
            overlay.appendChild(div);
            overlay.removeClassName('blcg-column-editor-overlay-container-editing');
            overlay.addClassName('blcg-column-editor-overlay-container-idle');
        }
    },
    
    showCellOverlay: function(cell, overlay)
    {
        overlay = (overlay ? overlay : this.getCellOverlay(cell));
        this.fillCellOverlay(cell, overlay);
        this.positionCellOverlay(cell, overlay, true);
    },
    
    hideCellOverlay: function(cell, overlay)
    {
        overlay = (overlay ? overlay : this.getCellOverlay(cell));
        overlay.hide();
    },
    
    stopHoverStart: function()
    {
        if (this.hoverStart) {
            window.clearTimeout(this.hoverStart);
            this.hoverStart = null;
        }
    },
    
    stopHoverEnd: function()
    {
        if (this.hoverEnd) {
            window.clearTimeout(this.hoverEnd);
            this.hoverEnd = null;
        }
    },
    
    onCellMouseOver: function(cell)
    {
        this.mouseCell = cell;
        
        if (!this.compareCells(this.mouseCell, this.hoveredCell)) {
            this.stopHoverStart();
            
            this.hoverStart = window.setTimeout(function(){
                this.hoverStart = null;
                this.stopHoverEnd();
                
                if (this.hoveredCell) {
                    this.hideCellOverlay(this.hoveredCell);
                }
                
                this.hoveredCell = cell;
                this.showCellOverlay(cell);
            }.bind(this), 50);
        } else {
            this.stopHoverStart();
            this.stopHoverEnd();
        }
    },
    
    onCellMouseOut: function(cell)
    {
        if (this.compareCells(this.mouseCell, cell)) {
            this.mouseCell = null;
            this.stopHoverStart();
        }
        if (this.compareCells(this.hoveredCell, cell)) {
            this.stopHoverEnd();
            
            this.hoverEnd = window.setTimeout(function(){
                this.hoverEnd = null;
                this.hideCellOverlay(this.hoveredCell);
                this.hoveredCell = null;
            }.bind(this), 25);
        }
    },
    
    parseCellParamKey: function(baseKey, valueKey)
    {
        var paramKey   = '';
        var bracketPos = valueKey.indexOf('[');
            
        if (bracketPos != -1) {
            paramKey = baseKey+'['+valueKey.substr(0, bracketPos)+']'+valueKey.substr(bracketPos);
        } else {
            paramKey = baseKey+'['+valueKey+']';
        }
        
        return paramKey;
    },
    
    getCellParamsHash: function(cell)
    {
        var cellId = cell.identify();
        var config = this.cellsConfigs[cellId];
        var rowIds = this.rowsIds[this.cellsRowNums[cellId]];
        var params = $H({});
        
        // Identifiers
        $H(rowIds).each(function(pair){
            params.set(this.parseCellParamKey(config.ids_key, pair.key), pair.value);
        }.bind(this));
        
        // Additional parameters
        this.additionalParams.each(function(pair){
            params.set(this.parseCellParamKey(config.additional_key, pair.key), pair.value);
        }.bind(this));

        // Additional column parameters
        if (!Object.isArray(config.column_params)) {
            $H(config.column_params).each(function(pair){
                params.set(this.parseCellParamKey(config.additional_key, pair.key), pair.value);
            }.bind(this));
        }
        
        // Global parameters
        params.update(this.globalParams);
        
        return params;
    },
    
    parseHashDimensions: function(hash, dimensions)
    {
        var vpDimensions = document.viewport.getDimensions();        
        
        $H(dimensions).each(function(pair){
            if (hash.get(pair.key) != '') {
                var dimension = ''+hash.get(pair.key);
                if (dimension.substr(dimension.length-1) == '%') {
                    if (!isNaN(dimension = parseInt(dimension.substr(0, dimension.length-1)))) {
                        hash.set(pair.key, parseInt((vpDimensions[pair.value]*dimension)/100));
                    } else {
                        hash.unset(pair.key);
                    }
                }
            }
        }.bind(this));
        
        return hash;
    },
    
    closeEditWindow: function()
    {
        if (this.editWindow) {
            this.editWindow.setCloseCallback(null);
            blcg.Tools.closeDialog(this.editWindow);
            this.editWindow = null;
        }
    },
    
    editCell: function(cell)
    {
        if (!this.compareCells(this.editedCell, cell)) {
            this.cancelEdit();
            this.editedCell = cell;            
            var cellConfig  = this.cellsConfigs[cell.identify()];
            var editUrl     = cellConfig.edit_url;
            var editParams  = this.getCellParamsHash(this.editedCell);
            
            if (cellConfig.in_grid) {
                var editor = this;
                editUrl += editUrl.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true';
                
                new Ajax.Request(editUrl, {
                    method: 'post',
                    parameters: editParams,
                    onSuccess: function(transport){
                        try {
                            if (transport.responseText.isJSON()) {
                                var response = transport.responseText.evalJSON();
                                
                                if (response.error) {
                                    editor.cancelEdit();
                                    alert(response.message);
                                } else if (response.ajaxExpired && response.ajaxRedirect) {
                                    setLocation(response.ajaxRedirect);
                                } else {
                                    var cell = editor.editedCell;
                                    cell.addClassName('blcg-column-editor-editing');
                                    editor.previousValue = cell.innerHTML;
                                    editor.hasPreviousValue = true;
                                    
                                    var form = document.createElement('form');
                                    form.id = 'blcg-column-editor-form-' + cell.identify();
                                    form.innerHTML = response.content;
                                    cell.innerHTML = '';
                                    cell.appendChild(form);
                                    blcg.Tools.execNodeJS(cell);
                                    
                                    editor.fillCellOverlay(cell);
                                    editor.positionCellOverlay(cell, null, editor.compareCells(cell, editor.mouseCell));
                                    
                                    cell.getElementsBySelector('.blcg-editor-required-marker').each(function(e){
                                        e.hide();
                                        cell.addClassName('blcg-column-editor-editing-required');
                                    });
                                }
                            } else {
                                editor.cancelEdit();
                                if (transport.responseText != '') {
                                    alert(transport.responseText);
                                }
                            }
                        } catch(e) {
                            editor.cancelEdit();
                            if (transport.responseText != '') {
                                alert(transport.responseText);
                            }
                        }
                    },
                    onFailure: function(transport){
                        editor.cancelEdit();
                        if (editor.errorMessages.get('edit_request_failure')) {
                            alert(editor.errorMessages.get('edit_request_failure'));
                        }
                    }
                });
            } else {
                editUrl += (editUrl.match(new RegExp('\\?')) ? '&' : '?') + editParams.toQueryString();
                var windowConfig = $H(cellConfig.window);
                windowConfig.set('closeCallback', function(){ this.cancelEdit(true); return true; }.bind(this));
                windowConfig = this.parseHashDimensions(windowConfig, {
                    'width': 'width',
                    'height': 'height',
                    'minWidth': 'width',
                    'minHeight': 'height'
                });
                this.editWindow = blcg.Tools.openIframeDialog(editUrl, windowConfig.toObject(), true);
            }
        }
    },
    
    validateEdit: function(formParams)
    {
        if (this.editedCell) {
            var cell   = this.editedCell;
            var cellId = cell.identify();
            var params = null;
            var cellConfig = this.cellsConfigs[cellId];
            
            if (cellConfig.in_grid) {
                var form = $('blcg-column-editor-form-' + cellId);
                if (form) {
                    var validator = new Validation(form);
                    if (validator) {
                        if (!validator.validate()) {
                            return;
                        }
                        params = $H(form.serialize(true));
                    }
                }
            } else if (formParams) {
                params = $H(formParams);
                this.closeEditWindow();
            }
            
            if (params && (params.values().length > 0)) {
                params.update(this.getCellParamsHash(cell));
                var saveUrl = cellConfig.save_url;
                saveUrl += saveUrl.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true';
                var editor  = this;
                
                new Ajax.Request(saveUrl, {
                    method: 'post',
                    parameters: params,
                    onSuccess: function(transport){
                        try {
                            if (transport.responseText.isJSON()) {
                                var response = transport.responseText.evalJSON();
                                
                                if (response.error) {
                                    editor.cancelEdit();
                                    alert(response.message);
                                } else if (response.ajaxExpired && response.ajaxRedirect) {
                                    setLocation(response.ajaxRedirect);
                                } else {
                                    var cell = editor.editedCell;
                                    cell.addClassName('blcg-column-editor-updated');
                                    cell.removeClassName('blcg-column-editor-editing');
                                    cell.removeClassName('blcg-column-editor-editing-required');
                                    cell.innerHTML = response.content;
                                    blcg.Tools.execNodeJS(cell);
                                    
                                    editor.previousValue = null;
                                    editor.hasPreviousValue = false;
                                    editor.editedCell = null;
                                    
                                    editor.fillCellOverlay(cell);
                                    editor.positionCellOverlay(cell, null, editor.compareCells(cell, editor.mouseCell));
                                }
                            } else {
                                editor.cancelEdit();
                                if (transport.responseText != '') {
                                    alert(transport.responseText);
                                }
                            }
                        } catch(e) {
                            editor.cancelEdit();
                            if (transport.responseText != '') {
                                alert(transport.responseText);
                            }
                        }
                    },
                    onFailure: function(transport){
                        editor.cancelEdit();
                        if (editor.errorMessages.get('save_request_failure')) {
                            alert(editor.errorMessages.get('save_request_failure'));
                        }
                    }
                });
            } else {
                this.cancelEdit();
                if (this.errorMessages.get('save_no_params')) {
                    alert(this.errorMessages.get('save_no_params'));
                }
            }
        }
    },
    
    cancelEdit: function(fromDialog, errorMessage)
    {
        if (this.editedCell) {
            if (this.hasPreviousValue) {
                this.editedCell.innerHTML = this.previousValue;
                this.hasPreviousValue = false;
            }

            var cellConfig = this.cellsConfigs[this.editedCell.identify()];
            this.previousValue = null;
            this.editedCell.removeClassName('blcg-column-editor-editing');
            this.editedCell.removeClassName('blcg-column-editor-editing-required');
            this.fillCellOverlay(this.editedCell);
            this.positionCellOverlay(this.editedCell);
            this.editedCell = null;
            
            if (!cellConfig.in_grid && !fromDialog) {
                this.closeEditWindow();
            }
            if (errorMessage && (errorMessage != '')) {
                alert(errorMessage);
            }
        }
    }
}