<style>
/* ── UDP Media Manager ─────────────────────────────────────────────────────── */
#udp-media-manager {
	display: flex;
	gap: 0;
	min-height: calc(100vh - 120px);
}

/* Sidebar */
#udp-mm-sidebar {
	width: 200px;
	flex-shrink: 0;
	border-right: 1px solid #e8e8e8;
	padding: 16px 0;
}
#udp-mm-sidebar h3 {
	font-size: 0.72em;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	color: #aaa;
	margin: 0 16px 8px;
}
.udp-mm-album-item {
	display: flex;
	align-items: center;
	padding: 6px 16px;
	cursor: pointer;
	font-size: 0.9em;
	border-left: 3px solid transparent;
	user-select: none;
}
.udp-mm-album-item:hover { background: #f5f5f5; }
.udp-mm-album-item.active {
	border-left-color: #555;
	font-weight: 600;
	background: #f0f0f0;
}
.udp-mm-album-item-count {
	margin-left: auto;
	font-size: 0.75em;
	color: #aaa;
}
#udp-mm-new-album {
	margin: 8px 16px 0;
	width: calc(100% - 32px);
	padding: 4px 8px;
	border: 1px dashed #ccc;
	border-radius: 4px;
	font-size: 0.82em;
	background: none;
	cursor: pointer;
	color: #666;
	text-align: left;
}
#udp-mm-new-album:hover { border-color: #888; color: #333; }

/* Main area */
#udp-mm-main {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
}
#udp-mm-topbar {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 16px;
	border-bottom: 1px solid #eee;
	flex-shrink: 0;
}
#udp-mm-search {
	flex: 1;
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 5px 10px;
	font-size: 0.88em;
}
#udp-mm-type-filter {
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 5px 8px;
	font-size: 0.85em;
	background: #fff;
}
#udp-mm-selection-bar {
	display: none;
	align-items: center;
	gap: 8px;
	padding: 6px 16px;
	background: #f8f8f8;
	border-bottom: 1px solid #eee;
	font-size: 0.85em;
}
#udp-mm-selection-bar.visible { display: flex; }
#udp-mm-sel-count { font-weight: 600; }
#udp-mm-sel-album-input {
	border: 1px solid #ddd;
	border-radius: 4px;
	padding: 3px 8px;
	font-size: 0.85em;
}
#udp-mm-scroll {
	overflow-y: auto;
	flex: 1;
	padding: 12px 16px;
}
.udp-mm-group-label {
	font-size: 0.72em;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.06em;
	color: #999;
	margin: 10px 0 5px;
}
.udp-mm-grid {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
	gap: 6px;
	margin-bottom: 12px;
}
.udp-mm-item {
	position: relative;
	aspect-ratio: 1;
	border-radius: 6px;
	overflow: hidden;
	cursor: pointer;
	background: #eee;
	border: 3px solid transparent;
	transition: border-color 0.1s;
}
.udp-mm-item.selected { border-color: #333; }
.udp-mm-item img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	pointer-events: none;
}
.udp-mm-item-placeholder {
	width: 100%;
	height: 100%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 2em;
	color: #aaa;
	pointer-events: none;
}
.udp-mm-item-check {
	position: absolute;
	top: 4px;
	left: 4px;
	width: 20px;
	height: 20px;
	border-radius: 50%;
	background: rgba(0,0,0,0.45);
	border: 2px solid rgba(255,255,255,0.7);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 11px;
	color: transparent;
}
.udp-mm-item.selected .udp-mm-item-check {
	background: #333;
	border-color: #fff;
	color: #fff;
}
.udp-mm-item-filename {
	position: absolute;
	bottom: 0; left: 0; right: 0;
	padding: 3px 5px;
	font-size: 0.65em;
	background: rgba(0,0,0,0.5);
	color: #fff;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	pointer-events: none;
}
.udp-mm-empty {
	text-align: center;
	color: #bbb;
	font-style: italic;
	padding: 48px 0;
}

@media (max-width: 767px) {
	#udp-media-manager { flex-direction: column; }
	#udp-mm-sidebar {
		width: 100%;
		border-right: none;
		border-bottom: 1px solid #eee;
		padding: 8px 0;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 4px;
		padding: 8px 12px;
	}
	#udp-mm-sidebar h3 { display: none; }
	.udp-mm-album-item {
		padding: 3px 10px;
		border-radius: 12px;
		border-left: none;
		border: 1px solid #ccc;
		background: #f5f5f5;
		font-size: 0.8em;
	}
	.udp-mm-album-item.active { background: #555; color: #fff; border-color: #555; }
	.udp-mm-grid { grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); }
}
</style>

