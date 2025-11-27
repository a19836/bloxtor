//Save native prepareOffsets method from ddmanager
//console.log(typeof nativePrepareOffsets);
if (typeof nativePrepareOffsets == "undefined") //This if is very important in case this file gets included twice, the nativePrepareOffsets var will always continue having the original prepareOffsets function
	nativePrepareOffsets = $.ui.ddmanager.prepareOffsets;

//Overrided prepareOffsets method - init the right offsets to drag an element to an iframe
$.ui.ddmanager.prepareOffsets = function(t, event) {
 	//console.log("prepareOffsets");
	//console.log(t);
	//console.log(event);
	//console.log(nativePrepareOffsets);
	
	//Call parent method
	nativePrepareOffsets.apply(this, arguments);
	//console.log("prepareOffsets");
	var m = $.ui.ddmanager.droppables[t.options.scope] || [];

	//console.log(t.element.offset());
	for (i = 0; i < m.length; i++) {
	  	// console.log(m[i].element[0].className);
		
		//Iframe fixes
		var draggableDocument = t.element[0].ownerDocument;
		//var doc = m[i].document[0];
		var doc = m[i].element[0].ownerDocument; //must be the ownerDocument, otherwise the document[0], returns the parent iframe document 
		
		//console.log(draggableDocument);
		//console.log(doc);
		
		//if the document of the draggable element is different from the document of the droppable elements
		if (doc !== draggableDocument && (doc.defaultView || doc.parentWindow)) { //This allow drag elements inside of iframes. the check of (doc.defaultView || doc.parentWindow) is very important if the layoutuieditor is not set yet, there will not be any doc.defaultView or doc.parentWindow. So we only want to execute the code bellow, if this exists. Otherwise this will break in any block with the Form Module after executing the wizard and replacing the html.
		//if (doc !== document) {
			//console.log("is different draggableDocument");
			//console.log(m[i])
			//console.log(t)
			//console.log((doc.defaultView || doc.parentWindow))
			var iframe = $((doc.defaultView || doc.parentWindow).frameElement);
			var iframeOffset = iframe.offset();
			var el = m[i].element;
			
			//Check our droppable element is in the viewport of out iframe
			var viewport = {
				top: iframe.contents().scrollTop(),
				left: iframe.contents().scrollLeft()
			};
			viewport.right = viewport.left + iframe.width();
			viewport.bottom = viewport.top + iframe.height();
			
			var bounds = el.offset();
			bounds.right = bounds.left + el.outerWidth();
			bounds.bottom = bounds.top + el.outerHeight();
			
			//console.log(el[0].className);
			//console.log(el);
			//console.log(viewport);
			//console.log(bounds);

			if (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom)) {
				//In view port
				var ytop = bounds.top - iframe.contents().scrollTop();
				ytop = ytop < 0 ? 0 : ytop;
				var xtop = bounds.left - iframe.contents().scrollLeft();
				xtop = xtop < 0 ? 0 : xtop;
				var ybottom = bounds.top + el.height() - iframe.contents().scrollTop();
				ybottom = ybottom > iframe.height() ? iframe.height() : ybottom;
				var xbottom = bounds.left + el.width() - iframe.contents().scrollLeft();
				xbottom = xbottom > iframe.width() ? iframe.width() : xbottom;

				//be sure that the offset exissts. According with some tests, we detected that the offset can be undefined.
				if (!m[i].hasOwnProperty("offset"))
					m[i].offset = {};

				m[i].offset.top = iframeOffset.top + ytop;
				m[i].offset.left = iframeOffset.left + xtop;
				m[i].proportions({
					width: xbottom - xtop,
					height: ybottom - ytop,
				});
				m[i].offset2 = m[i].offset;//because of iframe position absolute
				
				//console.log(el[0].className);
				//console.log({top: m[i].offset.top, left: m[i].offset.left, width: xbottom - xtop, height: ybottom - ytop});
			} 
			else {
				//Out of view port - skip
				m[i].proportions().height = 0;
				continue;
			}
		}
	}
};

//choose the correct droppables element (doesn't include the Greedy option, which means it can choose multiple droppables)
$.ui.ddmanager.drop = function(draggable, event) {
//console.log("START");
//console.log(draggable);
//console.log(event);
//console.log(draggable.options);
//console.log(draggable.options.iframeFix);
	
	var dropped = false;
	// Create a copy of the droppables in case the list changes during the drop (#9116)
	$.each( ( $.ui.ddmanager.droppables[ draggable.options.scope ] || [] ).slice(), function() {
		//console.log("0- droppable:"+this.element[0].className);
		//console.log(this);
		
		if ( !this.options ) {
			return;
		}
		
		//console.log(draggable.options.iframeFix);
		//console.log(this.offset);
		//console.log(this.offset2);
		//if iframe position is absolute and draggable is outside of iframe, the draggable must be the offset calculated by the $.ui.ddmanager.prepareOffsets. The offset is getting overwrited, so we need to put the right offset
		if (draggable.options.iframeFix && this.hasOwnProperty("offset2") && this.offset2)
			this.offset = this.offset2;
		
		if ( !this.options.disabled && this.visible && $.ui.intersect( draggable, this, this.options.tolerance, event ) ) {
			dropped = this._drop.call( this, event ) || dropped;
		//console.log("1- droppable:"+this.element[0].className);
		//console.log(this);
		//console.log(dropped);
		}

		if ( !this.options.disabled && this.visible && this.accept.call( this.element[ 0 ], ( draggable.currentItem || draggable.element ) ) ) {
			this.isout = true;
			this.isover = false;
			this._deactivate.call( this, event );
		}
	});
	return dropped;
};

