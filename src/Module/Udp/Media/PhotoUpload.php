<?php

// UDP Social — photo upload endpoint (Cat2 — new file)
// Wraps upstream Photo\Upload logic and indexes each uploaded photo in udp-media.

namespace Friendica\Module\Udp\Media;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\System;
use Friendica\Model\Photo;
use Friendica\Model\UdpMedia;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Object\Image;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class PhotoUpload extends BaseModule
{
	private UserSession $userSession;
	private IManageConfigValues $config;

	public function __construct(
		UserSession $userSession,
		IManageConfigValues $config,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = []
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->userSession = $userSession;
		$this->config      = $config;
	}

	protected function post(array $request = [])
	{
		$owner = User::getOwnerDataById($this->userSession->getLocalUserId());
		if (!$owner) {
			$this->jsonError(401, ['error' => 'Not authenticated.']);
		}

		$src = $filename = '';
		$filesize = 0;
		$filetype = '';

		if (!empty($_FILES['userfile'])) {
			$src      = $_FILES['userfile']['tmp_name'];
			$filename = basename($_FILES['userfile']['name']);
			$filesize = (int) $_FILES['userfile']['size'];
			$filetype = $_FILES['userfile']['type'];
		} elseif (!empty($_FILES['media'])) {
			$src      = is_array($_FILES['media']['tmp_name']) ? $_FILES['media']['tmp_name'][0]  : $_FILES['media']['tmp_name'];
			$filename = is_array($_FILES['media']['name'])     ? basename($_FILES['media']['name'][0]) : basename($_FILES['media']['name']);
			$filesize = is_array($_FILES['media']['size'])     ? (int) $_FILES['media']['size'][0] : (int) $_FILES['media']['size'];
			$filetype = is_array($_FILES['media']['type'])     ? $_FILES['media']['type'][0]       : $_FILES['media']['type'];
		}

		if (empty($src)) {
			$this->jsonError(400, ['error' => 'No file received.']);
		}

		$imagedata = @file_get_contents($src);
		$image     = new Image($imagedata, $filetype, $filename);

		if (!$image->isValid()) {
			@unlink($src);
			$this->jsonError(400, ['error' => $this->t('Unable to process image.')]);
		}

		$image->orient($src);
		@unlink($src);

		$maxLength = $this->config->get('system', 'max_image_length');
		if ($maxLength > 0) {
			$image->scaleDown($maxLength);
			$filesize = strlen($image->asString());
		}

		$resourceId = Photo::newResource();
		$allowCid   = '<' . $owner['id'] . '>';

		// Store with preview; album is left empty — the user organizes later via /media
		$preview = Photo::storeWithPreview($image, $owner['uid'], $resourceId, $filename, $filesize, '', '', $allowCid, '', '', '');

		if ($preview < 0) {
			$this->jsonError(500, ['error' => $this->t('Image upload failed.')]);
		}

		// Index in udp-media so this photo appears in the unified media manager
		UdpMedia::create(
			$owner['uid'],
			UdpMedia::TYPE_PHOTO,
			UdpMedia::REF_PHOTO,
			0,       // ref-id unused for photo rows; resource-id is the key
			$resourceId
		);

		// Return plain BBCode — same format as upstream Photo\Upload so compose JS works unchanged
		$bbcode = Images::getBBCodeByResource($resourceId, $owner['nickname'], $preview, $image->getExt());

		$this->response->addContent($bbcode);
		System::echoResponse($this->response->generate());
		System::exit();
	}
}
