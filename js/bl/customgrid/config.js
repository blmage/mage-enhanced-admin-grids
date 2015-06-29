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
 * @copyright  Copyright (c) 2015 Benoît Leulliette <benoit.leulliette@gmail.com>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

if (typeof(blcg) == 'undefined') {
    var blcg = {};
}
if (typeof(blcg.Form) == 'undefined') {
    blcg.Form = {};
}
if (typeof(blcg.Form.Element) == 'undefined') {
    blcg.Form.Element = {};
}
if (typeof(blcg.Grid) == 'undefined') {
    blcg.Grid = {};
}
if (typeof(blcg.Grid.Renderer) == 'undefined') {
    blcg.Grid.Renderer = {};
}
if (typeof(blcg.Grid.Renderer.Collection) == 'undefined') {
    blcg.Grid.Renderer.Collection = {};
}
if (typeof(blcg.Grid.Renderer.Attribute) == 'undefined') {
    blcg.Grid.Renderer.Attribute = {};
}
if (typeof(blcg.Grid.CustomColumn) == 'undefined') {
    blcg.Grid.CustomColumn = {};
}
if (typeof(blcg.Grid.Filter) == 'undefined') {
    blcg.Grid.Filter = {};
}

blcg.Tools = {
    windowsCount: 0,
    
    isPrimitiveValue: function(value)
    {
        var valueType = Object.prototype.toString.call(value);
        return (valueType == '[object Boolean]')
            || (valueType == '[object Number]')
            || (valueType == '[object String]');
    },
    
    isEmptyValue: function(value)
    {
        return (value === null)
            || (value === undefined)
            || ((Object.isArray(value) || Object.isString(value)) && (value.length === 0));
    },
    
    openDialog: function(windowConfig, otherWindow)
    {
        if (!otherWindow && $('blcg_window') && (typeof(Windows) != 'undefined')) {
            Windows.focus('blcg_window');
            return;
        }
        
        var windowId  = 'blcg_window' + (otherWindow ? '_' + (++this.windowsCount) : '');
        var windowUrl = windowConfig.url;
        
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
            onClose: this.closeDialog.bind(this),
            options: {}
        }, windowConfig || {}); 
        
        if (windowConfig.resizable) {
            windowConfig.windowClassName += ' blcg-resizable-popup-window';
        }
        if (windowUrl) {
            // Dialog.info() doesn't care about url parameter, then always uses innerHTML even when it shouldn't
            windowConfig.url = '';
        }
        
        var dialogWindow = Dialog.info(null, windowConfig);
        
        if (windowUrl) {
            // We can safely set the URL now, the Dialog class will not interfere anymore
            dialogWindow.setURL(windowUrl);
        }
        if (!otherWindow) {
            this.dialogWindow = dialogWindow;
            return;
        }
        
        return dialogWindow;
    },
    
    checkDialogAjaxResponse: function(transport)
    {
        var isValid = false;
        
        try {
            if (transport.responseText.isJSON()) {
                response = transport.responseText.evalJSON();
                
                if (response.error) {
                    alert(response.message);
                } else if (response.type && (response.type == 'error')) {
                    if (response.message) {
                        alert(response.message);
                    }
                } else if (!response.ajaxExpired) {
                    isValid = true;
                }
            } else {
                isValid = true;
            }
        } catch (e) {
            isValid = true;
        }
        
        return isValid;
    },
    
    buildQueryStringFromValue: function(value, currentParamName)
    {
        var parts = [];
        var isEmptyParamName = this.isEmptyValue(currentParamName);
        
        if (this.isPrimitiveValue(value)) {
            if (!isEmptyParamName) {
                parts.push(currentParamName + '=' + encodeURIComponent(value));
            }
        } else if (Object.isArray(value)) {
            if (!isEmptyParamName) {
                var paramName = currentParamName + '[]';
                
                for (var i=0, l=value.length; i<l; i++) {
                    parts.push(this.buildQueryStringFromValue(value[i], paramName));
                }
            }
        } else if (value) {
            $H(value).each(function(pair) {
                var paramName = encodeURIComponent(pair.key);
                paramName = (!isEmptyParamName ? currentParamName + '[' + paramName + ']' : paramName);
                parts.push(this.buildQueryStringFromValue(pair.value, paramName));
            }.bind(this));
        }
        
        return parts.join('&');
    },
    
    openDialogFromUrl: function(url, windowConfig)
    {
        var window = this.openDialog(windowConfig);
        var loadingClassName = (windowConfig.loadingClassName || 'blcg-loading');
        $('modal_dialog_message').addClassName(loadingClassName);
        
        new Ajax.Updater('modal_dialog_message', url, {
            evalScripts: true,
            onComplete: function(transport) {
                if (!blcg.Tools.checkDialogAjaxResponse(transport)) {
                    blcg.Tools.closeDialog(window);
                }
                $('modal_dialog_message').removeClassName(loadingClassName);
            }
        });
        
        return window;
    },
    
    openDialogFromPost: function(url, data, windowConfig)
    {
        var window = this.openDialog(windowConfig);
        var loadingClassName = (windowConfig.loadingClassName || 'blcg-loading');
        $('modal_dialog_message').addClassName(loadingClassName);
        
        new Ajax.Updater('modal_dialog_message', url, {
            method: 'post',
            parameters: blcg.Tools.buildQueryStringFromValue(data),
            evalScripts: true,
            onComplete: function(transport) {
                if (!blcg.Tools.checkDialogAjaxResponse(transport)) {
                    blcg.Tools.closeDialog(window);
                }
                $('modal_dialog_message').removeClassName(loadingClassName);
            }
        });
        
        return window;
    },
    
    openDialogFromElement: function(elementId, windowConfig)
    {
        var window = this.openDialog(windowConfig);
        $('modal_dialog_message').update($(elementId).innerHTML);
        return window;
    },
    
    openDialogWithContent: function(content, windowConfig)
    {
        var window = this.openDialog(windowConfig);
        $('modal_dialog_message').update(content);
        return window;
    },
    
    openIframeDialog: function(iframeUrl, windowConfig, otherWindow)
    {
        windowConfig.url = iframeUrl;
        return this.openDialog(windowConfig, otherWindow);
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
    
    getAjaxUrl: function(url)
    {
        url = '' + url;
        return url + (url.match(new RegExp('\\?')) ? '&isAjax=true' : '?isAjax=true');
    },
    
    focusFirstInput: function(container)
    {
        var firstInput = $(container).select('input[type!=hidden], select, textarea').first();
        
        if (firstInput) {
            firstInput.focus();
        }
    },
    
    submitContainerValues: function(container, url, additional, method, useAjax, ajaxCallbacks)
    {
        container = $(container);
        
        if (!container) {
            return false;
        }
        
        var elements = [];
        var isValid  = true;
        
        container.select('input, select').each(function(input) {
            var isInput = (input.tagName.toUpperCase() == 'INPUT');
            var isCheckbox = (isInput && (input.readAttribute('type').toUpperCase() == 'CHECKBOX'));
            
            if (!input.disabled && (!isCheckbox || input.checked)) {
                elements.push(input);
                
                if (!Validation.validate(input)) {
                    isValid = false;
                }
            }
        });
        
        if (isValid) {
            method = (method || '').toUpperCase();
            method = ((method == 'GET') || (method == 'POST') ? method : 'POST');
            
            if (useAjax) {
                ajaxCallbacks  = ajaxCallbacks || {};
                var parameters = $H(Form.serializeElements(elements, true));
                parameters.update($H(additional || {}));
                
                new Ajax.Request(blcg.Tools.getAjaxUrl(url), {
                    method: method,
                    parameters: parameters,
                    onSuccess:  (ajaxCallbacks.success  || Prototype.emptyFunction),
                    onFailure:  (ajaxCallbacks.failure  || Prototype.emptyFunction),
                    onComplete: (ajaxCallbacks.complete || Prototype.emptyFunction)
                })
                
                return true;
            } else {
                var form = $(document.createElement('form'));
                form.writeAttribute({'action': url, 'method': method}); 
                document.body.appendChild(form);
                
                $A(elements).each(function(element) {
                    var input = $(document.createElement('input'));
                    
                    input.writeAttribute({
                        'type':  'hidden',
                        'name':  element.readAttribute('name'),
                        'value': $F(element)
                    });
                    
                    form.appendChild(input);
                });
                
                $H(additional || {}).each(function(option) {
                    var input = $(document.createElement('input'));
                    
                    input.writeAttribute({
                        'type':  'hidden',
                        'name':  option.key,
                        'value': option.value
                    });
                    
                    form.appendChild(input);
                });
                
                form.submit();
                return true;
            }
        }
        
        return false;
    },
    
    handleAjaxOnSuccessResponse: function(successCallback, defaultErrorMessage, transport)
    {
        var isSuccess = false;
        var response = null;
        
        try {
            if (transport.responseText.isJSON()) {
                response = transport.responseText.evalJSON();
                
                if (response.blcgMessagesHtml && response.blcgMessagesWrapperId) {
                    if (typeof(blcgMessagesTabs) != 'undefined') {
                        $(document.body).insert(response.blcgMessagesHtml);
                        blcgMessagesTabs.addMessagesFromWrapper(response.blcgMessagesWrapperId)
                        $(response.blcgMessagesWrapperId).remove();
                    }
                }
                if (response.error) {
                    alert(response.message);
                } else if (response.ajaxExpired && response.ajaxRedirect) {
                    setLocation(response.ajaxRedirect);
                } else if (response.type) {
                    if (response.type == 'error') {
                        alert(response.message);
                    } else if (response.type == 'success') {
                        isSuccess = true;
                        
                        if (successCallback) {
                            successCallback(transport, response);
                        }
                    } else {
                        alert(defaultErrorMessage);
                    }
                } else {
                    alert(defaultErrorMessage);
                }
            } else if (transport.responseText != '') {
                alert(transport.responseText);
            } else {
                alert(defaultErrorMessage);
            }
        } catch(e) {
            alert(defaultErrorMessage);
        }
        
        return (isSuccess ? response : false);
    },
    
    handleAjaxOnErrorResponse: function(errorMessage, transport)
    {
        alert(errorMessage);
    },
    
    executeNodeJS: function(node)
    {
        var isSafari  = (navigator.userAgent.indexOf('Safari') != -1);
        var isOpera   = (navigator.userAgent.indexOf('Opera') != -1);
        var isMozilla = (navigator.appName == 'Netscape');
        
        if (!node) {
            return;
        }
        
        var scriptTags = node.getElementsByTagName('SCRIPT');
        var scriptCode;
        
        for(var i=0; i<scriptTags.length; i++) {
            if (isSafari) {
                scriptCode = scriptTags[i].innerHTML;
                scriptTags[i].innerHTML = '';
            } else if (isOpera) {
                scriptCode = scriptTags[i].text;
                scriptTags[i].text = '';
            } else if (isMozilla) {
                scriptCode = scriptTags[i].textContent;
                scriptTags[i].textContent = '';
            } else {
                scriptCode = scriptTags[i].text;
                scriptTags[i].text = '';
            }
            
            try {
                var scriptTag  = document.createElement('script');
                scriptTag.type = 'text/javascript';
                
                if (isSafari || isOpera || isMozilla) {
                    scriptTag.innerHTML = scriptCode;
                } else {
                    scriptTag.text = scriptCode;
                }
                
                document.getElementsByTagName('head')[0].appendChild(scriptTag);
            } catch(e) {
                return;
            }
        }
    },
    
    checkContainerCheckboxes: function(containerId, checked)
    {
        $(containerId).select('input[type=checkbox]').each(function(checkbox) {
            checkbox.checked = !!checked;
        });
    },
    
    quoteRegex: function(regexp)
    {
        return ('' + regexp).replace(/([.?*+^$[\]\\(){}|-])/g, '\\$1');
    },
    
    translate: function(text)
    {
        try {
            if(Translator) {
               return Translator.translate(text);
            }
        } catch(e) {}
        
        return text;
    }
};

