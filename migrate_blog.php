<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
if ( !function_exists('migrate_blog') ) :
function migrate_blog() {
	$factory = DbConnectionFactory::getInstance();

	$blog_query = 'SELECT `id`, `user_id`, `date`, `title`, `text` '
	. 'FROM `blogposts`';

	$comment_query = 'SELECT bc.`user_id`, u.`username`, u.email, IFNULL(u.url, '."''".') AS url, bc.`text`, bc.`date` '
	. 'FROM `blogcomments` AS bc, `punbb_users` AS u '
	. 'WHERE bc.`pid` = ? '
	. 'AND u.id = bc.user_id';

	$insert_wp_post = 'INSERT INTO `wp_posts` '
	. '(`post_author`, `post_date`, `post_date_gmt`, `post_content`, '
	. '`post_title`, `post_name`, `post_modified`, `post_modified_gmt`, `guid`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';

	$insert_wp_term_relationships = 'INSERT INTO `wp_term_relationships` '
	. '(`object_id`, `term_taxonomy_id`)'
	. 'VALUES(?, 4)';

	$insert_wp_comment = 'INSERT INTO `wp_comments` '
	. '(`comment_post_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, '
	. ' `comment_date`, `comment_date_gmt`, `comment_content`, `user_id`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?)';

	$pbb = $factory->getConnection('PunBB');
	$blog = $pbb->query($blog_query, MYSQLI_USE_RESULT);

	$comments = $factory->getConnection('PunBB');
	$comments_stmt = $comments->prepare($comment_query);

	$wppost = $factory->getConnection('WordPress');
	$wppost_stmt = $wppost->prepare($insert_wp_post);

	$wptermrel = $factory->getConnection('WordPress');
	$wptermrel_stmt = $wptermrel->prepare($insert_wp_term_relationships);

	$wpcomment = $factory->getConnection('WordPress');
	$wpcomment_stmt = $wpcomment->prepare($insert_wp_comment);

	while ($b = $blog->fetch_assoc()) {
		$name = 'blog-' . $b['id'];
		$guid = 'http://www.warsow.net/?page=news&id=' . $b['id'];

		$wppost_stmt->bind_param(
			'issssssss',
		$b['user_id'],
		gmdate('Y-m-d H:i:s', $b['date']),
		gmdate('Y-m-d H:i:s', $b['date']),
		parse_message($b['text']),
		$b['title'],
		$name,
		gmdate('Y-m-d H:i:s', $b['date']),
		gmdate('Y-m-d H:i:s', $b['date']),
		$guid);
		$wppost_stmt->execute();
		$id = $wppost->insert_id;

		$wptermrel_stmt->bind_param('i', $id);
		$wptermrel_stmt->execute();

		$comments_stmt->bind_param('i', $b['id']);
		$comments_stmt->execute();
		
		$comments_stmt->bind_result($cuserid, $cuser, $cmail, $curl, $ctext, $cdate);
		while ($comments_stmt->fetch()) {
			$wpcomment_stmt->bind_param(
				'issssssi',
			$id,
			$cuser,
			$cmail,
			$curl,
			gmdate('Y-m-d H:i:s', $cdate),
			gmdate('Y-m-d H:i:s', $cdate),
			parse_message($ctext, Target::WordPress),
			$cuserid);
			$wpcomment_stmt->execute();
		}
	}

	$wpcomment_stmt->close();
	$wpcomment->close();
	$wptermrel_stmt->close();
	$wptermrel->close();
	$wppost_stmt->close();
	$wppost->close();
	$comments_stmt->close();
	$comments->close();
	$blog->close();
	$pbb->close();
}
endif;