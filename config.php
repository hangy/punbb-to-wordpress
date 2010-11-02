<?php
/*  Copyright 2010  hangy
 This program is free software. It comes without any warranty, to
 the extent permitted by applicable law. You can redistribute it
 and/or modify it under the terms of the Do What The Fuck You Want
 To Public License, Version 2, as published by Sam Hocevar. See
 http://sam.zoy.org/wtfpl/COPYING for more details.
 */

// Here, we define the database configuration for both sites.
$config = array(
	'WordPress' => array(
		'db' => array(
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'database' => 'wordpress'
			)
			),
	'PunBB' => array(
		'db' => array(
			'host' => 'localhost',
			'username' => 'root',
			'password' => '',
			'database' => 'punbb'
			)
			)
			);

			// Since Simple:Press has it's own usergroups and PunBB does the same,
			// it probably is the best idea to setup the groups in advance
			// and to setup the mapping here.
			// NOTE: Simple:Press allows user to be in more than one group while
			//       PunBB does not. We do not support more than one group here!
			$usergroups = array(
			// PunBB => Simple:Press
			1 => 8,
			4 => 7,
			2 => 5,
			3 => 6,
			10 => 7,
			13 => 7,
			17 => 6,
			20 => 9,
			21 => 9,
			22 => 6
			);
				
			// Just like user groups, I expect forums to be set up before running
			// this script.
			$forums = array(
			// PunBB => Simple:Press
			28 => 15,
			36 => 14,
			53 => 13,
			52 => 16,
			50 => 12,
			4 => 36,
			2 => 34,
			8 => 35,
			9 => 18,
			7 => 37,
			42 => 27,
			29 => 22,
			26 => 9,
			18 => 10,
			17 => 8,
			13 => 32,
			14 => 31,
			55 => 23,
			3 => 25,
			22 => 20,
			6 => 4,
			5 => 1,
			20 => 3,
			12 => 7,
			32 => 19,
			21 => 6,
			43 => 2,
			31 => 17,
			19 => 26,
			15 => 5,
			30 => 24,
			49 => 33,
			10 => 30,
			11 => 29,
			24 => 28,
			54 => 11,
			16 => 21);