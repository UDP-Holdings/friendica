{{*
  * UDP Social — Node Pairing admin page
  * Three views: landing (action=""), generate (Admin B), accept (Admin A)
  *}}

<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

{{if $action == ""}}
	{{* ── Landing: choose a role ─────────────────────────────────────── *}}
	<p>Connect this node to another UDP Social node so your communities can follow each other.</p>

	{{if $pending_requests}}
	<div class="alert alert-info" style="margin-bottom:1.5em;">
		<h4 style="margin-top:0;">Incoming pairing requests</h4>
		{{foreach $pending_requests as $req}}
		<div style="{{if !$req@last}}margin-bottom:.75em; padding-bottom:.75em; border-bottom:1px solid rgba(0,0,0,.15);{{/if}}">
			<strong>{{$req.domain}}</strong> wants to pair
			{{if $req.requester_handle}}
			— <em>{{$req.requester_handle}} wants to connect with {{$req.target_handle}}</em>
			{{/if}}
			<br><small class="text-muted">Received {{$req.received_at}}</small>
			&nbsp;
			<a href="{{$baseurl}}/admin/node-pair/accept?payload={{$req.accept_payload}}"
			   class="btn btn-success btn-xs" style="vertical-align:middle;">Accept pairing</a>
			<form action="{{$baseurl}}/admin/node-pair/reject" method="post"
			      style="display:inline; margin-left:.25em;">
				<input type="hidden" name="form_security_token" value="{{$form_security_token_reject}}">
				<input type="hidden" name="domain" value="{{$req.domain}}">
				<button type="submit" class="btn btn-default btn-xs"
				        onclick="return confirm('Decline pairing request from {{$req.domain}}?')">Decline</button>
			</form>
		</div>
		{{/foreach}}
	</div>
	{{/if}}

	{{if $paired_nodes}}
	<div class="well" style="margin-bottom:1.5em;">
		<h4 style="margin-top:0;">Connected nodes</h4>
		<table class="table table-condensed" style="margin:0;">
			<thead>
				<tr>
					<th>Domain</th>
					<th>Type</th>
					<th>Added</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			{{foreach $paired_nodes as $node}}
			<tr>
				<td><a href="https://{{$node.allowed_domain}}" target="_blank" rel="noopener">{{$node.allowed_domain}}</a></td>
				<td><span class="label label-{{if $node.source == 'peer'}}success{{else}}info{{/if}}">{{$node.source}}</span></td>
				<td><small class="text-muted">{{$node.created_at}}</small></td>
				<td>
					<form action="{{$baseurl}}/admin/node-pair/remove" method="post" style="margin:0;">
						<input type="hidden" name="form_security_token" value="{{$form_security_token_remove}}">
						<input type="hidden" name="domain" value="{{$node.allowed_domain}}">
						<button type="submit" class="btn btn-danger btn-xs"
						        onclick="return confirm('Remove {{$node.allowed_domain}} from your network?')">Remove</button>
					</form>
				</td>
			</tr>
			{{/foreach}}
			</tbody>
		</table>
	</div>
	{{/if}}

	{{* ── Bulk import ────────────────────────────────────────────────── *}}
	<div class="well" style="margin-bottom:1.5em;">
		<h4 style="margin-top:0;">Import a trusted node list</h4>
		<p class="text-muted" style="margin-bottom:.75em;">Paste a list of node domains (one per line, or comma-separated). Each node is added to your allowlist and sent a pairing request — their admins will see a one-click accept in their admin panel.</p>
		<form action="{{$baseurl}}/admin/node-pair/bulk_add" method="post">
			<input type="hidden" name="form_security_token" value="{{$form_security_token_bulk_add}}">
			<div class="form-group" style="margin-bottom:.75em;">
				<label for="udp-bulk-domains" style="font-weight:normal;">Node domains</label>
				<textarea id="udp-bulk-domains" name="domains" class="form-control" rows="6"
					placeholder="alice.udp.social&#10;bob.udp.social&#10;carol.udp.social"
					style="font-family:monospace; font-size:12px; margin-top:.25em;"></textarea>
			</div>
			<button type="submit" class="btn btn-primary">Import and announce</button>
		</form>
	</div>

	{{* ── Single domain add ──────────────────────────────────────────── *}}
	<div class="well" style="margin-bottom:1.5em;">
		<h4 style="margin-top:0;">Add a single domain</h4>
		<p class="text-muted" style="margin-bottom:.75em;">Add one domain without sending a pairing request — useful for relay nodes.</p>
		<form action="{{$baseurl}}/admin/node-pair/add" method="post" style="display:flex; gap:.5em; align-items:flex-end; flex-wrap:wrap;">
			<input type="hidden" name="form_security_token" value="{{$form_security_token_add}}">
			<div class="form-group" style="margin:0; flex:1; min-width:200px;">
				<label for="udp-manual-domain" style="font-weight:normal;">Domain</label>
				<input type="text" id="udp-manual-domain" name="domain" class="form-control"
					placeholder="relay.example.social" style="margin-top:.25em;">
			</div>
			<button type="submit" class="btn btn-default">Add</button>
		</form>
	</div>

	<div style="display:flex; gap:2em; flex-wrap:wrap; margin-top:1.5em;">
		<div class="well" style="flex:1; min-width:220px;">
			<h3>Share my node</h3>
			<p>Generate a pairing token that another admin can scan or paste into their node.</p>
			<form action="{{$baseurl}}/admin/node-pair/generate" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token_gen}}">
				<div class="form-group" style="margin-bottom:.75em;">
					<label for="udp-pair-email" style="font-weight:normal;">Email to other admin <span class="text-muted">(optional)</span></label>
					<input type="email" id="udp-pair-email" name="email" class="form-control"
						placeholder="admin@their-node.example" style="max-width:280px; margin-top:.25em;">
				</div>
				<button type="submit" class="btn btn-primary">Generate pairing token</button>
			</form>
		</div>

		<div class="well" style="flex:1; min-width:220px;">
			<h3>Join another node</h3>
			<p>Scan or paste the token from the other admin's screen to complete the pairing.</p>
			<a href="{{$baseurl}}/admin/node-pair/accept" class="btn btn-default">Scan / paste token</a>
		</div>
	</div>

