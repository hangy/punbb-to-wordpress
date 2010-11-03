<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
if ( !function_exists('migrate_memberships') ) :
function migrate_memberships() {
	global $usergroups;
	
	$factory = DbConnectionFactory::getInstance();

	$users_query = 'SELECT `id`, `group_id` '
	. 'FROM `punbb_users` '
	. 'WHERE `group_id` <> 0';

	$insert_wp_sfmemberships = 'INSERT INTO `wp_sfmemberships` '
	. '(`user_id`, `usergroup_id`) '
	. 'VALUES(?, ?)';
	$pbb = $factory->getConnection('PunBB');
	$users = $pbb->query($users_query, MYSQLI_USE_RESULT);

	$wp_sfmembership = $factory->getConnection('WordPress');
	$wp_sfmembership_stmt = $wp_sfmembership->prepare($insert_wp_sfmemberships);

	while ($user = $users->fetch_assoc()) {
		$wp_sfmembership_stmt->bind_param(
			'ii',
		$user['id'],
		$usergroups[(int)$user['group_id']]);
		$wp_sfmembership_stmt->execute();
	}

	$wp_sfmembership_stmt->close();
	$wp_sfmembership->close();
	$users->close();
	$pbb->close();
}
endif;