<?php

namespace Sooh\DB\Cases;

class OauthRefresh extends \Sooh\DB\Base\KVObj {
    public static function numToSplit() {
        return 2;
    }

    protected static function splitedTbName($n,$isCache)
    {
        return 'tb_oauth_refresh_' . ($n % static::numToSplit());
    }

    public static function getCopy($refreshToken) {
        return parent::getCopy(['refreshToken' => $refreshToken]);
    }

	protected static function idFor_dbByObj_InConf($isCache)
	{
		return 'oauth';
	}
}