blcg.Grid.Tools = {
    getGridObject: function(gridObject)
    {
        if (Object.isString(gridObject)) {
            gridObject = (gridObject in window ? window[gridObject] : null);
        }
        return (gridObject instanceof varienGrid ? gridObject : null);
    },
    
    getGridReloadUrl: function(gridObject, removableParams, additionalParams)
    {
        var parts = gridObject.url.split(new RegExp('\\?'));
        var url = parts[0];
        
        if (removableParams) {
            $A(removableParams).each(function(param) {
                var regexp = new RegExp('\/(' + param + '\/.*?\/)');
                url = url.replace(regexp, '/');
            });
        }
        
        if (additionalParams) {
            $H(additionalParams).each(function(pair) {
                var regexp = new RegExp('\/(' + pair.key + '\/.*?\/)');
                url  = url.replace(regexp, '/');
                url += pair.key + '/' + pair.value + '/';
            });
        }
        
        if(parts.size() > 1) {
            url += '?' + parts[1];
        }
        
        return url;
    },
    
    reloadGrid: function(gridObject, removableParams, additionalParams)
    {
        
        if (!(gridObject = blcg.Grid.Tools.getGridObject(gridObject))) {
            alert(blcg.Tools.translate('Could not reload the grid. Please reload it manually to apply any change'));
            return false;
        }
        // Setting the URL on the object is safer than passing it as the corresponding parameter to reload()
        // If the grid does not reload itself with AJAX and have reload params, then the given URL would not be used
        gridObject.url = blcg.Grid.Tools.getGridReloadUrl(gridObject, removableParams, additionalParams);
        gridObject.reload();
    },
    
    reapplyDefaultFilter: function(gridObjectName, reapplyDefaultFilterUrl, filterResetRequestValue)
    {
        var gridObject = blcg.Grid.Tools.getGridObject(gridObjectName);
        
        if (gridObject) {
            var additionalReloadParams = {};
            additionalReloadParams[gridObject.filterVar] = filterResetRequestValue;
            
            new Ajax.Request(reapplyDefaultFilterUrl, {
                method: 'post',
                
                onSuccess: blcg.Tools.handleAjaxOnSuccessResponse.curry(
                    blcg.Grid.Tools.reloadGrid.curry(gridObjectName, [], additionalReloadParams),
                    blcg.Tools.translate('An error occurred while reapplying the default filter')
                ),
                
                onFailure: blcg.Tools.handleAjaxOnErrorResponse.curry(
                    blcg.Tools.translate('An error occurred while reapplying the default filter')
                )
            });
        }
    }
};

blcg.EventsManager = Class.create();
blcg.EventsManager.prototype = {
    initialize: function()
    {
        this.handlers = $H();
    },
    
    addUniqueHandler: function(id, type, element, eventName, callback)
    {
        var handler = this.handlers.get(id);
        
        if (handler) {
            if (handler.type == 'varien') {
                if (typeof(varienGlobalEvents) != 'undefined') {
                    varienGlobalEvents.removeEventHandler(handler.eventName, handler.callback)
                }
            } else if (type == 'prototype') {
                Event.stopObserving(handler.element, handler.eventName, handler.callback);
            }
            this.handlers.unset(id);
        }
        if (type == 'varien') {
            if (typeof(varienGlobalEvents) != 'undefined') {
                varienGlobalEvents.attachEventHandler(eventName, callback);
            } else {
                return;
            }
        } else if (type == 'prototype') {
            Event.observe(element, eventName, callback);
        } else {
            return;
        }
        
        this.handlers.set(id, {
            type: type,
            element: element,
            eventName: eventName,
            callback: callback
        });
    }
};

blcgEventsManager = new blcg.EventsManager();

blcg.Form.Element.DependenceController = Class.create();
blcg.Form.Element.DependenceController.prototype = {
    initialize : function(elementsMap, config)
    {
        this.idsStates = $H();
        this.invertedMap = $A();
        this.elementsMap = elementsMap;
        
        this.config = Object.extend({
            chainHidden: true,
            levelsUp: 1
        }, config || {});
        
        for (var idTo in elementsMap) {
            this.idsStates[idTo] = true;
            
            for (var idFrom in elementsMap[idTo]) {
                if (!this.invertedMap[idFrom]) {
                    this.invertedMap[idFrom] = $A();
                }
                
                this.invertedMap[idFrom].push(idTo);
                this.idsStates[idFrom] = true;
                var elementFrom = $(idFrom);
                
                if (elementFrom) {
                    elementFrom.observe('change', this.trackChange.bindAsEventListener(this, idTo));
                    this.trackChange(null, idTo);
                }
            }
        }
    },
    
    trackChange : function(e, idTo, inspectedIds)
    {
        if (inspectedIds) {
            if (inspectedIds.indexOf(idTo) != -1) {
                return;
            }
            inspectedIds.push(idTo)
        } else {
            inspectedIds = [idTo];
        }
        
        var valuesFrom = this.elementsMap[idTo];
        var shouldShowUp = true;
        
        for (var idFrom in valuesFrom) {
            if ((this.config.chainHidden && !this.idsStates[idFrom])
                || (valuesFrom[idFrom].indexOf($F(idFrom)) == -1)) {
                shouldShowUp = false;
            }
        }
        if (shouldShowUp) {
            $(idTo).up(this.config.levelsUp).select('input', 'select').each(function(item) {
                if (!item.type || (item.type != 'hidden')) { // Don't touch hidden inputs, they may have custom logic
                    item.disabled = false;
                }
            });
            $(idTo).up(this.config.levelsUp).show();
            this.idsStates[idTo] = true;
        } else {
            $(idTo).up(this.config.levelsUp).select('input', 'select').each(function(item) {
                if (!item.type || (item.type != 'hidden')) { // Same thing as above
                    item.disabled = true;
                }
            });
            $(idTo).up(this.config.levelsUp).hide();
            this.idsStates[idTo] = false;
        }
        
        if (this.invertedMap[idTo]) {
            this.invertedMap[idTo].each(function(subIdTo) {
                this.trackChange(null, subIdTo, inspectedIds);
            }.bind(this));
        }
    }
};

blcg.MessagesTabs = Class.create();
blcg.MessagesTabs.prototype = {
    initialize: function(wrapperId, types, config)
    {
        this.config = Object.extend({
            tabIdPrefix: 'blcg-messages-tab-',
            countIdPrefix: 'blcg-messages-tab-count-',
            wrapperIdPrefix: 'blcg-messages-list-wrapper-',
            wrapperClassName: 'blcg-messages-list-wrapper',
            hiddenClassName: 'blcg-no-display',
            messageSelector: '.messages li li'
        }, config || {});
        
        this.wrapper = $(wrapperId);
        this.types = $A(types);
        
        this.types.each(function(type) {
            $(this.config.tabIdPrefix + type).observe('click', this.toggleMessages.bind(this, type, false));
        }.bind(this));
    },
    
    hideMessages: function()
    {
        $$('.' + this.config.wrapperClassName).invoke('addClassName', this.config.hiddenClassName);
    },
    
    showMessages: function(type)
    {
        this.hideMessages();
        $(this.config.wrapperIdPrefix + type).removeClassName(this.config.hiddenClassName);
    },
    
    toggleMessages: function(type, closeOnly)
    {
        if ($(this.config.wrapperIdPrefix + type).hasClassName(this.config.hiddenClassName)) {
            if (!closeOnly) {
                this.showMessages(type);
            }
        } else {
            this.hideMessages();
        }
    },
    
    addMessagesFromWrapper: function(wrapperId)
    {
        var newWrapper = $(wrapperId);
        
        this.types.each(function(type) {
            var mainWrapper = $(this.config.wrapperIdPrefix + type),
                subWrapper  = newWrapper.select('.' + this.config.wrapperIdPrefix + type).first();
            
            if (subWrapper) {
                mainWrapper.insert({top: subWrapper.down()});
                var count = mainWrapper.select(this.config.messageSelector).size();
                $(this.config.countIdPrefix + type).update('' + count);
                
                if (count > 0) {
                    $(this.config.tabIdPrefix + type).removeClassName(this.config.hiddenClassName);
                } else {
                    $(this.config.tabIdPrefix + type).addClassName(this.config.hiddenClassName);
                    this.toggleMessages(type, true);
                }
            }
        }.bind(this));
    }
};

blcg.Fieldset = {
    applyCollapse: function(containerId)
    {
        var container = $(containerId);
        var stateElement = $(containerId + '-state');
        var headElement  = $(containerId + '-head');
        
        if (stateElement) {
            collapsed = (stateElement.value == 1 ? 0 : 1);
        } else {
            collapsed = headElement.collapsed;
        }
        
        if ((collapsed == 1) || (typeof(collapsed) == 'undefined')) {
           headElement.removeClassName('open');
           container.hide();
        } else {
           headElement.addClassName('open');
           container.show();
        }
    },
    
    toggleCollapse: function(containerId, saveStateUrl)
    {
        var stateElement = $(containerId + '-state');
        var headElement  = $(containerId + '-head');
        
        if (stateElement) {
            collapsed = (stateElement.value == 1 ? 0 : 1);
        } else {
            collapsed = $(containerId + '-head').collapsed;
        }
        
        if ((collapsed == 1) || (typeof(collapsed) == 'undefined')) {
            if (stateElement) {
                stateElement.value = 1;
            }
            headElement.collapsed = 0;
        } else {
            if (stateElement) {
                stateElement.value = 0;
            }
            headElement.collapsed = 1;
        }
        
        this.applyCollapse(containerId);
        
        if ((saveStateUrl != '') && stateElement) {
            this.saveState(saveStateUrl, {
                container: containerId,
                value: stateElement.value
            });
        }
    },
    
    saveState: function(url, parameters)
    {
        new Ajax.Request(url, {
            method: 'get',
            parameters: Object.toQueryString(parameters),
            loaderArea: false
        });
    }
};

