<?php

class VoidAuthenticator extends MemberAuthenticator {

	public static function authenticate($RAW_data, Form $form = null) {

		SS_Log::log(new Exception(print_r($databaseConfig, true)), SS_Log::NOTICE);

		return Member::get()->first();
	}

}