{{elseif $action == "generate"}}
	{{* ── Generate: Admin B shows QR + copy text ─────────────────────── *}}
	<p>Show this QR code to the other admin, or send them the token text. The token expires in 24 hours and can only be used once.</p>

	<div style="margin:1.5em 0; display:flex; gap:2em; flex-wrap:wrap; align-items:flex-start;">
		<div>
			<div style="background:#fff; padding:12px; display:inline-block; border:1px solid #ccc; border-radius:4px;">{{$qr_svg nofilter}}</div>
		</div>
		<div style="flex:1; min-width:220px;">
			<label for="udp-token-text"><strong>Copy-pastable token</strong></label>
			<textarea id="udp-token-text" class="form-control" rows="4" readonly
				onclick="this.select()" style="font-family:monospace; font-size:12px; margin-top:.5em;">{{$qr_payload}}</textarea>
			<button class="btn btn-default btn-sm" style="margin-top:.5em;" onclick="
				navigator.clipboard.writeText(document.getElementById('udp-token-text').value)
					.then(function(){ this.textContent='Copied!'; }.bind(this));
				return false;">Copy to clipboard</button>
		</div>
	</div>

	<a href="{{$baseurl}}/admin/node-pair" class="btn btn-link">&larr; Back</a>

{{elseif $action == "accept"}}
	{{* ── Accept: Admin A scans or pastes ────────────────────────────── *}}
	<p>Scan the QR code from the other admin's screen, or paste their token text below, then confirm.</p>

	{{* Camera scanner *}}
	<div id="udp-scanner-wrap" style="margin-bottom:1.5em;">
		<button id="udp-scan-btn" class="btn btn-default" type="button">Open camera scanner</button>
		<div id="udp-scanner" style="display:none; margin-top:1em; max-width:400px;">
			<video id="udp-video" style="width:100%; border:2px solid #5cb85c; border-radius:4px;" playsinline></video>
			<canvas id="udp-canvas" style="display:none;"></canvas>
			<p id="udp-scan-status" style="margin-top:.5em; color:#888;">Scanning…</p>
			<button id="udp-stop-btn" class="btn btn-link btn-sm" type="button">Stop camera</button>
		</div>
	</div>

	{{* Manual paste form *}}
	<form action="{{$baseurl}}/admin/node-pair/accept" method="post" id="udp-accept-form">
		<input type="hidden" name="form_security_token" value="{{$form_security_token_accept}}">
		<div class="form-group">
			<label for="udp-payload-input"><strong>Token</strong> (paste here or scan above)</label>
			<textarea name="payload" id="udp-payload-input" class="form-control" rows="3"
				placeholder="Paste the token from the other admin…"
				style="font-family:monospace; font-size:12px;">{{$prefill_payload}}</textarea>
		</div>
		<button type="submit" class="btn btn-primary" id="udp-confirm-btn"
			{{if !$prefill_payload}}disabled{{/if}}>Confirm pairing</button>
		&nbsp;<a href="{{$baseurl}}/admin/node-pair" class="btn btn-link">&larr; Back</a>
	</form>

	<script src="{{$baseurl}}/view/js/jsQR.min.js"></script>
	<script>
	(function () {
		var scanBtn  = document.getElementById('udp-scan-btn');
		var stopBtn  = document.getElementById('udp-stop-btn');
		var wrap     = document.getElementById('udp-scanner');
		var video    = document.getElementById('udp-video');
		var canvas   = document.getElementById('udp-canvas');
		var status   = document.getElementById('udp-scan-status');
		var input    = document.getElementById('udp-payload-input');
		var confirm  = document.getElementById('udp-confirm-btn');
		var stream   = null;
		var animId   = null;

		// Enable confirm button when there is content in the textarea
		input.addEventListener('input', function () {
			confirm.disabled = input.value.trim() === '';
		});

		scanBtn.addEventListener('click', function () {
			navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
				.then(function (s) {
					stream = s;
					video.srcObject = s;
					video.play();
					wrap.style.display = 'block';
					scanBtn.style.display = 'none';
					tick();
				})
				.catch(function (err) {
					alert('Camera unavailable: ' + err.message);
				});
		});

		stopBtn.addEventListener('click', stopCamera);

		function stopCamera() {
			if (animId)  cancelAnimationFrame(animId);
			if (stream)  stream.getTracks().forEach(function (t) { t.stop(); });
			wrap.style.display  = 'none';
			scanBtn.style.display = '';
			stream = null;
		}

		function tick() {
			if (video.readyState === video.HAVE_ENOUGH_DATA) {
				canvas.width  = video.videoWidth;
				canvas.height = video.videoHeight;
				var ctx = canvas.getContext('2d');
				ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
				var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
				var code = jsQR(imageData.data, imageData.width, imageData.height);
				if (code) {
					input.value      = code.data;
					confirm.disabled = false;
					status.textContent = 'QR detected — confirm below to complete pairing.';
					status.style.color = '#5cb85c';
					stopCamera();
					return;
				}
			}
			animId = requestAnimationFrame(tick);
		}
	}());
	</script>
{{/if}}
</div>
