<?php

class VoidAuthenticator extends MemberAuthenticator {

	public static function authenticate($RAW_data, Form $form = null) {
		return Member::get()->first();
	}

}