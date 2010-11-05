<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
function update_forum_stats() {
	$factory = DbConnectionFactory::getInstance();

	$forum_query = 'SELECT forum_id '
	. 'FROM wp_sfforums';

	$forum_stats_query = 'SELECT '
	. 'MAX(wp_sfposts.post_id), COUNT(DISTINCT wp_sfposts.post_id), '
	. 'COUNT(DISTINCT wp_sftopics.topic_id) '
	. 'FROM wp_sfposts, wp_sftopics, wp_sfforums '
	. 'WHERE wp_sftopics.forum_id = wp_sfforums.forum_id '
	. 'AND wp_sfposts.forum_id = wp_sfforums.forum_id '
	. 'AND wp_sfforums.forum_id = ? '
	. 'GROUP BY wp_sfforums.forum_id';

	$update_forum_query = 'UPDATE wp_sfforums '
	. 'SET post_id = ?, topic_count = ?, '
	. 'post_count = ? '
	. 'WHERE forum_id = ?';

	$wp = $factory->getConnection('WordPress');
	$forums = $wp->query($forum_query, MYSQLI_USE_RESULT);

	$forum_stats = $factory->getConnection('WordPress');
	$forum_stats_stmt = $forum_stats ->prepare($forum_stats_query);

	$update_forum = $factory->getConnection('WordPress');
	$update_forum_stmt = $update_forum->prepare($update_forum_query);

	while ($forum = $forums->fetch_assoc()) {
		$forum_stats_stmt->bind_param('i', $forum['forum_id']);
		$forum_stats_stmt->execute();
		$forum_stats_stmt->bind_result($post_id, $post_count, $topic_count);

		while ($forum_stats_stmt->fetch()) {
			$update_forum_stmt->bind_param(
				'iiii',
			$post_id,
			$topic_count,
			$post_count,
			$forum['forum_id']);
			$update_forum_stmt->execute();
		}
	}

	$update_forum_stmt->close();
	$update_forum->close();
	$forum_stats_stmt->close();
	$forum_stats->close();
	$forums->close();
	$wp->close();
}