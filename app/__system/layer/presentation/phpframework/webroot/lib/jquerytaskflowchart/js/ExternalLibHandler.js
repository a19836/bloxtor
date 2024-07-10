/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Paulo Lopes Pinto -- http://jplpinto.com
 * The use of this code must comply with the Bloxtor framework license.
 */

/*Go to line 535 of the file jquery.jsPlumb-1.3.16-all.js and jquery.jsPlumb-1.3.16-all-min.js and change the _getOffset function to the folowing, because has a bug.
_getOffset = function(el, _instance) {
	var o = jsPlumb.CurrentLibrary.getOffset(_getElementObject(el));
	if (_instance != null) {
		var z = _instance.getZoom();
		return o ? {left:o.left / z, top:o.top / z } : {left:0,top:0};    
	}
	else
		return o;
},*/

function ExternalLibHandler() {
	var me = this;
	
	me.lib = null;
	me.SVG = null;
	me.Defaults = null;
	me.instance = null;
	me.is_ready = false;
	me.is_cloned = false;
	me.repaint_enable = true;
	
	if (typeof jsPlumb == "object")
		me.lib = jsPlumb;
	else if (typeof FlowHandlerObj == "object")
		me.lib = FlowHandlerObj;
	
	if (me.lib) {
		me.SVG = me.lib.SVG;
		me.Defaults = me.lib.Defaults;
	}
	
	me.init = function() {
		if (me.lib) {
			me.instance = me.lib.getInstance();
			me.is_cloned = true;
			me.setRepaintStatus(me.repaint_enable);
		}
	};
	
	me.isJsPlumb = function() {
		return typeof jsPlumb == "object" && me.lib == jsPlumb;
	};
	
	me.onReady = function(handler) {
		return me.lib.bind("ready", function(e) {
			me.is_ready = true;
			
			handler(e);
		});
	};
	
	me.bind = function(type, handler) {
		return me.instance.bind(type, handler);
	};
	
	me.detachEveryConnection = function() {
		return me.instance.detachEveryConnection();
	};
	
	me.deleteEveryEndpoint = function() {
		return me.instance.deleteEveryEndpoint();
	};
	
	me.unmakeEverySource = function() {
		return me.instance.unmakeEverySource();
	};
	
	me.unmakeEveryTarget = function() {
		return me.instance.unmakeEveryTarget();
	};
	
	me.reset = function() {
		return me.instance.reset();
	};
	
	me.selectEndpoints = function() {
		return me.instance.selectEndpoints();
	};
	
	me.setRenderMode = function(mode) {
		return me.instance.setRenderMode(mode);
	};
	
	me.importDefaults = function(options) {
		var ret = me.instance.importDefaults(options);
		me.Defaults = me.instance.Defaults;
		return ret;
	};
	
	me.draggable = function(j_elm, options) { //j_elm: task
		return me.instance.draggable(j_elm, options);
	};
	
	me.getSelector = function(j_elm) { //j_elm: task
		return me.instance.getSelector(j_elm);
	};
	
	me.makeTarget = function(j_elm) { //j_elm: task
		return me.instance.makeTarget(j_elm);
	};
	
	me.makeSource = function(j_elm) { //j_elm: task
		return me.instance.makeSource(j_elm);
	};
	
	me.connect = function(options) {
		return me.instance.connect(options);
	};
	
	me.getConnections = function() {
		return me.instance.getConnections();
	};
	
	me.deleteEndpoint = function(end_point) {
		return me.instance.deleteEndpoint(end_point);
	};
	
	me.removeAllEndpoints = function(j_elm) { //j_elm: task or end_point
		return me.instance.removeAllEndpoints(j_elm);
	};
	
	me.unmakeSource = function(j_elm) { //j_elm: task or end_point
		return me.instance.unmakeSource(j_elm);
	};
	
	me.unmakeTarget = function(j_elm) { //j_elm: task
		return me.instance.unmakeTarget(j_elm);
	};
	
	me.repaint = function(j_elm, position) { //j_elm: task
		return me.repaint_enable && me.instance.repaint(j_elm, position);
	};
	
	me.repaintEverything = function() {
		if (me.repaint_enable)
			me.instance.repaintEverything();
	};
	
	me.setRepaintStatus = function(status) {
		me.repaint_enable = status;
		me.instance.repaint_enable = status;
	};
	
	me.getRepaintStatus = function() {
		return me.repaint_enable;
	};
	
	me.setZoom = function(zoom_level) {
		me.instance.setZoom(zoom_level);
	};
};
