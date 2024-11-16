document.addEventListener('DOMContentLoaded', function () {
	const codeBlocks = document.querySelectorAll('.xdebug-error td:nth-of-type(4)');
	if(codeBlocks.length) {
		codeBlocks.forEach((codeBlock) => {
			codeBlock.classList.add('php');

			// Add highlighting
			// Note: This needs to happen before manipulating the content below, or else things break
			hljs.highlightElement(codeBlock);

			// Wrap truncated arrays and objects in a span
			wrapTruncatedObjects(codeBlock);

			// Add an extra class to string elements that are WordPress block output strings
			wrapWpBlockHtml(codeBlock);

			// Use a regular expression to match the word "class" followed by a class name
			// and wrap the class name in a span with a class that matches what highlight.js uses for others
			wrapKeywords(codeBlock);

			// Detect and wrap arrays
			if(codeBlock.innerText.includes('[') && codeBlock.innerText.includes(']')) {
				wrapArrayContents(codeBlock);
			}

			// Detect classes and add a wrapping div to their inner contents
			if(codeBlock.innerText.includes('{') && codeBlock.innerText.includes('}')) {
				replaceFirstAndLastBraces(codeBlock);
			}

			// Detect function arguments using () and add a wrapping span to their inner contents
			if(codeBlock.innerText.includes('(') && codeBlock.innerText.includes(')')) {
				wrapFunctionArgs(codeBlock);
			}

			// Detect and wrap namespaces and file paths (done together because they both use backslashes)
			if(codeBlock.innerText.includes('\\')) {
				wrapNamespacesAndFilePaths(codeBlock);
			}
		});
	}
});

function wrapTruncatedObjects(node) {
	node.innerHTML = node.innerHTML
		.replaceAll('= [...];', '<span class="hljs-array-truncated">= [...];</span>')
		.replaceAll('= [...]', '<span class="hljs-array-truncated">= [...]</span>')
		.replaceAll('=> { ... };', '<span class="hljs-class-truncated">=> { ... };</span>')
		.replaceAll('{ ... };', '<span class="hljs-class-truncated">{ ... };</span>')

}

function wrapWpBlockHtml(node) {
	const stringElements = node.querySelectorAll('.hljs-string');
	const blockElements = Array.from(stringElements).filter((el) => {
		const content = el.textContent.trim();
		return content.startsWith("'<!-- wp:") && content.endsWith("-->'");
	});
	blockElements.forEach((el) => {
		el.classList.add('hljs-wp-block');

		// Split the content into parts at \n and wrap each newline in a span
		const parts = el.innerHTML.split(/\\n/);
		const wrappedContent = parts.map(part => {
			return `<span class="hljs-wp-block-line">${part}</span>`;
		}).join('');
		el.innerHTML = wrappedContent;
	});
}

function wrapKeywords(node) {
	const keywordSpans = node.querySelectorAll('span.hljs-keyword');
	keywordSpans.forEach((span) => {
		if (span.textContent.trim() === 'class') {
			// Update the class name of this span to identify it as a class keyword
			span.classList.add('class_');

			// Traverse siblings until we find the class name as plain text
			let sibling = span.nextSibling;
			while (sibling && (sibling.nodeType !== node.TEXT_NODE || sibling.textContent.trim() === '')) {
				sibling = sibling.nextSibling;
			}

			if (sibling && sibling.nodeType === node.TEXT_NODE) {
				// Extract the class name using regex
				const match = sibling.textContent.trim().match(/^([a-zA-Z0-9_]+)/);
				if (match) {
					const className = match[1];
					const wrappedClassName = `<span class="hljs-title class_">${className}</span>`;
					const newHTML = sibling.textContent.replace(className, wrappedClassName);

					// Create a temporary container to parse the HTML
					const tempDiv = document.createElement('div');
					tempDiv.innerHTML = newHTML;

					// Insert the parsed HTML back into the DOM and remove the original node
					while (tempDiv.firstChild) {
						sibling.parentNode.insertBefore(tempDiv.firstChild, sibling);
					}
					sibling.parentNode.removeChild(sibling);
				}
			}
		}
	});
}

function wrapFunctionArgs(node) {
	// Add an opening div after the opening (
	node.innerHTML = node.innerHTML.replace('(', '(<span class="hljs-function-args">');
	// Add a closing div before the closing )
	node.innerHTML = node.innerHTML.replace(')', '</span>)');
}

function replaceFirstAndLastBraces(node) {
	const htmlContent = node.innerHTML;

	// Find the position of the first `{` and the last `}`
	const firstBraceIndex = htmlContent.indexOf('{');
	const lastBraceIndex = htmlContent.lastIndexOf('}');

	if (firstBraceIndex !== -1 && lastBraceIndex !== -1) {
		// Split the HTML content into three parts
		const beforeFirstBrace = htmlContent.substring(0, firstBraceIndex);
		const firstBrace = '{<div class="hljs-class-contents">';
		const betweenBraces = htmlContent.substring(firstBraceIndex + 1, lastBraceIndex);
		const lastBrace = '</div>}';
		const afterLastBrace = htmlContent.substring(lastBraceIndex + 1);

		// Reconstruct the HTML with the wrapping div
		node.innerHTML = beforeFirstBrace + firstBrace + betweenBraces + lastBrace + afterLastBrace;
	}
}

function wrapArrayContents(node) {
	// Wrap [ ... ] and their inner contents, excluding [ ... ]
	node.innerHTML = node.innerHTML.replace(/\[(?!\s*\.\.\.\s*\])([^\[\]]*?)\]/g, (match, innerContent) => {
		// Split the inner content on commas
		const parts = innerContent.split(',');
		// Wrap each part in its own span
		const wrappedParts = parts.map(part => `<span class="hljs-array-item">${part},</span>`);
		// Rejoin the parts
		const wrappedContentItems = wrappedParts.join('');

		return `<span class="hljs-array">[<div class="hljs-array-contents">${wrappedContentItems}</div>]</span>`;
	});
}

function wrapNamespacesAndFilePaths(node) {
	const walker = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null);

	let currentNode;
	while ((currentNode = walker.nextNode())) {
		// Skip text nodes that are children of .hljs-string spans
		if (currentNode.parentNode.classList && currentNode.parentNode.classList.contains('hljs-string')) {
			continue;
		}

		// Check if the text node contains a backslash
		if (currentNode.nodeValue.includes('\\')) {
			// Split the text content by `->` to separate parts
			const parts = currentNode.nodeValue.split('->');

			// Create a document fragment to rebuild the node
			const fragment = document.createDocumentFragment();

			parts.forEach((part, index) => {
				if (part.includes('\\')) {
					// Create a span for parts with backslashes
					const span = document.createElement('span');
					if(currentNode.nodeValue.includes('C:\\')) {
						span.className = 'hljs-filepath';
					} else {
						span.className = 'hljs-namespace';
					}
					span.textContent = part;
					fragment.appendChild(span);
				}
				else if (part) {
					// Add plain text for parts without backslashes
					fragment.appendChild(document.createTextNode(part));
				}

				// Add `->` back between parts, except after the last one
				if (index < parts.length - 1) {
					fragment.appendChild(document.createTextNode('->'));
				}
			});

			// Replace the original text node with the fragment
			currentNode.parentNode.replaceChild(fragment, currentNode);
		}
	}
}
