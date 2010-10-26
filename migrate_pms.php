<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
if ( !function_exists('migrate_pms') ) :
function migrate_pms() {
	$factory = DbConnectionFactory::getInstance();

	$pms_query = 'SELECT `id`, `sender_id`, `receiver_id`, '
	. '`lastedited_at`, `read_at`, `subject`, `body`, '
	. '`status`, `deleted_by_sender`, `deleted_by_receiver` '
	. 'FROM `punbb_pun_pm_messages`';

	$insert_wp_sfmessage = 'INSERT INTO `wp_sfmessages` '
	. '(`message_id`, `sent_date`, `from_id`, `to_id`, `title`, '
	. '`type`, `message`, `message_status`, `inbox`, `sentbox`, '
	. '`is_reply`, `message_slug`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	$pbb = $factory->getConnection('PunBB');
	$pms = $pbb->query($pms_query, MYSQLI_USE_RESULT);

	$wp_sfmessage = $factory->getConnection('WordPress');
	$wp_sfmessage_stmt = $wp_sfmessage->prepare($insert_wp_sfmessage);

	while ($pm = $pms->fetch_assoc()) {
		$type = 1;
		$status = read === $pm['status'] ? 1 : 0;
		$inbox = !$pm['deleted_by_receiver'];
		$sentbox = !$pm['deleted_by_sender'];
		$reply = false;
		
		$wp_sfmessage_stmt->bind_param(
			'isiisisiiiis',
		$pm['id'],
		gmdate('Y-m-d H:i:s', $pm['lastedited_at']),
		$pm['sender_id'],
		$pm['receiver_id'],
		$pm['subject'],
		$type,
		parse_message($pm['body']),
		$status,
		$inbox,
		$sentbox,
		$reply,
		sanitize_title_with_dashes($pm['subject']));
		$wp_sfmessage_stmt->execute();
	}

	$wp_sfmessage_stmt->close();
	$wp_sfmessage->close();
	$pms->close();
	$pbb->close();
}
endif;