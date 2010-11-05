<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
if ( !function_exists('migrate_posts') ) :
function migrate_posts() {
	global $forums;

	$factory = DbConnectionFactory::getInstance();

	$posts_query = 'SELECT `punbb_posts`.`id`, `punbb_posts`.`poster`, `punbb_posts`.`poster_id`, `punbb_posts`.`poster_ip`, '
	. '`punbb_posts`.`poster_email`, `punbb_posts`.`message`, `punbb_posts`.`posted`, `punbb_posts`.`edited`, `punbb_posts`.`edited_by`, '
	. '`punbb_posts`.`topic_id`, `punbb_topics`.`forum_id` '
	. 'FROM `punbb_posts` INNER JOIN '
	. '`punbb_topics` ON `punbb_topics`.`id` = `punbb_posts`.`topic_id` '
	. 'ORDER BY `punbb_posts`.`topic_id`, `punbb_posts`.`posted`';

	$insert_wp_sftopics = 'INSERT INTO `wp_sfposts` '
	. '(`post_id`, `post_content`, `post_date`, `topic_id`, '
	. '`user_id`, `forum_id`, `guest_name`, `guest_email`, '
	. '`post_status`, `post_pinned`, `post_index`, '
	. '`post_edit`, `poster_ip`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
	$pbb = $factory->getConnection('PunBB');
	$posts = $pbb->query($posts_query, MYSQLI_USE_RESULT);

	$wp_sfposts = $factory->getConnection('WordPress');
	$wp_sfposts_stmt = $wp_sfposts->prepare($insert_wp_sftopics);

	$current_topic = -1;
	$index = 0;
	while ($post = $posts->fetch_assoc()) {
		if ($current_topic !== $post['topic_id']) {
			// Since the query is sorted by topic id then by date,
			// we can just reset the post_index to 1 on a topic change.
			$current_topic = $post['topic_id'];
			$index = 1;
		} else {
			// Otherswise, we just increase the index, so that
			// Simple:Press will hopefully recognize the PunBB order. Somewhat.
			++$index;
		}

		$user_id = 1 == $post['poster_id'] ? null : $post['poster_id']; // Guest?!
		$edited = null == $post['edited']
		? null
		: 'a:1:{i:0;a:2:{s:2:"by";s:' . strlen($post['edited_by']) . ':"' . $post['edited_by'] . '";s:2:"at";i:'. $post['edited'] .';}}';
		$mail = null; // PunBB does not save the guests' mail address.
		$status = 0; // Auto approve any post.
		$pinned = false; // PunBB does not know how to pin/stick single posts.

		$wp_sfposts_stmt->bind_param(
			'issiiissiiisi',
		$post['id'],
		parse_message($post['message'], Target::WordPress),
		gmdate('Y-m-d H:i:s', $post['posted']),
		$post['topic_id'],
		$user_id,
		$forums[(int)$post['forum_id']],
		$post['poster'],
		$mail,
		$status,
		$pinned,
		$index,
		$edited,
		$post['poster_ip']);
		$wp_sfposts_stmt->execute();
	}

	$wp_sfposts_stmt->close();
	$wp_sfposts->close();
	$posts->close();
	$pbb->close();
}
endif;