ImageResize Plugin for CKEditor 4
=================================

Created by ALL-INKL.COM - Neue Medien Münnich - 29. Jan 2014

This Plugin provides a small image processor. You can limit the size of images
directly on the client without upload images on your server. Big images will be
resized automatically on paste.


## Installation

 1. Download the plugin from http://github.com/nmmf/imageresize

 2. Extract (decompress) the downloaded file into the plugins folder of your
	CKEditor installation.
	Example: http://example.com/ckeditor/plugins/imageresize

 3. Enable the plugin by using the extraPlugins configuration setting.
	Example: CKEDITOR.config.extraPlugins = "imageresize";

 4. Config:
	CKEDITOR.config.imageResize.maxWidth = 800;
	CKEDITOR.config.imageResize.maxHeight = 800;

## Documentation

 // Resize all images in a node:
	CKEDITOR.plugins.imageresize.resizeAll(
		CKEditor Instance,
		(CKEditor node object) parent container,
		(integer) max-width,
		(integer) max-height
	);

 // Resize one image:
	CKEDITOR.plugins.imageresize.resize(
		CKEditor Instance,
		(CKEditor node object) image,
		(integer) max-width,
		(integer) max-height
	);

 // Detect browser support:
 // returns boolean true or false
	CKEDITOR.plugins.imageresize.support();
