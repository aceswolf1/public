/**
 * JavaScript code for the DataTables Input Paging feature.
 *
 * @package TablePress
 * @subpackage DataTables Input Paging
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/* globals jQuery, DataTable */

( function ( $, document ) {

	DataTable.feature.register('inputPaging', function (settings, opts) {
		const api = new DataTable.Api(settings);
		const tags = stylingStructure(api);
		const options = Object.assign({
			firstLast: true,
			previousNext: true,
			pageOf: true
		}, opts);
		// Create the DOM elements for the paging control
		const wrapper = createElement(tags.wrapper);
		const first = createElement(tags.item, api.i18n('oPaginate.sFirst', '\u00AB'), () => api.page('first').draw(false));
		const previous = createElement(tags.item, api.i18n('oPaginate.sPrevious', '\u2039'), () => api.page('previous').draw(false));
		const next = createElement(tags.item, api.i18n('oPaginate.sNext', '\u203A'), () => api.page('next').draw(false));
		const last = createElement(tags.item, api.i18n('oPaginate.sLast', '\u00BB'), () => api.page('last').draw(false));
		const box = createElement(tags.inputItem);
		box.style.display = 'inline-block';
		box.style.margin = '0 0.25em';
		const input = createElement(tags.input);
		const of = createElement({ tag: 'span', className: '' });
		input.setAttribute('type', 'text');
		input.setAttribute('inputmode', 'numeric');
		input.setAttribute('pattern', '[0-9]*');
		input.style.textAlign = 'center';
		// Assemble the DOM structure
		if (options.firstLast) {
			wrapper.appendChild(first);
		}
		if (options.previousNext) {
			wrapper.appendChild(previous);
		}
		wrapper.appendChild(box);
		if (options.previousNext) {
			wrapper.appendChild(next);
		}
		if (options.firstLast) {
			wrapper.appendChild(last);
		}
		box.appendChild(input);
		if (options.pageOf) {
			box.appendChild(of);
		}
		// Block characters other than numbers
		input.addEventListener('keypress', function (e) {
			if (e.charCode < 48 || e.charCode > 57) {
				e.preventDefault();
			}
		});
		// Increase and decrease the input field value when the up or down arrow keys are pressed.
		input.addEventListener('keyup', function (e) {
			if (e.key === 'ArrowUp') {
				e.preventDefault();
				input.value = Math.min(parseInt(input.value, 10) + 1, api.page.info().pages);
				api.page(input.value - 1).draw(false);
			} else if (e.key === 'ArrowDown') {
				e.preventDefault();
				input.value = Math.max(parseInt(input.value, 10) - 1, 1);
				api.page(input.value - 1).draw(false);
			}
		});
		// On new value, redraw the table
		input.addEventListener('input', function () {
			if (input.value) {
				api.page(input.value - 1).draw(false);
			}
		});
		api.on('draw', () => {
			const info = api.page.info();
			// Update the classes for the "jump" buttons to show what is available
			setState(first, tags.item.disabled, info.page === 0);
			setState(previous, tags.item.disabled, info.page === 0);
			setState(next, tags.item.disabled, info.page === info.pages - 1);
			setState(last, tags.item.disabled, info.page === info.pages - 1);
			// Set the new page value into the input box
			if (input.value !== info.page + 1) {
				input.value = info.page + 1;
			}
			// Auto adjust the width so the content is visible
			input.style.width = (input.value.length + 2) + 'ch';
			// Show how many pages there are
			of.textContent = ' / ' + info.pages;
		});
		return wrapper;
	});
	function setState(el, disabledClass, disabled) {
		el.classList.toggle(disabledClass, disabled);
		const a = el.querySelector('a');
		if (a) {
			if (disabled) {
				a.setAttribute('disabled', 'disabled');
			} else {
				a.removeAttribute('disabled');
			}
		}
	}
	/**
	 * Gets details about the DOM structure that input paging needs to build.
	 *
	 * @returns DOM information object
	 */
	function stylingStructure( /*api*/ ) {
		/*
		// TablePress: These styling integrations are not used in TablePress.
		let container = api.table().container();
		let classList = container.classList;
		if (classList.contains('dt-bootstrap5') ||
			classList.contains('dt-bootstrap4') ||
			classList.contains('dt-bootstrap')) {
			return {
				wrapper: {
					tag: 'ul',
					className: 'dt-inputpaging pagination',
				},
				item: {
					tag: 'li',
					className: 'page-item',
					disabled: 'disabled',
					liner: {
						tag: 'a',
						className: 'page-link',
					}
				},
				inputItem: {
					tag: 'li',
					className: 'page-item dt-paging-input'
				},
				input: {
					tag: 'input',
					className: '',
				}
			};
		}
		else if (classList.contains('dt-bulma')) {
			return {
				wrapper: {
					tag: 'ul',
					className: 'dt-inputpaging pagination-list',
				},
				item: {
					tag: 'li',
					className: '',
					disabled: 'disabled',
					liner: {
						tag: 'a',
						className: 'pagination-link',
					}
				},
				inputItem: {
					tag: 'li',
					className: 'dt-paging-input'
				},
				input: {
					tag: 'input',
					className: '',
				}
			};
		}
		else if (classList.contains('dt-foundation')) {
			return {
				wrapper: {
					tag: 'ul',
					className: 'dt-inputpaging pagination',
				},
				item: {
					tag: 'li',
					className: '',
					disabled: 'disabled',
					liner: {
						tag: 'a',
						className: '',
					}
				},
				inputItem: {
					tag: 'li',
					className: 'dt-paging-input'
				},
				input: {
					tag: 'input',
					className: '',
				}
			};
		}
		else if (classList.contains('dt-semanticUI')) {
			return {
				wrapper: {
					tag: 'div',
					className: 'dt-inputpaging ui unstackable pagination menu',
				},
				item: {
					tag: 'a',
					className: 'page-link item',
					disabled: 'disabled'
				},
				inputItem: {
					tag: 'div',
					className: 'dt-paging-input'
				},
				input: {
					tag: 'input',
					className: 'ui input',
				}
			};
		}*/
		return {
			wrapper: {
				tag: 'div',
				className: 'dt-inputpaging dt-paging',
			},
			item: {
				tag: 'button',
				className: 'dt-paging-button',
				disabled: 'disabled',
			},
			inputItem: {
				tag: 'div',
				className: 'dt-paging-input',
				liner: {
					tag: '',
					className: ''
				}
			},
			input: {
				tag: 'input',
				className: 'dt-input',
			}
		};
	}
	/**
	 * Creates a new DOM element.
	 *
	 * @param {Object}   opts Tag and class name
	 * @param {string }  text Text to show in the element
	 * @param {Function} fn   Click event handler
	 * @returns Element
	 */
	function createElement( opts, text, fn ) {
		const el = document.createElement(opts.tag);
		el.className = opts.className;
		if (opts.liner && opts.liner.tag) {
			const liner = createElement(opts.liner, text);
			el.appendChild(liner);
		}
		else {
			// Bottom nesting level
			// eslint-disable-next-line no-lonely-if
			if (text) {
				el.textContent = text;
			}
		}
		// Top level only
		if (fn) {
			el.addEventListener('click', fn);
		}
		return el;
	}

	return DataTable;
}( jQuery, document ) );
