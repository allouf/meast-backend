<?php

namespace App\Library;

use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Agora
{
    protected static $appId;
    protected static $appCertificate;

    public static function init()
    {
        self::$appId = env('AGORA_APP_ID');
        self::$appCertificate = env('AGORA_APP_CERTIFICATE');

        if (!self::$appId || !self::$appCertificate) {
            throw new HttpException(500, 'AGORA_APP_ID or AGORA_APP_CERTIFICATE is not set.');
        }
    }

  public static function generateToken($channelName, $uid, $role = RtcTokenBuilder::RolePublisher, $expireInSeconds = 3600)
   {
    self::init();

    $currentTimestamp = now()->timestamp;
    $expireTimestamp = $currentTimestamp + $expireInSeconds;

    return RtcTokenBuilder::buildTokenWithUid(
        self::$appId,
        self::$appCertificate,
        $channelName,
        $uid,
        $role,
        $expireTimestamp,
        $expireTimestamp
    );
}

}