//choose the correct droppables element (includes the Greedy option, which means it can only choose 1 droppable)
$.widget("ui.droppable", $.ui.droppable, {
	_drop: function(event, custom) {
		var draggable = custom || $.ui.ddmanager.current,
			childrenIntersection = false;

		// Bail if draggable and droppable are same element
		if ( !draggable || ( draggable.currentItem || draggable.element )[ 0 ] === this.element[ 0 ] ) {
			return false;
		}

		this.element.find( ":data(ui-droppable)" ).not( ".ui-draggable-dragging" ).each(function() {
			var inst = $( this ).droppable( "instance" );
			
			//if iframe position is absolute and draggable is outside of iframe, the draggable must be the offset calculated by the $.ui.ddmanager.prepareOffsets. The offset is getting overwrited, so we need to put the right offset
			var offset = draggable.options.iframeFix && inst.offset ? inst.offset : inst.element.offset(); 
			//console.log(draggable.options.iframeFix);
			//console.log(offset);
			
			if (
				inst.options.greedy &&
				!inst.options.disabled &&
				inst.options.scope === draggable.options.scope &&
				inst.accept.call( inst.element[ 0 ], ( draggable.currentItem || draggable.element ) ) &&
				$.ui.intersect( draggable, $.extend( inst, { offset: inst.offset } ), inst.options.tolerance, event )
			) { childrenIntersection = true; return false; }
		});
		if ( childrenIntersection ) {
			return false;
		}

		if ( this.accept.call( this.element[ 0 ], ( draggable.currentItem || draggable.element ) ) ) {
			if ( this.options.activeClass ) {
				this.element.removeClass( this.options.activeClass );
			}
			if ( this.options.hoverClass ) {
				this.element.removeClass( this.options.hoverClass );
			}
			this._trigger( "drop", event, this.ui( draggable ) );
			return this.element;
		}

		return false;
	}
});

//fix scroll when elements is dragged inside of iframe
$.ui.plugin.add("draggable", "iframeScroll", {
    drag: function(event, ui, i) {

        var o = i.options;
        var selector = o.iframeFix === true ? "iframe" : o.iframeFix;
		
        //check if mouse in scroll zone
        i.document.find(selector).each(function() {
            var scrolled = false;
            var iframeDocument;
            var iframe = $(this);
            var offset = iframe.offset();
            offset.width = iframe.width();
            offset.height = iframe.height();
            //Check scroll top
            if (offset.left < event.pageX && event.pageX < offset.left + offset.width) {
                if (offset.top < event.pageY && event.pageY < offset.top + o.scrollSensitivity) {
                    iframeDocument = iframe.contents();
                    scrolled = iframeDocument.scrollTop(iframeDocument.scrollTop() - o.scrollSpeed);
                }
            }
            //Check scroll down
            if (offset.left < event.pageX && event.pageX < offset.left + offset.width) {
                if ((offset.top + offset.height - o.scrollSensitivity) < event.pageY && event.pageY < offset.top + offset.height) {
                    iframeDocument = iframe.contents();
                    scrolled = iframeDocument.scrollTop(iframeDocument.scrollTop() + o.scrollSpeed);
                }
            }
            //Check scroll left
            if (offset.left < event.pageX && event.pageX < offset.left + o.scrollSensitivity) {
                if (offset.top < event.pageY && event.pageY < offset.top + offset.height) {
                    iframeDocument = iframe.contents();
                    scrolled = iframeDocument.scrollLeft(iframeDocument.scrollLeft() - o.scrollSpeed);
                }
            }
            //Check scroll right
            if ((offset.left + offset.width - o.scrollSensitivity) < event.pageX && event.pageX < offset.left + offset.width) {
                if (offset.top < event.pageY && event.pageY < offset.top + offset.height) {
                    iframeDocument = iframe.contents();
                    scrolled = iframeDocument.scrollLeft(iframeDocument.scrollLeft() + o.scrollSpeed);
                }
            }

            if (scrolled !== false && $.ui.ddmanager && !o.dropBehaviour) {
                $.ui.ddmanager.prepareOffsets(i, event);
            }

            clearTimeout(i.scrollTimer);
            if (i._mouseStarted) {
                i.scrollTimer = setTimeout(function() {
                    //call drag trigger
                    i._trigger("drag", event);
                    //update offsets
                    if ($.ui.ddmanager) {
                        $.ui.ddmanager.drag(i, event);
                    }
                }, 10);
            }


        });
    },
    stop: function(event, ui, i) {
        clearInterval(i.scrollTimer);
    }
});

//when we move an iframe to another position or parent element, the inner iframes (like template_widgets_iframe) will get refreshed (browser default behaviour and we cannot change it). 
//This refresh action will loose all the droppable elements, but the elements from the previous iframe's html will continue in the $.ui.ddmanager.droppables, which means, that when we try to drag something in jquery, the jquery will break because is trying to access elements that don't exist anymore. So we need to check the existent droppables and remove the old ones.
function resetJqueryUIDDManagerDroppables() {
	var changed = false;
	
	$.each( $.ui.ddmanager.droppables, function(k, v) {
		var scope_droppables = [];
		
		$.each(v, function(idx, item) {
			var is_valid = false;
			
			try {
				item.element.offset(); //if element does not exist anymore, jquery will throw exception
				is_valid = true;
			}
			catch(e) {
				is_valid = false;
			}
			
			if (is_valid)
				scope_droppables.push(item);
			else 
				changed = true;
		});
		
		$.ui.ddmanager.droppables[k] = scope_droppables;
	});
	
	return changed;
}
