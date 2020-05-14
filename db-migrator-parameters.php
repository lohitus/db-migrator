<?php
/*
 * db-migrator-parameters.php:
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
 * Database string replacement utility parameters.
 *
 * These are the parameters required for @see class ASTX_DB_Migrator.
 *
 * @version     0.1.0
 * @since       0.1.0
 *
 * @var array $_dbm {
 * 		Database string replacement parameters.
 * 		@type string $db_name				Required. Name of database.
 * 		@type string $db_user				Required. Database user name.
 * 		@type string $db_pass				Required. Database password.
 * 		@type string $db_host				Optional. Database server host name. Defaults to 'localhost'.
 * 		@type string $db_port				Optional. Database connection port.
 * 		@type string $db_cset				Optional. Database connection character set.
 * 		@type string $db_coll				Optional. Database connection collation.
 * 		@type array $db_sslc {
 * 			Optional. SSL/TLS parameters for secure connection to database server.
 * 			@type string $key				Optional. Path to client private key file.
 * 			@type string $cert				Optional. Path to client public key certificate file.
 * 			@type string $ca				Optional. Path to certificate authority file. When used, this must specify the same certificate used by the server.
 * 			@type string $capath			Optional. Path to directory containing trusted TLS CA certificates.
 * 			@type string $cipher			Optional. Permitted cipher list for TLS encryption.
 * 			}
 * 		@type string $table_prefix			Optional. Restricts string replacements to tables names starting with this prefix.
 * 		@type string $new_prefix			Optional. WordPress databases only. Replace current table name prefix with new prefix.
 * 		@type array $exclude_columns {
 * 			Optional. Database columns to exclude from string replacement.
 * 			@type string $key				Required. Name of table.
 * 			@type mixed $value {
 * 				@type array $value {
 * 					Table column names.
 * 					@type int $key			Optional. Ignored.
 * 					@type string $value		Required. Column name of table.
 * 					}
 * 				@type any $value			Optional. Exclude all columns of table if not array of specific fields.
 * 				}
 * 			}
 * 		@type array $include_columns {
 * 			Optional. Database columns to include for string replacement.
 * 			@type string $key				Required. Name of table.
 * 			@type mixed $value {
 * 				@type array $value {
 * 					Table column names.
 * 					@type int $key			Optional. Ignored.
 * 					@type string $value		Required. Column name of table.
 * 					}
 * 				@type any $value			Optional. Include all columns of table if not array of specific fields.
 * 				}
 * 			}
 * 		@type bool $include_blob_columns 	Optional. Include [TINY|MEDIUM|LONG]BLOB columns for string replacement.
 * 		@type int $record_retrieval_limit	Optional. Process these many records at a time.
 * 		@type bool $protect_email			Optional. Defaults to TRUE: Do not change e-mail address domains derived from source URL strings below.
 * 		@type array $url_strings {
 * 			Optional. URL strings to search/replace.
 * 			@type string $key				Required. URL string to search for.
 * 			@type string $value				Required. URL string to replace with.
 * 			}
 * 		@type array $dir_strings {
 * 			Optional. Directory strings to search/replace.
 * 			@type string $key				Required. Directory string to search for.
 * 			@type string $value				Required. Directory string to replace with.
 * 			}
 * 		@type array $txt_strings {
 * 			Optional. Miscellaneous text strings to search/replace.
 * 			@type string $key				Required. Text string to search for.
 * 			@type string $value				Required. Text string to replace with.
 * 			}
 * 		@type mixed $_log					Optional. TRUE|FALSE for log output or file name to append log.
 * 		}
 */
