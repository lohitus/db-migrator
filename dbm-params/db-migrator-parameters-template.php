<?php
/*
 * db-migrator-parameters-template.php:
 * a part of the Database Migrator utility
 *
 * Copyright Ⓒ 2020 *asterysk <wordpress@asterysk.com>
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 2 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/**
 * Database string replacement utility parameters template.
 *
 * The annotated version of this file is @see db-migrator-parameters.php in the
 * parent directory of this file.
 *
 * @version     0.1.0
 * @since       0.1.0
 */
$_dbm = array(
	/* Database connection parameters: Required */
	'db_name'			=> '',
	'db_user'			=> '',
	'db_pass'			=> '',

	/* Database connection parameters: Optional */
	'db_host'			=> '',
	'db_port'			=> '',
	'db_cset'			=> '',
	'db_coll'			=> '',
	'db_sslc'			=> array(),

	/* String replacement filter parameters */
	'table_prefix'			=> '',
	'exclude_columns'		=> array(),
	'include_columns'		=> array(),
	'include_blob_columns'		=> false,

	/* Processing options */
	'record_retrieval_limit' 	=> 0,
	'protect_email'			=> true,

	/* String replacement parameters */
	'url_strings'			=> array(),
	'dir_strings'			=> array(),
	'txt_strings'			=> array(),

	/* Logging options */
	'log'				=> true,

	/* WordPress table prefix replacement option */
	'table_prefix_replace' 		=> '',
	);
