<?php

namespace Doubleedesign\BasePlugin;

class MediaHandler {

	public function __construct() {
		add_filter('wp_check_filetype_and_ext', [$this, 'allow_svg_upload'], 10, 4);
		add_filter('upload_mimes', [$this, 'add_svg_to_valid_mime_types']);
		add_action('admin_head', [$this, 'fix_svg_preview']);
	}

	function allow_svg_upload($data, $file, $filename, $mimes): array {
		$filetype = wp_check_filetype($filename, $mimes);

		return [
			'ext'             => $filetype['ext'],
			'type'            => $filetype['type'],
			'proper_filename' => $data['proper_filename']
		];
	}

	function add_svg_to_valid_mime_types($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		return $mimes;
	}


	function fix_svg_preview(): void {
		echo '<style type="text/css">
        .attachment-266x266, .thumbnail img {
             width: 100% !important;
             height: auto !important;
        }
        </style>';
	}
}