/*
 * Based on tooltip-0.2.js - Small tooltip library on top of Prototype:
 * Copyright (c) 2006 Jonathan Weiss <jw@innerewut.de>
 *
 * Permission to use, copy, modify, and distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

blcg.Tooltip = Class.create();
blcg.Tooltip.prototype = {
    initialize: function(element, tooltip, config)
    {
        this.config = Object.extend({
            defaultCss: false,
            className: 'blcg-tooltip',
            margin: '0px',
            padding: '5px',
            bkgColor: '#fff',
            xMinDist: 20,
            yMinDist: 5,
            xDelta: 0,
            yDelta: 0,
            zIndex: 1000,
            moving: false
        }, config || {});
        
        this.element = $(element);
        
        if ($(tooltip)) {
            this.tooltip = $(tooltip);
        } else {
            this.tooltip = $(document.createElement('div')); 
            this.tooltip.appendChild(document.createTextNode(tooltip));
        }
        
        this.tooltip.addClassName(this.config.className);
        document.body.appendChild(this.tooltip);
        this.tooltip.hide();
        
        this.eventMouseOver = this.showTooltip.bindAsEventListener(this);
        this.eventMouseOut  = this.hideTooltip.bindAsEventListener(this);
        
        if (this.config.moving) {
            this.eventMouseMove = this.moveTooltip.bindAsEventListener(this);
        }
        
        this.registerEvents();
    },
    
    destroy: function()
    {
        Event.stopObserving(this.element, 'mouseover', this.eventMouseOver);
        Event.stopObserving(this.element, 'mouseout',  this.eventMouseOut);
        
        if (this.config.moving) {
            Event.stopObserving(this.element, 'mousemove', this.eventMouseMove);
        }
    },
    
    registerEvents: function()
    {
        Event.observe(this.element, 'mouseover', this.eventMouseOver);
        Event.observe(this.element, 'mouseout',  this.eventMouseOut);
        
        if (this.config.moving) {
            Event.observe(this.element, 'mousemove', this.eventMouseMove);
        }
    },
    
    moveTooltip: function(event)
    {
        Event.stop(event);
        
        var mouseX = Event.pointerX(event);
        var mouseY = Event.pointerY(event);
        var dimensions = Element.getDimensions(this.tooltip);
        var width  = dimensions.width;
        var height = dimensions.height;
        var scroll = this.getWindowScrolls();
        
        if ((width + mouseX - scroll.left) >= (this.getWindowWidth() - this.config.xMinDist)) {
            mouseX -= width + this.config.xMinDist;
        } else {
            mouseX += this.config.xMinDist;
        }
        if ((height + mouseY - scroll.top) >= (this.getWindowHeight() - this.config.yMinDist)) {
            mouseY -= height + this.config.yMinDist;
        } else {
            mouseY += this.config.yMinDist;
        } 
        
        this.setStyles(mouseX, mouseY);
    },
    
    showTooltip: function(event)
    {
        Event.stop(event);
        this.moveTooltip(event);
        new Element.show(this.tooltip);
    },
    
    setStyles: function(x, y)
    {
        Element.setStyle(this.tooltip, {
            position: 'absolute',
            top:  y + this.config.yDelta + 'px',
            left: x + this.config.xDelta + 'px',
            zindex: this.config.zIndex
        });
        
        if (this.config.defaultCss) {
            Element.setStyle(this.tooltip, {
                margin: this.config.margin,
                padding: this.config.padding,
                backgroundColor: this.config.bkgColor
            });
        }
    },
    
    hideTooltip: function(event)
    {
        Element.hide(this.tooltip);
    },
    
    getWindowHeight: function()
    {
        return document.viewport.getHeight();
    },
    
    getWindowWidth: function()
    {
        return document.viewport.getWidth();
    },
        
    getWindowScrolls: function()
    {
        return document.viewport.getScrollOffsets();
    }
};

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
    initialize: function(table, config)
    {
        this.config = Object.extend({
            onDragStyle: null,
            onDropStyle: null,
            onDragClassName: 'blcg-dnd-whileDrag',
            dragHandleClassName: null,
            onDrop: null,
            onExchange: null,
            onDragStart: null,
            scrollAmount: 5
        }, config || {});
        
        this.currentTable  = null;
        this.draggedObject = null;
        this.mouseOffset = null;
        this.oldY = 0;
        this.makeDraggable(table);
        
        blcgEventsManager.addUniqueHandler(
            'blcg-dnd-' + $(table).identify() + ' -mouse-move',
            'prototype',
            document,
            'mousemove',
            this.onMouseMove.bind(this)
        );
        
        blcgEventsManager.addUniqueHandler(
            'blcg-dnd-' + $(table).identify() + '-mouse-up',
            'prototype',
            document,
            'mouseup',
            this.onMouseUp.bind(this)
        );
    },
    
    makeDraggable: function(table)
    {
        var table = $(table);
        
        if (this.config.dragHandleClassName) {
            var cells = table.select('td.' + this.config.dragHandleClassName);
            
            cells.each(function(cell) {
                cell.observe('mousedown', function(event) {
                    this.draggedObject = cell.parentNode;
                    this.currentTable  = table;
                    this.mouseOffset   = this.getMouseOffset(cell, event);
                    
                    if (this.config.onDragStart) {
                        event.preventDefault(); 
                        this.config.onDragStart(table, cell);
                    }
                    
                    return false;
                }.bind(this));
            }.bind(this));
        } else {
            // For backwards compatibility, we add the event to the whole row
            var rows = table.select('tr');
            
            rows.each(function(row) {
                if (!row.hasClassName('nodrag')) {
                    row.observe('mousedown', function(event) {
                        if (event.target.tagName.toUpperCase() == 'TD') {
                            this.draggedObject = row;
                            this.currentTable  = table;
                            this.mouseOffset   = this.getMouseOffset(row, event);
                            
                            if (this.config.onDragStart) {
                                event.preventDefault(); 
                                this.config.onDragStart(table, row);
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
        
        if (this.config.dragHandleClassName) {
            var cells = row.select('td.' + this.config.dragHandleClassName);
            
            cells.each(function(cell) {
                cell.observe('mousedown', function(event) {
                    this.draggedObject = cell.parentNode;
                    this.currentTable  = table;
                    this.mouseOffset   = this.getMouseOffset(cell, event);
                    
                    if (this.config.onDragStart) {
                        event.preventDefault(); 
                        this.config.onDragStart(table, cell);
                    }
                    
                    return false;
                }.bind(this));
            }.bind(this));
        } else {
            // For backwards compatibility, we add the event to the whole row
            if (!row.hasClassName('nodrag')) {
                row.observe('mousedown', function(event) {
                    if (event.target.tagName.toUpperCase() == 'TD') {
                        this.draggedObject = row;
                        this.currentTable  = table;
                        this.mouseOffset   = this.getMouseOffset(row, event);
                        
                        if (this.config.onDragStart) {
                            event.preventDefault(); 
                            this.config.onDragStart(table, row);
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
        var targetPosition = this.getPosition(target);
        var mousePosition  = this.getMouseCoords(event);
        return {x: mousePosition.x - targetPosition.x, y: mousePosition.y - targetPosition.y};
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
        if (this.draggedObject == null) {
            return;
        }
        
        var draggedObject = $(this.draggedObject);
        var mousePosition = this.getMouseCoords(event);
        var y = mousePosition.y - this.mouseOffset.y;
        
        // Auto scroll the window
        var yOffset = window.pageYOffset;
        
        if (document.all) {
            if ((typeof(document.compatMode) != 'undefined') && (document.compatMode != 'BackCompat')) {
                yOffset = document.documentElement.scrollTop;
            } else if (typeof(document.body) != 'undefined') {
                yOffset = document.body.scrollTop;
            }
        }
        
        if (mousePosition.y - yOffset < this.config.scrollAmount) {
            window.scrollBy(0, -this.config.scrollAmount);
        } else {
            var windowHeight;
            
            if (window.innerHeight) {
                windowHeight = window.innerHeight
            } else if (document.documentElement.clientHeight) {
                windowHeight = document.documentElement.clientHeight;
            } else {
                windowHeight = document.body.clientHeight;
            }
            
            if (windowHeight - (mousePosition.y - yOffset) < this.config.scrollAmount) {
                window.scrollBy(0, this.config.scrollAmount);
            }
        }
        
        if (y != this.oldY) {
            this.oldY = y;
            
            if (this.config.onDragClassName) {
                draggedObject.addClassName(this.config.onDragClassName);
            } else if (this.config.onDragStyle) {
                draggedObject.setStyle(this.config.onDragStyle);
            }
            
            var currentRow = this.findDropTargetRow(draggedObject, y);
            
            if (currentRow) {
                var movingDown = true;
                var previousRow;
                var observedRow = draggedObject;
                
                while (previousRow = observedRow.previous('tr')) {
                    if (previousRow == currentRow) {
                        movingDown = false;
                        break;
                    }
                    observedRow = previousRow;
                }
                
                if (movingDown && (this.draggedObject != currentRow)) {
                    this.draggedObject.parentNode.insertBefore(this.draggedObject, currentRow.nextSibling);
                    
                    if (this.config.onExchange) {
                        this.config.onExchange(this.draggedObject, currentRow);
                    }
                } else if (!movingDown && (this.draggedObject != currentRow)) {
                    this.draggedObject.parentNode.insertBefore(this.draggedObject, currentRow);
                    
                    if (this.config.onExchange) {
                        this.config.onExchange(this.draggedObject, currentRow);
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
                if (row == draggedRow) {
                    return null;
                }
                
                if (this.config.onAllowDrop) {
                    if (this.config.onAllowDrop(draggedRow, row)) {
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
        if (this.currentTable && this.draggedObject) {
            var droppedRow = this.draggedObject;
            
            // If we have a dragged object, then we need to release it
            // The row will already have been moved to the right place so we just reset stuff
            if (this.config.onDragClassName) {
                $(droppedRow).removeClassName(this.config.onDragClassName);
            } else if (this.config.onDropStyle) {
                $(droppedRow).setStyle(this.config.onDropStyle);
            }
            
            this.draggedObject = null;
            
            if (this.config.onDrop) {
                this.config.onDrop(this.currentTable, droppedRow);
            }
            
            this.currentTable = null;
        }
    }
};

blcg.Grid.ActionsPinner = Class.create();
blcg.Grid.ActionsPinner.prototype = {
    initialize: function(containerId, gridTableId, config)
    {
        this.config = Object.extend({
            tabContentIdSuffix: '_content',
            blockClassName: 'blcg-grid-pinnable-actions-block',
            placeholderClassName: 'blcg-grid-pinned-actions-block-placeholder',
            pinnedBlockClassName: 'blcg-grid-pinned-actions-block',
            hiddenClassName: 'blcg-no-display',
            floatingHeaderSelector: '.content-header-floating',
            filtersTableSelector: '.blcg-additional-filters-table'
        }, config || {});
        
        this.container = $(containerId);
        this.gridTable = $(gridTableId);
        this.pinnedBlock = this.container.select('.' + this.config.blockClassName).first();
        
        if (!this.pinnedBlock || this.pinnedBlock.empty()) {
            return;
        }
        
        this.pinnedPlaceholder = this.container.select('.' + this.config.placeholderClassName).first();
        this.isVisible   = true;
        this.isPinned    = false;
        this.isWorking   = false;
        this.pinPosition = 0;
        this.pinnedPartHeight = 0;
        
        if (this.pinnedBlock && this.pinnedPlaceholder) {
            var onScrollHandler = this.onScroll.bind(this);
            var eventBaseId = 'blcg-gap-' + gridTableId;
            
            blcgEventsManager.addUniqueHandler(eventBaseId + '-load',   'prototype', window, 'load',   onScrollHandler);
            blcgEventsManager.addUniqueHandler(eventBaseId + '-scroll', 'prototype', window, 'scroll', onScrollHandler);
            blcgEventsManager.addUniqueHandler(eventBaseId + '-resize', 'prototype', window, 'resize', onScrollHandler);
            
            var onTabShowHandler = function(tab) { this.onTabShow(tab.tab); }.bind(this);
            var onTabHideHandler = function(tab) { this.onTabHide(tab.tab); }.bind(this);
            blcgEventsManager.addUniqueHandler(eventBaseId + '-tab-show', 'varien', null, 'showTab', onTabShowHandler);
            blcgEventsManager.addUniqueHandler(eventBaseId + '-tab-hide', 'varien', null, 'hideTab', onTabHideHandler);
        }
        
        this.onScroll();
    },
    
    getFixedHeaderHeight: function()
    {
        var fixedHeader = $$(this.config.floatingHeaderSelector).first();
        return (fixedHeader && fixedHeader.visible() ? fixedHeader.getHeight() : 0);
    },
    
    pin: function()
    {
        if (this.isPinned || this.isWorking) {
            this.checkPosition();
            return;
        }
        
        this.isWorking   = true;
        this.isPinned    = true;
        this.pinPosition = this.getFixedHeaderHeight();
        
        if (this.pinnedBlock) {
            var filtersTables = this.pinnedBlock.select(this.config.filtersTableSelector);
            filtersTables.invoke('addClassName', this.config.hiddenClassName);
            
            this.pinnedBlock.setStyle({
                position: 'fixed',
                left: 0,
                top: this.pinPosition + 'px',
                width: '100%'
            });
            
            this.pinnedBlock.addClassName(this.config.pinnedBlockClassName); 
            this.pinnedPartHeight = this.pinnedBlock.getHeight();
        }
        
        this.pinnedPlaceholder.setStyle({height: this.pinnedPartHeight + 'px'});
        this.isWorking = false;
    },
    
    unPin: function()
    {
        if (!this.isPinned || this.isWorking) {
            return;
        }
        
        this.isWorking   = true;
        this.isPinned    = false;
        this.pinPosition = 0;
        this.pinnedPartHeight = 0;
        
        if (this.pinnedBlock) {
            var filtersTables = this.pinnedBlock.select(this.config.filtersTableSelector);
            filtersTables.invoke('removeClassName', this.config.hiddenClassName);
            this.pinnedBlock.removeClassName(this.config.pinnedBlockClassName);
            this.pinnedPlaceholder.setStyle({height: '0'});
            this.pinnedBlock.writeAttribute('style', '');
        }
        
        this.isWorking = false;
    },
    
    onTabShow: function(tab)
    {
        if (!this.isVisible
            && (tab = $(tab.id + this.config.tabContentIdSuffix))
            && this.container.descendantOf(tab)) {
            this.isVisible = true;
            this.onScroll();
        }
    },
    
    onTabHide: function(tab)
    {
        if (this.isVisible
            && (tab = $(tab.id + this.config.tabContentIdSuffix))
            && this.container.descendantOf(tab)) {
            this.isVisible = false;
            this.unPin();
        }
    },
    
    checkPosition: function()
    {
        if (!this.isPinned || this.isWorking) {
            return;
        }
        
        this.isWorking  = true;
        this.pinnedPartHeight = this.pinnedBlock.getHeight();
        var newPosition = this.getFixedHeaderHeight();
        
        if (newPosition != this.pinPosition) {
            this.pinPosition = newPosition;
            this.pinnedBlock.setStyle({top: newPosition + 'px'});
        }
        
        this.isWorking = false;
    },
    
    onScroll: function()
    {
        if (this.isWorking || !this.isVisible) {
            return;
        }
        
        var tableHeight = this.gridTable.getHeight();
        
        if (tableHeight > 0) {
            var tableTop = this.gridTable.viewportOffset().top;
            var headerHeight = this.getFixedHeaderHeight();
            
            if (tableTop+tableHeight+this.pinnedPartHeight < headerHeight) {
                this.unPin();
            } else if (tableTop-headerHeight-this.pinnedPartHeight < 0) {
                this.pin();
            } else {
                this.unPin();
            }
        }
    }
};

blcg.Grid.ProfilesBar = Class.create();
blcg.Grid.ProfilesBar.prototype = {
    initialize: function(barId, profiles, sortedIds, actions, gridObjectName, config)
    {
        this.config = Object.extend({
            removableUrlParams: [],
            profileIdPlaceholder: '{{profile_id}}',
            profileItemIdPrefix: 'blcg-grid-profile-item-',
            profileOptionsClassName: 'blcg-grid-profiles-list-options',
            profilesListClassName: 'blcg-grid-profiles-list',
            previousArrowClassName: 'blcg-grid-profiles-bar-button-previous',
            nextArrowClassName: 'blcg-grid-profiles-bar-button-next',
            listCountBaseClassName: 'blcg-grid-profiles-list-count-',
            listFullClassName: 'blcg-grid-profiles-list-full',
            currentClassName: 'blcg-current',
            baseClassName: 'blcg-base',
            disabledClassName: 'blcg-disabled',
            hiddenClassName: 'blcg-no-display',
            maxDisplayedCount: 8
        }, config || {});
        
        this.barId = barId;
        this.bar = $(this.barId);
        this.profilesList = this.bar.select('.' + this.config.profilesListClassName).first();
        this.profileOptions = this.bar.select('.' + this.config.profileOptionsClassName).first();
        this.previousArrow  = this.bar.select('.' + this.config.previousArrowClassName).first()
        this.nextArrow = this.bar.select('.' + this.config.nextArrowClassName).first()
        this.gridObjectName = gridObjectName;
        
        this.sortedIds = $A(sortedIds); // Hash.each() may not keep initial order
        this.profiles  = $H(profiles);
        this.actions   = $H(actions);
        this.baseProfileId    = null;
        this.currentProfile   = null;
        this.currentProfileId = null;
        this.isActionRunning  = false;
        this.isInitializing   = true;
        
        this.sortedIds.each(function(profileId) {
            var profile = this.getProfile(profileId);
            
            if (profile) {
                this.addProfileItem(profile, null);
                
                if (profile.isBase) {
                    this.baseProfileId = profile.id;
                }
                if (profile.isCurrent) {
                    this.currentProfileId = profile.id;
                    this.currentProfile = this.getProfile(this.currentProfileId);
                }
            }
        }.bind(this));
        
        this.isInitializing = true;
        this.currentDisplayIndex = 0;
        this.refreshProfilesCount();
        
        if (this.currentProfile) {
            this.scrollToProfile(this.currentProfileId);
            
            this.actions.each(function(pair) {
                if (!pair.value.appliesToBase && this.currentProfile.isBase) {
                    return;
                }
                if (!pair.value.appliesToCurrent && this.currentProfile.isCurrent) {
                    return;
                }
                
                var actionButton = $(document.createElement('button'));
                
                try {
                    actionButton.type = 'button';
                } catch (e) {
                    // Prevent crash on IE 8
                }
                
                actionButton.update('<span><span>' + pair.value.label.escapeHTML() + '</span></span>');
                actionButton.writeAttribute('title', pair.value.label);
                actionButton.addClassName('blcg-grid-profiles-bar-button');
                actionButton.addClassName('blcg-grid-profiles-bar-button-' + pair.key);
                
                actionButton.observe('click', function(e) {
                    var action = pair.value;
                    
                    if (!action.confirm || confirm(action.confirm)) {
                        this.applyAction(this.currentProfile, pair.key);
                    }
                    
                    e.preventDefault();
                }.bind(this));
                
                if (pair.key == 'copy_new') {
                    this.profilesList.up().insert({top: actionButton});
                } else {
                    this.profileOptions.insert(actionButton);
                }
            }.bind(this));
        } else {
             this.scrollToIndex(0);
        }
        
        this.isInitializing = false;
        this.previousArrow.observe('click', function(e) { this.scrollToPrevious(); }.bind(this));
        this.nextArrow.observe('click', function(e) { this.scrollToNext(); }.bind(this));
    },
    
    refreshProfilesCount: function()
    {
        $w(this.profilesList.className).each(function(listClass) {
            if (listClass.substr(0, this.config.listCountBaseClassName.length) == this.config.listCountBaseClassName) {
                this.profilesList.removeClassName(listClass);
            }
        }.bind(this));
        
        this.profilesCount = this.profiles.size();
        var listClassCount = Math.min(this.profilesCount, this.config.maxDisplayedCount);
        this.profilesList.addClassName('' + this.config.listCountBaseClassName + listClassCount);
        
        if (listClassCount == this.config.maxDisplayedCount) {
            this.profilesList.addClassName(this.config.listFullClassName);
        } else {
            this.profilesList.removeClassName(this.config.listFullClassName);
        }
    },
    
    refreshListArrows: function()
    {
        if (this.profilesCount < this.config.maxDisplayedCount) {
            this.previousArrow.addClassName(this.config.hiddenClassName);
            this.nextArrow.addClassName(this.config.hiddenClassName);
        } else {
            this.previousArrow.removeClassName(this.config.hiddenClassName);
            this.nextArrow.removeClassName(this.config.hiddenClassName);
            
            if (this.currentDisplayIndex == 0) {
                this.previousArrow.addClassName(this.config.disabledClassName);
            } else {
                this.previousArrow.removeClassName(this.config.disabledClassName);
            }
            if (this.profilesCount >= this.config.maxDisplayedCount) {
                if (this.currentDisplayIndex + this.config.maxDisplayedCount < this.profilesCount) {
                    this.nextArrow.removeClassName(this.config.disabledClassName);
                } else {
                    this.nextArrow.addClassName(this.config.disabledClassName);
                }
            }
        }
    },
    
    scrollToIndex: function(index)
    {
        this.currentDisplayIndex = index;
        var hideableSelector = 'li:nth-child(-n+' + index + ')';
        hideableSelector += ',li:nth-child(n+' + (index + this.config.maxDisplayedCount + 1) + ')';
        this.profilesList.select('li').invoke('removeClassName', 'blcg-no-display');
        this.profilesList.select(hideableSelector).invoke('addClassName', 'blcg-no-display');
        this.refreshListArrows();
    },
    
    scrollToPrevious: function()
    {
        this.currentDisplayIndex = Math.max(0, this.currentDisplayIndex - 1);
        this.scrollToIndex(this.currentDisplayIndex);
    },
    
    scrollToNext: function()
    {
        this.currentDisplayIndex = Math.min(
            this.profilesCount - this.config.maxDisplayedCount,
            this.currentDisplayIndex + 1
        );
        this.scrollToIndex(this.currentDisplayIndex);
    },
    
    scrollToProfile: function(profileId)
    {
        var profileIndex   = this.getProfileIndex(profileId),
            leftMostIndex  = Math.min(profileIndex, this.profilesCount - this.config.maxDisplayedCount)
            rightMostIndex = Math.max(0, profileIndex - this.config.maxDisplayedCount + 1);
        
        if (Math.abs(this.currentDisplayIndex - leftMostIndex) > Math.abs(this.currentDisplayIndex - rightMostIndex)) {
            this.currentDisplayIndex = rightMostIndex;
        } else {
            this.currentDisplayIndex = leftMostIndex;
        }
        
        this.scrollToIndex(this.currentDisplayIndex);
    },
    
    refreshScroll: function()
    {
        this.currentDisplayIndex = Math.max(
            0,
            Math.min(
                this.profilesCount - this.config.maxDisplayedCount,
                this.currentDisplayIndex
            )
        );
        this.scrollToIndex(this.currentDisplayIndex);
    },
    
    getProfileIndex: function(profileId)
    {
        return Math.max(0, this.sortedIds.indexOf(profileId));
    },
    
    getProfileItem: function(profileId)
    {
        return $(this.config.profileItemIdPrefix + profileId);
    },
    
    addProfileItem: function(profile, insertBeforeId)
    {
        var item = $(document.createElement('li'));
        item.id  = this.config.profileItemIdPrefix + profile.id;
        item.update('<div><span>' + profile.name.escapeHTML() + '</span></div>');
        var itemButton = item.down('span');
        itemButton.writeAttribute('title', profile.name);
        itemButton.observe('click', this.applyLeftClickAction.bind(this, profile.id));
        
        if (profile.isBase) {
            item.addClassName(this.config.baseClassName);
        }
        if (profile.isCurrent) {
            item.addClassName(this.config.currentClassName);
        }
        
        if (insertBeforeId == null) {
            this.profilesList.insert({bottom: item});
        } else {
            this.getProfileItem(insertBeforeId).insert({before: item});
        }
        
        if (!this.isInitializing) {
            this.refreshProfilesCount();
            this.refreshScroll();
            (item.hasClassName(this.config.hiddenClassName) && this.scrollToProfile(profile.id));
        }
    },
    
    deleteProfileItem: function(profileId)
    {
        this.getProfileItem(profileId).remove();
        this.refreshProfilesCount();
        this.refreshScroll();
    },
    
    getProfile: function(profileId)
    {
        return this.profiles.get(profileId);
    },
    
    addProfile: function(profile)
    {
        var insertBeforeId = null;
        
        this.sortedIds.each(function(profileId) {
            if (insertBeforeId === null) {
                var existingProfile = this.getProfile(profileId);
                
                if (existingProfile
                    && !existingProfile.isBase
                    && (existingProfile.name.toUpperCase() > profile.name.toUpperCase())) {
                    insertBeforeId = profileId;
                }
            }
        }.bind(this));
        
        this.profiles.set(profile.id, profile);
        
        if (insertBeforeId === null) {
            this.sortedIds.push(profile.id);
        } else {
            this.sortedIds.splice(this.sortedIds.indexOf(insertBeforeId), 0, profile.id);
        }
        
        this.addProfileItem(profile, insertBeforeId);
    },
    
    deleteProfile: function(profileId)
    {
        var profile = this.getProfile(profileId);
        
        if (profile) {
            var sortedIndex = this.sortedIds.indexOf(profile.id);
            this.sortedIds  = this.sortedIds.slice(0, sortedIndex).concat(this.sortedIds.slice(sortedIndex + 1));
            this.profiles.unset(profile.id);
            this.deleteProfileItem(profile.id);
        }
    },
    
    renameProfile: function(profileId, newName)
    {
        var profile = this.getProfile(profileId);
        
        if (profile) {
            profile.name = newName;
            this.deleteProfile(profile.id);
            this.addProfile(profile);
        }
    },
    
    handleActionResponse: function(response)
    {
        if (response.actions) {
            $A(response.actions).each(function(action) {
                if (action.type == 'alert') {
                    alert(action.message);
                } else if (action.type == 'reload') {
                    blcg.Grid.Tools.reloadGrid(this.gridObjectName, this.config.removableUrlParams);
                } else if (action.type == 'create') {
                    if (action.profile && action.profile.id) {
                        this.addProfile(action.profile);
                    }
                } else if (action.type == 'rename') {
                    if (action.profileId && action.profileName) {
                        this.renameProfile(action.profileId, action.profileName);
                    }
                } else if (action.type == 'delete') {
                    if (action.profileId) {
                        this.deleteProfile(action.profileId);
                    }
                }
            }.bind(this));
        }
    },
    
    applyAction: function(profile, actionCode, actionValues)
    {
        if (!profile || this.isActionRunning) {
            return;
        }
        
        var action = this.actions.get(actionCode);
        
        if (action) {
            if ((profile.isBase && !action.appliesToBase)
                || (profile.isCurrent && !action.appliesToCurrent)) {
                return;
            }
            
            var windowMode = ((action.mode == 'window') && !actionValues);
            var actionUrl  = (windowMode ? action.windowUrl : action.url);
            actionUrl = actionUrl.replace(this.config.profileIdPlaceholder, profile.id);
            
            if (windowMode) {
                var windowConfig = Object.extend({
                    title: profile.name + ' - ' + action.label
                }, action.windowConfig || {});
                
                blcg.Tools.openDialogFromUrl(actionUrl, windowConfig);
            } else {
                if (action.windowUrl) {
                    blcg.Tools.closeDialog();
                }
                
                var actionUrl = blcg.Tools.getAjaxUrl(actionUrl);
                this.isActionRunning = true;
                
                new Ajax.Request(actionUrl, {
                    method: 'post',
                    parameters: (actionValues || {}),
                    
                    onSuccess: blcg.Tools.handleAjaxOnSuccessResponse.curry(
                        function(transport, response) {
                            this.handleActionResponse(response);
                            this.isActionRunning = false;
                        }.bind(this),
                        blcg.Tools.translate('An error occurred while applying the action')
                    ), 
                    
                    onFailure: function(transport) {
                        alert(blcg.Tools.translate('An error occurred while applying the action'));
                        this.isActionRunning = false;
                    }.bind(this)
                });
            }
        }
    },
    
    applyLeftClickAction: function(profileId)
    {
        var profile = this.getProfile(profileId);
        var actionCode = null;
        
        if (!profile) {
            return;
        }
        
        this.actions.each(function(pair) {
            if (actionCode) {
                return;
            }
            if (pair.value.leftClickable) {
                actionCode = pair.key;
            }
        });
        
        this.applyAction(profile, actionCode);
    }
};

blcg.Grid.Form = Class.create();
blcg.Grid.Form.prototype = {
    initialize: function(containerId, saveUrl, config)
    {
        this.containerId = containerId;
        this.saveUrl = saveUrl;
        
        this.formConfig = Object.extend({
            gridObjectName: null,
            additionalParams: {},
            submitMethod: 'post',
            errorMessage: blcg.Tools.translate('An error occurred while saving the values')
        }, config || {});
    },
    
    save: function()
    {
        var container = $(this.containerId);
        var ajaxCallbacks = false;
        
        if (this.formConfig.gridObjectName) {
            ajaxCallbacks = {
                success: blcg.Tools.handleAjaxOnSuccessResponse.curry(
                    blcg.Grid.Tools.reloadGrid.curry(this.formConfig.gridObjectName, null, null),
                    this.formConfig.errorMessage
                ),
                
                failure: blcg.Tools.handleAjaxOnErrorResponse.curry(this.formConfig.errorMessage)
            };
        }
        
        return blcg.Tools.submitContainerValues(
            container,
            this.saveUrl,
            this.formConfig.additionalParams,
            'post',
            !!ajaxCallbacks,
            ajaxCallbacks || {}
        );
    },
};

blcg.Grid.ConfigForm = Class.create();
blcg.Grid.ConfigForm.prototype = {
    initialize: function(form, rendererTargetId)
    {
        this.form = form;
        this.rendererTargetId = rendererTargetId;
        blcg.Tools.focusFirstInput(this.form);
        
        $(this.form).observe('keydown', function(e) {
            if (e.keyCode == Event.KEY_RETURN) {
                e.stop();
                this.insertParams();
                return;
            }
        }.bind(this));
    },
    
    insertParams: function()
    {
        var optionsForm = new varienForm(this.form);
        
        if (!optionsForm.validator || optionsForm.validator.validate()) {
            var formElements = [];
            var i = 0;
            
            Form.getElements($(this.form)).each(function(e) {
                if (!e.hasClassName('skip-submit')) {
                    formElements[i++] = e;
                }
            });
            
            new Ajax.Request(blcg.Tools.getAjaxUrl($(this.form).action), {
                parameters: Form.serializeElements(formElements),
                
                onSuccess: blcg.Tools.handleAjaxOnSuccessResponse.curry(
                    function(transport, response) { this.updateContent(response.parameters); }.bind(this),
                    blcg.Tools.translate('An error occurred while applying the values')
                ),
                
                onFailure: blcg.Tools.handleAjaxOnErrorResponse.curry(
                    blcg.Tools.translate('An error occurred while applying the values')
                )
            });
            
            blcg.Tools.closeDialog();
        }
    },
    
    updateContent: function(content)
    {
        $(this.rendererTargetId).value = content;
    }
};

blcg.Grid.ColumnsConfigForm = Class.create(blcg.Grid.Form, {
    initialize: function($super, containerId, rowClassName, saveUrl, config)
    {
        this.config = Object.extend({
            tableIdSuffix: '-table',
            rowsIdSuffix: '-table-rows',
            columnsCountIdSuffix: '-count',
            filtersCountIdSuffix: '-filter-count',
            visibleCheckboxClassName: 'blcg-visible-checkbox',
            filterCheckboxClassName: 'blcg-filter-only-checkbox',
            newRowId: '',
            newRowClassNames: [],
            newRowColumns: [],
            orderInputId: '',
            idTemplate: '{{id}}',
            jsIdTemplate: '{{js_id}}',
            orderTemplate: '{{order}}',
            maxOrder: 0,
            orderPitch: 1,
            useDnd: false,
            dndHandleClassName: 'blcg-drag-handle',
            gridObjectName: '',
            additionalSaveParams: {}
        }, config || {});
        
        $super(containerId, saveUrl, {
            gridObjectName: this.config.gridObjectName,
            additionalParams: this.config.additionalSaveParams,
            submitMethod: 'post'
        });
        
        this.config.newRowClassNames = $A(this.config.newRowClassNames);
        this.config.newRowColumns    = $A(this.config.newRowColumns);
        this.config.originalMaxOrder = this.config.maxOrder;
        var quotedIdTemplate = blcg.Tools.quoteRegex(this.config.idTemplate);
        var quotedNewRowId   = blcg.Tools.quoteRegex(this.config.newRowId);
        this.config.idRegex    = new RegExp(quotedIdTemplate, 'g');
        this.config.jsIdRegex  = new RegExp(blcg.Tools.quoteRegex(this.config.jsIdTemplate),'g');
        this.config.orderRegex = new RegExp(blcg.Tools.quoteRegex(this.config.orderTemplate), 'g'); 
        this.config.columnIdRegex = new RegExp(quotedNewRowId.replace(quotedIdTemplate, '(-?[0-9]+)'), 'g');
        
        for (var i=0, l=this.config.newRowColumns.length; i<l; ++i) {
            this.config.newRowColumns[i] = $H(this.config.newRowColumns[i]);
        }
        
        this.columns = $H();
        this.columnsIds = $A();
        this.checkboxes = $H();
        this.filterCheckboxes = $H();
        this.checkedValues = $A();
        this.checkedFilterValues = $A();
        this.orderInputs = $H();
        this.nextId = 0;
        
        this.containerId  = containerId;
        this.rowClassName = rowClassName;
        this.saveUrl = saveUrl;
        
        this.container = $(this.containerId);
        this.table     = $(this.containerId + this.config.tableIdSuffix);
        this.rows      = $(this.containerId + this.config.rowsIdSuffix);
        this.visibleCount = $(this.containerId + this.config.columnsCountIdSuffix);
        this.filtersCount = $(this.containerId + this.config.filtersCountIdSuffix);
        
        if (this.config.useDnd) {
            this.tableDnd  = new blcg.TableDnd(this.table, {
                dragHandleClassName: this.config.dndHandleClassName,
                onDragStart: this.makeTableUnselectable.bind(this, true),
                onDrop: this.makeTableUnselectable.bind(this, false),
                onExchange: function(from, to) { this.exchangeRows(from, to); }.bind(this)
            });
        }
        
        this.initColumns();
        this.initCheckboxes();
        this.updateCount();
    },
    
    initColumns: function()
    {
        this.table.select('.' + this.rowClassName).each(function(row) {
            var columnId = this.getColumnIdFromRowId(row.id);
            this.columns[columnId] = row;
            this.orderInputs[columnId] = $(this.config.orderInputId.replace(this.config.idRegex, columnId));
            this.columnsIds.push(columnId);
        }.bind(this));
    },
    
    initCheckboxes: function()
    {
        this.columnsIds.each(function(columnId) {
            var column = this.columns[columnId];
            var visibleCheckbox = column.select('.' + this.config.visibleCheckboxClassName).first();
            var filterCheckbox  = column.select('.' + this.config.filterCheckboxClassName).first();
            this.prepareColumnCheckboxes(columnId, visibleCheckbox, filterCheckbox);
        }.bind(this));
    },
    
    updateCount: function()
    {
        this.checkedValues = this.checkedValues.uniq();
        this.checkedFilterValues = this.checkedFilterValues.uniq();
        this.visibleCount.update(this.checkedValues.size());
        this.filtersCount.update(this.checkedFilterValues.size());
    },
    
    makeTableUnselectable: function(unselectable)
    {
        this.rows.setStyle({
            'MozUserSelect': (unselectable ? 'none' : ''),
            'KhtmlUserSelect': (unselectable ? 'none' : ''),
            'userSelect': (unselectable ? 'none' : '')
        });
        
        if (Prototype.Browser.IE) {
            this.rows.ondrag = (unselectable ? function() { return false; } : null)
            this.rows.onselectstart = (unselectable ? function() { return false; } : null);
        } else if (Prototype.Browser.Opera) {
            this.rows.writeAttribute('unselectable', (unselectable ? 'on' : 'off'));
        }
    },
    
    getColumnIdFromRowId: function(rowId)
    {
        // Reset RegExp state
        // See: http://stackoverflow.com/questions/1520800/why-regexp-with-global-flag-in-javascript-give-wrong-results
        this.config.columnIdRegex.lastIndex = 0;
        var result = this.config.columnIdRegex.exec(rowId);
        
        if (result && result.length) {
            return result[1];
        }
        
        return -1;
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
                
                if (i%2 == 0) {
                    this.columns[fromId].removeClassName('even').addClassName('odd');
                    this.columns[toId].removeClassName('odd').addClassName('even');
                } else {
                    this.columns[fromId].removeClassName('odd').addClassName('even');
                    this.columns[toId].removeClassName('even').addClassName('odd');
                }
                
                this.columnsIds[i] = fromId;
                this.columnsIds[i-add] = toId;
                
                var buffer = this.orderInputs[fromId].getValue();
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
    
    redecorateColumns: function()
    {
        var i = 0;
        
        this.columnsIds.each(function(columnId) {
            if (i++%2 == 1) {
                this.columns[columnId].removeClassName('odd').addClassName('even');
            } else {
                this.columns[columnId].removeClassName('even').addClassName('odd');
            }
        }.bind(this));
    },
    
    parseRowTemplate: function(template, nextId, nextOrder)
    {
        template = template.replace(this.config.idRegex, nextId);
        template = template.replace(this.config.jsIdRegex, ('' + nextId).replace('-', '_'));
        template = template.replace(this.config.orderRegex, nextOrder);
        return template;
    },
    
    addRowCell: function(row, template, cellId, nextId, nextOrder, classNames)
    {
        var cell = $(row.insertCell(-1));
        cell.innerHTML = this.parseRowTemplate(template, nextId, nextOrder);
        blcg.Tools.executeNodeJS(cell);
        
        if (cellId) {
            cell.id = this.parseRowTemplate(cellId, nextId, nextOrder);
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
        row.writeAttribute('id', this.parseRowTemplate(this.config.newRowId, nextId, nextOrder));
        
        for (var i=0, l=this.config.newRowClassNames.length; i<l; ++i) {
            row.addClassName(this.parseRowTemplate(this.config.newRowClassNames[i], nextId, nextOrder));
        }
        for (var i=0, l=this.config.newRowColumns.length; i<l; ++i) {
            var template = this.config.newRowColumns[i].get('template');
            var cellId   = this.config.newRowColumns[i].get('id');
            var cellClassNames = $A(this.config.newRowColumns[i].get('classNames'));
            this.addRowCell(row, template, cellId, nextId, nextOrder, cellClassNames);
        }
        
        this.columns[nextId] = row;
        this.columnsIds.push(nextId);
        
        var visibleCheckbox = row.select('.' + this.config.visibleCheckboxClassName).first();
        var filterCheckbox  = row.select('.' + this.config.filterCheckboxClassName).first();
        this.prepareColumnCheckboxes(nextId, visibleCheckbox, filterCheckbox);
        
        this.orderInputs[nextId] = $(this.config.orderInputId.replace(this.config.idRegex, nextId));
        this.updateCount();
        this.redecorateColumns();
        
        if (this.config.useDnd) {
            this.tableDnd.registerNewRow(row);
        }
    },
    
    deleteColumn: function(columnId)
    {
        columnId = '' + columnId;
        var i = this.columnsIds.indexOf(columnId), j;
        
        if (i != -1) {
            this.columns[columnId].remove();
            this.columnsIds.splice(i, 1);
            this.columns.unset(columnId);
            this.checkboxes.unset(columnId);
            this.filterCheckboxes.unset(columnId);
            
            if ((j = this.checkedValues.indexOf(columnId)) != -1) {
                this.checkedValues.splice(j, 1);
            }
            if ((j = this.checkedFilterValues.indexOf(columnId)) != -1) {
                this.checkedFilterValues.splice(j, 1);
            }
            
            this.updateCount();
            this.redecorateColumns();
        }
    },
    
    onVisibleCheckboxClick: function(visibleCheckbox, filterCheckbox, columnId, fromFilter)
    {
        if (visibleCheckbox.checked) {
            this.checkedValues.push(columnId);
        } else {
            var i = this.checkedValues.indexOf(columnId);
            
            if (i != -1) {
                this.checkedValues.splice(i, 1);
            }
            
            if (!fromFilter && filterCheckbox) {
                filterCheckbox.checked = false;
                this.onFilterCheckboxClick(filterCheckbox, visibleCheckbox, columnId, true);
            }
        }
        if (!fromFilter) {
            this.updateCount();
        }
    },
    
    onFilterCheckboxClick: function(filterCheckbox, visibleCheckbox, columnId, fromVisible)
    {
        if (filterCheckbox.checked) {
            this.checkedFilterValues.push(columnId);
            
            if (!fromVisible) {
                visibleCheckbox.checked = true;
                this.onVisibleCheckboxClick(visibleCheckbox, filterCheckbox, columnId, true);
            }
        } else {
            var i = this.checkedFilterValues.indexOf(columnId);
            
            if (i != -1) {
                this.checkedFilterValues.splice(i, 1);
            }
        }
        if (!fromVisible) {
            this.updateCount();
        }
    },
    
    prepareColumnCheckboxes: function(columnId, visibleCheckbox, filterCheckbox)
    {
        if (visibleCheckbox) {
            this.checkboxes[columnId] = visibleCheckbox;
            var handler = this.onVisibleCheckboxClick.bind(this, visibleCheckbox, filterCheckbox, columnId, false);
            visibleCheckbox.observe('click', handler);
            
            if (visibleCheckbox.checked) {
                this.checkedValues.push(columnId);
            }
        }
        if (filterCheckbox) {
            this.filterCheckboxes[columnId] = filterCheckbox;
            
            if (visibleCheckbox) {
                var handler = this.onFilterCheckboxClick.bind(this, filterCheckbox, visibleCheckbox, columnId, false);
                filterCheckbox.observe('click', handler);
            }
            if (filterCheckbox.checked) {
                this.checkedFilterValues.push(columnId);
            }
        }
    },
    
    selectAll: function()
    {
        this.columnsIds.each(function(columnId) {
            if (!!this.checkboxes[columnId]) {
                this.checkboxes[columnId].checked = true;
            }
        }.bind(this));
        
        this.checkedValues = this.columnsIds;
        this.updateCount();
        return false;
    },
    
    unselectAll: function()
    {
        this.columnsIds.each(function(columnId) {
            if (!!this.checkboxes[columnId]) {
                this.checkboxes[columnId].checked = false;
            }
            if (!!this.filterCheckboxes[columnId]) {
                this.filterCheckboxes[columnId].checked = false;
            }
        }.bind(this));
        
        this.checkedValues = $A();
        this.checkedFilterValues = $A();
        this.updateCount();
        return false;
    },
    
    saveColumns: function()
    {
        if ((this.checkedValues.size() == 0)
            || (this.checkedValues.size() == this.checkedFilterValues.size())) {
            alert(blcg.Tools.translate('At least one column must be fully visible'));
            return false;
        }
        return this.save();
    }
});

blcg.Grid.Renderer.ConfigForm = Class.create(blcg.Grid.ConfigForm);

blcg.Grid.Renderer.SelectsManager = Class.create();
blcg.Grid.Renderer.SelectsManager.prototype = {
    initialize: function(renderersConfig, configUrl)
    {
        this.renderersConfig = $H();
        this.configUrl = configUrl;
        this.pendingSelects = $H();
        this.selects = $H();
        
        $A(renderersConfig).each(function(renderer) {
            if (renderer.code) {
                this.renderersConfig.set(renderer.code, Object.extend({
                    code: '',
                    isCustomizable: false
                }, renderer));
            }
        }.bind(this));
    },
    
    getBaseConfig: function()
    {
        return this.renderersConfig;
    },
    
    getSelectConfig: function(select, configButton, rendererTarget)
    {
        var currentCode = $F(select);
        var renderersParams = $H();
        var baseConfig = this.getBaseConfig();
        
        if (currentCode && baseConfig.get(currentCode)) {
            renderersParams.set(currentCode, $F(rendererTarget));
        } else {
            currentCode = null;
            rendererTarget.value = '';
        }
        
        return Object.extend({
            select: select,
            configButton: configButton,
            rendererTarget: rendererTarget,
            currentCode: currentCode,
            renderersParams: renderersParams
        }, this.getSelectAdditionalConfig.apply(this, arguments));
    },
    
    getSelectAdditionalConfig: function()
    {
        return {};
    },
    
    registerSelect: function(selectId, configButtonId, rendererTargetId, initialize)
    {
        if (initialize) {
            this.initializeSelect(
                selectId,
                configButtonId,
                rendererTargetId,
                Array.prototype.slice.call(arguments, 4)
            );
        } else {
            this.pendingSelects.set(selectId, {
                selectId: selectId,
                configButtonId: configButtonId,
                rendererTargetId: rendererTargetId,
                additional: Array.prototype.slice.call(arguments, 4)
            });
        }
    },
    
    initializeSelect: function(selectId, configButtonId, rendererTargetId, additional)
    {
        var select = $(selectId);
        var configButton = $(configButtonId);
        var rendererTarget = $(rendererTargetId);
        
        if (select && configButton && rendererTarget) {
            var argumentsList = [select, configButton, rendererTarget];
            
            if (additional.length > 0) {
                argumentsList = argumentsList.concat(additional);
            }
            
            this.selects.set(select.id, this.getSelectConfig.apply(this, argumentsList));
            configButton.hide();
            this.onSelectChange(select.id);
            select.observe('change', this.onSelectChange.bind(this, select.id));
        }
    },
    
    initializeSelects: function()
    {
        this.pendingSelects.each(function(pair) {
            var select = pair.value;
            
            this.initializeSelect(
                select.selectId,
                select.configButtonId,
                select.rendererTargetId,
                select.additional
            );
            
            this.pendingSelects.unset(pair.key);
        }.bind(this));
    },
    
    getRendererFromSelectCode: function(code)
    {
        return this.renderersConfig.get(code);
    },
    
    handleSelectChange: function(selectConfig)
    {
        return;
    },
    
    onSelectChange: function(selectId)
    {
        var selectConfig = this.selects.get(selectId);
        
        if (selectConfig) {
            var newCode = $F(selectConfig.select);
            var baseConfig = this.getBaseConfig();
            
            if (newCode && baseConfig.get(newCode)) {
                var newRenderer = this.getRendererFromSelectCode(newCode);
                
                if (newRenderer && newRenderer.isCustomizable) {
                   if (selectConfig.currentCode) {
                        selectConfig.renderersParams.set(selectConfig.currentCode, $F(selectConfig.rendererTarget));
                    }
                    if (selectConfig.renderersParams.get(newCode)) {
                        selectConfig.rendererTarget.value = selectConfig.renderersParams.get(newCode);
                    } else {
                        selectConfig.rendererTarget.value = '';
                    }
                    
                    selectConfig.configButton.stopObserving('click');
                    selectConfig.configButton.show();
                    
                    selectConfig.configButton.observe('click', function() { 
                        blcg.Tools.openDialogFromPost(
                            this.configUrl,
                            {
                                'code': newRenderer.code,
                                'renderer_target_id': selectConfig.rendererTarget.id,
                                'params': $F(selectConfig.rendererTarget)
                            },
                            newRenderer.windowConfig
                        );
                    }.bind(this));
                    
                } else {
                    selectConfig.configButton.stopObserving('click');
                    selectConfig.configButton.hide();
                }
                
                selectConfig.currentCode = newCode;
            } else {
                if (selectConfig.currentCode) {
                    selectConfig.renderersParams.set(selectConfig.currentCode, $F(selectConfig.rendererTarget));
                }
                
                selectConfig.configButton.stopObserving('click');
                selectConfig.configButton.hide();
                selectConfig.rendererTarget.value = '';
                selectConfig.currentCode = '';
            }
            
            this.handleSelectChange(selectConfig);
        }
    }
};

blcg.Grid.Renderer.Collection.SelectsManager = Class.create(blcg.Grid.Renderer.SelectsManager);

blcg.Grid.Renderer.Attribute.SelectsManager  = Class.create(blcg.Grid.Renderer.SelectsManager, {
    initialize: function($super, renderersConfig, configUrl, attributesConfig)
    {
        this.attributesConfig = $H();
        
        $A(attributesConfig).each(function(attribute) {
            if (attribute.code) {
                this.attributesConfig.set(attribute.code, Object.extend({
                    code: '',
                    isEditable: false,
                    rendererCode: ''
                }, attribute));
            }
        }.bind(this));
        
        $super(renderersConfig, configUrl);
    },
    
    getBaseConfig: function()
    {
        return this.attributesConfig;
    },
    
    getSelectAdditionalConfig: function(select, configButton, rendererTarget, editableConfig)
    {
        return {
            editableConfig: Object.extend({
                editableContainerId: false,
                editableCheckboxId: false,
                yesMessageText: blcg.Tools.translate('Yes'),
                noMessageText: blcg.Tools.translate('No')
            }, editableConfig || {})
        };
    },
    
    getRendererFromSelectCode: function(code)
    {
        var renderer = null;
        var attributeConfig = this.attributesConfig.get(code);
        
        if (attributeConfig && attributeConfig.rendererCode) {
            renderer = this.renderersConfig.get(attributeConfig.rendererCode);
        }
        
        return renderer;
    },
    
    handleSelectChange: function(selectConfig)
    {
        if (!selectConfig.editableConfig) {
            return;
        }
        
        var isEditable = false;
        
        if (selectConfig.currentCode) {
            isEditable = !!this.attributesConfig.get(selectConfig.currentCode).isEditable;
        }
        
        if (selectConfig.editableConfig.editableContainerId) {
            var container = $(selectConfig.editableConfig.editableContainerId);
            
            if (container) {
                var checkbox = false;
                
                if (selectConfig.editableConfig.editableCheckboxId) {
                    checkbox = $(selectConfig.editableConfig.editableCheckboxId);
                }
                if (checkbox) {
                    checkbox.disabled = !isEditable;
                } else {
                    if (isEditable) {
                        container.innerHTML = selectConfig.editableConfig.yesMessageText;
                    } else {
                        container.innerHTML = selectConfig.editableConfig.noMessageText;
                    }
                }
            }
        }
    }
});

blcg.Grid.CustomColumn.ConfigButton = Class.create();
blcg.Grid.CustomColumn.ConfigButton.prototype = {
    initialize: function(code, configButtonId, configTargetId, configUrl, windowConfig)
    {
        $(configButtonId).observe('click', function() { 
            blcg.Tools.openDialogFromPost(
                configUrl,
                {
                    'code': code,
                    'config_target_id': configTargetId,
                    'params': $F(configTargetId)
                },
                windowConfig
            );
        });
    }
};

blcg.Grid.CustomColumn.ConfigForm = Class.create(blcg.Grid.ConfigForm);

blcg.Grid.CustomColumn.RowColorizer = {
    colorizeRow: function(childId, backgroundColor, textColor, onlyCell)
    {
        var element   = $(childId);
        var upElement = null;
        var searchFor = (!!onlyCell ? 'td' : 'tr');
        
        if (element && (upElement = element.up(searchFor))) {
            if (backgroundColor != '') {
                upElement.setStyle({backgroundColor: backgroundColor});
            }
            if (textColor != '') {
                upElement.setStyle({color: textColor});
                // Force color for links, as they certainly are given a specific one
                upElement.select('a').each(function(link) { link.setStyle({color: textColor}); });
            }
        }
    }
};

blcg.Grid.Editor = Class.create();
blcg.Grid.Editor.prototype = {
    initialize: function(tableId, cells, rowsIds, config)
    {
        this.config = Object.extend({
            additionalParams: {},
            globalParams: {},
            overlayShowDelay: 50,
            overlayHideDelay: 25,
            overlayTopOffset: 2,
            overlayLeftOffset: -3,
            formIdBase: 'blcg-column-editor-form-',
            overlayIdBase: 'blcg-column-editor-overlay-',
            overlayClassName: 'blcg-column-editor-overlay',
            overlayEditClassName: 'blcg-column-editor-overlay-edit',
            overlayValidateClassName: 'blcg-column-editor-overlay-validate',
            overlayCancelClassName: 'blcg-column-editor-overlay-cancel',
            idleOverlayClassName: 'blcg-column-editor-overlay-container-idle',
            editedOverlayClassName: 'blcg-column-editor-overlay-container-editing',
            editedCellClassName: 'blcg-column-editor-editing',
            requiredCellClassName: 'blcg-column-editor-editing-required',
            updatedCellClassName: 'blcg-column-editor-updated', 
            requiredMarkerSelector: '.blcg-editor-required-marker',
            inputtableFieldsSelector: '.select, .required-entry, .input-text'
        }, config || {});
        
        if (!Object.isArray(this.config.additionalParams)) {
            this.config.additionalParams = $H(this.config.additionalParams);
        } else {
            this.config.additionalParams = $H();
        }
        if (!Object.isArray(this.config.globalParams)) {
            this.config.globalParams = $H(this.config.globalParams);
        } else {
            this.config.globalParams = $H();
        }
        
        this.tableId = tableId;
        this.table   = $(tableId);
        this.cells   = $A(cells);
        this.rowsIds = $A(rowsIds);
        
        this.editWindow = null;
        this.isRequestRunning = false;
        
        if (!this.table
            || (this.cells.length == 0) 
            || (this.rowsIds.length == 0)) {
            return false;
        }
        
        this.initCells();
    },
    
    getElementIndex: function(element)
    {
        var index = element.readAttribute('blcg-index');
        
        if (index === null) {
            index = element.previousSiblings().length;
            element.writeAttribute('blcg-index', index);
        }
        
        return index;
    },
    
    initCells: function()
    {
        this.editedCell       = null;
        this.previousValue    = null;
        this.hasPreviousValue = false;
        
        this.table.up().select('#' + this.tableId + ' > tbody > tr > td').each(function(cell) {
            var row = cell.up();
            var rowIndex= this.getElementIndex(row);
            
            if (this.rowsIds[rowIndex]) {
                var cellIndex = this.getElementIndex(cell);
                
                if (this.cells[cellIndex]) {
                    cell.observe('mouseover', this.onCellMouseOver.bind(this, cell));
                    cell.observe('mouseout',  this.onCellMouseOut.bind(this,  cell));
                }
            }
        }.bind(this));
        
        this.shortHoveredCell  = null;
        this.longHoveredCell   = null;
        this.hoverStartTimeout = null;
        this.hoverStopTimeout  = null;
    },
    
    compareCells: function(cell1, cell2)
    {
        return (cell1 && cell2 ? (cell1.identify() === cell2.identify()) : false);
    },
    
    createDiv: function(id, classNames)
    {
        var div = $(document.createElement('div'));
        
        if (id) {
            div.id = id;
        }
        if (classNames) {
            $A(classNames).each(function(className) {
                div.addClassName(className);
            });
        }
        
        return div;
    },
    
    createCellOverlay: function(cell)
    {
        var overlay = this.createDiv(this.getCellOverlayId(cell), [this.config.overlayClassName]);
        
        overlay.setStyle({
            display: 'none',
            position: 'absolute'
        }).observe('mouseover', function() {
            this.onCellMouseOver(cell);
        }.bind(this)).observe('mouseout', function() {
            this.onCellMouseOut(cell);
        }.bind(this));
        
        document.body.appendChild(overlay);
        return overlay;
    },
    
    positionCellOverlay: function(cell, overlay, mustShow)
    {
        overlay = (overlay ? overlay : this.getCellOverlay(cell));
        var offset = cell.cumulativeOffset();
        var scrollOffset = cell.cumulativeScrollOffset();
        offset.left -= scrollOffset.left;
        var width;
        
        if (!overlay.visible()) {
            overlay.show();
            width = overlay.getWidth();
            overlay.hide();
        } else {
            width = overlay.getWidth();
        }
        
        overlay.setStyle({
            position: 'absolute',
            top: (offset.top + this.config.overlayTopOffset) + 'px',
            left: (offset.left + cell.getWidth() - width + this.config.overlayLeftOffset) + 'px'
        });
        
        if (mustShow) {
            overlay.show();
        }
    },
    
    fillCellOverlay: function(cell, overlay)
    {
        overlay = (overlay ? overlay : this.getCellOverlay(cell));
        
        if (cell.hasClassName(this.config.editedCellClassName)) {
            if (!overlay.hasClassName(this.config.editedOverlayClassName)) {
                overlay.innerHTML = '';
                var div = this.createDiv(null, [this.config.overlayValidateClassName]);
                div.observe('click', this.validateEdit.bind(this));
                overlay.appendChild(div);
                div = this.createDiv(null, [this.config.overlayCancelClassName]);
                div.observe('click', this.cancelEdit.bind(this));
                overlay.appendChild(div);
                overlay.removeClassName(this.config.idleOverlayClassName);
                overlay.addClassName(this.config.editedOverlayClassName);
            }
        } else if (!overlay.hasClassName(this.config.idleOverlayClassName)) {
            overlay.innerHTML = '';
            var div = this.createDiv(null, [this.config.overlayEditClassName]);
            div.observe('click', this.editCell.bind(this, cell));
            overlay.appendChild(div);
            overlay.removeClassName(this.config.editedOverlayClassName);
            overlay.addClassName(this.config.idleOverlayClassName);
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
    
    getCellOverlayId: function(cell)
    {
        return this.config.overlayIdBase + cell.identify();
    },
    
    getCellOverlay: function(cell)
    {
        var overlay = $(this.getCellOverlayId(cell));
        return (overlay ? overlay : this.createCellOverlay(cell));
    },
    
    stopHoverStartTimeout: function()
    {
        if (this.hoverStartTimeout) {
            window.clearTimeout(this.hoverStartTimeout);
            this.hoverStartTimeout = null;
        }
    },
    
    stopHoverEndTimeout: function()
    {
        if (this.hoverEndTimeout) {
            window.clearTimeout(this.hoverEndTimeout);
            this.hoverEndTimeout = null;
        }
    },
    
    onCellMouseOver: function(cell)
    {
        this.shortHoveredCell = cell;
        
        if (!this.compareCells(this.shortHoveredCell, this.longHoveredCell)) {
            this.stopHoverStartTimeout();
            
            this.hoverStartTimeout = window.setTimeout(function() {
                this.hoverStartTimeout = null;
                this.stopHoverEndTimeout();
                
                if (this.longHoveredCell) {
                    this.hideCellOverlay(this.longHoveredCell);
                }
                
                this.longHoveredCell = cell;
                this.showCellOverlay(cell);
            }.bind(this), this.config.overlayShowDelay);
        } else {
            this.stopHoverStartTimeout();
            this.stopHoverEndTimeout();
        }
    },
    
    onCellMouseOut: function(cell)
    {
        if (this.compareCells(this.shortHoveredCell, cell)) {
            this.shortHoveredCell = null;
            this.stopHoverStartTimeout();
        }
        if (this.compareCells(this.longHoveredCell, cell)) {
            this.stopHoverEndTimeout();
            
            this.hoverEndTimeout = window.setTimeout(function() {
                this.hoverEndTimeout = null;
                this.hideCellOverlay(this.longHoveredCell);
                this.longHoveredCell = null;
            }.bind(this), this.config.overlayHideDelay);
        }
    },
    
    parseCellParamKey: function(baseKey, valueKey)
    {
        var paramKey = '';
        var bracketPosition = valueKey.indexOf('[');
            
        if (bracketPosition != -1) {
            paramKey = baseKey + '[' + valueKey.substr(0, bracketPosition) + ']' + valueKey.substr(bracketPosition);
        } else {
            paramKey = baseKey + '[' + valueKey + ']';
        }
        
        return paramKey;
    },
    
    getCellParamsHash: function(cell)
    {
        var config = this.cells[this.getElementIndex(cell)];
        var rowIds = this.rowsIds[this.getElementIndex(cell.up())];
        var params = $H();
        
        $H(rowIds).each(function(pair) {
            params.set(this.parseCellParamKey(config.idsKey, pair.key), pair.value);
        }.bind(this));
        
        this.config.additionalParams.each(function(pair) {
            params.set(this.parseCellParamKey(config.additionalKey, pair.key), pair.value);
        }.bind(this));
        
        if (!Object.isArray(config.columnParams)) {
            $H(config.columnParams).each(function(pair) {
                params.set(this.parseCellParamKey(config.additionalKey, pair.key), pair.value);
            }.bind(this));
        }
        
        params.update(this.config.globalParams);
        return params;
    },
    
    parseHashDimensions: function(hash, dimensions)
    {
        var viewportDimensions = document.viewport.getDimensions();
        
        $H(dimensions).each(function(pair) {
            if (hash.get(pair.key) != '') {
                var dimension = '' + hash.get(pair.key);
                
                if (dimension.substr(dimension.length-1) == '%') {
                    if (!isNaN(dimension = parseInt(dimension.substr(0, dimension.length-1)))) {
                        hash.set(pair.key, parseInt(viewportDimensions[pair.value]*dimension/100));
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
        if (this.isRequestRunning) {
            return;
        }
        if (!this.compareCells(this.editedCell, cell)) {
            this.cancelEdit();
            this.editedCell = cell;
            var cellConfig  = this.cells[this.getElementIndex(cell)];
            var editUrl     = cellConfig.editUrl;
            var editParams  = this.getCellParamsHash(this.editedCell);
            
            if (cellConfig.inGrid) {
                this.isRequestRunning = true;
                
                new Ajax.Request(blcg.Tools.getAjaxUrl(editUrl), {
                    method: 'post',
                    parameters: editParams,
                    
                    onSuccess: function(transport) {
                        var response= blcg.Tools.handleAjaxOnSuccessResponse(
                            null,
                            blcg.Tools.translate('An error occurred while initializing the edit form'),
                            transport
                        );
                        
                        this.isRequestRunning = false;
                        
                        if (response) {
                            try {
                                var cell = this.editedCell;
                                cell.addClassName(this.config.editedCellClassName);
                                this.previousValue = cell.innerHTML;
                                this.hasPreviousValue = true;
                                
                                var form = document.createElement('form');
                                form.id = this.config.formIdBase + cell.identify();
                                form.innerHTML = response.content;
                                cell.innerHTML = '';
                                cell.appendChild(form);
                                blcg.Tools.executeNodeJS(cell);
                                
                                this.fillCellOverlay(cell);
                                this.positionCellOverlay(cell, null, this.compareCells(cell, this.shortHoveredCell));
                                
                                cell.select(this.config.requiredMarkerSelector).each(function(element) {
                                    element.hide();
                                    cell.addClassName(this.config.requiredCellClassName);
                                }.bind(this));
                                
                                var formInputs = $(form).select(this.config.inputtableFieldsSelector);
                                
                                formInputs.each(function(input) {
                                    input.observe('keydown', function(e) {
                                        switch (e.keyCode) {
                                            case Event.KEY_RETURN:
                                                e.preventDefault();
                                                this.validateEdit();
                                                break;
                                            case Event.KEY_ESC:
                                                e.preventDefault();
                                                this.cancelEdit();
                                                break;
                                        }
                                    }.bind(this));
                                }.bind(this));
                                
                                if (formInputs.size() > 0) {
                                    formInputs.first().activate();
                                }
                            } catch (e) {
                                this.cancelEdit();
                                alert(blcg.Tools.translate('An error occurred while initializing the edit form'));
                            }
                        } else {
                            this.cancelEdit();
                        }
                    }.bind(this),
                    
                    onFailure: function(transport) {
                        this.isRequestRunning = false;
                        this.cancelEdit();
                        alert(blcg.Tools.translate('An error occurred while initializing the edit form'));
                    }.bind(this)
                });
            } else {
                var windowConfig = $H(cellConfig.window);
                windowConfig.set('closeCallback', function() { this.cancelEdit(true); return true; }.bind(this));
                
                windowConfig = this.parseHashDimensions(windowConfig, {
                    width: 'width',
                    height: 'height',
                    minWidth: 'width',
                    minHeight: 'height'
                });
                
                editUrl += (editUrl.match(new RegExp('\\?')) ? '&' : '?') + editParams.toQueryString();
                this.editWindow = blcg.Tools.openIframeDialog(editUrl, windowConfig.toObject(), true);
            }
        }
    },
    
    validateEdit: function(formParams)
    {
        if (this.isRequestRunning) {
            return;
        }
        if (this.editedCell) {
            var cell   = this.editedCell;
            var cellId = cell.identify();
            var params = null;
            var cellConfig = this.cells[this.getElementIndex(cell)];
            
            if (cellConfig.inGrid) {
                var form = $(this.config.formIdBase + cellId);
                
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
                var saveUrl = cellConfig.saveUrl;
                this.isRequestRunning = true;
                
                new Ajax.Request(blcg.Tools.getAjaxUrl(saveUrl), {
                    method: 'post',
                    parameters: params,
                    
                    onSuccess: function(transport) {
                        var response= blcg.Tools.handleAjaxOnSuccessResponse(
                            null,
                            blcg.Tools.translate('An error occurred while editing the value'),
                            transport
                        );
                        
                        this.isRequestRunning = false;
                        
                        if (response) {
                            try {
                                var cell = this.editedCell;
                                cell.addClassName(this.config.updatedCellClassName);
                                cell.removeClassName(this.config.editedCellClassName);
                                cell.removeClassName(this.config.requiredCellClassName);
                                cell.innerHTML = response.content;
                                blcg.Tools.executeNodeJS(cell);
                                
                                this.previousValue = null;
                                this.hasPreviousValue = false;
                                this.editedCell = null;
                                
                                this.fillCellOverlay(cell);
                                this.positionCellOverlay(cell, null, this.compareCells(cell, this.shortHoveredCell));
                            } catch (e) {
                                this.cancelEdit();
                                alert(blcg.Tools.translate('An error occurred while editing the value'));
                            }
                        } else {
                            this.cancelEdit();
                        }
                    }.bind(this),
                    
                    onFailure: function(transport) {
                        this.isRequestRunning = false;
                        this.cancelEdit(false, blcg.Tools.translate('An error occurred while editing the value'));
                    }.bind(this)
                });
            } else {
                this.cancelEdit();
                alert(blcg.Tools.translate('There is no value to save'));
            }
        }
    },
    
    cancelEdit: function(fromDialog)
    {
        if (this.isRequestRunning) {
            return;
        }
        if (this.editedCell) {
            if (this.hasPreviousValue) {
                this.editedCell.innerHTML = this.previousValue;
                this.hasPreviousValue = false;
            }
            
            var cellConfig = this.cells[this.getElementIndex(this.editedCell)];
            this.previousValue = null;
            this.editedCell.removeClassName(this.config.editedCellClassName);
            this.editedCell.removeClassName(this.config.requiredCellClassName);
            this.fillCellOverlay(this.editedCell);
            this.positionCellOverlay(this.editedCell);
            this.editedCell = null;
            
            if (!cellConfig.inGrid && !fromDialog) {
                this.closeEditWindow();
            }
        }
    }
};

blcg.Grid.Filter.Categories = Class.create();
blcg.Grid.Filter.Categories.prototype = {
    initialize: function(inputId, buttonId, containerId, chooserUrl, paramName, windowConfig)
    {
        this.input        = $(inputId);
        this.button       = $(buttonId);
        this.container    = $(containerId);
        this.chooserUrl   = chooserUrl;
        this.paramName    = paramName;
        this.window       = null;
        this.windowConfig = windowConfig;
        this.button.observe('click', this.openChooser.bind(this));
    },
    
    openChooser: function()
    {
        var ids = $F(this.input), chooserUrl = this.chooserUrl;
        chooserUrl += (chooserUrl.match(new RegExp('\\?')) ? '&' : '?') + this.paramName + '=' + ids;
        this.window = blcg.Tools.openIframeDialog(chooserUrl, this.windowConfig, true);
    },
    
    applyChoice: function(ids)
    {
        if (this.window) {
            ids = ids.split(',').uniq().without('', null);
            this.input.value = ids.join(',');
            ids.sort(function(a, b) { a = parseInt(a); b = parseInt(b); return (a > b ? 1 : (a < b ? -1 : 0)); });
            this.container.update(ids.join(', '));
            blcg.Tools.closeDialog(this.window);
        }
    }
};
