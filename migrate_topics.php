<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
function migrate_topics() {
	global $forums;

	$factory = DbConnectionFactory::getInstance();

	$users_query = 'SELECT `punbb_topics`.`id`, `punbb_topics`.`subject`, `punbb_topics`.`posted`, '
	. '	`punbb_topics`.`first_post_id`, `punbb_topics`.`num_views`, `punbb_topics`.`num_replies`, `punbb_topics`.`closed`, '
	. '	`punbb_topics`.`sticky`, `punbb_topics`.`forum_id`, `punbb_posts`.`poster_id` '
	. '	FROM `punbb_topics` INNER JOIN '
	. ' `punbb_posts` ON `punbb_posts`.`id`=`punbb_topics`.`first_post_id` '
	. '	WHERE `punbb_topics`.`moved_to` IS NULL AND `punbb_topics`.`first_post_id` <> 0';

	$insert_wp_sftopics = 'INSERT INTO `wp_sftopics` '
	. '(`topic_id`, `topic_name`, `topic_date`, `topic_status`, '
	. '`forum_id`, `user_id`, `topic_pinned`, `topic_opened`, '
	. '`topic_slug`, `post_id`, `post_count`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	$pbb = $factory->getConnection('PunBB');
	$topics = $pbb->query($users_query, MYSQLI_USE_RESULT);

	$wp_sftopics = $factory->getConnection('WordPress');
	$wp_sftopics_stmt = $wp_sftopics->prepare($insert_wp_sftopics);

	while ($topic = $topics->fetch_assoc()) {
		$slug = 'topic-' . $topic['id'];
		$posts = 1 + $topic['num_replies'];

		$wp_sftopics_stmt->bind_param(
			'issiiiiisii',
		$topic['id'],
		$topic['subject'],
		gmdate('Y-m-d H:i:s', $topic['posted']),
		$topic['closed'],
		$forums[(int)$topic['forum_id']],
		$topic['poster_id'],
		$topic['sticky'],
		$topic['num_views'],
		$slug,
		$topic['first_post_id'],
		$posts);
		$wp_sftopics_stmt->execute();
	}

	$wp_sftopics_stmt->close();
	$wp_sftopics->close();
	$topics->close();
	$pbb->close();
}