$_dbm = array(
	/**
	 * Name of database.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Required. Name of database.
	 */
	'db_name'					=> '',

	/**
	 * Database user name.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Required. Database user name.
	 */
	'db_user'					=> '',

	/**
	 * Database password.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Required. Database password.
	 */
	'db_pass'					=> '',

	/**
	 * Database connection host.
	 *
	 * Database server host name defaults to 'localhost' if the host
	 * name is not specified here.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Optional. Database server host name.
	 */
	'db_host'					=> '',

	/**
	 * Database connection port.
	 *
	 * Database server connection port uses the default if not specified
	 * here.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Optional. Database connection port.
	 */
	'db_port'					=> '',

	/**
	 * Database connection character set.
	 *
	 * Database connection character set uses the default if not
	 * specified here. This character set affects the way data is
	 * escaped whilst communicating with the server.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Optional. Database connection character set.
	 */
	'db_cset'					=> '',

	/**
	 * Database connection collation.
	 *
	 * This value has no effect on string replacement.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Optional. Database connection collation.
	 */
	'db_coll'					=> '',

	/**
	 * TLS/SSL secured database connection.
	 *
	 * For connections to a remote database over TLS/SSL, mysqli allows for the
	 * server to verify the client certificate and vice versa.
	 *
	 * It is not common for the remote server to be set up to verify the client
	 * certificate. Client verification of the server certificate is usually
	 * adequate for preventing the man-in-the-middle.
	 *
	 * For client verification of the server certificate, set this parameter as
	 * $db_sslc = array(
	 * 		'ca'		=> /path/to/certificate/authority/file
	 * 		),
	 *
	 * and for others set this parameter as
	 * $db_sslc = array(
	 * 		'key'		=> /path/to/client/private/key/file
	 * 		'cert'		=> /path/to/client/public/key/certificate/file
	 * 		'ca'		=> /path/to/certificate/authority/file
	 * 		'capath'	=> /path/to/directory/containing/trusted/TLS/CA/certificates
	 * 		'cipher'	=> Space delimited cipher list for TLS encryption
	 * 		),
	 *
	 * As demonstrated, not all parameters above may be necessary and may vary
	 * depending on the database server set up.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array {
	 * 		Optional. SSL/TLS parameters for secure connection to database server.
	 * 		@type string $key				Optional. Path to client private key file.
	 * 		@type string $cert				Optional. Path to client public key certificate file.
	 * 		@type string $ca				Optional. Path to certificate authority file. When used, this must specify the same certificate used by the server.
	 * 		@type string $capath			Optional. Path to directory containing trusted TLS CA certificates.
	 * 		@type string $cipher			Optional. Permitted cipher list for TLS encryption.
	 * 		}
	 */
	'db_sslc'					=> array(),

	/**
	 * Table name prefix.
	 *
	 * Filters out table names not starting with this string.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Optional. Restricts string replacements to tables names starting with this prefix.
	 */
	'table_prefix'				=> '',

	/**
	 * Exclude database columns.
	 *
	 * Array of columns to exclude from string replacement grouped by
	 * table. Anything other than a valid array of fields excludes all
	 * columns from the table.
	 * Exclusions are processed before inclusions below.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array {
	 * 		Optional. Database columns to exclude from string replacement.
	 * 		@type string $key			Required. Name of table.
	 * 		@type array $value {
	 * 			Column names from table in $key.
	 * 			@type int $key			Optional. Ignored.
	 * 			@type string $value		Required. Column name of table.
	 * 			}
	 * 		}
	 */
	'exclude_columns'			=> array(),

	/**
	 * Include database columns.
	 *
	 * Array of columns to include for string replacement grouped by
	 * table. Anything other than a valid array of fields includes all
	 * columns from the table.
	 * Inclusions are processed after exclusions above.
	 *
	 * CAUTION: When specified, only tables/columns in this list are
	 * processed and no others.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array {
	 * 		Optional. Database columns to include for string replacement.
	 * 		@type string $key			Required. Name of table.
	 * 		@type array $value {
	 * 			Column names from table in $key.
	 * 			@type int $key			Optional. Ignored.
	 * 			@type string $value		Required. Column name of table.
	 * 			}
	 * 		}
	 */
	'include_columns'			=> array(),

	/**
	 * Include BLOB columns for string replacement.
	 *
	 * By default, columns of type [TINY|MEDIUM|LONG]BLOB are excluded
	 * from string replacements. This flag forces their inclusion, if
	 * not specifically excluded in $[exclude|include]_columns above.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var bool				Optional. TRUE to include BLOB columns.
	 */
	'include_blob_columns'		=> false,

	/**
	 * Row retrieval limit.
	 *
	 * This is the limit of the number of rows retrieved and processed
	 * at a time.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var int					Optional. Defaults to 100.
	 */
	'record_retrieval_limit' 	=> 0,

	/**
	 * Protect e-mail addresses.
	 *
	 * E-mail addresses derived from concatenation of '@' and the host
	 * name of URL search strings are preserved from string replacement.
	 * This flag requires to be set to boolean|string 'false' or '0' to
	 * override protection.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var bool				Optional. FALSE to exclude protection.
	 */
	'protect_email'				=> true,

	/**
	 * URL strings and replacements.
	 *
	 * This parameter is an array comprising search URL strings in keys
	 * and corresponding replacement URL strings in values. Optionally,
	 * this array may be split into two: 'url_strings_src' containing
	 * the array keys and 'url_strings_tgt' containing the corresponding
	 * array values. It is recommended to ensure the correct protocol
	 * prefixed to both the search and replacement strings and also to
	 * ensure the trailing / on both strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array {
	 * 		Required. URL strings to search/replace.
	 * 		@type string $key		Required. URL string to search for.
	 * 		@type string $value		Required. URL string to replace with.
	 * 		}
	 */
	'url_strings'				=> array(),

	/**
	 * Directory strings and replacements.
	 *
	 * This parameter is an array comprising search directory strings in
	 * keys and corresponding replacement directory strings in values.
	 * Optionally, this array may be split into two: 'dir_strings_src'
	 * containing the array keys and 'dir_strings_tgt' containing the
	 * corresponding array values. It is recommended to ensure leading
	 * Windows drive letter/directory separator and trailing directory
	 * separator on both the search and replacement strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array {
	 * 		Required. Directory strings to search/replace.
	 * 		@type string $key		Required. Directory string to search for.
	 * 		@type string $value		Required. Directory string to replace with.
	 * 		}
	 */
	'dir_strings'				=> array(),

	/**
	 * Miscellaneous text strings and replacements.
	 *
	 * This parameter is an array comprising search text strings in
	 * keys and corresponding replacement text strings in values.
	 * Optionally, this array may be split into two: 'txt_strings_src'
	 * containing the array keys and 'txt_strings_tgt' containing the
	 * corresponding array values.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array {
	 * 		Required. Text strings to search/replace.
	 * 		@type string $key		Required. Text string to search for.
	 * 		@type string $value		Required. Text string to replace with.
	 * 		}
	 */
	'txt_strings'				=> array(),

	/**
	 * Log.
	 *
	 * Setting boolean|string 'true' or '1' outputs log to the console.
	 * A valid filename string appends log output to the file specified
	 * and setting boolean|string 'false' or '0' suppresses log output.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var mixed				Optional. TRUE|FALSE for log output or file name to append log.
	 */
	'log'						=> true,

	/**
	 * Table name prefix replace.
	 *
	 * Applies only to WordPress databases. Changes table name prefix
	 * from $table_prefix above to the string specified here.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Optional. Replacement table name prefix.
	 */
	'table_prefix_replace' 		=> '',
	);
