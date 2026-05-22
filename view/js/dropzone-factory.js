// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

Dropzone.autoDiscover = false;

// Infer MIME type from the first 12 bytes of a file.
// Returns a MIME string or '' if unrecognised.
function dzSniffType(bytes) {
	// JPEG
	if (bytes[0] === 0xFF && bytes[1] === 0xD8 && bytes[2] === 0xFF) return 'image/jpeg';
	// PNG
	if (bytes[0] === 0x89 && bytes[1] === 0x50 && bytes[2] === 0x4E && bytes[3] === 0x47) return 'image/png';
	// GIF
	if (bytes[0] === 0x47 && bytes[1] === 0x49 && bytes[2] === 0x46) return 'image/gif';
	// BMP
	if (bytes[0] === 0x42 && bytes[1] === 0x4D) return 'image/bmp';
	// WebP  (RIFF....WEBP)
	if (bytes[0] === 0x52 && bytes[1] === 0x49 && bytes[2] === 0x46 && bytes[3] === 0x46 &&
		bytes[8] === 0x57 && bytes[9] === 0x45 && bytes[10] === 0x42 && bytes[11] === 0x50) return 'image/webp';
	// WebM / MKV
	if (bytes[0] === 0x1A && bytes[1] === 0x45 && bytes[2] === 0xDF && bytes[3] === 0xA3) return 'video/webm';
	// AVI  (RIFF....AVI )
	if (bytes[0] === 0x52 && bytes[1] === 0x49 && bytes[2] === 0x46 && bytes[3] === 0x46 &&
		bytes[8] === 0x41 && bytes[9] === 0x56 && bytes[10] === 0x49) return 'video/x-msvideo';
	// WAV  (RIFF....WAVE)
	if (bytes[0] === 0x52 && bytes[1] === 0x49 && bytes[2] === 0x46 && bytes[3] === 0x46 &&
		bytes[8] === 0x57 && bytes[9] === 0x41 && bytes[10] === 0x56 && bytes[11] === 0x45) return 'audio/wav';
	// MP3 sync word or ID3 tag
	if ((bytes[0] === 0xFF && (bytes[1] === 0xFB || bytes[1] === 0xF3 || bytes[1] === 0xF2)) ||
		(bytes[0] === 0x49 && bytes[1] === 0x44 && bytes[2] === 0x33)) return 'audio/mpeg';
	// AAC ADTS
	if (bytes[0] === 0xFF && (bytes[1] === 0xF1 || bytes[1] === 0xF9)) return 'audio/aac';
	// OGG
	if (bytes[0] === 0x4F && bytes[1] === 0x67 && bytes[2] === 0x67 && bytes[3] === 0x53) return 'audio/ogg';
	// FLAC
	if (bytes[0] === 0x66 && bytes[1] === 0x4C && bytes[2] === 0x61 && bytes[3] === 0x43) return 'audio/flac';
	// ftyp-based container (MP4, MOV, M4V, M4A, 3GP, HEIC, AVIF …)
	// The 4-byte box type 'ftyp' sits at offset 4; the major brand follows at offset 8.
	if (bytes[4] === 0x66 && bytes[5] === 0x74 && bytes[6] === 0x79 && bytes[7] === 0x70) {
		var brand = String.fromCharCode(bytes[8], bytes[9], bytes[10], bytes[11]);
		if (/^(heic|heis|heix|hevc|hevx|mif1|msf1|avif|avis)/.test(brand)) return 'image/heic';
		if (/^(M4A |M4B |M4P |f4a |f4b )/.test(brand)) return 'audio/mp4';
		return 'video/mp4'; // mp41, mp42, isom, qt__, 3gp*, m4v_, …
	}
	return '';
}

