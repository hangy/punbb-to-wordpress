<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
if ( !function_exists('migrate_users') ) :
function migrate_users() {
	$factory = DbConnectionFactory::getInstance();

	$users_query = 'SELECT `id`, `username`, `password`, `salt`, '
	. '`email`, `title`, `realname`, `url`, `jabber`, `icq`, `msn`, `aim`, '
	. '`yahoo`, `location`, `signature`, `last_post`, '
	. '`registered`, `last_visit`, `num_posts` '
	. 'FROM `punbb_users`';

	$insert_wp_user = 'INSERT INTO `wp_users` '
	. '(`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, '
	. '`user_url`, `user_registered`, `display_name`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?)';

	$insert_wp_usermeta = 'INSERT INTO `wp_usermeta` '
	. '(`user_id`, `meta_key`, `meta_value` )'
	. 'VALUES(?, ?, ?)';

	$insert_wp_sfmembers = 'INSERT INTO `wp_sfmembers` '
	. '(`user_id`, `display_name`, `pm`, `avatar`, `signature`, '
	. '`posts`, `lastvisit`, `checktime`, `user_options`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';

	$pbb = $factory->getConnection('PunBB');
	$users = $pbb->query($users_query, MYSQLI_USE_RESULT);

	$wpuser = $factory->getConnection('WordPress');
	$wpuser_stmt = $wpuser->prepare($insert_wp_user);

	$wpusermeta = $factory->getConnection('WordPress');
	$wpusermeta_stmt = $wpusermeta->prepare($insert_wp_usermeta);

	$wpsfmembers = $factory->getConnection('WordPress');
	$wpsfmembers_stmt = $wpsfmembers->prepare($insert_wp_sfmembers);

	while ($user = $users->fetch_assoc()) {
		$wpuser_stmt->bind_param(
			'isssssss',
		$user['id'],
		$user['username'],
		$user['password'],
		sanitize_title_with_dashes($user['username']),
		$user['email'],
		$user['url'],
		gmdate('Y-m-d H:i:s', $user['registered']),
		$user['username']);
		$wpuser_stmt->execute();

		add_user_meta($user, 'jabber', 'jabber', $wpusermeta_stmt);
		add_user_meta($user, 'icq', 'icq', $wpusermeta_stmt);
		add_user_meta($user, 'msn', 'msn', $wpusermeta_stmt);
		add_user_meta($user, 'aim', 'aim', $wpusermeta_stmt);
		add_user_meta($user, 'yahoo', 'yim', $wpusermeta_stmt);
		add_user_meta($user, 'location', 'location', $wpusermeta_stmt);
		add_user_meta($user, 'username', 'nickname', $wpusermeta_stmt);
		add_user_meta($user, 'salt', 'punbb_salt', $wpusermeta_stmt);

		add_user_meta_manually($user, 'wp_capabilities', 'a:1:{s:10:"subscriber";s:1:"1";}', $wpusermeta_stmt);
		add_user_meta_manually($user, 'wp_user_level', '0', $wpusermeta_stmt);

		$pm = 1;
		$avatar = 'a:1:{s:8:"uploaded";s:0:"";}';
		$options = 'a:6:{s:11:"autosubpost";i:0;s:10:"hidestatus";i:0;s:8:"timezone";i:0;s:6:"editor";i:1;s:7:"pmemail";i:1;s:8:"namesync";i:1;}';
		$wpsfmembers_stmt->bind_param(
			'isississs',
		$user['id'],
		$user['username'],
		$pm,
		$avatar,
		parse_signature($user['signature']),
		$user['num_posts'],
		gmdate('Y-m-d H:i:s', $user['last_visit']),
		gmdate('Y-m-d H:i:s', $user['last_visit']),
		$options);
		$wpsfmembers_stmt->execute();
	}

	$wpsfmembers_stmt->close();
	$wpsfmembers->close();
	$wpusermeta_stmt->close();
	$wpusermeta->close();
	$wpuser_stmt->close();
	$wpuser->close();
	$users->close();
	$pbb->close();
}
endif;

if ( !function_exists('add_user_meta') ) :
function add_user_meta($user, $pbkey, $wpkey, $stmt) {
	$value = $user[$pbkey];
	if (null === $value || 0 == count($value)) {
		return;
	}

	$stmt->bind_param('iss', $user['id'], $wpkey, $value);
	$stmt->execute();
}
endif;

if ( !function_exists('add_user_meta_manually') ) :
function add_user_meta_manually($user, $key, $value, $stmt) {
	$stmt->bind_param('iss', $user['id'], $key, $value);
	$stmt->execute();
}
endif;