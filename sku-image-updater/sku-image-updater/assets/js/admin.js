/* global jQuery, SIU_Data */
( function ( $ ) {
	'use strict';

	var rowIndex = 0;

	function addRow() {
		rowIndex++;

		var $row = $(
			'<tr class="siu-row" data-row="' + rowIndex + '">' +
				'<td><input type="file" class="siu-file-input" accept="image/png,image/jpeg,image/gif,image/webp" /></td>' +
				'<td><input type="text" class="siu-sku-input" placeholder="SKU" /></td>' +
				'<td class="siu-preview">&#8212;</td>' +
				'<td><button type="button" class="button siu-remove-row">Remove</button></td>' +
			'</tr>'
		);

		$( '#siu-rows-body' ).append( $row );
	}

	function logEntry( type, html ) {
		var icon = type === 'success' ? '\u2705' : ( type === 'error' ? '\u274C' : '\u23F3' );
		var $entry = $( '<div class="siu-log-entry siu-log-' + type + '">' + icon + ' ' + html + '</div>' );
		$( '#siu-log' ).prepend( $entry );
		return $entry;
	}

	function guessSkuFromFilename( filename ) {
		return filename.replace( /\.[^/.]+$/, '' );
	}

	function processRow( $row ) {
		var fileInput = $row.find( '.siu-file-input' )[ 0 ];
		var sku = $.trim( $row.find( '.siu-sku-input' ).val() );

		if ( ! fileInput || ! fileInput.files.length || ! sku ) {
			return $.Deferred().resolve().promise();
		}

		var formData = new FormData();
		formData.append( 'action', 'siu_upload_image' );
		formData.append( 'nonce', SIU_Data.nonce );
		formData.append( 'sku', sku );
		formData.append( 'delete_old', $( '#siu-delete-old' ).is( ':checked' ) ? 1 : 0 );
		formData.append( 'siu_image', fileInput.files[ 0 ] );

		logEntry( 'pending', SIU_Data.i18n.processing + ' <strong>' + escapeHtml( sku ) + '</strong>&hellip;' );

		return $.ajax( {
			url: SIU_Data.ajax_url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false
		} ).then(
			function ( response ) {
				if ( response && response.success ) {
					var thumb = response.data.thumbnail
						? '<img class="siu-log-thumb" src="' + response.data.thumbnail + '" alt="" />'
						: '';
					logEntry(
						'success',
						thumb + '<strong>' + escapeHtml( sku ) + '</strong>: ' + escapeHtml( response.data.message ) +
							' (<a href="' + response.data.edit_link + '" target="_blank" rel="noopener">edit product</a>)'
					);
				} else {
					var msg = response && response.data && response.data.message ? response.data.message : 'Unknown error.';
					logEntry( 'error', '<strong>' + escapeHtml( sku ) + '</strong>: ' + escapeHtml( msg ) );
				}
			},
			function () {
				logEntry( 'error', '<strong>' + escapeHtml( sku ) + '</strong>: ' + SIU_Data.i18n.requestFailed );
			}
		);
	}

	function escapeHtml( str ) {
		return $( '<div>' ).text( str == null ? '' : str ).html();
	}

	$( function () {
		addRow();

		$( '#siu-add-row' ).on( 'click', function ( e ) {
			e.preventDefault();
			addRow();
		} );

		$( document ).on( 'click', '.siu-remove-row', function () {
			var $rows = $( '.siu-row' );
			if ( $rows.length > 1 ) {
				$( this ).closest( 'tr' ).remove();
			} else {
				$( this ).closest( 'tr' ).find( '.siu-file-input, .siu-sku-input' ).val( '' );
				$( this ).closest( 'tr' ).find( '.siu-preview' ).html( '&#8212;' );
			}
		} );

		$( document ).on( 'change', '.siu-file-input', function () {
			var file = this.files && this.files[ 0 ];
			var $row = $( this ).closest( 'tr' );

			if ( ! file ) {
				return;
			}

			var $skuInput = $row.find( '.siu-sku-input' );
			if ( ! $.trim( $skuInput.val() ) ) {
				$skuInput.val( guessSkuFromFilename( file.name ) );
			}

			var reader = new FileReader();
			reader.onload = function ( e ) {
				$row.find( '.siu-preview' ).html( '<img src="' + e.target.result + '" class="siu-row-thumb" alt="" />' );
			};
			reader.readAsDataURL( file );
		} );

		$( '#siu-submit' ).on( 'click', function ( e ) {
			e.preventDefault();

			var $rows = $( '.siu-row' ).filter( function () {
				var fileInput = $( this ).find( '.siu-file-input' )[ 0 ];
				var sku = $.trim( $( this ).find( '.siu-sku-input' ).val() );
				return fileInput && fileInput.files.length && sku;
			} );

			if ( ! $rows.length ) {
				logEntry( 'error', SIU_Data.i18n.noRows );
				return;
			}

			var $btn = $( this );
			$btn.prop( 'disabled', true ).text( SIU_Data.i18n.uploading );

			var chain = $.Deferred().resolve().promise();

			$rows.each( function () {
				var $row = $( this );
				chain = chain.then( function () {
					return processRow( $row );
				} );
			} );

			chain.always( function () {
				$btn.prop( 'disabled', false ).text( SIU_Data.i18n.submit );
			} );
		} );
	} );
} )( jQuery );
