(function () {
    'use strict';

    var ResizeHandlePosition;
    (function (ResizeHandlePosition) {
        ResizeHandlePosition["TOP"] = "top";
        ResizeHandlePosition["BOTTOM"] = "bottom";
        ResizeHandlePosition["LEFT"] = "left";
        ResizeHandlePosition["RIGHT"] = "right";
    })(ResizeHandlePosition || (ResizeHandlePosition = {}));

    class ContextHandler {
        element;
        container = document.body;
        constructor({ element, container }) {
            this.element = element;
            this.container = (container instanceof HTMLElement) ? container : (element.parentElement ?? document.body);
        }
        getSiblingsBefore() {
            const nodes = Array.from(this.container?.children ?? []);
            const elementIndex = nodes.indexOf(this.element);
            if (elementIndex === -1)
                return [];
            return Array.from(nodes).slice(0, elementIndex);
        }
        getSiblingsAfter() {
            const nodes = Array.from(this.container?.children ?? []);
            const elementIndex = nodes.indexOf(this.element);
            if (elementIndex === -1)
                return [];
            return Array.from(nodes).slice(elementIndex + 1);
        }
        calculateFreeSpace(side) {
            const containerRect = this.container.getBoundingClientRect();
            const elementRect = this.element.getBoundingClientRect();
            const calculatingVertical = side === ResizeHandlePosition.TOP || side === ResizeHandlePosition.BOTTOM;
            const siblings = (side === ResizeHandlePosition.LEFT || side === ResizeHandlePosition.TOP)
                ? this.getSiblingsBefore()
                : this.getSiblingsAfter();
            const siblingsSize = siblings.reduce((total, sibling) => {
                const siblingRect = sibling.getBoundingClientRect();
                return total + (calculatingVertical ? siblingRect.height : siblingRect.width);
            }, 0);
            // If we want the space on the left or top, we just need to compare the element's position to the container edge
            // and subtract the size of any siblings in the way
            if (side === ResizeHandlePosition.LEFT || side === ResizeHandlePosition.TOP) {
                return (elementRect[calculatingVertical ? 'top' : 'left'] - containerRect[calculatingVertical ? 'top' : 'left']) - siblingsSize;
            }
            // If we want the space on the right or bottom, we need to also subtract the distance from the element to the container edge
            const distanceToContainerEdge = calculatingVertical
                ? containerRect.bottom - elementRect.bottom
                : containerRect.right - elementRect.right;
            return distanceToContainerEdge - siblingsSize;
        }
    }

    function getOppositeSide(side) {
        switch (side) {
            case ResizeHandlePosition.RIGHT:
                return ResizeHandlePosition.LEFT;
            case ResizeHandlePosition.LEFT:
                return ResizeHandlePosition.RIGHT;
            case ResizeHandlePosition.TOP:
                return ResizeHandlePosition.BOTTOM;
            case ResizeHandlePosition.BOTTOM:
                return ResizeHandlePosition.TOP;
        }
    }

    class ResizeHandle {
        element;
        container = document.body;
        classes;
        ariaLabel;
        tooltip;
        position;
        lastDragPosition;
        handlePosition;
        context;
        oppositeSide;
        constructor({ element, position, className, ariaLabel, tooltip }) {
            this.element = element;
            this.container = element.parentElement ?? document.body;
            this.classes = ['vt-resizable', className].filter(cls => cls !== undefined);
            this.ariaLabel = ariaLabel ?? 'Resize';
            this.tooltip = tooltip ?? ariaLabel ?? 'Resize';
            this.position = position;
            this.oppositeSide = getOppositeSide(position);
            this.lastDragPosition = undefined;
            this.handlePosition = undefined;
            this.context = new ContextHandler({ element: this.element, container: this.container });
            this.maybeAddClasses();
            this.maybeAddResizeHandle();
        }
        maybeAddClasses() {
            if (!this.canResize()) {
                return;
            }
            this.element.classList.add('vt-resizable');
            this.element.classList.add(`vt-resizable--${this.position}`);
        }
        maybeAddResizeHandle() {
            if (!this.canResize()) {
                console.warn('ResizeHandle: Cannot add resize handle because the element is already at the edge of the container in the direction of resizing.');
                return;
            }
            const dragHandle = document.createElement('button');
            dragHandle.className = this.classes.map(cls => `${cls}__drag-handle`).join(' ');
            dragHandle.ariaLabel = this.ariaLabel;
            dragHandle.title = this.tooltip;
            dragHandle.draggable = true;
            const iconClasses = this.classes.map(cls => `${cls}__drag-handle__icon`).join(' ');
            /* eslint-disable max-len */
            if (this.position === 'top' || this.position === 'bottom') {
                dragHandle.innerHTML = `<span class="${iconClasses}">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-expand" viewBox="0 0 16 16">
				  <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 0 1h-13A.5.5 0 0 1 1 8M7.646.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 1.707V5.5a.5.5 0 0 1-1 0V1.707L6.354 2.854a.5.5 0 1 1-.708-.708zM8 10a.5.5 0 0 1 .5.5v3.793l1.146-1.147a.5.5 0 0 1 .708.708l-2 2a.5.5 0 0 1-.708 0l-2-2a.5.5 0 0 1 .708-.708L7.5 14.293V10.5A.5.5 0 0 1 8 10"/>
				</svg>
			</span>`;
            }
            else {
                dragHandle.innerHTML = `<span class="${iconClasses}">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrows-expand-vertical" viewBox="0 0 16 16">
			  		<path d="M8 15a.5.5 0 0 1-.5-.5v-13a.5.5 0 0 1 1 0v13a.5.5 0 0 1-.5.5M.146 8.354a.5.5 0 0 1 0-.708l2-2a.5.5 0 1 1 .708.708L1.707 7.5H5.5a.5.5 0 0 1 0 1H1.707l1.147 1.146a.5.5 0 0 1-.708.708zM10 8a.5.5 0 0 1 .5-.5h3.793l-1.147-1.146a.5.5 0 0 1 .708-.708l2 2a.5.5 0 0 1 0 .708l-2 2a.5.5 0 0 1-.708-.708L14.293 8.5H10.5A.5.5 0 0 1 10 8"/>
				</svg>
			</span>`;
            }
            /* eslint-enable max-len */
            if (this.position === 'top' || this.position === 'left') {
                this.element.insertBefore(dragHandle, this.element.firstChild);
            }
            else {
                this.element.appendChild(dragHandle);
            }
            dragHandle.addEventListener('dragstart', this.onDragStart.bind(this));
            dragHandle.addEventListener('dragend', this.onDragEnd.bind(this));
        }
        canResize() {
            const space = this.context.calculateFreeSpace(this.position);
            return space >= 32; // 32px is a minimum to avoid showing the handle when there is very little space to resize
        }
        getHandlePosition(event) {
            return {
                x: event.clientX,
                y: event.clientY
            };
        }
        onDragStart(event) {
            this.handlePosition = this.getHandlePosition(event);
            this.container.addEventListener('dragover', this.onContainerDragOver.bind(this));
        }
        onContainerDragOver(event) {
            event.preventDefault(); // Prevent the cursor being the "not allowed" one while dragging
            // Keep updating the last known good position here, because getting the drop position on drag end is unreliable
            this.lastDragPosition = this.getHandlePosition(event);
        }
        onDragEnd() {
            this.container.removeEventListener('dragover', this.onContainerDragOver.bind(this));
            if (!this.handlePosition || !this.lastDragPosition)
                return;
            const oldHandlePosition = this.handlePosition;
            const newHandlePosition = this.lastDragPosition;
            if (this.position === ResizeHandlePosition.RIGHT || this.position === ResizeHandlePosition.LEFT) {
                const diffX = newHandlePosition.x - oldHandlePosition.x;
                this.updateElementWidth(diffX);
            }
            else {
                const diffY = newHandlePosition.y - oldHandlePosition.y;
                this.updateElementHeight(diffY);
            }
        }
        updateElementWidth(diffX) {
            if (isNaN(diffX))
                return;
            this.context.calculateFreeSpace(this.position);
            const oppositeSpace = this.context.calculateFreeSpace(this.oppositeSide);
            const elementRect = this.element.getBoundingClientRect();
            // If no space on the opposite side, just adjust width by the amount the handle was dragged
            if (oppositeSpace < 32) {
                const newWidth = this.position === ResizeHandlePosition.RIGHT
                    ? `${elementRect.width + diffX}px`
                    : `${elementRect.width - diffX}px`;
                this.element.style.setProperty('width', newWidth, 'important');
                return;
            }
            // Otherwise, double the amount the handle was dragged,
            // to account for the fact that the element will be resizing in both directions as the handle is dragged
            // - this will put the dragged edge where the handle was dragged to
            const newWidth = this.position === ResizeHandlePosition.RIGHT
                ? `${elementRect.width + (diffX * 2)}px`
                : `${elementRect.width - (diffX * 2)}px`;
            this.element.style.setProperty('width', newWidth, 'important');
        }
        updateElementHeight(diffY) {
            if (isNaN(diffY))
                return;
            this.context.calculateFreeSpace(this.position);
            const oppositeSpace = this.context.calculateFreeSpace(this.oppositeSide);
            const elementRect = this.element.getBoundingClientRect();
            // If no space on the opposite side, just adjust width by the amount the handle was dragged
            if (oppositeSpace < 32) {
                const newHeight = this.position === ResizeHandlePosition.BOTTOM
                    ? `${elementRect.height + diffY}px`
                    : `${elementRect.height - diffY}px`;
                this.element.style.setProperty('height', newHeight, 'important');
                return;
            }
            // Otherwise, double or halve the amount the handle was dragged (depending on side),
            // to account for the fact that the element will be resizing in both directions as the handle is dragged
            // - this will put the dragged edge where the handle was dragged to
            const newHeight = this.position === ResizeHandlePosition.BOTTOM
                ? `${elementRect.height + (diffY * 2)}px`
                : `${elementRect.height - (diffY * 2)}px`;
            this.element.style.setProperty('height', newHeight, 'important');
        }
    }

    /* global wp */
    /* global acf */

    wp.domReady(() => {

    	acf.addAction('remount', function (elements) {
    		if(!elements) return;

    		const components = shouldAddDragHandle(elements);
    		if(!components) return;

    		const element = document.querySelector('.acf-block-form-modal');
    		if(!element) return;

    		element.style.setProperty('width', 'unset', 'important');
    		element.style.setProperty('min-width', '50%');

    		new ResizeHandle({
    			element: element,
    			position: 'left',
    			className: 'acf-block-form-modal'}
    		);
    	});

    	function shouldAddDragHandle(elements) {
    		if (!elements[0].classList.contains('acf-block-panel')) {
    			return undefined;
    		}

    		if (elements[0].style.display === 'none') {
    			return undefined;
    		}

    		const modal = elements[0].closest('.acf-block-form-modal');
    		if (!modal) {
    			return undefined;
    		}

    		return {
    			modal: modal,
    		}
    	}
    });

})();
//# sourceMappingURL=acf-drag-handle.dist.js.map
