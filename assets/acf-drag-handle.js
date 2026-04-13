/* global wp */
/* global acf */
import { ResizeHandle } from '@doubleedesign/vanilla-resize-handle';

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