<div class="generic-page-wrapper">
	<h2>Media</h2>

	<div id="udp-media-manager">
		<nav id="udp-mm-sidebar">
			<h3>Albums</h3>
			<div class="udp-mm-album-item active" data-album="">
				All media
				<span class="udp-mm-album-item-count" id="udp-mm-all-count"></span>
			</div>
			<div class="udp-mm-album-item" data-album="__unorganized__">
				Unorganized
				<span class="udp-mm-album-item-count"></span>
			</div>
			{{foreach $albums as $album}}
			<div class="udp-mm-album-item" data-album="{{$album}}">
				{{$album}}
				<span class="udp-mm-album-item-count"></span>
			</div>
			{{/foreach}}
			<button id="udp-mm-new-album">+ New album</button>
		</nav>

		<div id="udp-mm-main">
			<div id="udp-mm-topbar">
				<input type="search" id="udp-mm-search" placeholder="Search by filename…" autocomplete="off">
				<select id="udp-mm-type-filter">
					<option value="">All types</option>
					<option value="photo">Photos</option>
					<option value="video">Videos</option>
					<option value="audio">Audio</option>
				</select>
			</div>

			<div id="udp-mm-selection-bar">
				<span id="udp-mm-sel-count">0 selected</span>
				<span>→ Move to album:</span>
				<input type="text" id="udp-mm-sel-album-input" placeholder="Album name (blank = unorganized)" list="udp-mm-album-datalist" autocomplete="off">
				<datalist id="udp-mm-album-datalist"></datalist>
				<button class="btn btn-xs btn-default" id="udp-mm-sel-move">Move</button>
				<button class="btn btn-xs btn-link" id="udp-mm-sel-cancel">Cancel</button>
			</div>

			<div id="udp-mm-scroll">
				<div class="udp-mm-empty">Loading…</div>
			</div>
		</div>
	</div>
</div>

