<?php

class VoidAuthenticator extends MemberAuthenticator {

	public static function authenticate($RAW_data, Form $form = null) {
		$member = Member::get()->first();

		if (!$member || !$member->exists()) {
			$member = Member::create();
			$member->FirstName = _t('Member.DefaultAdminFirstname', 'Default Admin');
			$member->write();
		}
		return $member;
	}
}