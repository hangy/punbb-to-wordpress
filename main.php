<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */
require 'bootstrap.php';
require 'migrate_users.php';
require 'migrate_memberships.php';
require 'migrate_news.php';
require 'migrate_blog.php';
require 'migrate_pms.php';
require 'update_comment_count.php';

migrate_users();
migrate_memberships();
migrate_news();
migrate_blog();
migrate_pms();
update_comment_count();