<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
function migrate_news() {
	$factory = DbConnectionFactory::getInstance();

	$news_query = 'SELECT `id`, `user_id`, `date`, `title`, `text` '
	. 'FROM `newsposts`';

	$comment_query = 'SELECT np.`user_id`, u.`username`, u.email, IFNULL(u.url, '."''".') AS url, np.`text`, '
	. 'np.`date`, np.`user_ip` '
	. 'FROM `comments` AS np, `punbb_users` AS u '
	. 'WHERE np.`pid` = ? '
	. 'AND u.id = np.user_id';

	$insert_wp_post = 'INSERT INTO `wp_posts` '
	. '(`post_author`, `post_date`, `post_date_gmt`, `post_content`, '
	. '`post_title`, `post_name`, `post_modified`, `post_modified_gmt`, `guid`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';

	$insert_wp_term_relationships = 'INSERT INTO `wp_term_relationships` '
	. '(`object_id`, `term_taxonomy_id`)'
	. 'VALUES(?, 3)';

	$insert_wp_comment = 'INSERT INTO `wp_comments` '
	. '(`comment_post_ID`, `comment_author`, `comment_author_email`, `comment_author_url`, '
	. ' `comment_date`, `comment_date_gmt`, `comment_content`, `user_id`, `comment_author_IP`) '
	. 'VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';

	$pbb = $factory->getConnection('PunBB');
	$news = $pbb->query($news_query, MYSQLI_USE_RESULT);

	$comments = $factory->getConnection('PunBB');
	$comments_stmt = $comments->prepare($comment_query);

	$wppost = $factory->getConnection('WordPress');
	$wppost_stmt = $wppost->prepare($insert_wp_post);

	$wptermrel = $factory->getConnection('WordPress');
	$wptermrel_stmt = $wptermrel->prepare($insert_wp_term_relationships);

	$wpcomment = $factory->getConnection('WordPress');
	$wpcomment_stmt = $wpcomment->prepare($insert_wp_comment);

	while ($new = $news->fetch_assoc()) {
		$name = 'news-' . $new['id'];
		$guid = 'http://www.warsow.net/?page=news&id=' . $new['id'];

		$wppost_stmt->bind_param(
			'issssssss',
		$new['user_id'],
		gmdate('Y-m-d H:i:s', $new['date']),
		gmdate('Y-m-d H:i:s', $new['date']),
		parse_message($new['text']),
		$new['title'],
		$name,
		gmdate('Y-m-d H:i:s', $new['date']),
		gmdate('Y-m-d H:i:s', $new['date']),
		$guid);
		$wppost_stmt->execute();
		$id = $wppost->insert_id;

		$wptermrel_stmt->bind_param('i', $id);
		$wptermrel_stmt->execute();

		$comments_stmt->bind_param('i', $new['id']);
		$comments_stmt->execute();

		$comments_stmt->bind_result($cuserid, $cuser, $cmail, $curl, $ctext, $cdate, $cuserip);
		while ($comments_stmt->fetch()) {
			if (null == $cuserip) {
				$cuserip = '';
			}
				
			$wpcomment_stmt->bind_param(
				'issssssis',
			$id,
			$cuser,
			$cmail,
			$curl,
			gmdate('Y-m-d H:i:s', $cdate),
			gmdate('Y-m-d H:i:s', $cdate),
			parse_message($ctext, Target::WordPress),
			$cuserid,
			$cuserip);
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
	$news->close();
	$pbb->close();
}