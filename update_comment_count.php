<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
if ( !function_exists('update_comment_count') ) :
function update_comment_count() {
	$factory = DbConnectionFactory::getInstance();

	$posts_query = 'SELECT id, ( '
	. 'SELECT COUNT( * ) '
	. 'FROM wp_comments '
	. 'WHERE comment_post_ID = id '
	. ') AS count '
	. 'FROM wp_posts '
	. 'WHERE ( '
	. 'SELECT COUNT( * ) '
	. 'FROM wp_comments '
	. 'WHERE comment_post_ID = id '
	. ') >0';

	$update_posts = 'UPDATE wp_posts SET comment_count = ? WHERE ID = ?';
	$wp = $factory->getConnection('WordPress');
	$posts = $wp->query($posts_query, MYSQLI_USE_RESULT);

	$update_post = $factory->getConnection('WordPress');
	$update_post_stmt = $update_post->prepare($update_posts);

	while ($post = $posts->fetch_assoc()) {
		$update_post_stmt->bind_param(
			'ii',
		$post['count'],
		$post['id']);
		$update_post_stmt->execute();
	}

	$update_post_stmt->close();
	$update_post->close();
	$posts->close();
	$wp->close();
}
endif;