var DzFactory = function (max_imagesize) {

	// Resolve the effective MIME type: browser-reported first, magic-byte sniff second.
	function effectiveType(file) {
		return file.type || file._sniffedType || '';
	}

	this.createDropzone = function(dropSelector, textareaElementId, clickableSelector, previewsContainerId) {
		return new Dropzone(dropSelector, {
			paramName: 'userfile',
			// 2 GB hard ceiling — prevents Dropzone's built-in check from firing on large
			// videos before our type-aware limit runs in the accept callback below.
			maxFilesize: 2048,
			url: function(files) {
				return effectiveType(files[0]).match(/^image\//)
					? '/media/photo/upload?album='
					: '/media/attachment/upload/chunk';
			},
			acceptedFiles: null, // input[accept] gates the OS picker; Dropzone's check breaks empty-type files on mobile
			clickable: clickableSelector || false,
			previewsContainer: previewsContainerId || null,
			// UDP: chunked upload for non-image files (video, audio, attachments)
			chunking: false,              // toggled on per-file in the processing event
			forceChunking: false,         // toggled on per-file in the processing event
			chunkSize: 50 * 1024 * 1024, // 50 MB per HTTP request
			retryChunks: true,
			retryChunksLimit: 3,
			parallelChunkUploads: false,  // sequential chunks simplify server-side assembly
			dictDefaultMessage: dzStrings.dictDefaultMessage,
			dictFallbackMessage: dzStrings.dictFallbackMessage,
			dictFallbackText: dzStrings.dictFallbackText,
			dictFileTooBig: dzStrings.dictFileTooBig,
			dictInvalidFileType: dzStrings.dictInvalidFileType,
			dictResponseError: dzStrings.dictResponseError,
			dictCancelUpload: dzStrings.dictCancelUpload,
			dictUploadCanceled: dzStrings.dictUploadCanceled,
			dictCancelUploadConfirmation: dzStrings.dictCancelUploadConfirmation,
			dictRemoveFile: dzStrings.dictRemoveFile,
			dictMaxFilesExceeded: dzStrings.dictMaxFilesExceeded,
			accept: function(file, done) {
				var targetTextarea = document.getElementById(textareaElementId);

				function proceed() {
					if (targetTextarea && targetTextarea.setRangeText) {
						targetTextarea.setRangeText("\n[!upload-" + file.name + "]\n",
							targetTextarea.selectionStart, targetTextarea.selectionEnd, "end");
					}
					done();
				}

				function checkAndProceed() {
					var t       = effectiveType(file);
					var isImage = !!t.match(/^image\//);
					var limitMB = isImage ? max_imagesize : 2048;
					if (file.size > limitMB * 1024 * 1024) {
						done((isImage ? 'Image' : 'Video/audio') + ' too large (max ' + limitMB + ' MB)');
					} else {
						proceed();
					}
				}

				if (file.type) {
					checkAndProceed();
				} else {
					// Browser didn't report a MIME type (common for direct camera recordings on mobile).
					// Read 12 bytes and sniff the magic number before checking size or proceeding.
					try {
						var reader = new FileReader();
						reader.onload = function(e) {
							try { file._sniffedType = dzSniffType(new Uint8Array(e.target.result)); } catch (_) {}
							checkAndProceed();
						};
						reader.onerror = function() { checkAndProceed(); };
						reader.readAsArrayBuffer(file.slice(0, 12));
					} catch (_) {
						checkAndProceed();
					}
				}
			},
			init: function() {
				this.on("processing", function(file) {
					if (effectiveType(file).match(/^image\//)) {
						// Images: non-chunked, photo endpoint (url function handles routing)
						this.options.chunking      = false;
						this.options.forceChunking = false;
					} else {
						// Video/audio/attachments: force chunking so dzuuid is always sent
						this.options.chunking      = true;
						this.options.forceChunking = true;
					}
				});

				this.on("error", function(file, message, xhr) {
					var targetTextarea = document.getElementById(textareaElementId);
					if (!targetTextarea || !targetTextarea.setRangeText) return;

					// Normalise message from three possible shapes:
					//   string  — client-side validation or plain-text server error
					//   object  — JSON error from chunk endpoint: {error: "..."}
					//   xhr     — fallback when Dropzone couldn't parse the response
					var errorMsg;
					if (typeof message === 'string' && message.length) {
						errorMsg = message;
					} else if (message && typeof message === 'object') {
						errorMsg = message.error || message.message || JSON.stringify(message);
					} else if (xhr) {
						try {
							var parsed = JSON.parse(xhr.responseText);
							errorMsg = parsed.error || parsed.message || xhr.responseText;
						} catch (_) {
							errorMsg = xhr.responseText || 'Upload failed';
						}
					} else {
						errorMsg = 'Upload failed';
					}

					var errorText  = '[Upload error: ' + errorMsg + ']';
					var placeholder = '[!upload-' + file.name + ']';
					var idx = targetTextarea.value.indexOf(placeholder);

					if (idx !== -1) {
						// Replace the pending placeholder with the error
						targetTextarea.setRangeText(errorText, idx, idx + placeholder.length);
					} else {
						// No placeholder was inserted (pre-accept rejection) — insert at cursor
						var pos    = targetTextarea.selectionStart;
						var prefix = targetTextarea.value.substring(0, pos);
						targetTextarea.setRangeText(
							(prefix.length > 0 && !prefix.endsWith('\n') ? '\n' : '') + errorText + '\n',
							pos, pos, 'end'
						);
					}
					targetTextarea.dispatchEvent(new Event('change', { bubbles: true }));
				});

				this.on('success', function(file, serverResponse) {
					const targetTextarea = document.getElementById(textareaElementId);
					if (targetTextarea.setRangeText) {
						let u = "[!upload-" + file.name + "]";

						// Normalise serverResponse: Dropzone may pass a raw JSON string
						// or a pre-parsed object depending on version and Content-Type.
						let parsed = serverResponse;
						if (typeof serverResponse === 'string' && serverResponse.length) {
							try { parsed = JSON.parse(serverResponse); } catch (_) {}
						}

						let srp;
						if (parsed && typeof parsed === 'object' && parsed.constructor === Object) {
							if (parsed.ok && parsed.id) {
								var attachUrl = window.location.protocol + '//' + window.location.host + '/attach/' + parsed.id;
								var t = effectiveType(file);
								if (t.match(/^video\//)) {
									srp = '[video]' + attachUrl + '[/video]';
								} else if (t.match(/^audio\//)) {
									srp = '[audio]' + attachUrl + '[/audio]';
								} else {
									srp = '[attachment]' + attachUrl + '[/attachment]';
								}
							} else {
								var errMsg = parsed.error || parsed.message || 'Upload failed';
								srp = '[Upload error: ' + errMsg + ']';
							}
						} else {
							// Unparseable response — treat as a plain string insert
							srp = typeof parsed === 'string' ? parsed : '';
						}

						// Allow page-level hook to transform the inserted text (e.g. photo tokens)
						let insertText = srp;
						if (typeof window.onDropzoneInsert === 'function') {
							const hooked = window.onDropzoneInsert(srp, file);
							if (hooked !== null && hooked !== undefined) {
								insertText = hooked;
							}
						}

						let idx = targetTextarea.value.indexOf(u);
						if (idx !== -1) {
							// Normal path: replace the pending placeholder
							let c = targetTextarea.selectionStart;
							if (c > idx) { c = c + insertText.length - u.length; }
							targetTextarea.setRangeText(insertText, idx, idx + u.length);
							targetTextarea.selectionStart = c;
							targetTextarea.selectionEnd   = c;
						} else {
							// Placeholder was already replaced (e.g. by an error on first chunk
							// attempt before retry succeeded) — insert at current cursor position.
							let pos    = targetTextarea.selectionStart;
							let prefix = targetTextarea.value.substring(0, pos);
							targetTextarea.setRangeText(
								(prefix.length > 0 && !prefix.endsWith('\n') ? '\n' : '') + insertText + '\n',
								pos, pos, 'end'
							);
						}
					} else {
						targetTextarea.focus();
						document.execCommand('insertText', false /*no UI*/, serverResponse);
					}
					targetTextarea.dispatchEvent(new Event('change', { bubbles: true }));
				});

				this.on('complete', function(file) {
					const dz = this;
					// Remove just uploaded file from dropzone, makes interface more clear.
					// Image can be seen in posting-preview
					// We need preview to get optical feedback about upload-progress.
					// you see success, when the bb-code link for image is inserted
					setTimeout(function(){
						dz.removeFile(file);
					},5000);
				});
			},
			paste: function(event){
				const items = (event.clipboardData || event.originalEvent.clipboardData).items;
				items.forEach((item) => {
					if (item.kind === 'file') {
						dz.addFile(item.getAsFile());
					}
				})
			},
		});
	};

	this.copyPaste = function(event, dz) {
		const items = (event.clipboardData || event.originalEvent.clipboardData).items;
		items.forEach((item) => {
			if (item.kind === 'file') {
				dz.addFile(item.getAsFile());
			}
		})
	};

	this.setupDropzone = function(dropSelector, textareaElementId, clickableSelector, previewsContainerId) {
		const self = this;
		var dropzone = this.createDropzone(dropSelector, textareaElementId, clickableSelector, previewsContainerId);
		$(dropSelector).on('paste', function(event) {
			self.copyPaste(event, dropzone);
		});
		return dropzone;
	};
}
