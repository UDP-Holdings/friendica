{{*
  * UDP Social — Node Pairing admin page
  * Three views: landing (action=""), generate (Admin B), accept (Admin A)
  *}}

<div id="adminpage">
	<h1>{{$title}} - {{$page}}</h1>

{{if $action == ""}}
	{{* ── Landing: choose a role ─────────────────────────────────────── *}}
	<p>Connect this node to another UDP Social node so your communities can follow each other.</p>

	<div style="display:flex; gap:2em; flex-wrap:wrap; margin-top:1.5em;">
		<div class="well" style="flex:1; min-width:220px;">
			<h3>Share my node</h3>
			<p>Generate a pairing token that another admin can scan or paste into their node.</p>
			<form action="{{$baseurl}}/admin/node-pair/generate" method="post">
				<input type="hidden" name="form_security_token" value="{{$form_security_token_gen}}">
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
				placeholder="Paste the token from the other admin…" style="font-family:monospace; font-size:12px;"></textarea>
		</div>
		<button type="submit" class="btn btn-primary" id="udp-confirm-btn" disabled>Confirm pairing</button>
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