<script>
(function() {
	var LIST_URL  = '{{$list_api_url}}';
	var ALBUM_URL = '{{$album_api_url}}';

	var sidebar   = document.getElementById('udp-mm-sidebar');
	var scrollEl  = document.getElementById('udp-mm-scroll');
	var searchEl  = document.getElementById('udp-mm-search');
	var typeEl    = document.getElementById('udp-mm-type-filter');
	var selBar    = document.getElementById('udp-mm-selection-bar');
	var selCount  = document.getElementById('udp-mm-sel-count');
	var selInput  = document.getElementById('udp-mm-sel-album-input');
	var selMove   = document.getElementById('udp-mm-sel-move');
	var selCancel = document.getElementById('udp-mm-sel-cancel');
	var datalist  = document.getElementById('udp-mm-album-datalist');
	var newAlbum  = document.getElementById('udp-mm-new-album');

	var allGroups   = [];
	var allAlbums   = [];
	var activeAlbum = '';   // '' = all, '__unorganized__' = empty album
	var selected    = {};   // id → item
	var searchTerm  = '';
	var typeFilter  = '';
	var searchTimer = null;

	function fetchMedia() {
		var url = LIST_URL;
		var params = [];
		if (activeAlbum === '__unorganized__') {
			params.push('album=');
		} else if (activeAlbum !== '') {
			params.push('album=' + encodeURIComponent(activeAlbum));
		}
		if (params.length) { url += '?' + params.join('&'); }

		scrollEl.innerHTML = '<div class="udp-mm-empty">Loading…</div>';
		fetch(url, { credentials: 'same-origin' })
			.then(function(r) { return r.json(); })
			.then(function(data) {
				if (!data.ok) { scrollEl.innerHTML = '<div class="udp-mm-empty">Could not load media.</div>'; return; }
				allGroups = data.groups || [];
				allAlbums = data.albums || [];
				selected  = {};
				updateSelectionBar();
				updateAlbumDatalist();
				renderGrid();
			})
			.catch(function() {
				scrollEl.innerHTML = '<div class="udp-mm-empty">Could not load media.</div>';
			});
	}

	function updateAlbumDatalist() {
		datalist.innerHTML = '';
		allAlbums.forEach(function(a) {
			var opt = document.createElement('option');
			opt.value = a;
			datalist.appendChild(opt);
		});
	}

	function renderGrid() {
		var groups = allGroups;
		var term   = searchTerm.toLowerCase();
		var type   = typeFilter;

		if (term || type) {
			groups = groups.map(function(g) {
				return {
					label: g.label,
					items: g.items.filter(function(item) {
						var nameOk = !term || (item.filename || '').toLowerCase().indexOf(term) !== -1;
						var typeOk = !type || item['media-type'] === type;
						return nameOk && typeOk;
					})
				};
			}).filter(function(g) { return g.items.length > 0; });
		}

		if (!groups.length) {
			scrollEl.innerHTML = '<div class="udp-mm-empty">No media found.</div>';
			return;
		}

		var html = '';
		groups.forEach(function(group) {
			html += '<div class="udp-mm-group-label">' + esc(group.label) + '</div>';
			html += '<div class="udp-mm-grid">';
			group.items.forEach(function(item) {
				var sel  = selected[item.id] ? ' selected' : '';
				var attr = 'data-item=\'' + escAttr(JSON.stringify(item)) + '\'';
				html += '<div class="udp-mm-item' + sel + '" ' + attr + '>';
				html += '<span class="udp-mm-item-check">✓</span>';
				if (item.thumb_url) {
					html += '<img src="' + esc(item.thumb_url) + '" alt="' + esc(item.filename) + '" loading="lazy">';
				} else {
					var icon = item['media-type'] === 'video' ? '▶' : item['media-type'] === 'audio' ? '♫' : '📄';
					html += '<div class="udp-mm-item-placeholder">' + icon + '</div>';
				}
				html += '<span class="udp-mm-item-filename">' + esc(item.filename) + '</span>';
				html += '</div>';
			});
			html += '</div>';
		});
		scrollEl.innerHTML = html;
	}

	scrollEl.addEventListener('click', function(e) {
		var el = e.target.closest('.udp-mm-item');
		if (!el) return;
		var item;
		try { item = JSON.parse(el.dataset.item); } catch(_) { return; }
		if (selected[item.id]) {
			delete selected[item.id];
			el.classList.remove('selected');
		} else {
			selected[item.id] = item;
			el.classList.add('selected');
		}
		updateSelectionBar();
	});

	function updateSelectionBar() {
		var count = Object.keys(selected).length;
		if (count > 0) {
			selBar.classList.add('visible');
			selCount.textContent = count + ' selected';
		} else {
			selBar.classList.remove('visible');
		}
	}

	selCancel.addEventListener('click', function() {
		selected = {};
		scrollEl.querySelectorAll('.udp-mm-item.selected').forEach(function(el) {
			el.classList.remove('selected');
		});
		updateSelectionBar();
	});

	selMove.addEventListener('click', function() {
		var ids   = Object.keys(selected).map(Number);
		var album = selInput.value.trim();
		if (!ids.length) return;

		var fd = new FormData();
		fd.append('ids',   JSON.stringify(ids));
		fd.append('album', album);

		fetch(ALBUM_URL, { method: 'POST', credentials: 'same-origin', body: fd })
			.then(function(r) { return r.json(); })
			.then(function(data) {
				if (data.ok) {
					selected = {};
					selInput.value = '';
					updateSelectionBar();
					fetchMedia(); // refresh
				}
			});
	});

	newAlbum.addEventListener('click', function() {
		var name = prompt('New album name:');
		if (!name) return;
		// Select all current items and move to new album
		selInput.value = name.trim();
		selMove.click();
	});

	sidebar.addEventListener('click', function(e) {
		var item = e.target.closest('.udp-mm-album-item');
		if (!item) return;
		sidebar.querySelectorAll('.udp-mm-album-item').forEach(function(el) {
			el.classList.remove('active');
		});
		item.classList.add('active');
		activeAlbum = item.dataset.album;
		selected = {};
		updateSelectionBar();
		fetchMedia();
	});

	searchEl.addEventListener('input', function() {
		searchTerm = this.value.trim();
		clearTimeout(searchTimer);
		searchTimer = setTimeout(renderGrid, 180);
	});

	typeEl.addEventListener('change', function() {
		typeFilter = this.value;
		renderGrid();
	});

	function esc(s) {
		return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
	}
	function escAttr(s) { return String(s).replace(/'/g, '&#39;'); }

	// Initial load
	fetchMedia();
}());
</script>
