/**
 * @license Copyright (c) 2003-2017, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For complete reference see:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		{ name: 'colors' },
		{ name: 'about' }
	];

	// Remove some buttons provided by the standard plugins, which are
	// not needed in the Standard(s) toolbar.
	config.removeButtons = 'Subscript,Superscript';
	
	config.contentsCss = [ '/templates/default/css/content.css', '/templates/default/css/reset.css' ];
	
	config.height = 500; 

	// Set the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';
	
	// we don't want an empty <p></p> as content
	config.ignoreEmptyParagraph = false;
	
	// allow these tags to accept classes
	config.extraAllowedContent = 'hr(*)';
	config.extraAllowedContent = 'audio(*)';
	config.extraAllowedContent = 'source(*)';
	config.extraAllowedContent = 'code';
	config.extraAllowedContent = 'a(*)';

	// Simplify the dialog windows.
	config.removeDialogTabs = 'image:advanced;link:advanced';
	
	config.extraPlugins = 'widget';
	config.extraPlugins = 'widgetselection';
	config.extraPlugins = 'lineutils';
	config.extraPlugins = 'html5audio';
	
	config.linkShowTargetTab = false
};
