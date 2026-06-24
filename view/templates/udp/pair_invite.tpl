{{*
  * UDP Social — Public node-pairing invitation page
  * Shown to Admin A after clicking an emailed pairing link from Admin B.
  *}}
<div class="generic-page-wrapper" style="max-width:580px; margin:3em auto;">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">Node pairing invitation</h3>
		</div>
		<div class="panel-body">
			<p>
				<strong>{{$domain}}</strong> would like to pair their node with yours,
				so your communities can follow each other.
			</p>
			<p>
				To accept, go to your node's admin panel →
				<strong>Node Pairing</strong> → <strong>Scan / paste token</strong>,
				then scan the QR code below or copy the token text.
			</p>

			<div style="text-align:center; margin:1.5em 0; background:#fff; padding:12px; display:inline-block; border:1px solid #ddd; border-radius:4px; width:100%; box-sizing:border-box;">
				{{$qr_svg nofilter}}
			</div>

			<div style="margin-top:1.25em;">
				<label for="udp-pair-payload"><strong>Token</strong> (copy and paste at your node)</label>
				<textarea id="udp-pair-payload" class="form-control" rows="3" readonly
					onclick="this.select()" style="font-family:monospace; font-size:12px; margin-top:.4em;">{{$payload}}</textarea>
				<button class="btn btn-default btn-sm" style="margin-top:.5em;" onclick="
					navigator.clipboard.writeText(document.getElementById('udp-pair-payload').value)
						.then(function(){ this.textContent='Copied!'; }.bind(this));
					return false;">Copy to clipboard</button>
			</div>

			<p class="text-muted" style="font-size:.9em; margin-top:1.5em;">
				This invitation expires <strong>{{$expires}}</strong> and can only be used once.
			</p>
		</div>
	</div>
</div>
