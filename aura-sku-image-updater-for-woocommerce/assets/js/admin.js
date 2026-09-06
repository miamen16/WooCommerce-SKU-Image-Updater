/* global jQuery, AURASKU_Data */
( function ( $ ) {
	'use strict';
	var rowIndex = 0;

	function addRow() {
		rowIndex++;
		var $row = $( '<tr class="aurasku-row" data-row="' + rowIndex + '"><td><input type="file" class="aurasku-file-input" accept="image/png,image/jpeg,image/gif,image/webp" /></td><td><input type="text" class="aurasku-sku-input" placeholder="SKU" /></td><td class="aurasku-preview">&#8212;</td><td><button type="button" class="button aurasku-remove-row">Remove</button></td></tr>' );
		$( '#aurasku-rows-body' ).append( $row );
	}

	function logEntry( type, html ) {
		var icon = type === 'success' ? '\u2705' : ( type === 'error' ? '\u274C' : '\u23F3' );
		var $entry = $( '<div class="aurasku-log-entry aurasku-log-' + type + '">' + icon + ' ' + html + '</div>' );
		$( '#aurasku-log' ).prepend( $entry );
		return $entry;
	}

	function guessSkuFromFilename( filename ) { return filename.replace( /\.[^/.]+$/, '' ); }

	function processRow( $row ) {
		var fileInput = $row.find( '.aurasku-file-input' )[ 0 ];
		var sku = $.trim( $row.find( '.aurasku-sku-input' ).val() );
		if ( ! fileInput || ! fileInput.files.length || ! sku ) { return $.Deferred().resolve().promise(); }

		var formData = new FormData();
		formData.append( 'action', 'aurasku_upload_image' );
		formData.append( 'nonce', AURASKU_Data.nonce );
		formData.append( 'sku', sku );
		formData.append( 'delete_old', $( '#aurasku-delete-old' ).is( ':checked' ) ? 1 : 0 );
		formData.append( 'aurasku_image', fileInput.files[ 0 ] );
		logEntry( 'pending', AURASKU_Data.i18n.processing + ' <strong>' + escapeHtml( sku ) + '</strong>&hellip;' );

		return $.ajax( { url: AURASKU_Data.ajax_url, type: 'POST', data: formData, processData: false, contentType: false } ).then(
			function ( response ) {
				if ( response && response.success ) {
					var thumb = response.data.thumbnail ? '<img class="aurasku-log-thumb" src="' + response.data.thumbnail + '" alt="" />' : '';
					logEntry( 'success', thumb + '<strong>' + escapeHtml( sku ) + '</strong>: ' + escapeHtml( response.data.message ) + ' (<a href="' + response.data.edit_link + '" target="_blank" rel="noopener">edit product</a>)' );
				} else {
					var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error.';
					logEntry( 'error', '<strong>' + escapeHtml( sku ) + '</strong>: ' + escapeHtml( msg ) );
				}
			},
			function () { logEntry( 'error', '<strong>' + escapeHtml( sku ) + '</strong>: ' + AURASKU_Data.i18n.requestFailed ); }
		);
	}

	function escapeHtml( str ) { return $( '<div>' ).text( str == null ? '' : str ).html(); }

	$( function () {
		addRow();
		$( '#aurasku-add-row' ).on( 'click', function ( e ) { e.preventDefault(); addRow(); } );
		$( document ).on( 'click', '.aurasku-remove-row', function () {
			var $rows = $( '.aurasku-row' );
			if ( $rows.length > 1 ) { $( this ).closest( 'tr' ).remove(); }
			else { $( this ).closest( 'tr' ).find( '.aurasku-file-input, .aurasku-sku-input' ).val( '' ); $( this ).closest( 'tr' ).find( '.aurasku-preview' ).html( '&#8212;' ); }
		} );
		$( document ).on( 'change', '.aurasku-file-input', function () {
			var file = this.files && this.files[ 0 ]; var $row = $( this ).closest( 'tr' ); if ( ! file ) { return; }
			var $skuInput = $row.find( '.aurasku-sku-input' ); if ( ! $.trim( $skuInput.val() ) ) { $skuInput.val( guessSkuFromFilename( file.name ) ); }
			var reader = new FileReader(); reader.onload = function ( e ) { $row.find( '.aurasku-preview' ).html( '<img src="' + e.target.result + '" class="aurasku-row-thumb" alt="" />' ); }; reader.readAsDataURL( file );
		} );
		$( '#aurasku-submit' ).on( 'click', function ( e ) {
			e.preventDefault();
			var $rows = $( '.aurasku-row' ).filter( function () { var fileInput = $( this ).find( '.aurasku-file-input' )[ 0 ]; var sku = $.trim( $( this ).find( '.aurasku-sku-input' ).val() ); return fileInput && fileInput.files.length && sku; } );
			if ( ! $rows.length ) { logEntry( 'error', AURASKU_Data.i18n.noRows ); return; }
			var $btn = $( this ); $btn.prop( 'disabled', true ).text( AURASKU_Data.i18n.uploading );
			var chain = $.Deferred().resolve().promise();
			$rows.each( function () { var $row = $( this ); chain = chain.then( function () { return processRow( $row ); } ); } );
			chain.always( function () { $btn.prop( 'disabled', false ).text( AURASKU_Data.i18n.submit ); } );
		} );
	} );
} )( jQuery );
