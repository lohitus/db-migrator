<?php ( ( false === http_response_code() ) || exit ); // Prevent access over HTTP
/*
 * db-migrator.php:
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
 *
 * This program incorporates work covered by the following copyright and
 * permission notices:
 *   WordPress - Web publishing software
 *   Copyright 2011-2019 by the contributors
 *   WordPress is released under the GPL
 */

/**
 * Database string replacement utility.
 *
 * This script replaces a set of strings with corresponding replacement strings
 * in Mariadb/MySQL database tables. Typical use of this script is to replace
 * domain/home directory/URL strings for migration of a website from one
 * location/server to another.
 *
 * @version     0.4.0
 * @since       0.1.0
 */

class ASTX_DB_Migrator {

	/**
	 * Constant: Database Migrator version.
	 *
	 * @since   0.1.0
	 *
	 * @var string				Current version of this utility
	 */
	const DBM_VERSION = '0.4.0';

	/**
	 * Constant: Minimum PHP version required.
	 *
	 * @since   0.4.0
	 *
	 * @var string				Minimum PHP version required
	 */
	const PHP_VERSION = '5.4';

	/**
	 * Constant: Row retrieval limit (default).
	 *
	 * @since   0.4.0
	 *
	 * @var int					Number of rows to process at a time
	 */
	const DBM_MAXROWS = 10000;

	/**
	 * Autorun flag.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var bool				TRUE if parameter initialisation successful
	 */
	private $arun = false;

	/**
	 * Command line invocation flag.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var bool				TRUE if script invoked on command line
	 */
	private $icli = false;

	/**
	 * PHP MySQLi object.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var object				Instance of mysqli
	 */
	private $sqli = null;

	/**
	 * Database name.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Name of database for string replacement
	 */
	private $srdb = null;

	/**
	 * Row retrieval limit.
	 *
	 * @since   0.2.0
	 * @access  private
	 *
	 * @var int					Number of rows to process at a time
	 */
	private $rlim = null;

	/**
	 * Table name prefix.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var string				Name prefix (filter) for tables
	 */
	private $tpfx = null;

	/**
	 * Replacement table name prefix.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var string				Name prefix replacement for tables
	 */
	private $rpfx = null;

	/**
	 * Blob inclusion flag.
	 *
	 * Columns of type [TINY|MEDIUM|LONG]BLOB are normally excluded from
	 * string replacements. This flag marks them for inclusion.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var bool				TRUE for BLOB column types to be included
	 */
	private $blob = null;

	/**
	 * Column inclusions/exclusions filter.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var array				Column include/exclude filter per table
	 */
	private $cief = null;

	/**
	 * Database setup information.
	 *
	 * Array of table names, column names and mysqli_stmt::bind_param()
	 * types grouped by table name.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var array				Columns per table
	 */
	private $dbsi = null;

	/**
	 * Unique DBM sequence column.
	 *
	 * Column prefixed to tables being processed for string replacament.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var string				Temporary column name for DBM
	 */
	private $udsc = null;

	/**
	 * Row retrieval query strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array				'%' enclosed strings to query database
	 */
	private $rrqs = null;

	/**
	 * Row retrieval query strings count.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var int					Count of '%' enclosed strings to query
	 */
	private $rrqk = null;

	/**
	 * Database string replacement collection.
	 *
	 * Collection of strings for find/replace in database including
	 * corresponding JSON/URL encoded values.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array				Database string replacement collection
	 */
	private $dbsr = null;

	/**
	 * DBM search string collection.
	 *
	 * Search string collection being array keys of @see $dbsr.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array				DBM search string collection
	 */
	private $dssc = null;

	/**
	 * DBM replacement string collection.
	 *
	 * Replacement string collection being array values of @see $dbsr.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array				DBM replacement string collection
	 */
	private $drsc = null;

	/**
	 * Intermediate replacement string collection.
	 *
	 * @since   0.1.0
	 * @access  private
	 * @see set_dbm_string_intermediates()
	 *
	 * @var array				Intermediate replacement strings
	 */
	private $irsc = null;

	/**
	 * Normalized serialized string collection.
	 *
	 * Mapping of normalization index to PHP serialized string.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var array				Normalized serialized string collection
	 */
	private $nssc = null;

	/**
	 * Normalized serialized object collection.
	 *
	 * Mapping of normalization index to PHP serialized object.
	 *
	 * @since   0.4.0
	 * @access  private
	 *
	 * @var array				Normalized serialized object collection
	 */
	private $nsoc = null;

	/**
	 * DBM log.
	 *
	 * Logging data.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @var array				Logging data.
	 */
	private $dlog = null;

	/**
	 * Constructor.
	 *
	 * Initializes object and runs default processing sequence.
	 *
	 * @since   0.1.0
	 * @access  public
	 * @param array	$_arg		Optional. An array of arguments.
	 */
	public function __construct( $_arg = null ) {
		if (
			version_compare( phpversion(), $this::PHP_VERSION, '<' )
			|| ! extension_loaded( 'mysqli' )
			|| ! class_exists( 'mysqli' )
			|| ! extension_loaded( 'openssl' )
			|| ! function_exists( 'openssl_random_pseudo_bytes' )
			) {
			trigger_error( $this->get_dbm_error_message( 1 ), E_USER_ERROR );
		}

		// Run
		if ( true === ( $this->arun = $this->init( $_arg ) ) ) {
			$this->exec();
		} else {
			$this->reset();
		}
	}

	/**
	 * Destructor.
	 *
	 * Closes database connection and resets object to initial state.
	 *
	 * @since   0.1.0
	 * @access  public
	 */
	public function __destruct() {
		$this->reset();
	}

	/**
	 * Set a property.
	 *
	 * @since   0.1.0
	 * @access  public
	 *
	 * @param string $_var      Required. Name of the property.
	 * @param mixed $_val       Required. Value to assign.
	 * @return mixed			Value assigned or NULL on failure.
	 */
	public function set( $_var = null, $_val = null ) {
		return ( $this->is_valid_var_name( $_var ) ? ( $this->{$_var} = $_val ) : null );
	}

	/**
	 * Retrieve a property.
	 *
	 * @since   0.1.0
	 * @access  public
	 *
	 * @param string $_var      Required. Name of the property.
	 * @return mixed            Value assigned or NULL on failure.
	 */
	public function get( $_var = null ) {
		return ( ( $this->is_valid_var_name( $_var ) && property_exists( $this, $_var ) ) ? $this->{$_var} : null );
	}

	/**
	 * Set up object.
	 *
	 * Sets parameters for default processing sequence.
	 *
	 * @since   0.1.0
	 * @access  public
	 *
	 * @param array	$_arg		Optional. An array of arguments.
	 * @return bool             TRUE if object setup successful or FALSE otherwise.
	 */
	public function init( $_arg = null ) {
		// Initialise dbm parameters
		$this->reset();

		// Ready?
		return ( false !== $this->set_dbm_params( $this->get_dbm_params( ( ( isset( $_arg ) && is_array( $_arg ) ) ? $_arg : null ) ) ) );
	}

	/**
	 * Test readiness.
	 *
	 * Checks parameter set up for default processing sequence.
	 *
	 * @since   0.1.0
	 * @access  public
	 *
	 * @param string $_arg		Optional. str_replace|tbl_rename|NULL.
	 * @return bool             TRUE if ready or FALSE otherwise.
	 */
	public function ready( $_arg = null ) {
		// Required controls
		if (
			! isset( $this->sqli, $this->srdb )
			|| ! ( $this->sqli instanceof mysqli )
			|| ! empty( $this->sqli->connect_errno )
			|| ! empty( $this->sqli->errno )
			|| ! is_array( $_sdb = $this->get_query_result( 'SELECT DATABASE() AS `srdb`', null, true ) )
			|| ( $this->srdb !== reset( $_sdb ) )
			) {
			return false;
		}

		// String replacement controls
		$_src = (
			isset( $this->dbsi, $this->udsc, $this->rrqs, $this->rrqk )
			&& is_array( $this->dbsi )
			&& count( $this->dbsi )
			&& is_string( $this->udsc )
			&& (bool) preg_match( '#^_dbm_udsc_[0-9A-F]{8}$#', $this->udsc )
			&& is_array( $this->rrqs )
			&& ( $this->rrqk === count( array_filter( $this->rrqs, 'is_string' ) ) )
			&& ( true === $this->set_dbm_search_replace_arrays() )
			);

		// Table rename controls
		$_trc = (
			isset( $this->tpfx, $this->rpfx )
			&& is_string( $this->tpfx )
			&& strlen( $this->tpfx )
			&& is_string( $this->rpfx )
			&& strlen( $this->rpfx )
			);

		return ( ( 'str_replace' === $_arg ) ? $_src : ( ( 'tbl_rename' === $_arg ) ? $_trc : ( $_src || $_trc ) ) );
	}

	/**
	 * Run default sequence.
	 *
	 * @since   0.1.0
	 * @access  public
	 */
	public function exec() {
		// Perform string replacement in database
		$this->exec_str_replace();

		// Perform table rename, if any
		$this->exec_tbl_rename();
	}

	/**
	 * Perform string replacement in database.
	 *
	 * @since   0.1.0
	 * @access  public
	 */
	public function exec_str_replace() {
		if ( true !== $this->ready( 'str_replace' ) ) {
			return false;
		}

		// Disable foreign key checks
		$_fkc = $this->get_query_result( 'SET @DBM_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0' );

		// Database string replacement loop
		foreach ( $this->dbsi as $_key => $_val ) {
			// Check for field list, acquire table lock and set unique DBM sequence column
			if (
				! isset( $_val )
				|| ! is_array( $_val )
				|| ! ( count( $_val ) > 1 )
				|| ( false === $this->get_query_result( "LOCK TABLE `{$_key}` WRITE" ) )
				|| ( false === $this->set_dbm_sequence_column( $_key ) )
				) {
				continue;
			}

			// Perform string replacements in table
			$this->process_dbm_records( $_key );

			// Remove unique DBM sequence column
			$this->del_dbm_sequence_column( $_key );
		}

		// Release table locks
		$this->get_query_result( 'UNLOCK TABLES' );

		// Restore foreign key checks
		if ( true === $_fkc ) {
			$this->get_query_result( 'SET FOREIGN_KEY_CHECKS=@DBM_FOREIGN_KEY_CHECKS' );
		}
	}

	/**
	 * Rename tables in database.
	 *
	 * @since   0.1.0
	 * @access  public
	 */
	public function exec_tbl_rename() {
		if ( true !== $this->ready( 'tbl_rename' ) ) {
			return false;
		}
		$this->wp_replace_table_prefix();
	}

	/**
	 * Reset object to initial state.
	 *
	 * @since   0.1.0
	 * @access  public
	 */
	public function reset() {
		// Flush log
		$this->flush_dbm_log();
		$this->get_print_log( '_dbm_reset' );

		// Ensure mysqli::close()
		$this->connect_to_db( '_dbm_reset' );

		// Clear $this
		foreach( get_object_vars( $this ) as $_key => $_val ) {
			$this->{$_key} = null;
		}

		// Reset $icli, $rlim
		$this->icli = $this->is_sapi_cli();
		$this->rlim = $this::DBM_MAXROWS;
	}

	/**
	 * Setup: Read and validate parameters.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Optional. An array of arguments.
	 * @return mixed            Validated parameters array or FALSE.
	 */
	private function get_dbm_params( $_arg = null ) {
		// Raw parameters
		if ( ! empty( $_arg ) && is_array( $_arg ) ) {
			$_dbm = $_arg;
		} elseif ( $this->icli && is_array( $_gca = $this->get_cli_argv() ) && ! empty( $_gca['dbm'] ) ) {
			$_dbm = array( 'dbm' => $_gca['dbm'] );
		} elseif ( ! $this->icli && isset( $_REQUEST ) && is_array( $_REQUEST ) ) {
			$_dbm = $_REQUEST;
		}

		// DB Migration parameters in file?
		if ( isset( $_dbm ) && is_array( $_dbm ) && isset( $_dbm['dbm'] ) && is_string( $_dbm['dbm'] ) && is_file( $_dbm['dbm'] ) && is_readable( $_dbm['dbm'] ) ) {
			include "{$_dbm['dbm']}";
		}

		// Maybe split string parameters in $_REQUEST?
		if (
			isset( $_dbm )
			&& is_array( $_dbm )
			) {
			foreach ( array( 'url_strings', 'dir_strings', 'txt_strings' ) as $_key => $_val ) {
				if ( isset( $_dbm["{$_val}"] ) ) {
					continue;
				} elseif (
					isset( $_dbm["{$_val}_src"], $_dbm["{$_val}_tgt"] )
					&& is_array( $_dbm["{$_val}_src"] )
					&& is_array( $_dbm["{$_val}_tgt"] )
					&& ( count( $_dbm["{$_val}_tgt"] ) === count( $_src = array_filter( $_dbm["{$_val}_src"], array( $this, 'is_notnum_string' ) ) ) )
					) {
					$_dbm["{$_val}"] = array_combine( $_src, $_dbm["{$_val}_tgt"] );
				}
			}
		}

		// Validate and return parameters | false
		return ( ( isset( $_dbm ) && $this->raw_dbm_params_valid( $_dbm ) ) ? $_dbm : false );
	}

	/**
	 * Setup: Validate raw parameters.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Required. An array of arguments.
	 * @return bool             TRUE if valid parameters array or FALSE.
	 */
	private function raw_dbm_params_valid( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! is_array( $_arg )
			) {
			return false;
		}

		$_ren = $_str = true;
		foreach ( array( 'url_strings', 'dir_strings', 'txt_strings' ) as $_key => $_val ) {
			if ( ! isset( $_arg["{$_val}"] ) ) {
				continue;
			} elseif (
				! is_array( $_arg["{$_val}"] )
				|| ( count( $_arg["{$_val}"] ) !== count( array_filter( array_keys( $_arg["{$_val}"] ), array( $this, 'is_notnum_string' ) ) ) )
				) {
				$_str = false;
				break;
			}
		}

		$_stk = count(
			array_merge(
				( isset( $_arg['url_strings'] ) && is_array( $_arg['url_strings'] ) ? $_arg['url_strings'] : array() ),
				( isset( $_arg['dir_strings'] ) && is_array( $_arg['dir_strings'] ) ? $_arg['dir_strings'] : array() ),
				( isset( $_arg['txt_strings'] ) && is_array( $_arg['txt_strings'] ) ? $_arg['txt_strings'] : array() )
				)
			);

		$_str = ( ( true === $_str ) && ( 0 < $_stk ) );
		$_ren = (
			isset( $_arg['table_prefix'], $_arg['table_prefix_replace'] )
			&& is_string( $_arg['table_prefix'] )
			&& strlen( $_arg['table_prefix'] )
			&& is_string( $_arg['table_prefix_replace'] )
			&& strlen( $_arg['table_prefix_replace'] )
			);

		return (
			( ( true === $_str ) || ( true === $_ren ) )
			&& isset( $_arg['db_name'], $_arg['db_user'], $_arg['db_pass'] )
			&& is_string( $_arg['db_name'] )
			&& is_string( $_arg['db_user'] )
			&& is_string( $_arg['db_pass'] )
			);
	}

	/**
	 * Setup: Import and set processing parameters.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Required. An array of arguments.
	 * @return bool             TRUE if set up successful or FALSE.
	 */
	private function set_dbm_params( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! is_array( $_arg )
			|| ! $this->prep_dbm_connect( $_arg )
			|| ! isset( $this->sqli, $this->srdb )
			|| ! ( $this->sqli instanceof mysqli )
			|| ! empty( $this->sqli->connect_errno )
			|| ! empty( $this->sqli->errno )
			|| ! is_string( $this->srdb )
			|| ! strlen( $this->srdb )
			|| ! $this->prep_dbm_filters( $_arg )
			|| ( ! $this->prep_dbm_setup_info() && ! $this->ready() )
			) {
			return false;
		}

		// DBM logger: Set up
		$this->dlog = array(
			'_typ'	=> ( isset( $_arg['log'] ) ? ( is_bool( $_log = $this->string_to_boolean( $_arg['log'] ) ) ? $_log : ( is_string( $_arg['log'] ) && $this->get_print_log( $_arg['log'] ) ? 'file' : false ) ) : false ),
			'_run'	=> $this->get_current_timestamp(),
			'_dta'	=> array( 'dbsr' => array(), 'dbsi' => array() ),
			'_err'	=> array(),
			);

		// Collector array: Row retrieval query strings
		$rrqs = array();

		// Collector array: Database strings and replacements
		$dbsr = array();

		// Protect email address strings derived from host name in URL
		$peas = ( ! isset( $_arg['protect_email'] ) || ( false !== $this->string_to_boolean( $_arg['protect_email'] ) ) );

		// Collect strings: URL, directory path and miscellaneous text
		/*
		 * String replacement conflict resolution priority is determined
		 * by the order of string collection: txt_string replacements
		 * override dir_strings that in turn, override url_strings.
		 */
		foreach ( array( 'txt_strings', 'dir_strings', 'url_strings' ) as $_key => $_val ) {
			if (
				! isset( $_arg["{$_val}"] )
				&& is_array( $_arg["{$_val}"] )
				) {
				continue;
			}

			$this->dlog['_dta']['dbsr']["{$_val}"] = $_arg["{$_val}"];

			$_fnc = "get_dbm_params_{$_val}";

			foreach ( $_arg["{$_val}"] as $lkey => $lval ) {
				if ( ! is_array( $_sca = $this->{$_fnc}( $lkey, $lval, $peas ) ) ) {
					continue;
				}
				$rrqs = array_merge(
					$rrqs,
					array_filter(
						( ( isset( $_sca['rrqs'] ) && is_array( $_sca['rrqs'] ) ) ? $_sca['rrqs'] : array() ),
						function( $rval ) use ( $rrqs ) {
							return ( ! in_array( $rval, $rrqs ) );
							}
						)
					);
				$dbsr = array_merge(
					$dbsr,
					array_filter(
						( ( isset( $_sca['dbsr'] ) && is_array( $_sca['dbsr'] ) ) ? $_sca['dbsr'] : array() ),
						function( $skey ) use ( $dbsr ) {
							return ( ! isset( $dbsr["{$skey}"] ) );
							},
						ARRAY_FILTER_USE_KEY
						)
					);
			}
		}

		// Extend row retrieval query strings
		$this->rrqs = array();
		foreach ( $rrqs as $_key => $_val ) {
			$this->rrqs = array_merge(
				$this->rrqs,
				array_filter(
					array_unique(
						array_map(
							function( $rval ) {
								return sprintf( '%%%s%%', preg_replace( '#(?<![\\\\])(%|_)#', '\\\\${1}', $rval ) );
								},
							array_map(
								array( $this->sqli, 'real_escape_string' ),
								array_values( $this->get_dbm_params_enc_strings( $_val ) )
								)
							)
						),
					function( $rval ) {
						return ( ! in_array( $rval, $this->rrqs ) );
						}
					)
				);
		}

		// Number of row retrieval query strings
		$this->rrqk = count( $this->rrqs );

		// Row retrieval limit for string replacement
		if (
			isset( $_arg['record_retrieval_limit'] )
			&& is_numeric( $_arg['record_retrieval_limit'] )
			&& ( ( $_lim = abs( intval( $_arg['record_retrieval_limit'] ) ) ) > 0 )
			) {
			$this->rlim = $_lim;
		}

		// Extend strings and replacements
		$this->dbsr = array();
		foreach ( $dbsr as $_key => $_val ) {
			$_xtk = $this->get_dbm_params_enc_strings( $_key );
			$_xtv = $this->get_dbm_params_enc_strings( $_val );
			foreach ( $_xtk as $xkey => $xval ) {
				if (
					isset( $this->dbsr["{$xval}"] )
					|| ! isset( $_xtv["{$xkey}"] )
					) {
					continue;
				} else {
					$this->dbsr["{$xval}"] = $_xtv["{$xkey}"];
				}
			}
		}

		// Filter tables without string replacement records
		$this->dbsi = array_filter(
			( ( isset( $this->dbsi ) && is_array( $this->dbsi ) ) ? $this->dbsi : array() ),
			function( $_key ) {
				return $this->has_dbm_record_count( $_key );
				},
			ARRAY_FILTER_USE_KEY
			);

		// Set up complete for either string replacement or table rename
		return $this->ready();
	}

	/**
	 * Setup: URL strings and replacements.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_src		Required. Valid URL.
	 * @param string $_tgt		Required. String to replace $_src in the database.
	 * @param bool $_pde		Required. TRUE to protect derived e-mail addresses.
	 * @return array            Collection of source and target strings for URL replacement.
	 */
	private function get_dbm_params_url_strings( $_src = null, $_tgt = null, $_pde = null ) {
		// Validate source/target strings
		if (
			! isset( $_src, $_tgt )
			|| ! $this->is_notnum_string( $_src )
			|| ! $this->is_notnum_string( $_tgt )
			) {
			return false;
		}

		$_rtn = array(
			'rrqs'	=> array(),
			'dbsr'	=> array(
				"{$_src}" => "{$_tgt}",
				),
			);

		$_url = parse_url( $_src );
		$_rpl = parse_url( $_tgt );
		$_rtn['rrqs'][] = $this->strip_www_prefix( ( is_array( $_url ) && ! empty( $_url['host'] ) ) ? $_url['host'] : $_src );

		if (
			is_array( $_url )
			&& is_array( $_rpl )
			&& isset( $_url['scheme'], $_url['host'], $_rpl['scheme'], $_rpl['host'] )
			) {
			// url host strings
			$_uph = sprintf(
				'%1$s%2$s',
				( isset( $_url['user'] ) ? ( isset( $_url['pass'] ) ? "{$_url['user']}:{$_url['pass']}@" : "{$_url['user']}@" ) : '' ),
				( isset( $_url['port'] ) ? "{$_url['host']}:{$_url['port']}" : $_url['host'] )
				);
			$_rph = sprintf(
				'%1$s%2$s',
				( isset( $_rpl['user'] ) ? ( isset( $_rpl['pass'] ) ? "{$_rpl['user']}:{$_rpl['pass']}@" : "{$_rpl['user']}@" ) : '' ),
				( isset( $_rpl['port'] ) ? "{$_rpl['host']}:{$_rpl['port']}" : $_rpl['host'] )
				);

			$_url['path'] = ( isset( $_url['path'] ) ? trim( $_url['path'], '\\/' ) : '' );
			$_rpl['path'] = ( isset( $_rpl['path'] ) ? trim( $_rpl['path'], '\\/' ) : '' );

			// source/target path with/without slash
			$_ups = ( empty( $_url['path'] ) ? '/' : "/{$_url['path']}/" );
			$_upn = ( empty( $_url['path'] ) ? '' : "/{$_url['path']}" );
			$_rps = ( empty( $_rpl['path'] ) ? '/' : "/{$_rpl['path']}/" );
			$_rpn = ( empty( $_rpl['path'] ) ? '' : "/{$_rpl['path']}" );

			// query/fragment
			$_uqf = sprintf( '%1$s%2$s', ( empty( $_url['query'] ) ? '' : "?{$_url['query']}" ), ( empty( $_url['fragment'] ) ? '' : "#{$_url['fragment']}" ) );
			$_rqf = sprintf( '%1$s%2$s', ( empty( $_rpl['query'] ) ? '' : "?{$_rpl['query']}" ), ( empty( $_rpl['fragment'] ) ? '' : "#{$_rpl['fragment']}" ) );

			// url strings without www prefix
			$_uwp = $this->strip_www_prefix( $_uph );
			$_rwp = $this->strip_www_prefix( $_rph );

			// merge source/target url variation strings
			$_mvs = array(
				0	=> "{$_url['scheme']}://{$_uph}{$_ups}{$_uqf}",
				1	=> "{$_url['scheme']}://{$_uph}{$_upn}{$_uqf}",
				2	=> "//{$_uph}{$_ups}{$_uqf}",
				3	=> "//{$_uph}{$_upn}{$_uqf}",
				4	=> "{$_uph}{$_ups}{$_uqf}",
				5	=> "{$_uph}{$_upn}{$_uqf}",
				6	=> "{$_url['scheme']}://{$_uwp}{$_ups}{$_uqf}",
				7	=> "{$_url['scheme']}://{$_uwp}{$_upn}{$_uqf}",
				8	=> "//{$_uwp}{$_ups}{$_uqf}",
				9	=> "//{$_uwp}{$_upn}{$_uqf}",
				10	=> "{$_uwp}{$_ups}{$_uqf}",
				11	=> "{$_uwp}{$_upn}{$_uqf}",
				);
			$_mvt = array(
				0	=> "{$_rpl['scheme']}://{$_rph}{$_rps}{$_rqf}",
				1	=> "{$_rpl['scheme']}://{$_rph}{$_rpn}{$_rqf}",
				2	=> "//{$_rph}{$_rps}{$_rqf}",
				3	=> "//{$_rph}{$_rpn}{$_rqf}",
				4	=> "{$_rph}{$_rps}{$_rqf}",
				5	=> "{$_rph}{$_rpn}{$_rqf}",
				6	=> "{$_rpl['scheme']}://{$_rwp}{$_rps}{$_rqf}",
				7	=> "{$_rpl['scheme']}://{$_rwp}{$_rpn}{$_rqf}",
				8	=> "//{$_rwp}{$_rps}{$_rqf}",
				9	=> "//{$_rwp}{$_rpn}{$_rqf}",
				10	=> "{$_rwp}{$_rps}{$_rqf}",
				11	=> "{$_rwp}{$_rpn}{$_rqf}",
				);

			// protect derived e-mail addresses (@$_src domain)
			if (
				( true === $_pde )
				&& strlen( $_ead = preg_replace( '#^www[\\\\]*?\.#i', '', $_url['host'] ) )
				) {
				$_rtn['dbsr']["@{$_ead}"] = "@{$_ead}";
			}

			// collect url strings
			if ( count( $_mvt ) === ( $j = count( $_mvs ) ) ) {
				for ( $i = 0; $i < $j; $i += 1 ) {
					if (
						isset( $_rtn['dbsr']["{$_mvs[$i]}"] )
						|| ! isset( $_mvt[$i] )
						) {
						continue;
					}
					$_rtn['dbsr']["{$_mvs[$i]}"] = $_mvt[$i];
				}
			}
		}

		return $_rtn;
	}

	/**
	 * Setup: Directory strings and replacements.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_src		Required. Valid directory name.
	 * @param string $_tgt		Required. String to replace $_src in the database.
	 * @return array            Collection of source and target strings for directory name replacement.
	 */
	private function get_dbm_params_dir_strings( $_src = null, $_tgt = null ) {
		// regex for windows drive, backslash \, forward slash /
		static $_wdr, $_bsr, $_fsr;
		if ( ! isset( $_wdr, $_bsr, $_fsr ) ) {
			$_wdr = array( '#^(/|[a-z]:[/\\\\]*)#i', '#^([a-z]:[/\\\\]*)#i' );
			$_bsr = array( '#[\\\\]+#', '#^[\\\\]+#', '\\', '\\' );
			$_fsr = array( '#[/]+#', '#^[/]+#', '/', '/' );
		}

		// Validate source/target strings
		if (
			! isset( $_src, $_tgt )
			|| ! $this->is_notnum_string( $_src )
			|| ! $this->is_notnum_string( $_tgt )
			) {
			return false;
		}

		// return array
		$_rtn = array(
			'rrqs'	=> array(),
			'dbsr'	=> array(),
			);

		// directory separator
		$_bds = (bool) preg_match( $_bsr[0], $_src );
		$_fds = (bool) preg_match( $_fsr[0], $_src );

		// db retrieval string for subject directory
		$_dir = implode(
			( empty( $_bds ) ? $_fsr[2] : $_bsr[2] ),
			preg_split( ( empty( $_bds ) ? $_fsr[0] : $_bsr[0] ), $_src, -1, PREG_SPLIT_NO_EMPTY )
			);
		$_drs = preg_replace( $_wdr[0], '', $_dir );

		// short-circuit?
		if ( is_string( $_drs ) && strlen( $_drs ) ) {
			$_rtn['rrqs'][] = $_drs;
		} else {
			$_rtn['rrqs'][] = $_src;
			$_rtn['dbsr']["{$_src}"] = "{$_tgt}";
			return $_rtn;
		}

		// cleverness (@_@ ;-P)
		$_bts = (bool) preg_match( $_bsr[0], $_tgt );
		$_fts = (bool) preg_match( $_fsr[0], $_tgt );
		$_rpl = implode(
			( empty( $_bts ) ? $_fsr[2] : $_bsr[2] ),
			preg_split( ( empty( $_bts ) ? $_fsr[0] : $_bsr[0] ), $_tgt, -1, PREG_SPLIT_NO_EMPTY )
			);
		$_trs = preg_replace( $_wdr[0], '', $_rpl );

		// directory source/target separator strings
		$_sss = ( empty( $_bds ) ? $_fsr[3] : $_bsr[3] );
		$_tss = ( empty( $_bts ) ? $_fsr[3] : $_bsr[3] );

		// leading source/target directory separators
		// { not backslash ? [ not forward slash ? ( not windows drive root ? '' : windows drive root ) : forward slash ] : backslash }
		$_lss = ( ! (bool) preg_match( $_bsr[1], $_src ) ? ( ! (bool) preg_match( $_fsr[1], $_src ) ? ( ( ! (bool) preg_match( $_wdr[1], $_src, $_pma ) || ! isset( $_pma ) || empty( $_pma[1] ) ) ? '' : $_pma[1] ) : $_fsr[3] ) : $_bsr[3] );
		$_lts = ( ! (bool) preg_match( $_bsr[1], $_tgt ) ? ( ! (bool) preg_match( $_fsr[1], $_tgt ) ? ( ( ! (bool) preg_match( $_wdr[1], $_tgt, $_pma ) || ! isset( $_pma ) || empty( $_pma[1] ) ) ? '' : $_pma[1] ) : $_fsr[3] ) : $_bsr[3] );

		// rollback backslashes for php processing
		$_dir = ( empty( $_bds ) ? $_drs : preg_replace( $_bsr[0], $_bsr[3], $_drs ) );
		$_rpl = ( empty( $_bts ) ? $_trs : preg_replace( $_bsr[0], $_bsr[3], $_trs ) );

		// string replacement extended update
		$_rtn['dbsr'] = array( "{$_src}" => "{$_tgt}" );
		if ( ! isset( $_rtn['dbsr']["{$_lss}{$_dir}{$_sss}"] ) ) {
			$_rtn['dbsr']["{$_lss}{$_dir}{$_sss}"] = "{$_lts}{$_rpl}{$_tss}";
		}
		if ( ! isset( $_rtn['dbsr']["{$_lss}{$_dir}"] ) ) {
			$_rtn['dbsr']["{$_lss}{$_dir}"] = "{$_lts}{$_rpl}";
		}
		if (
			(bool) preg_match( ( empty( $_bds ) ? $_fsr[0] : $_bsr[0] ), $_drs )
			&& (bool) preg_match( ( empty( $_bts ) ? $_fsr[0] : $_bsr[0] ), $_trs )
			&& is_string( $_trs )
			&& strlen( $_trs )
			&& ! empty( $_lss )
			&& ! empty( $_lts )
			) {
			if ( ! isset( $_rtn['dbsr']["{$_dir}{$_sss}"] ) ) {
				$_rtn['dbsr']["{$_dir}{$_sss}"] = "{$_rpl}{$_tss}";
			}
			if ( ! isset( $_rtn['dbsr']["{$_dir}"] ) ) {
				$_rtn['dbsr']["{$_dir}"] = "{$_rpl}";
			}
		}

		return $_rtn;
	}

	/**
	 * Setup: Miscellaneous text strings and replacements.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_src		Required. Valid string.
	 * @param string $_tgt		Required. String to replace $_src in the database.
	 * @return array            Collection of source and target strings for literal replacement.
	 */
	private function get_dbm_params_txt_strings( $_src = null, $_tgt = null ) {
		if (
			! isset( $_src, $_tgt )
			|| ! $this->is_notnum_string( $_src )
			|| ! $this->is_notnum_string( $_tgt )
			) {
			return false;
		}

		return array(
			'rrqs'	=> array( $_src ),
			'dbsr'	=> array( "{$_src}" => "{$_tgt}" ),
			);
	}

	/**
	 * Setup: Encode strings
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Valid string.
	 * @return array            Collection of encoded strings.
	 */
	private function get_dbm_params_enc_strings( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! $this->is_notnum_string( $_arg )
			) {
			return array();
		} else {
			return array(
				'_raw'	=> $_arg,
				'_url'	=> urlencode( $_arg ),
				'_jse'	=> trim( json_encode( $_arg ), '" ' ),
				'_uje'	=> urlencode( trim( json_encode( $_arg ), '" ' ) )
				);
		}
	}

	/**
	 * Database: Set up connection
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Required. An array of arguments.
	 * @return bool             TRUE if connection successful or FALSE.
	 */
	private function prep_dbm_connect( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! is_array( $_arg )
			) {
			return false;
		}

		// $db_port should be int
		$_arg['db_port'] = ( ( isset( $_arg['db_port'] ) && is_numeric( $_arg['db_port'] ) ) ? intval( $_arg['db_port'] ) : null );

		// Parse host parameters and merge with $_arg
		$_hps = $this->connect_to_db_get_host( isset( $_arg['db_host'] ) ? $_arg['db_host'] : null );
		if ( ! isset( $_hps['db_port'] ) ) {
			unset( $_hps['db_port'] );
		}
		$_arg = array_merge( $_arg, $_hps );

		// Connect to database
		return $this->connect_to_db( $_arg );
	}

	/**
	 * Database: Set up table/column filters.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Required. An array of arguments.
	 * @return bool             TRUE if filters set up or FALSE.
	 */
	private function prep_dbm_filters( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! is_array( $_arg )
			) {
			return false;
		}

		// Initialise column include/exclude filters
		$this->cief = array(
			'exclude'	=> array(),
			'include'	=> array(),
			);

		// Collate exclusions/inclusions per column per table
		foreach ( array( 'exclude', 'include' ) as $_key => $_val ) {
			// No exclusion/inclusion specified
			if (
				! isset( $_arg["{$_val}_columns"] )
				|| ! is_array( $_arg["{$_val}_columns"] )
				) {
				continue;
			}

			foreach ( array_filter( array_keys( $_arg["{$_val}_columns"] ), 'is_string' ) as $lkey => $lval ) {
				// filter column count
				$_fcc = ( is_array( $_arg["{$_val}_columns"]["{$lval}"] ) ? count( $_fct = array_filter( $_arg["{$_val}_columns"]["{$lval}"], 'is_string' ) ) : 0 );
				if ( empty( $_fcc ) ) {
					$this->cief["{$_val}"]["{$lval}"] = true;
				} else {
					$this->cief["{$_val}"]["{$lval}"] = array_combine( array_values( $_fct ), array_fill( 0, $_fcc, true ) );
				}
			}
		}

		// BLOB column type exclusion
		$this->blob = ( isset( $_arg['include_blob_columns'] ) && ( true === $this->string_to_boolean( $_arg['include_blob_columns'] ) ) );

		return true;
	}

	/**
	 * Database: Table/column set up for string replacement.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @return bool             TRUE if information set up or FALSE.
	 */
	private function prep_dbm_setup_info() {
		// Retrieve column information
		$_sql = 'SELECT `c`.`TABLE_NAME`, `c`.`COLUMN_NAME`, CAST( `c`.`ORDINAL_POSITION` AS UNSIGNED ) AS `ORDINAL_POSITION`, `c`.`COLUMN_TYPE`, `c`.`COLUMN_COMMENT`, `s`.`INDEX_NAME`, `s`.`INDEX_COMMENT`
			FROM `INFORMATION_SCHEMA`.`COLUMNS` AS `c`
				INNER JOIN `INFORMATION_SCHEMA`.`TABLES` AS `t`
					ON `t`.`TABLE_SCHEMA` = `c`.`TABLE_SCHEMA` AND `t`.`TABLE_NAME` = `c`.`TABLE_NAME` AND `t`.`TABLE_TYPE` = ?
				LEFT JOIN `INFORMATION_SCHEMA`.`STATISTICS` AS `s`
					ON `s`.`TABLE_SCHEMA` = `c`.`TABLE_SCHEMA` AND `s`.`TABLE_NAME` = `c`.`TABLE_NAME` AND `s`.`COLUMN_NAME` = `c`.`COLUMN_NAME`
			WHERE `c`.`TABLE_SCHEMA` = ?
			GROUP BY `c`.`TABLE_NAME`, `ORDINAL_POSITION`, `c`.`COLUMN_NAME`
			ORDER BY `c`.`TABLE_NAME`, `ORDINAL_POSITION`, `c`.`COLUMN_NAME`
			ASC';

		// Abort on information retrieval failure
		if (
			( false === ( $_dta = $this->get_query_result( $_sql, array( 'ss', 'BASE TABLE', $this->srdb ) ) ) )
			|| ! is_array( $_dta )
			) {
			return false;
		}

		// Reorganize unique DBM sequence columns
		$_udf = array();
		foreach ( $_dta as $_key => $_val ) {
			if ( $this->is_dbm_residual( $_val ) ) {
				$this->del_dbm_sequence_column( $_val );
				continue;
			}
			if ( is_array( $_val ) && ! empty( $_val['COLUMN_NAME'] ) ) {
				$_udf["{$_val['COLUMN_NAME']}"] = true;
			}
			if ( is_array( $_val ) && ! empty( $_val['INDEX_NAME'] ) ) {
				$_udf["{$_val['INDEX_NAME']}"] = true;
			}
		}

		// Set new unique DBM sequence column
		do {
			$this->udsc = '_dbm_udsc_' . strtoupper( $this->get_random_string( 4 ) );
		} while ( isset( $_udf["{$this->udsc}"] ) );

		// Parse column information array
		$dbsi = array();
		foreach ( $_dta as $_key => $_val ) {
			if (
				! is_array( $_val )
				|| ! isset( $_val['TABLE_NAME'], $_val['COLUMN_NAME'], $_val['COLUMN_TYPE'] )
				|| $this->is_dbm_filtered( $_val )
				) {
				continue;
			}
			if ( ! isset( $dbsi["{$_val['TABLE_NAME']}"] ) ) {
				$dbsi["{$_val['TABLE_NAME']}"] = array();
			}
			if ( ! is_null( $_pbt = $this->get_dbm_param_bind_type( $_val['COLUMN_TYPE'] ) ) ) {
				$dbsi["{$_val['TABLE_NAME']}"]["{$_val['COLUMN_NAME']}"] = $_pbt;
			}
		}

		// Insert unique DBM sequence column
		$this->dbsi = array_map(
			function( $_val ) {
				return array_merge( array( "{$this->udsc}" => 's' ), ( is_array( $_val ) ? $_val : array() ) );
				},
			array_filter( $dbsi )
			);

		// Do we have table/column info?
		return ( ! empty( $this->dbsi ) );
	}

	/**
	 * Database: Connect/re-connect/close/clear.
	 *
	 * Sets up PHP mysqli object for communication with database and
	 * manages re-connection when necessary. Also clears database
	 * connection and associated parameters on process completion.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Required. An array of arguments.
	 * @param int $_try			Optional. Maximum number of retries for connection failures.
	 * @return bool             TRUE if connection successful or FALSE.
	 */
	private function connect_to_db( $_arg = null, $_try = null ) {
		// Keep parameters for reconnection
		static $db_host, $db_user, $db_pass, $db_name, $db_port, $db_sock, $db_cset, $db_coll, $db_sslc, $db_tpfx, $db_rpfx;

		// Destroy stored parameters
		if ( '_dbm_reset' === $_arg ) {
			foreach( get_defined_vars() as $_key => $_val ) {
				${$_key} = null;
			}
			if (
				isset( $this->sqli )
				&& ( $this->sqli instanceof mysqli )
				) {
				@$this->sqli->close();
			}
			return $this->sqli = $this->srdb = $this->tpfx = $this->rpfx = null;
		}

		// Store parameters
		if (
			! isset( $db_host, $db_user, $db_pass, $db_name )
			&& isset( $_arg )
			&& is_array( $_arg )
			&& isset( $_arg['db_host'], $_arg['db_user'], $_arg['db_pass'], $_arg['db_name'] )
			) {
			// $db_host, $db_user, $db_pass, $db_name etc.
			extract( $_arg );
			// $table_prefix
			$db_tpfx = ( ( isset( $table_prefix ) && is_string( $table_prefix ) ) ? $table_prefix : null );
			$db_rpfx = ( ( isset( $table_prefix_replace ) && is_string( $table_prefix_replace ) ) ? $table_prefix_replace : null );
		}

		// Check for required connection parameters
		if ( ! isset( $db_host, $db_user, $db_pass, $db_name ) ) {
			return false;
		}

		// Set up connection
		if (
			isset( $this->sqli )
			|| ( $this->sqli instanceof mysqli )
			) {
			@$this->sqli->close();
		}
		$this->sqli = new mysqli();

		// Set up TLS/SSL for connection
		if (
			isset( $db_sslc )
			&& is_array( $db_sslc )
			) {
			$this->connect_to_db_ssl( $db_sslc );
		}

		// Really connect!
		$this->sqli->real_connect( $db_host, $db_user, $db_pass, $db_name, ( isset( $db_port ) ? $db_port : null ), ( isset( $db_sock ) ? $db_sock : null ) );

		// Retry failed connection
		if ( ( $this->sqli instanceof mysqli ) && ( 0 !== $this->sqli->connect_errno ) ) {
			$_try = ( is_int( $_try ) ? abs( $_try ) : 3 );
			do {
				sleep( 5 );
				$_try -= 1;
				$this->sqli->real_connect( $db_host, $db_user, $db_pass, $db_name, ( isset( $db_port ) ? $db_port : null ), ( isset( $db_sock ) ? $db_sock : null ) );
			} while ( ( 0 !== $this->sqli->connect_errno ) && ( $_try > 0 ) );
		}

		// Check for connection error
		if (
			( $this->sqli instanceof mysqli )
			&& ( 0 === $this->sqli->connect_errno )
			&& ( true === ( $cset = ( empty( $db_cset ) ? true : $this->connect_to_db_charset( $db_cset ) ) ) )
			&& ( true === ( $coll = ( empty( $db_coll ) ? true : $this->connect_to_db_collation( $db_coll ) ) ) )
			) {
			// Set database name
			if (
				! isset( $this->srdb )
				|| ( $this->srdb !== $db_name )
				) {
				$this->srdb = $db_name;
			}
			// Set table name prefix
			if (
				! isset( $this->tpfx )
				|| ( $this->tpfx !== $db_tpfx )
				) {
				$this->tpfx = $db_tpfx;
			}
			// Set table prefix replacement
			if (
				! isset( $this->rpfx )
				|| ( $this->rpfx !== $db_rpfx )
				) {
				$this->rpfx = $db_rpfx;
			}
		} else {
			trigger_error( $this->get_dbm_error_message( 11, compact( 'cset', 'coll' ) ), E_USER_WARNING );
			@$this->sqli->close();
		}

		// Announce connection status
		return (
			isset( $this->sqli, $this->srdb )
			&& ( $this->sqli instanceof mysqli )
			&& ( 0 === $this->sqli->connect_errno )
			&& ( 0 === $this->sqli->errno )
			);
	}

	/**
	 * Database: Establish secure connection.
	 *
	 * For connections to a remote database over TLS/SSL, mysqli allows for the
	 * server to verify the client certificate and vice versa.
	 *
	 * @since   0.5.0
	 * @access  private
	 *
	 * @param array $_arg		Required. An array of arguments.
	 * @return bool             TRUE on success or FALSE on failure.
	 */
	private function connect_to_db_ssl( $_arg = null ) {
		// Verify arguments
		if (
			! isset( $_arg, $this->sqli )
			|| ! is_array( $_arg )
			|| ! count( $_arg )
			|| ! ( $this->sqli instanceof mysqli )
			) {
			return false;
		}

		// mysqli::ssl_set() arguments in order
		$_ssl = array(
			'key'		=> null, //path/to/private/key/file
			'cert'		=> null, //path/to/public/key/certificate/file
			'ca'		=> null, //path/to/certificate/authority/file
			'capath'	=> null, //path/to/directory/containing/trusted/TLS/CA/certificates/in/PEM/format
			'cipher'	=> null, //permitted cipher list for TLS encryption
			);

		// Filter mysqli::ssl_set() arguments ensuring string or null
		$_ssl = array_intersect_key(
			array_merge(
				$_ssl,
				array_map(
					'trim',
					array_filter( $_arg, 'is_string' )
					)
				),
			$_ssl
			);

		// Validate mysqli::ssl_set() arguments
		$_ssl['cipher'] = ( strlen( $_ssl['cipher'] ) ? $_ssl['cipher'] : null );
		foreach ( $_ssl as $_key => &$_val ) {
			if ( is_null( $_val ) || ( 'cipher' === $_key ) ) {
				continue;
			}

			if (
				( ( 'capath' === $_val ) ? ! is_dir( $_val ) : ! is_file( $_val ) )
				|| ! is_readable( $_val )
				) {
				$_val = null;
			}
		}
		unset( $_val );
		if ( ! count( array_filter( $_ssl ) ) ) {
			return false;
		}

		// SSL: Set up verification options
		if ( ! empty( $_ssl['ca'] ) ) {
			$this->sqli->options( MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true );
		}

		// SSL: Set (always returns TRUE)
		return $this->sqli->ssl_set( $_ssl['key'], $_ssl['cert'], $_ssl['ca'], $_ssl['capath'], $_ssl['cipher'] );
	}

	/**
	 * Database: Get host for connection.
	 *
	 * @see https://core.trac.wordpress.org/browser/tags/4.9/src/wp-includes/wp-db.php#L1627
	 * This function is adapted from wpdb::parse_db_host() of WordPress PHP
	 * class wpdb in file ...wp-includes/wp-db.php released under the terms of
	 * the same GNU General Public License as this script and copyright of which
	 * remains with WordPress contributors.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array	$_arg		Required. An array of arguments.
	 * @return array            Database host, port and socket.
	 */
	private function connect_to_db_get_host( $_arg = null ) {
		if ( empty( $_arg ) || ! is_string( $_arg ) ) {
			$_arg = 'localhost';
		}

		$db_host = $db_port = $db_sock = null;
		$db_ipv6 = false;

		// Peel off the socket parameter from the right
		if ( false !== ( $_pos = strpos( $_arg, ':/' ) ) ) {
			$db_sock = substr( $_arg, $_pos + 1 );
			$db_host = substr( $_arg, 0, $_pos );
		} else {
			$db_host = $_arg;
		}

		// If we do not have an IPv6 address (at least two colons)...
		if ( substr_count( $db_host, ':' ) > 1 ) {
			$db_ipv6 = true;
			$_rgx = '#^(?:\[)?(?P<host>[0-9a-fA-F:]+)(?:\]:(?P<port>[\d]+))?#';
		} else {
			// ...we have an IPv4 address.
			$_rgx = '#^(?P<host>[^:/]*)(?::(?P<port>[\d]+))?#';
		}

		$_pma = array();
		if ( (bool) preg_match( $_rgx, $_arg, $_pma ) ) {
			/*
			 * ipv6 addresses need enclosure in square braces when using the
			 * `mysqlnd` library but not when using the `libmysqlclient`
			 * library
			 * ref. https://bugs.php.net/bug.php?id=67563
			 */
			$db_host = ( empty( $_pma['host'] ) ? null : ( ( ( true === $db_ipv6 ) && extension_loaded( 'mysqlnd' ) ) ? "[{$_pma['host']}]" : $_pma['host'] ) );
			$db_port = ( empty( $_pma['port'] ) ? null : $_pma['port'] );
		}

		return compact( 'db_host', 'db_port', 'db_sock' );
	}

	/**
	 * Database: Set connection character set.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Valid character set.
	 * @return mixed            TRUE on success or FALSE|error string on failure.
	 */
	private function connect_to_db_charset( $_arg = null ) {
		// Validate $_arg and set db connection character set
		if (
			( false === ( $db_cset = $this->connect_to_db_get_cc( $_arg ) ) )
			|| ! is_array( $_res = $this->get_query_result( "SHOW CHARACTER SET LIKE '{$db_cset}'", null, true ) )
			|| ! isset( $_res['Charset'] )
			|| ( $db_cset !== $_res['Charset'] )
			) {
			$_rtn = sprintf( 'Invalid database connection character set %1$s', $db_cset );
		} else {
			$_rtn = $this->sqli->set_charset( $db_cset );
		}

		return $_rtn;
	}

	/**
	 * Database: Set connection collation.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Valid collation.
	 * @return mixed            TRUE on success or FALSE|error string on failure.
	 */
	private function connect_to_db_collation( $_arg = null ) {
		// Validate $_arg and set db connection collation
		if (
			( false === ( $db_coll = $this->connect_to_db_get_cc( $_arg ) ) )
			|| ! is_array( $_res = $this->get_query_result( $_qry = "SHOW COLLATION LIKE '{$db_coll}'", null, true ) )
			|| ! isset( $_res['Collation'] )
			|| ( $db_coll !== $_res['Collation'] )
			) {
			$_rtn = sprintf( 'Invalid database collation %1$s', $db_coll );
		} elseif ( false === ( $_rtn = $this->get_query_result( sprintf( "SET NAMES '%s' COLLATE '%s'", $this->sqli->get_charset()->charset, $db_coll ) ) ) ) {
			$_rtn = $this->sqli->error;
		}

		return $_rtn;
	}

	/**
	 * Database: Sanitize character set/collation name for connection.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Valid character set or collation.
	 * @return mixed            Sanitized character set or collation string or FALSE on failure.
	 */
	private function connect_to_db_get_cc( $_arg = null ) {
		// Validate $_arg
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			|| ! strlen( $_rtn = preg_replace( '#\W#', '', $_arg ) )
			) {
			return false;
		} else {
			return $_rtn;
		}
	}

	/**
	 * Database: Filter tables and columns.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array $_arg		Required. Valid array with table|column name and column type.
	 * @return bool             TRUE if filtered out or FALSE.
	 */
	private function is_dbm_filtered( $_arg = null ) {
		// Return default if invalid $args
		if (
			! isset( $_arg )
			|| ! is_array( $_arg )
			|| ! isset( $this->cief )
			|| ! is_array( $this->cief )
			|| ! isset( $this->cief['exclude'], $this->cief['include'] )
			|| ! is_array( $this->cief['exclude'] )
			|| ! is_array( $this->cief['include'] )
			|| ! isset( $_arg['TABLE_NAME'], $_arg['COLUMN_NAME'] )
			|| ! is_string( $_arg['TABLE_NAME'] )
			|| ! is_string( $_arg['COLUMN_NAME'] )
			) {
			return false;
		}

		// Global filters: table prefix, blob|number columns, DBM residuals
		$_dbt = ( isset( $_arg['COLUMN_TYPE'] ) ? ( ( ( 'b' === ( $_dbt = $this->get_dbm_param_bind_type( $_arg['COLUMN_TYPE'] ) ) ) && ( true !== $this->blob ) ) ? null : $_dbt ) : null );
		if (
			( ! empty( $this->tpfx ) && is_string( $this->tpfx ) && ( 0 !== strpos( $_arg['TABLE_NAME'], $this->tpfx ) ) )
			|| ( ( 's' !== $_dbt ) && ( 'b' !== $_dbt ) )
			|| $this->is_dbm_residual( $_arg )
			) {
			return true;
		}

		// Filter out by specific exclusion
		if (
			! empty( $this->cief['exclude'] )
			&& isset( $this->cief['exclude']["{$_arg['TABLE_NAME']}"] )
			) {
			return ( is_array( $this->cief['exclude']["{$_arg['TABLE_NAME']}"] ) ? ( ! isset( $this->cief['exclude']["{$_arg['TABLE_NAME']}"]["{$_arg['COLUMN_NAME']}"] ) ) : true );
		}

		// Filter out by specific inclusion
		if ( ! empty( $this->cief['include'] ) ) {
			return ( ! isset( $this->cief['include']["{$_arg['TABLE_NAME']}"] ) ? true : ( is_array( $this->cief['include']["{$_arg['TABLE_NAME']}"] ) ? ( ! isset( $this->cief['include']["{$_arg['TABLE_NAME']}"]["{$_arg['COLUMN_NAME']}"] ) ) : false ) );
		}

		// Default: Do not filter
		return false;
	}

	/**
	 * Database: Get bind type per column.
	 *
	 * Bind type could be one of
	 * 'd' => '#dec(imal)?|double|fixed|float|numeric#i',
	 * 'b' => '#blob#i',
	 * 's' => '#int(eger)?.*?unsigned|bigint|bit|char|json|text#i',
	 * 'i' => '#(tiny|small|medium)int|bool#i'
	 * for mysqli::bind_param type argument.
	 *
	 * 'i' limits UNSIGNED INT[EGER] range from 0 to 4294967295 and the
	 * default SIGNED INT[EGER] range from -2147483648 to 2147483647.
	 * Using 's' for BIGINT and UNSIGNED INT[EGER] bind type overcomes
	 * limitations for larger values stored in the database.
	 *
	 * In some cases using 's' for BLOB may work better.
	 *
	 * 'T' and 'I' are used to exclude columns from string search and
	 * are switched to 's' for parameter binding to prepared statements.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Column type.
	 * @return mixed			Bind type character or NULL for invalid input.
	 */
	private function get_dbm_param_bind_type( $_arg = null ) {
		// Validate $_arg
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			) {
			return null;
		}

		// Set by data type
		if ( (bool) preg_match( '#date|time|year#i', $_arg ) ) {
			return 'T';
		} elseif ( (bool) preg_match( '#int(eger)?.*?unsigned|bigint#i', $_arg ) ) {
			return 'I';
		} elseif ( (bool) preg_match( '#dec(imal)?|double|fixed|float|numeric#i', $_arg ) ) {
			return 'd';
		} elseif ( (bool) preg_match( '#blob#i', $_arg ) ) {
			return 'b';
		} elseif ( (bool) preg_match( '#(tiny|small|medium)?int(eger)?|bool#i', $_arg ) ) {
			return 'i';
		} else {
			// Default to 's' for everything else
			return 's';
		}
	}

	/**
	 * Database: Check if table column is residual from previous run.
	 *
	 * DBM creates a unique sequencing column at the beginning of every
	 * table processed and drops that column after process completion.
	 * This is to check if a previous attempt to drop this column had
	 * failed.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array $_arg		Required. Valid array with table|column name and column comment.
	 * @return bool             TRUE if residual or FALSE.
	 */
	private function is_dbm_residual( $_arg = null ) {
		return (
			isset( $_arg )
			&& is_array( $_arg )
			&& isset( $_arg['TABLE_NAME'], $_arg['COLUMN_NAME'], $_arg['COLUMN_COMMENT'] )
			&& is_string( $_arg['COLUMN_NAME'] )
			&& is_string( $_arg['COLUMN_COMMENT'] )
			&& (bool) preg_match( '#^_dbm_udsc_[0-9A-F]{8}$#', $_arg['COLUMN_NAME'] )
			&& (bool) preg_match( '#^' . $_arg['COLUMN_NAME'] . '\stemporarily\sadded\sby\sDBM\..*deleted\.$#', $_arg['COLUMN_COMMENT'] )
			);
	}

	/**
	 * Database: Create unique DBM sequencing column.
	 *
	 * Creates a unique sequencing column at the beginning of table to
	 * be processed.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name.
	 * @return bool				TRUE if column created or FALSE on failure.
	 */
	private function set_dbm_sequence_column( $_arg = null ) {
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			|| ! is_array( $_sqw = $this->get_dbm_select_query_where( $_arg ) )
			|| ! isset( $_sqw['_mts'], $_sqw['_wcl'], $_sqw['_qrn'] )
			|| ( 3 !== extract( $_sqw ) )
			|| empty( $_mts )
			|| empty( $_wcl )
			|| empty( $_qrn )
			) {
			return false;
		}

		$_sql = array(
			'_col'	=> "ALTER TABLE `{$_arg}` ADD `{$this->udsc}` BIGINT(20) UNSIGNED DEFAULT NULL UNIQUE COMMENT '{$this->udsc} temporarily added by DBM. This column should be deleted.' FIRST",
			'_seq'	=> "UPDATE `{$_arg}` JOIN ( SELECT @{$this->udsc} := 0 ) dbm SET `{$this->udsc}` = @{$this->udsc} := @{$this->udsc} + 1 WHERE {$_wcl}",
			);

		$_qrg = array_merge(
			array( $_mts ),
			call_user_func_array( 'array_merge', array_pad( array(), $_qrn, $this->rrqs ) )
			);

		return (
			$this->get_query_result( $_sql['_col'] )
			&& $this->get_query_result( $_sql['_seq'], $_qrg )
			);
	}

	/**
	 * Database: Drop unique DBM sequencing column.
	 *
	 * Drops the unique sequencing column at the beginning of tables.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param mixed $_arg		Required. Table name string or array with table|column name and column comment.
	 * @return bool				TRUE if column dropped or FALSE on failure.
	 */
	private function del_dbm_sequence_column( $_arg = null ) {
		if (
			isset( $_arg )
			&& is_string( $_arg )
			&& isset( $this->dbsi["{$_arg}"] )
			&& isset( $this->udsc )
			) {
			$_sql = "ALTER TABLE `{$_arg}` DROP COLUMN `{$this->udsc}`";
		} elseif ( $this->is_dbm_residual( $_arg ) ) {
			$_sql = "ALTER TABLE `{$_arg['TABLE_NAME']}` DROP COLUMN `{$_arg['COLUMN_NAME']}`";
		}

		return ( isset( $_sql ) ? $this->get_query_result( $_sql ) : false );
	}

	/**
	 * String replacement: Check for table records.
	 *
	 * Checks if table has records to process for string replacement.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name string.
	 * @return bool				TRUE if table has records to process or FALSE.
	 */
	private function has_dbm_record_count( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			|| ! is_array( $_sqw = $this->get_dbm_select_query_where( $_arg ) )
			|| ! isset( $_sqw['_mts'], $_sqw['_wcl'], $_sqw['_qrn'] )
			|| ( 3 !== extract( $_sqw ) )
			|| empty( $_mts )
			|| empty( $_wcl )
			|| empty( $_qrn )
			) {
			return false;
		}

		$_sql = "SELECT * FROM `{$_arg}` WHERE {$_wcl} LIMIT ?, ?";
		$_qrg = array_merge(
			array( "{$_mts}ii" ),
			call_user_func_array( 'array_merge', array_pad( array(), $_qrn, $this->rrqs ) ),
			array( 0, 1 )
			);

		return (
			is_array( $_hrc = $this->get_query_result( $_sql, $_qrg, true ) )
			&& ( count( $_hrc ) > 0 )
			);
	}

	/**
	 * String replacement: Process table records.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name.
	 * @return bool				TRUE if table processed or FALSE on failure.
	 */
	private function process_dbm_records( $_arg = null ) {
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			|| ! ( $_trc = $this->get_dbm_record_count( $_arg ) )
			|| ! is_array( $_rrq = $this->get_dbm_select_query( $_arg ) )
			|| ! isset( $_rrq['_mts'], $_rrq['_sql'], $_rrq['_qrn'] )
			|| ( false === ( $_qry = $this->get_prepared_query( $_rrq['_sql'] ) ) )
			|| ! ( $_qry instanceof mysqli_stmt )
			) {
			return false;
		}

		// DBM logger: Update
		if ( ! isset( $this->dlog['_dta']['dbsi']["{$_arg}"] ) ) {
			$this->dlog['_dta']['dbsi']["{$_arg}"] = array( 'rows_found' => $_trc, 'rows_updated' => 0 );
		}

		// $this->rrqs repeated array
		$_rra = call_user_func_array( 'array_merge', array_pad( array(), $_rrq['_qrn'], $this->rrqs ) );

		// Process records in iteration chunks of $_lim
		$_orn = $_itr = 0;
		$_lim = ( ( isset( $this->rlim ) && is_int( $this->rlim ) && ( $this->rlim > 0 ) ) ? $this->rlim : $this::DBM_MAXROWS );
		do {
			// Increment iteration number and LIMIT offset
			$_itr += 1;
			$_orn = ( ( $_lim * $_itr ) - $_lim );

			// Retrieve records and update strings
			$this->update_dbm_records( $_arg, $_qry, array_merge( array( "ii{$_rrq['_mts']}", $_orn, $_lim ), $_rra ), $_rrq['_sql'], $_trc );

			// Reset mysqli_stmt for next chunk
			$_qry->reset();
		} while ( ( $_orn + $_lim ) < $_trc );

		// Close mysqli_stmt
		return $_qry->close();
	}

	/**
	 * String replacement: Update table records.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_tbl		Required. Table name string.
	 * @param object $_qry		Required. Instance of mysqli_stmt.
	 * @param array $_arg		Required. Array of parameters to bind to query.
	 * @param string $_sql		Required. SQL statement.
	 * @param int $_trc			Optional. Total record count for processing in table.
	 * @return bool				TRUE if table processed or FALSE on failure.
	 */
	private function update_dbm_records( $_tbl = null, $_qry = null, $_arg = null, $_sql = null, $_trc = null ) {
		if (
			! isset( $_tbl, $_qry, $_arg, $_sql )
			|| ! is_string( $_tbl )
			|| ! isset( $this->dbsi["{$_tbl}"] )
			|| ! ( $_qry instanceof mysqli_stmt )
			|| ( false === $this->bind_query_params( $_qry, $_arg, $_sql ) )
			|| ( false === $_qry->execute() )
			|| ( false === ( $_qrs = $this->get_query_result_set( $_qry ) ) )
			|| ! is_array( $_qrs )
			) {
			return false;
		} elseif (
			is_array( $_ruq = $this->get_dbm_update_query( $_tbl ) )
			&& isset( $_ruq['_mts'], $_ruq['_sql'] )
			&& ( false !== ( $_qry = $this->get_prepared_query( $_ruq['_sql'] ) ) )
			&& ( $_qry instanceof mysqli_stmt )
			) {
			foreach ( $_qrs as $_key => $_val ) {
				$_qry->reset();
				$this->set_dbm_string_update( $_tbl, $_qry, array( '_mts' => $_ruq['_mts'], '_dta' => $_val ), $_ruq['_sql'] );
			}
			return $_qry->close();
		}
	}

	/**
	 * String replacement: Update table record with replacement strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_tbl		Required. Table name string.
	 * @param object $_qry		Required. Instance of mysqli_stmt.
	 * @param array $_arg		Required. Array of parameters to bind to query.
	 * @param string $_sql		Required. SQL statement.
	 * @return bool				TRUE if record processed or FALSE on failure.
	 */
	private function set_dbm_string_update( $_tbl = null, $_qry = null, $_arg = null, $_sql = null ) {
		// Validate $args
		if (
			! isset( $_tbl, $_qry, $_arg, $_sql )
			|| ! is_string( $_tbl )
			|| ! isset( $this->dbsi["{$_tbl}"] )
			|| ! ( $_qry instanceof mysqli_stmt )
			|| ! is_array( $_arg )
			|| ! isset( $_arg['_mts'], $_arg['_dta'] )
			|| ! is_string( $_arg['_mts'] )
			|| ! is_array( $_arg['_dta'] )
			) {
			return false;
		}

		// Perform string replacement
		$_upd = array( $_arg['_mts'] );
		$_idx = $_rrc = $_irc = null;
		foreach ( $_arg['_dta'] as $_key => $_val ) {
			if ( $this->udsc === $_key ) {
				$_idx = $_val;
				continue;
			}
			// Reset normalized serialized string/object collections
			$this->nssc = $this->nsoc = array();
			$_rpl = $this->get_dbm_string_replacement( $_val, $_irc );
			$_rrc += $_irc;
			$_upd[] = ( ( ( false === $_rpl ) || empty( $_irc ) ) ? $_val : $_rpl );
			// Flush normalized serialized string/object collections
			$this->nssc = $this->nsoc = null;
		}
		$_upd[] = $_idx;

		// Perform update to DB
		if (
			! empty( $_rrc )
			&& ( false !== $this->bind_query_params( $_qry, $_upd, $_sql ) )
			&& ( false !== $_qry->execute() )
			) {
			// DBM logger: Update
			$this->dlog['_dta']['dbsi']["{$_tbl}"]['rows_updated'] += 1;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * String replacement: Get replacement string.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String to be replaced.
	 * @param int $_rrc			Optional. Reference to be updated with replacement count.
	 * @return bool				String to replace input $_arg or FALSE on failure.
	 */
	private function get_dbm_string_replacement( $_arg = null, &$_rrc = null ) {
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			|| ( false === $this->set_dbm_string_intermediates( $_arg ) )
			) {
			return false;
		}

		// Replace strings
		if ( $this->has_serialized_strings( $_arg ) ) {
			return $this->replace_serialized_strings( $_arg, $_rrc );
		} else {
			return $this->replace_strings( $_arg, $_rrc );
		}
	}

	/**
	 * String replacement: Set search and replace string collections.
	 *
	 * Splits database string replacement collection (@see $dbsr) to
	 * search (@see $dssc) and replace (@see $drsc) arrays for use by
	 * str_replace().
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @return bool				TRUE on success or FALSE on failure.
	 */
	private function set_dbm_search_replace_arrays() {
		return (
			isset( $this->dbsr )
			&& is_array( $this->dbsr )
			&& count( $this->dbsr )
			&& count( $this->dssc = array_keys( $this->dbsr ) )		// Set DBM search string collections
			&& count( $this->drsc = array_values( $this->dbsr ) )	// Set DBM replacement string collections
			);
	}

	/**
	 * String replacement: Set intermediate replacement string collection.
	 *
	 * The migrator uses search and replace arrays as arguments for
	 * str_replace(). String replacement cascade is prevented by use of
	 * intermediate strings to first replace search strings and then be
	 * replaced with the final replacement strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param mixed $_arg		Optional. String or array of strings requiring substring update.
	 * @return bool				TRUE on success or FALSE on failure.
	 */
	private function set_dbm_string_intermediates( $_arg = null ) {
		// Set $irsc
		if ( ! isset( $this->irsc ) ) {
			$this->irsc = array();
		}

		// Ensure count of $irsc
		if (
			! isset( $this->dbsr )
			|| ! is_array( $this->dbsr )
			|| ! ( $j = count( $this->dbsr ) )
			) {
			return false;
		}

		// Set reference string
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			) {
			$_arg = ( is_array( $_arg ) ? serialize( $_arg ) : '' );
		}

		// Reset $irsc
		$this->irsc = array_values(
			array_filter(
				$this->irsc,
				function( $_val ) use ( $_arg ) {
					return ( false === strpos( $_arg, $_val ) );
					}
				)
			);

		// Pad $irsc
		$k = $j - count( $this->irsc );
		if ( $k > 0 ) {
			for ( $i = 0; $i < $k; $i += 1 ) {
				do {
					$_uid = vsprintf( '##' . "\x1A" . '__{DBMIRS%5$s-%1$s-%2$s-%3$s-%4$s}__' . "\x1A" . '##', str_split( strtoupper( $this->get_random_string( 17 ) ), 8 ) );
				} while ( in_array( $_uid, $this->irsc, true ) || ( false !== strpos( $_arg, $_uid ) ) );
				$this->irsc[] = $_uid;
			}
		} elseif ( $k < 0 ) {
			$this->irsc = array_slice( $this->irsc, 0, $j, false );
		}

		return true;
	}

	/**
	 * String replacement: Replace strings.
	 *
	 * Performs str_replace() on input with search and replace strings
	 * from @see $dbsr.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param mixed $_arg		Required. String or array of strings to be replaced.
	 * @param int $_rrc			Optional. Reference to be updated with replacement count.
	 * @return mixed			String or array of strings to replace input $_arg or FALSE on failure.
	 */
	private function replace_strings( $_arg = null, &$_rrc = null ) {
		if (
			! isset( $_arg, $this->dssc, $this->drsc, $this->irsc )
			|| ( ! is_string( $_arg ) && ! is_array( $_arg ) )
			|| ! is_array( $this->dssc )
			|| ! is_array( $this->drsc )
			|| ! is_array( $this->irsc )
			) {
			return false;
		}

		if ( is_string( $_arg ) ) {
			return str_replace( $this->irsc, $this->drsc, str_replace( $this->dssc, $this->irsc, $_arg ), $_rrc );
		} elseif ( is_array( $_arg ) ) {
			return array_map(
				function( $_val ) use ( &$_rrc ) {
					$_irc = null; // intermediate replacement count
					$_rtn = $this->replace_strings( $_val, $_irc );
					$_rrc += $_irc;
					return $_rtn;
					},
				$_arg
				);
		} else {
			return $_arg;
		}
	}

	/**
	 * String replacement: Replace serialized strings.
	 *
	 * Performs str_replace() on input with search and replace strings
	 * from @see $dbsr.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String to be replaced.
	 * @param int $_rrc			Optional. Reference to be updated with replacement count.
	 * @return mixed			String to replace input $_arg or FALSE on failure.
	 */
	private function replace_serialized_strings( $_arg = null, &$_rrc = null ) {
		if (
			! isset( $_arg, $this->dssc, $this->drsc, $this->irsc )
			|| ! is_string( $_arg )
			|| ! is_array( $this->dssc )
			|| ! is_array( $this->drsc )
			|| ! is_array( $this->irsc )
			) {
			return false;
		}

		// Normalize serialized strings
		$_str = $this->normalize_serialized_strings( $_arg, $_rrc );

		// Normalize serialized objects
		if ( $this->has_serialized_objects( $_str ) ) {
			$_str = $this->normalize_serialized_objects( $_str );
		}

		// Normalize nested serializations
		if ( $this->has_nested_serializations( $_str ) ) {
			$_str = $this->normalize_nested_serializations( $_str );
		}

		// Restore strings from normalization placeholders
		return $this->restore_normalized_string( $_str );
	}

	/**
	 * String replacement: Normalize serialized strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String for normalization.
	 * @param int $_rrc			Optional. Reference to be updated with replacement count.
	 * @return string			Normalized serialized string or empty string on failure.
	 */
	private function normalize_serialized_strings( $_arg = null, &$_rrc ) {
		// Serialization regex
		static $_rgx;
		if ( ! isset( $_rgx ) ) {
			//	's'	=> '#s:\d+:\".*?\";#s',
			$_rgx = array(
				'a'	=> '#(a:\d+:)\{#s',
				'b'	=> '#(b:[01];)#s',
				'd'	=> '#(d:[\d\.E\-]+;)#s',
				'i'	=> '#(i:\d+;)#s',
				'O'	=> '#(O:\d+:.*?:\d+:)\{#s',
				);
		}

		// Validate $_arg
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			) {
			return '';
		}

		// Serialized string and non-string (lookup) arrays
		$_ssa = $_nsa = array();

		// Strip/substitute non-strings
		foreach ( $_rgx as $_key => $_val ) {
			while ( (bool) preg_match( $_val, $_arg, $_pma, PREG_OFFSET_CAPTURE ) ) {
				$_idx = $this->get_dbm_normalization_id( $_arg, $_nsa );
				if ( $_len = strlen( $_pma[1][0] ) ) {
					$_nsa["{$_idx}"] = $_pma[1][0];
					$_arg = substr_replace( $_arg, "X:{$_idx};", $_pma[1][1], $_len );
				}
			}
		}

		// Mark nested serializations
		while ( (bool) preg_match( '#(s):\d+:"(X:_dbm_[0-9a-f]{40};|(s|M):\d+:"|N;).*?";#s', $_arg, $_pma, PREG_OFFSET_CAPTURE ) ) {
			$_arg = substr_replace( $_arg, 'M', $_pma[1][1], 1 );
		}

		// Normalize serialized strings
		while ( (bool) preg_match( '#(s:(\d+):"(?!X:_dbm_[0-9a-f]{40};|s:\d+:".*?";)(.*?)(?=";(N;|}*?(X:_dbm_[0-9a-f]{40};|(s|M):\d+:"|\Z)|\Z)))#s', $_arg, $_pma, PREG_OFFSET_CAPTURE ) ) {
			$_idx = $this->get_dbm_normalization_id( $_arg, array_merge( $_nsa, $_ssa ) );

			/*
			 * $_pma[3][0] is the longest possible string followed by '";'
			 * Shorten it right to left for each occurence of "; for closest
			 * match to $_pma[2][0]
			 */
			$_osl = strlen( $_pma[2][0] ); // original string length
			$_pma[2][0] = (int) $_pma[2][0];
			$_osv = $_pma[3][0] = $this->restore_xfragments( $_pma[3][0], $_nsa );
			while ( ( strlen( $_osv ) > $_pma[2][0] ) && ( false !== ( $_pos = strripos( $_osv, '";' ) ) ) ) {
				$_osv = substr( $_osv, 0, $_pos );
				if ( strlen( $_osv ) <= $_pma[2][0] ) {
					break;
				}
			}
			$_osl += ( strlen( $_osv ) + 6 ); // s::""; = 6 characters

			// Store original string value
			$_ssa["{$_idx}"] = $_osv;

			// Normalize original string
			$_arg = substr_replace( $_arg, "s:45:{$_idx};", $_pma[1][1], $_osl );
		}

		// Perform string replacement on $_ssa
		$this->nssc = $this->replace_strings( $_ssa, $_rrc );

		return $this->restore_xfragments( $_arg, $_nsa );
	}

	/**
	 * String replacement: Restore normalization X-fragments.
	 *
	 * Restores X:_dbm_[0-9a-f]{40}; from reference array.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String for X-fragment restoration.
	 * @param array $_ref		Required. X-fragment lookup array.
	 * @return string			Normalized serialized string or input $_arg on failure.
	 */
	private function restore_xfragments( $_arg = null, $_ref = null ) {
		// X-fragment regex
		static $_rgx;
		if ( ! isset( $_rgx ) ) {
			$_rgx = '#X:(_dbm_[0-9a-f]{40});#s';
		}

		// Validate $_arg and restore X-fragments
		if (
			empty( $_arg )
			|| empty( $_ref )
			|| ! is_string( $_arg )
			|| ! is_array( $_ref )
			|| ! (bool) preg_match( $_rgx, $_arg )
			) {
			return $_arg;
		} else {
			return preg_replace_callback(
				$_rgx,
				function( $_prc ) use ( $_ref ) {
					return ( isset( $_ref["{$_prc[1]}"] ) ? $_ref["{$_prc[1]}"] : $_prc[0] );
					},
				$_arg
				);
		}
	}

	/**
	 * String replacement: Normalize serialized objects.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String for normalization.
	 * @return string			Normalized serialized string or empty string on failure.
	 */
	private function normalize_serialized_objects( $_arg = null ) {
		// Serialized object/array element string prefix regex
		static $_rgx;
		if ( ! isset( $_rgx ) ) {
			$_rgx = array(
				'a'	=> '#((a):\d+:){#s',
				'O'	=> '#((O):\d+:"(.*?)":\d+:){#s',
				);
		}

		// Validate $_arg
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			) {
			return '';
		}

		$_esp = array();
		foreach ( $_rgx as $_key => $_val ) {
			while ( (bool) preg_match( $_val, $_arg, $_pma, PREG_OFFSET_CAPTURE ) ) {
				$_idx = $this->get_dbm_normalization_id( $_arg, $_esp );
				if ( $_len = strlen( $_pma[1][0] ) ) {
					$_esp["{$_idx}"] = array(
						'_pfx'	=> $_pma[1][0],
						'_typ'	=> $_pma[2][0],
						'_var'	=> ( ( 'a' === $_pma[2][0] ) ? '' : $_pma[3][0] ),
						);
					$_arg = substr_replace( $_arg, $_idx, $_pma[1][1], $_len );
				}
			}
		}

		/*
		Object elements: i|s => b|d|i|s|N pairs
		regex: ((i:\d+;)|(s:\d+:.*?;))((b:[01];)|(d:[\d\.E\-]+;)|(i:\d+;)|(s:\d+:.*?;)|(N;))
		$_pmc[1][0] => key/value pairs
		$_pmc[2][0] => integer keys
		$_pmc[3][0] => string keys
		$_pmc[4][0] => object values ([5|6|7|8|9 are respectively bdisN)
		*/
		// Normalize serialized objects
		while ( (bool) preg_match( '#((_dbm_[0-9a-f]{40})\{(?<=_dbm_[0-9a-f]{40}\{)([^\{\}]*)(?=\})\})#s', $_arg, $_pma, PREG_OFFSET_CAPTURE ) ) {
			$_idx = $this->get_dbm_normalization_id( $_arg, $this->nsoc );

			// Mark out nested (multi) serialized elements M:\d+:"...";
			$_nms = $this->normalize_nested_serializations( $_pma[3][0] );

			// Object elements: i|s => b|d|i|s|N pairs (get element count and object string)
			$_oec = (int) preg_match_all( '#((i:\d+;)|(s:\d+:.*?;))((b:[01];)|(d:[\d\.E\-]+;)|(i:\d+;)|(s:\d+:.*?;)|(N;))#s', $_nms, $_pmc, PREG_PATTERN_ORDER );
			$_oes = '';

			if (
				isset( $_esp["{$_pma[2][0]}"] )
				&& ( 'O' === $_esp["{$_pma[2][0]}"]['_typ'] )
				) {
				$_ovn = $this->get_dbm_property_name( $_esp["{$_pma[2][0]}"]['_var'] );
				$_oec = 0;
				foreach ( $_pmc[3] as $_key => $_val ) {
					if (
						empty( $_pmc[4][$_key] )
						|| ! strlen( $_ref = substr( $_val, 5, -1 ) )
						|| ! isset( $this->nssc["{$_ref}"] )
						) {
						continue;
					}
					$this->nssc["{$_ref}"] = $this->get_dbm_property_name( $this->nssc["{$_ref}"] );
					$_oec += 1;
					$_oes .= "{$_val}{$_pmc[4][$_key]}";
				}
				$this->nsoc["{$_idx}"] = sprintf( 'O:%d:"%s":%d:{%s}', strlen( $_ovn ), $_ovn, $_oec, $_oes );
			} elseif ( isset( $_esp["{$_pma[2][0]}"] ) && ( 'a' === $_esp["{$_pma[2][0]}"]['_typ'] ) ) {
				$this->nsoc["{$_idx}"] = sprintf( 'a:%d:{%s}', $_oec, implode( '', $_pmc[0] ) );
			} else {
				$this->nsoc["{$_idx}"] = 'a:0:{}';
			}

			// Update string
			$_arg = substr_replace( $_arg, "s:45:{$_idx};", $_pma[0][1], strlen( $_pma[0][0] ) );

		} // while ( (bool) preg_match( '#((_dbm_[0-9a-f]{40})\{(?<=_dbm_[0-9a-f]{40}\{)([^\{\}]*)(?=\})\})#s',...

		return $_arg;
	}

	/**
	 * String replacement: Normalize nested serialized strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String for normalization.
	 * @return string			Normalized serialized string or empty string on failure.
	 */
	private function normalize_nested_serializations( $_arg = null ) {
		// Validate $_arg
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			) {
			return '';
		}

		// Update normalized multi-serialized string into object collection/lookup array
		while ( (bool) preg_match( '#(M:\d+:"((?:(?!(M:\d+:")|;(";)).)*;)";)#s', $_arg, $_pma, PREG_OFFSET_CAPTURE ) ) {
			$_idx = $this->get_dbm_normalization_id( $_arg, $this->nsoc );

			// Store to lookup
			$_rpl = $this->restore_normalized_string( $_pma[2][0] );
			$this->nsoc["{$_idx}"] = sprintf( 's:%1$d:"%2$s";', strlen( $_rpl ), $_rpl );

			// Replace serialized object with string
			$_arg = substr_replace( $_arg, "s:45:{$_idx};", $_pma[1][1], strlen( $_pma[1][0] ) );
		}

		return $_arg;
	}

	/**
	 * String replacement: Restore normalized string to source value.
	 *
	 * De-normalizes string by cascaded replacement of normalization
	 * tokens with replacement strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Normalized string.
	 * @return string			Restored string or input $_arg on failure.
	 */
	private function restore_normalized_string( $_arg = null ) {
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			) {
			return $_arg;
		}

		// preg_match() returns false if $_pos > strlen( $_arg )
		$_pos = 0;
		while ( (bool) preg_match( '#s:45:(_dbm_[0-9a-f]{40});#', $_arg, $_pmr, PREG_OFFSET_CAPTURE, $_pos ) ) {
			// length of s:45:(_dbm_[0-9a-f]{40}); = 51
			if ( isset( $this->nssc["{$_pmr[1][0]}"] ) ) {
				$_arg = substr_replace( $_arg, sprintf( 's:%1$d:"%2$s";', strlen( $this->nssc["{$_pmr[1][0]}"] ), $this->nssc["{$_pmr[1][0]}"] ), $_pmr[0][1], 51 );
			} elseif ( isset( $this->nsoc["{$_pmr[1][0]}"] ) ) {
				$_arg = substr_replace( $_arg, $this->nsoc["{$_pmr[1][0]}"], $_pmr[0][1], 51 );
			} else { // skip this placeholder
				$_pos = $_pmr[0][1] + 51;
			}
		}

		return $_arg;
	}

	/**
	 * String replacement: Get normalization id.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. String for normalization.
	 * @param array $_ref		Optional. Reference array to check id against.
	 * @return string			Normalization id string.
	 */
	private function get_dbm_normalization_id( $_arg = null, $_ref = null ) {
		$_arg = ( ( ! empty( $_arg ) && is_string( $_arg ) ) ? $_arg : '' );
		$_ref = ( ( ! empty( $_ref ) && is_array( $_ref ) ) ? $_ref : array() );

		do {
			$_nid = '_dbm_' . $this->get_random_string( 20 );
		} while (
			isset( $this->nssc["{$_nid}"] )
			|| isset( $this->nsoc["{$_nid}"] )
			|| isset( $_ref["{$_nid}"] )
			|| ( false !== strpos( $_arg, $_nid ) )
			);

		return $_nid;
	}

	/**
	 * String replacement: Reset NULL in serialized properties.
	 *
	 * Protected and private serialized object properties are prefixed
	 * with null enclosed * character and class name respectively and
	 * the null character \x00 needs to be used in place of \0 strings
	 * to ensure correct string length calculation on serialization.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Object property name.
	 */
	private function get_dbm_property_name( $_arg = null ) {
		// Validate $_arg
		if (
			! isset( $_arg )
			|| ! is_string( $_arg )
			) {
			return $_arg;
		}

		return preg_replace( '#(\\x00|\\0)#s', "\x00", $_arg );
	}

	/**
	 * SQL: Get record retrieval statement.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name.
	 * @return array			Type string, SQL string, query string repeat number.
	 */
	private function get_dbm_select_query( $_arg = null ) {
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			|| ! is_array( $_sqw = $this->get_dbm_select_query_where( $_arg ) )
			|| ! isset( $_sqw['_mts'], $_sqw['_wcl'], $_sqw['_qrn'] )
			|| ( 3 !== extract( $_sqw ) )
			|| empty( $_mts )
			|| empty( $_wcl )
			|| empty( $_qrn )
			) {
			return false;
		}

		// SELECT columns
		$_sel = implode(
			', ',
			array_map(
				function( $_val ) {
					return "`{$_val}`";
					},
				array_keys( $this->dbsi["{$_arg}"] )
				)
			);

		// Query
		$_sql = "SELECT {$_sel} FROM ( SELECT {$_sel} FROM `{$_arg}` WHERE `{$this->udsc}` IS NOT NULL ORDER BY `{$this->udsc}` ASC LIMIT ?, ? ) AS `dbm` WHERE {$_wcl}";

		return compact( '_mts', '_sql', '_qrn' );
	}

	/**
	 * SQL: Get WHERE clause for record retrieval statement.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name.
	 * @return array			Type string, WHERE clause string, query string repeat number.
	 */
	private function get_dbm_select_query_where( $_arg = null ) {
		if (
			! isset( $_arg, $this->dbsi, $this->udsc, $this->rrqs, $this->rrqk )
			|| ! is_string( $_arg )
			|| ! is_array( $this->dbsi )
			|| ! is_string( $this->udsc )
			|| ! is_array( $this->rrqs )
			|| ! is_int( $this->rrqk )
			|| ! ( $this->rrqk > 0 )
			|| ! isset( $this->dbsi["{$_arg}"] )
			|| ! is_array( $this->dbsi["{$_arg}"] )
			|| ! ( count( $this->dbsi["{$_arg}"] ) > 1 )
			|| ! isset( $this->dbsi["{$_arg}"]["{$this->udsc}"] )
			) {
			return false;
		}

		// WHERE clause array
		$_wca = array();

		// mysqli::bind_param() type string
		$_mts = '';

		// Sequence numbers only for columns with $rrqs
		foreach ( $this->dbsi["{$_arg}"] as $_key => $_val ) {
			if ( $this->udsc === $_key ) {
				continue;
			}
			$_wca[] = implode( ' OR ', array_pad( array(), $this->rrqk, "`{$_key}` LIKE ?" ) );
			$_mts .= str_repeat( $_val, $this->rrqk );
		}

		// WHERE clause
		$_wcl = implode( ' OR ', $_wca );
		$_qrn = count( $_wca );

		return compact( '_mts', '_wcl', '_qrn' );
	}

	/**
	 * SQL: Get record update statement.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name.
	 * @return array			Type string, SQL string.
	 */
	private function get_dbm_update_query( $_arg = null ) {
		if (
			! isset( $_arg, $this->dbsi, $this->udsc )
			|| ! is_string( $_arg )
			|| ! is_array( $this->dbsi )
			|| ! is_string( $this->udsc )
			|| ! isset( $this->dbsi["{$_arg}"] )
			|| ! is_array( $this->dbsi["{$_arg}"] )
			|| ! ( count( $this->dbsi["{$_arg}"] ) > 1 )
			|| ! isset( $this->dbsi["{$_arg}"]["{$this->udsc}"] )
			) {
			return false;
		}

		// SET `column` = ? array
		$_sca = array();

		// mysqli::bind_param() type string
		$_mts = '';

		// Collect columns for update
		foreach ( $this->dbsi["{$_arg}"] as $_key => $_val ) {
			if ( $this->udsc === $_key ) {
				continue;
			}
			$_sca[] = "`{$_key}` = ?";
			$_mts .= $_val;
		}

		$_mts .= 's'; // for $this->udsc
		$_set = implode( ', ', $_sca );
		$_sql = "UPDATE `{$_arg}` SET {$_set} WHERE `{$this->udsc}` = ?";

		return compact( '_mts', '_sql' );
	}

	/**
	 * Query database: Get count of records to update.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg		Required. Table name.
	 * @return int				Number of records.
	 */
	private function get_dbm_record_count( $_arg = null ) {
		if (
			! isset( $_arg, $this->udsc )
			|| ! is_string( $_arg )
			|| ! is_string( $this->udsc )
			|| ! strlen( $_arg )
			|| ! strlen( $this->udsc )
			|| ! is_array( $_grc = $this->get_query_result( "SELECT COUNT(*) AS `{$_arg}` FROM `{$_arg}` WHERE `{$this->udsc}` IS NOT NULL", null, true ) )
			|| ! isset( $_grc["{$_arg}"] )
			|| ! is_numeric( $_grc["{$_arg}"] )
			) {
			return false;
		} else {
			return abs( intval( $_grc["{$_arg}"] ) );
		}
	}

	/**
	 * Query database: Get query result.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_sql		Required. SQL statement.
	 * @param array $_arg		Optional. Array of parameters to bind to prepared query.
	 * @param bool $_one		Optional. TRUE for retrieving first result only or FALSE otherwise.
	 * @return mixed			Array of retrieved records or boolean TRUE|FALSE for query success|failure.
	 */
	private function get_query_result( $_sql = null, $_arg = null, $_one = false ) {
		// Check if query possible
		if (
			! isset( $this->sqli )
			|| ! ( $this->sqli instanceof mysqli )
			|| empty( $_sql )
			|| ! is_string( $_sql )
			|| ! strlen( trim( $_sql ) )
			|| ( ! $this->sqli->ping() && ! $this->connect_to_db() )
			) {
			return false;
		}

		// Get results of 'prepared' or 'direct' query
		if (
			! isset( $_arg )
			|| ! is_array( $_arg )
			|| ! count( $_arg )
			|| ! is_string( $_qps = reset( $_arg ) )
			|| ! (bool) preg_match( '#^[dbsiTI]+$#', $_qps )
			) {
			return $this->get_direct_query_result( $_sql, $_one );
		} else {
			return $this->get_prepared_query_result( $_sql, $_arg, $_one );
		}
	}

	/**
	 * Query database: Get result by direct query.
	 *
	 * @see https://www.php.net/manual/en/mysqli.query.php
	 * mysqli::query() returns FALSE on failure.
	 * For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries
	 * mysqli::query() returns a mysqli_result object.
	 * For other successful queries mysqli::query() returns TRUE.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_sql		Required. SQL statement to execute.
	 * @param bool $_one		Optional. TRUE for retrieving first result only or FALSE otherwise.
	 * @return mixed			Array of retrieved records or boolean TRUE|FALSE for query success|failure.
	 */
	private function get_direct_query_result( $_sql = null, $_one = false ) {
		// Check query length
		if (
			empty( $_sql )
			|| ! is_string( $_sql )
			) {
			return false;
		}

		if ( false === ( $_res = $this->sqli->query( $_sql, MYSQLI_STORE_RESULT ) ) ) {
			trigger_error( $this->get_dbm_error_message( 23, null, $_sql ), E_USER_WARNING );
		}

		return $this->collect_query_result( $_res, $_one );
	}

	/**
	 * Query database: Get result by prepared query.
	 *
	 * @see https://www.php.net/manual/en/mysqli.query.php
	 * mysqli::query() returns FALSE on failure.
	 * For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli::query()
	 * returns a mysqli_result object.
	 * For other successful queries mysqli::query() returns TRUE.
	 * mysqli_stmt::close() closes the prepared statement and also deallocates
	 * the statement handle. If the current statement has pending or unread
	 * results, this function cancels them so that the next query can be
	 * executed.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_sql		Required. SQL statement to be prepared.
	 * @param array $_arg		Optional. Array of parameters to bind to prepared query.
	 * @param bool $_one		Optional. TRUE for retrieving first result only or FALSE otherwise.
	 * @return mixed			Array of retrieved records or boolean TRUE|FALSE for query success|failure.
	 */
	private function get_prepared_query_result( $_sql = null, $_arg = null, $_one = false ) {
		// Validate args
		if (
			! is_string( $_sql )
			|| ! (bool) preg_match( '#\?#s', $_sql )
			|| ! is_array( $_arg )
			|| ! count( $_arg )
			|| ( false === ( $_qry = $this->get_prepared_query( $_sql ) ) )
			|| ! ( $_qry instanceof mysqli_stmt )
			|| ( false === $this->bind_query_params( $_qry, $_arg, $_sql ) )
			|| ( false === $this->exec_prepared_query( $_qry, $_sql ) )
			|| ( false === ( $_rtn = $this->get_query_result_set( $_qry, $_one ) ) )
			) {
			$_rtn = false;
		}

		// Close query
		if ( isset( $_qry ) && ( $_qry instanceof mysqli_stmt ) ) {
			$_qry->close();
		}

		return $_rtn;
	}

	/**
	 * Query database: Prepare query.
	 *
	 * @see https://www.php.net/manual/en/book.mysqli.php
	 * mysqli::prepare() uses a DML statement to create and return a
	 * mysqli_stmt object or FALSE on error.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_sql		Required. SQL statement to be prepared.
	 * @return mixed			mysqli_stmt object or FALSE on error.
	 */
	private function get_prepared_query( $_sql = null ) {
		// Validate $_sql
		if (
			empty( $_sql )
			|| ! is_string( $_sql )
			|| ! (bool) preg_match( '#\?#s', $_sql )
			) {
			return false;
		}

		// mysqli_stmt?
		if ( false === ( $_rtn = $this->sqli->prepare( $_sql ) ) ) {
			trigger_error( $this->get_dbm_error_message( 23, null, $_sql ), E_USER_WARNING );
		}

		return ( ( $_rtn instanceof mysqli_stmt ) ? $_rtn : false );
	}

	/**
	 * Query database: Bind parameters to prepared query.
	 *
	 * @see https://www.php.net/manual/en/book.mysqli.php
	 * mysqli_stmt::bind_param() sequentially binds values to parameter
	 * markers in the DML statement that was passed to mysqli::prepare()
	 * and returns TRUE on success or FALSE on failure.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param object $_qry		Required. Instance of mysqli_stmt.
	 * @param array $_arg		Required. Array of parameters to bind to query.
	 * @param string $_sql		Optional. SQL statement to be prepared.
	 * @return bool				TRUE on success or FALSE on failure.
	 */
	private function bind_query_params( $_qry = null, $_arg = null, $_sql = null ) {
		// Validate $args and bind parameters
		if ( false !== ( $_rtn = $this->check_query_bind_params( $_qry, $_arg, $_sql ) ) ) {
			// Bind parameters
			$_rcm = new ReflectionClass( 'mysqli_stmt' );
			$_bpm = $_rcm->getMethod( 'bind_param' );
			if ( false === ( $_rtn = $_bpm->invokeArgs( $_qry, $this->get_param_ref_array( array_values( $_arg ) ) ) ) ) {
				trigger_error( $this->get_dbm_error_message( 22, null, $_sql ), E_USER_WARNING );
			}
		}

		return $_rtn;
	}

	/**
	 * Query database: Check parameters to bind to prepared query.
	 *
	 * @see https://www.php.net/manual/en/book.mysqli.php
	 * mysqli_stmt::bind_param() requires a type string length the same
	 * as the number of parameters to bind in mysqli_stmt->param_count
	 * that matches the number of parameter markers in the DML statement
	 * that was passed to mysqli::prepare().
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param object $_qry		Required. Instance of mysqli_stmt.
	 * @param array $_arg		Required. Array of parameters to bind to query.
	 * @param string $_sql		Optional. SQL statement to be prepared.
	 * @return bool				TRUE if parameter count valid or FALSE.
	 */
	private function check_query_bind_params( $_qry = null, $_arg = null, $_sql = null ) {
		// Validate $args
		if (
			! isset( $_qry, $_arg )
			|| ! ( $_qry instanceof mysqli_stmt )
			|| ! is_array( $_arg )
			|| ! is_string( $_qps = reset( $_arg ) )
			|| ! ( $_qpl = strlen( $_qps ) )
			|| ! (bool) preg_match( '#^[dbsiTI]+$#', $_qps )
			|| ( $_qpl !== $_qry->param_count )
			|| ( ( count( $_arg ) - 1 ) !== $_qry->param_count )
			) {
			trigger_error( $this->get_dbm_error_message( 21, null, $_sql ), E_USER_WARNING );
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Query database: Execute prepared query.
	 *
	 * @see https://www.php.net/manual/en/book.mysqli.php
	 * mysqli_stmt::execute() returns TRUE on success or FALSE on
	 * failure.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param object $_qry		Required. Instance of mysqli_stmt.
	 * @param string $_sql		Optional. SQL statement to be prepared.
	 * @return bool				TRUE on success or FALSE on failure.
	 */
	private function exec_prepared_query( $_qry = null, $_sql = null ) {
		// Validate $args
		if (
			! ( $_qry instanceof mysqli_stmt )
			|| ! empty( $_qry->errno )
			|| ! empty( $_qry->error )
			) {
			return false;
		}

		if ( false === ( $_rtn = $_qry->execute() ) ) {
			trigger_error( $this->get_dbm_error_message( 23, $_qry, $_sql ), E_USER_WARNING );
		}

		return $_rtn;
	}

	/**
	 * Query database: Get result set for executed query.
	 *
	 * @see https://www.php.net/manual/en/book.mysqli.php
	 * mysqli_stmt::get_result() returns a mysqli_result object
	 * (resultset) for successful SELECT queries or FALSE for other DML
	 * queries or FALSE on failure.
	 * mysqli::$errno (int $this->sqli->errno) needs to be used to
	 * distinguish between the two types of failure.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param object $_qry		Required. Instance of mysqli_stmt.
	 * @param bool $_one		Optional. TRUE for retrieving first result only or FALSE otherwise.
	 * @return mixed			Array of retrieved records or boolean TRUE|FALSE for query success|failure.
	 */
	private function get_query_result_set( $_qry = null, $_one = false ) {
		// Validate $args
		if (
			! ( $_qry instanceof mysqli_stmt )
			|| ! empty( $_qry->errno )
			|| ! empty( $_qry->error )
			) {
			return false;
		}

		if ( false === ( $_res = $_qry->get_result() ) ) {
			$_rtn = empty( $_qry->errno );
		} elseif ( $_res instanceof mysqli_result ) {
			$_rtn = $this->collect_query_result( $_res, $_one );
		}

		if (
			( $_qry instanceof mysqli_stmt )
			&& ! empty( $_qry->errno )
			) {
			trigger_error( $this->get_dbm_error_message( 23, $_qry, $_sql ), E_USER_WARNING );
		}

		return ( isset( $_rtn ) ? $_rtn : false );
	}

	/**
	 * Query database: Collect query result.
	 *
	 * @see https://www.php.net/manual/en/book.mysqli.php
	 * mysqli_result::fetch_assoc() fetches a result row as an
	 * associative array.
	 * mysqli_result::free() frees the memory associated with the result
	 * and must always be used after the result object is no longer
	 * needed.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param object $_arg		Required. Instance of mysqli_result.
	 * @param bool $_one		Optional. TRUE for retrieving first result only or FALSE otherwise.
	 * @return mixed			Array of retrieved records or boolean TRUE|FALSE for query success|failure.
	 */
	private function collect_query_result( $_arg = null, $_one = false ) {
		// Process $_arg
		if ( $_arg instanceof mysqli_result ) {
			$_rtn = array();
			while ( $_row = $_arg->fetch_assoc() ) {
				if ( true === $_one ) {
					$_rtn = $_row;
					break;
				} else {
					$_rtn[] = $_row;
				}
			}
			$_arg->free();
		} elseif ( is_bool( $_arg ) ) {
			$_rtn = $_arg;
		} else {
			$_rtn = false;
		}

		return $_rtn;
	}

	/**
	 * Query database: Convert `bind_param` array to array by reference.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param array $_arg		Required. Array of arguments required for mysqli_stmt::bind_param().
	 * @return mixed			Array with elements as references of input array or input $_arg if not array.
	 */
	private function get_param_ref_array( $_arg = null ) {
		if (
			! is_array( $_arg )
			|| ( ( $j = count( $_arg ) ) !== count( array_filter( array_keys( $_arg ), 'is_int' ) ) )
			) {
			return $_arg;
		}

		$_rtn = array( 0 => str_replace( array( 'T', 'I' ), array( 's', 's' ), $_arg[0] ) );
		for ( $i = 1; $i < $j; $i += 1 ) {
			$_rtn[$i] = &$_arg[$i];
		}

		return $_rtn;
	}

	/**
	 * WordPress: Change table prefix.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Optional. File name with full path.
	 * @return bool             File name with full path or FALSE.
	 */
	private function wp_replace_table_prefix() {
		// Set up replacement table prefix
		if (
			! isset( $this->tpfx, $this->rpfx )
			|| ! is_string( $this->tpfx )
			|| ! strlen( $this->tpfx )
			|| ! is_string( $this->rpfx )
			|| ! strlen( $this->rpfx )
			|| ( false === ( $_ren = $this->wp_get_rename_list( $this->rpfx ) ) )
			|| ! is_array( $_ren )
			|| ! isset( $_ren['_trs'] )
			|| ! is_array( $_ren['_trs'] )
			) {
			return false;
		}

		// Disable foreign key checks
		$_fkc = $this->get_query_result( 'SET @DBM_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0' );

		// Rename tables (single RENAME statement eschewed for locking and logging)
		foreach ( $_ren['_trs'] as $_key => $_val ) {
			// Acquire table lock
			if ( false !== $this->get_query_result( "LOCK TABLE `{$_key}` WRITE" ) ) {
				$this->dlog['_err'][] = $this->get_dbm_error_message( ( ( false === $this->get_query_result( $_val ) ) ? 61 : 62 ), $this->rpfx, $_key );
				// Release table locks
				$this->get_query_result( 'UNLOCK TABLES' );
			}
		}

		// Drop and re-create views
		if (
			isset( $_ren['_vrs'] )
			&& is_array( $_ren['_vrs'] )
			) {
			foreach ( $_ren['_vrs'] as $_key => $_val ) {
				if ( ! isset( $_val[0], $_val[1] ) ) {
					continue;
				}
				$this->dlog['_err'][] = $this->get_dbm_error_message( ( ( false === $this->get_query_result( $_val[0] ) ) ? 63 : 64 ), $this->rpfx, $_key );
				$this->dlog['_err'][] = $this->get_dbm_error_message( ( ( false === $this->get_query_result( $_val[1] ) ) ? 65 : 66 ), $this->rpfx, $_key );
			}
		}

		// Update WordPress options|usermeta
		if (
			is_array( $_upd = $this->wp_get_update_query( $this->rpfx, $_ren['_trs'] ) )
			&& isset( $_upd['_opt'], $_upd['_usr'] )
			) {
			$this->dlog['_err'][] = $this->get_dbm_error_message( ( ( false === $this->get_query_result( $_upd['_opt'] ) ) ? 71 : 72 ), $this->rpfx );
			$this->dlog['_err'][] = $this->get_dbm_error_message( ( ( false === $this->get_query_result( $_upd['_usr'] ) ) ? 73 : 74 ), $this->rpfx );
		}

		// Restore foreign key checks
		if ( true === $_fkc ) {
			$this->get_query_result( 'SET FOREIGN_KEY_CHECKS=@DBM_FOREIGN_KEY_CHECKS' );
		}
	}

	/**
	 * WordPress: Get table list.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @return mixed             File name with full path or FALSE.
	 */
	private function wp_get_rename_list( $_arg = null ) {
		// Process only if we have old and new prefixes
		if (
			! isset( $_arg, $this->tpfx )
			|| ! is_string( $_arg )
			|| ! strlen( $_arg )
			|| ! is_string( $this->tpfx )
			|| ! strlen( $this->tpfx )
			) {
			return false;
		}

		// Retrieve table/view information
		$_sql = 'SELECT `t`.`TABLE_NAME`, `t`.`TABLE_TYPE`, `v`.`VIEW_DEFINITION`
			FROM `INFORMATION_SCHEMA`.`TABLES` AS `t`
				LEFT JOIN `INFORMATION_SCHEMA`.`VIEWS` AS `v`
					ON `v`.`TABLE_SCHEMA` = `t`.`TABLE_SCHEMA` AND `v`.`TABLE_NAME` = `t`.`TABLE_NAME`
			WHERE `t`.`TABLE_SCHEMA` = ? AND `t`.`TABLE_NAME` LIKE ? AND ( `t`.`TABLE_TYPE` = ? OR `t`.`TABLE_TYPE` = ? )
			GROUP BY `t`.`TABLE_NAME`
			ORDER BY `t`.`TABLE_NAME`
			ASC';

		// Abort on information retrieval failure
		if (
			( false === ( $_dta = $this->get_query_result( $_sql, array( 'ssss', $this->srdb, "{$this->tpfx}%", 'BASE TABLE', 'VIEW' ) ) ) )
			|| ! is_array( $_dta )
			) {
			return false;
		}

		// Table/view rename/rebuild statements
		$_trs = array();
		$_vrs = array();

		// Collate table information
		$_tpl = strlen( $this->tpfx );	// Current table prefix length
		$_tnm = array();				// Table name map
		foreach ( $_dta as $_key => $_val ) {
			if ( ! isset( $_val['TABLE_NAME'], $_val['TABLE_TYPE'] ) ) {
				continue;
			}
			if (
				( 'VIEW' === $_val['TABLE_TYPE'] )
				&& ! empty( $_val['VIEW_DEFINITION'] )
				) {
				$_vrs["{$_val['TABLE_NAME']}"] = $_val['VIEW_DEFINITION'];
			} elseif (
				( 'BASE TABLE' === $_val['TABLE_TYPE'] )
				) {
				$_src = "`{$this->srdb}`.`{$_val['TABLE_NAME']}`";
				$_tgt = sprintf( '`%1$s`.`%2$s%3$s`', $this->srdb, $_arg, substr( $_val['TABLE_NAME'], $_tpl ) );
				$_trs["{$_val['TABLE_NAME']}"] = sprintf( 'ALTER TABLE %1$s RENAME TO %2$s', $_src, $_tgt );
				$_tnm["{$_src}"] = $_tgt;
			}
		}

		// Find/replace strings for view rebuild
		$_trf = array_keys( $_tnm );
		$_trr = array_values( $_tnm );

		// Collate view modification statements
		foreach ( $_vrs as $_key => &$_val ) {
			$_nvn = "`{$this->srdb}`.`{$_arg}" . substr( $_key, $_tpl ) . "`";
			$_def = str_replace( $_trf, $_trr, $_val );
			$_val = array(
				"CREATE VIEW {$_nvn} AS {$_def}",
				"DROP VIEW `{$this->srdb}`.`{$_key}`",
				);
		}

		// Return list
		return compact( '_trs', '_vrs' );
	}

	/**
	 * WordPress: Get record update query.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @return mixed             File name with full path or FALSE.
	 */
	private function wp_get_update_query( $_arg = null, $_ref = null ) {
		if (
			! isset( $_arg, $_ref, $this->tpfx )
			|| ! is_string( $_arg )
			|| ! strlen( $_arg )
			|| ! is_string( $this->tpfx )
			|| ! ( $_frm = strlen( $this->tpfx ) )
			|| ! is_array( $_ref )
			|| ! isset( $_ref["{$this->tpfx}options"], $_ref["{$this->tpfx}usermeta"] )
			) {
			return false;
		}

		// Test for WordPress `options` and `usermeta` tables
		$_otf = array( 'option_id', 'option_name', 'option_value', 'autoload' );
		$_utf = array( 'umeta_id', 'user_id', 'meta_key', 'meta_value' );
		$_ofn = array_filter(
			array_map(
				function( $_val ) use ( $_otf ) {
					return ( ( is_array( $_val ) && isset( $_val['Field'] ) && in_array( $_val['Field'], $_otf, true ) ) ? $_val['Field'] : null );
					},
				( is_array( $_col = $this->get_query_result( "SHOW COLUMNS FROM `{$this->srdb}`.`{$_arg}options`" ) ) ? $_col : array() )
				)
			);
		$_ufn = array_filter(
			array_map(
				function( $_val ) use ( $_utf ) {
					return ( ( is_array( $_val ) && isset( $_val['Field'] ) && in_array( $_val['Field'], $_utf, true ) ) ? $_val['Field'] : null );
					},
				( is_array( $_col = $this->get_query_result( "SHOW COLUMNS FROM `{$this->srdb}`.`{$_arg}usermeta`" ) ) ? $_col : array() )
				)
			);

		// Nothing to do if not WordPress `options` and `usermeta` tables
		if (
			( count( $_otf ) !== count( $_ofn ) )
			|| ( count( $_utf ) !== count( $_ufn ) )
			) {
			return false;
		}

		// Prefix length and next position
		$_for = $_frm;
		$_frm += 1;

		// Avoid clobbering options prefixed 'wp_'
		if ( 'wp_' === $this->tpfx ) {
			$_opt = "INSERT INTO `{$_arg}options` ( `option_id`, `option_name`, `option_value`, `autoload` )
				SELECT NULL, CONCAT( {$_arg}, SUBSTRING( `option_name` FROM {$_frm} ) ) AS `option_name`, `option_value`, `autoload`
					FROM `{$_arg}options`
					WHERE BINARY SUBSTRING( `option_name` FROM 1 FOR {$_for} ) = '{$this->tpfx}'";
			$_usr = "INSERT INTO `{$_arg}usermeta` ( `umeta_id`, `user_id`, `meta_key`, `meta_value` )
				SELECT NULL, `user_id`, CONCAT( {$_arg}, SUBSTRING( `meta_key` FROM {$_frm} ) ) AS `option_name`, `option_value`, `autoload`
					FROM `{$_arg}options`
					WHERE BINARY SUBSTRING( `option_name` FROM 1 FOR {$_for} ) = '{$this->tpfx}'";
		} else {
			$_opt = "UPDATE `{$_arg}options`
				SET `option_name` = CONCAT( '{$_arg}', SUBSTRING( `option_name` FROM {$_frm} ) )
				WHERE BINARY SUBSTRING( `option_name` FROM 1 FOR {$_for} ) = '$this->tpfx'";
			$_usr = "UPDATE `{$_arg}usermeta`
				SET `meta_key` = CONCAT( '{$_arg}', SUBSTRING( `meta_key` FROM {$_frm} ) )
				WHERE BINARY SUBSTRING( `meta_key` FROM 1 FOR {$_for} ) = '$this->tpfx'";
		}

		return compact( '_opt', '_usr' );
	}

	/**
	 * Generic function: Check if string has serialized strings.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required. String for testing.
	 * @return bool             TRUE if tested string contains serialized string or FALSE.
	 */
	public function has_serialized_strings( $_arg = null ) {
		return (
			! empty( $_arg )
			&& is_string( $_arg )
			&& (bool) preg_match( '#s:\d+:".*?";#s', $_arg )
			);
	}

	/**
	 * Generic function: Check if string has serialized objects.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required. String for testing.
	 * @return bool             TRUE if tested string contains serialized object or FALSE.
	 */
	public function has_serialized_objects( $_arg = null ) {
		return (
			! empty( $_arg )
			&& is_string( $_arg )
			&& ( (bool) preg_match( '#a:\d+:{#s', $_arg ) || (bool) preg_match( '#O:\d+:".*?":\d+:{#s', $_arg ) )
			);
	}

	/**
	 * Generic function: Check if string has nested serializations.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required. String for testing.
	 * @return bool             TRUE if tested string contains nested serialized strings or FALSE.
	 */
	public function has_nested_serializations( $_arg = null ) {
		return (
			! empty( $_arg )
			&& is_string( $_arg )
			&& (bool) preg_match( '#(?:s|M):\d+:"(?:a:\d+:{.*?}|b:[01];|d:[\d\.E\-];|i:\d+;|O:\d+:".*?":\d+:{.*?}|s:\d+:".*?";)*";#s', $_arg )
			);
	}

	/**
	 * Generic function: Get random string.
	 *
	 * The length of the random string generated is twice the requested number
	 * of random bytes.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param int $_arg         Required. Number of random bytes.
	 * @return string           TRUE if CLI or FALSE.
	 */
	public function get_random_string( $_arg = null ) {
		if ( false === ( $_rbs = ( ( ! is_numeric( $_arg ) || ! ( $_arg = ( abs( intval( $_arg ) ) ) ) ) ? false : openssl_random_pseudo_bytes( $_arg ) ) ) ) {
			trigger_error( $this->get_dbm_error_message( 91 ), E_USER_ERROR );
		} else {
			return bin2hex( $_rbs );
		}
	}

	/**
	 * Generic function: Check if CLI.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @return bool             TRUE if CLI or FALSE.
	 */
	public function is_sapi_cli() {
		global $argv, $argc;
		return (
			defined( 'STDIN' )
			|| ( defined( 'PHP_SAPI' ) && is_string( PHP_SAPI ) && ( 'cli' === strtolower( PHP_SAPI ) ) )
			|| ! isset( $_SERVER )
			|| ! is_array( $_SERVER )
			|| ! isset( $_SERVER['REQUEST_METHOD'], $_SERVER['REMOTE_ADDR'] )
			|| ( isset( $_SERVER['argv'], $_SERVER['argc'] ) && is_array( $_SERVER['argv'] ) && is_numeric( $_SERVER['argc'] ) && count( $_SERVER['argv'] ) )
			|| ( isset( $argv, $argc ) && is_array( $argv ) && is_numeric( $argc ) && count( $argv ) )
			);
	}

	/**
	 * Generic function: Collect CLI arguments into array.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @return mixed             CLI parameters array or FALSE.
	 */
	public function get_cli_argv() {
		// Test possibility
		$_raa = ini_get( 'register_argc_argv' );
		if (
			! $this->is_sapi_cli()
			|| empty( $_raa )
			) {
			return false;
		}

		// Collect $argv
		global $argv;
		$_arg = array_slice(
			( isset( $_SERVER ) && is_array( $_SERVER ) && isset( $_SERVER['argv'] ) && is_array( $_SERVER['argv'] ) ? $_SERVER['argv'] : ( isset( $argv ) && is_array( $argv ) ? $argv : array() ) ),
			1
			);

		// Parse $argv to key => value array
		$_rtn = array();
		foreach ( $_arg as $_key => $_val ) {
			parse_str( $_val, $_val );
			if (
				! is_array( $_val )
				|| ! count( $_val )
				) {
				continue;
			}

			$rval = reset( $_val );
			$rkey = trim( key( $_val ), '-=' );

			$_rtn["{$rkey}"] = ( empty( $rval ) ? '' :
				( is_numeric( $rval ) ? ( ( intval( $rval ) === floatval( $rval ) ) ? intval( $rval ) : floatval( $rval ) ) :
					( is_string( $rval ) ? ( ( 'true' === trim( strtolower( $rval ) ) ) ? true : ( ( 'false' === trim( strtolower( $rval ) ) ) ? false : trim( $rval ) ) ) :
						$rval
						)
					)
				);
		}

		// Only return valid key => value array
		return ( empty( $_rtn ) ? false : $_rtn );
	}

	/**
	 * Generic function: Test if a non-numeric string.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required. String for testing.
	 * @return bool             TRUE if tested string has one or more non-numeric characters or FALSE.
	 */
	public function is_notnum_string( $_arg = null ) {
		return ( isset( $_arg ) && is_string( $_arg ) && strlen( preg_replace( '#[\d\+\-\.\,]#', '', $_arg ) ) );
	}

	/**
	 * Generic function: Test if variable name is valid.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required. String for testing.
	 * @return bool             TRUE if tested string can form an object property name or FALSE.
	 */
	public function is_valid_var_name( $_arg = null ) {
		return ( isset( $_arg ) && is_string( $_arg ) && (bool) preg_match( '#^[a-zA-Z_\x80-\xFF][a-zA-Z0-9_\x80-\xFF]*$#', $_arg ) );
	}

	/**
	 * Generic function: Convert TRUE|FALSE 0|1 strings to boolean.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param mixed $_arg       Required.
	 * @return mixed            Boolean TRUE|FALSE for TRUE|FALSE|0|1 or input.
	 */
	public function string_to_boolean( $_arg = null ) {
		if ( is_bool( $_arg ) ) {
			return $_arg;
		} elseif ( is_numeric( $_arg ) ) {
			return ( 0 !== abs( intval( $_arg ) ) );
		} elseif ( is_string( $_arg ) && ( 'true' === strtolower( $_arg ) ) ) {
			return true;
		} elseif ( is_string( $_arg ) && ( 'false' === strtolower( $_arg ) ) ) {
			return false;
		} else {
			return $_arg;
		}
	}

	/**
	 * Generic function: Strip leading www. from string.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required.
	 * @return mixed            Stripped string or input if not string.
	 */
	public function strip_www_prefix( $_arg = null ) {
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			|| ( false === (bool) preg_match( '#^www[\\\\]*?\.(.*)$#i', $_arg, $_pma ) )
			|| ! isset( $_pma )
			|| ! is_array( $_pma )
			|| ! isset( $_pma[1] )
			|| ! strlen( $_pma[1] )
			) {
			return $_arg;
		} else {
			return $_pma[1];
		}
	}

	/**
	 * Generic function: Get current date/time.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Optional. Date/time format string.
	 * @return string           Date/time string.
	 */
	public function get_current_timestamp( $_arg = null ) {
		$_dtm = new DateTime();
		return $_dtm->setTimestamp( time() )->format( ( empty( $_arg ) || ! is_string( $_arg ) ) ? 'Y-m-d @ H:i:s T' : $_arg );
	}

	/**
	 * Generic function: Test if file name is valid.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Required. File name with full path.
	 * @return bool             TRUE if writeable file or FALSE.
	 */
	public function is_valid_file_name( $_arg = null ) {
		if (
			empty( $_arg )
			|| ! is_string( $_arg )
			) {
			return false;
		}

		if (
			! is_dir( $_dir = dirname( $_arg ) )
			|| ! is_writable( $_dir )
			|| (bool) preg_match( '#^(?:com[\d]?|con|lpt[\d]?|lst|nul|prn|aux)$#i', ( $_fil = basename( $_arg ) ) )
			|| (bool) preg_match( '#^(?:\$?(?:clock|config|idle|keybd|screen)\$)$#i', $_fil )
			|| (bool) preg_match( '#^(?:\$(?:attrdef|bitmap|boot|badclus|extend|logfile|mft(mirr)?|objid|quota|reparse|secure|upcase|volume))$#i', $_fil )
			) {
			return false;
		}

		return (
			isset( $_dir, $_fil )
			&& (bool) preg_match( '#[^\x00-\x1F\x22\x25\x2A\x2F\x3A\x3C\x3E\x3F\x5C\x7C\x7F]{1,254}$#i', $_fil )
			);
	}

	/**
	 * DBM log: Get log file.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param string $_arg      Optional. File name with full path.
	 * @return bool             File name with full path or FALSE.
	 */
	private function get_print_log( $_arg = null ) {
		static $_log;

		if ( '_dbm_reset' === $_arg ) {
			return ( $_log = null );
		}

		if (
			! isset( $_log )
			&& ! empty( $_arg )
			&& is_string( $_arg )
			&& $this->is_valid_file_name( $_arg )
			&& touch( $_arg )
			) {
			$_log = $_arg;
		}

		return ( empty( $_log ) ? false : $_log );
	}

	/**
	 * DBM log: Output.
	 *
	 * Outputs log to console and specified file, if any.
	 *
	 * @since   0.1.0
	 * @access  private
	 */
	private function flush_dbm_log() {
		if (
			! isset( $this->srdb, $this->dlog )
			|| ! is_string( $this->srdb )
			|| ! is_array( $this->dlog )
			|| empty( $this->dlog['_typ'] )
			|| ! isset( $this->dlog['_typ'], $this->dlog['_run'], $this->dlog['_dta'], $this->dlog['_err'] )
			) {
			return false;
		}

		// Header
		$_hdr = sprintf( '%1$s log | %2$s | %3$s', __CLASS__, $this->srdb, $this->dlog['_run'] );

		// Log
		$_log = $this->get_dbm_string_update_log();
		if ( false === $_log ) {
			$_log = 'String updates: None' . PHP_EOL;
		}

		// Extended log
		if ( ! empty( $this->dlog['_err'] ) && is_array( $this->dlog['_err'] ) ) {
			$_log .= sprintf( '%1$s%2$s%1$s', PHP_EOL, implode( PHP_EOL, $this->dlog['_err'] ) );
		}

		// Output to console but only if cli
		if (
			( true === $this->dlog['_typ'] )
			&& $this->icli
			) {
			printf(
				'%1$s%2$s%1$s%3$s%1$s%4$s%1$s',
				PHP_EOL,
				$_hdr,
				str_repeat( '-', strlen( $_hdr ) ),
				$_log
				);
		}

		// Output to file
		if (
			( 'file' === $this->dlog['_typ'] )
			&& ( false !== ( $_gpl = $this->get_print_log() ) )
			) {
			file_put_contents(
				$_gpl,
				sprintf(
					'%1$s%1$s%3$s%1$s%4$s%1$s%1$s%5$s%1$s%2$s%1$s%1$s',
					PHP_EOL,
					str_repeat( '=', 72 ),
					$_hdr,
					str_repeat( '-', strlen( $_hdr ) ),
					$_log
					),
				FILE_APPEND
				);
		}
	}

	/**
	 * DBM log: String updates.
	 *
	 * @since   0.1.0
	 * @access  private
	 */
	private function get_dbm_string_update_log() {
		if (
			! isset( $this->dlog )
			|| ! is_array( $this->dlog )
			|| empty( $this->dlog['_typ'] )
			|| ! isset( $this->dlog['_typ'], $this->dlog['_run'], $this->dlog['_dta'], $this->dlog['_err'] )
			|| ! is_array( $this->dlog['_dta'] )
			|| ! isset( $this->dlog['_dta']['dbsr'], $this->dlog['_dta']['dbsi'] )
			|| ! is_array( $this->dlog['_dta']['dbsr'] )
			|| ! count( array_filter( $this->dlog['_dta']['dbsr'] ) )
			|| ! is_array( $this->dlog['_dta']['dbsi'] )
			|| ! count( array_filter( $this->dlog['_dta']['dbsi'] ) )
			) {
			return false;
		}

		// Log
		$_log = '';

		// String updates
		$_sln = max(
			max(
				array_map(
					function( $_val ) {
						return ( ( 0 === ceil( $_val )%10 ) ? ceil( $_val ) : ( 10 * ( round( ( $_val + 10 / 2 ) / 10 ) ) ) );
						},
					array_map(
						function( $_val ) {
							return max( array_merge( array( 10 ), array_map( 'strlen', array_keys( $_val ) ), array_map( 'strlen', array_values( $_val ) ) ) );
							},
						$this->dlog['_dta']['dbsr']
						)
					)
				),
			20
			);

		$_sln += 2;
		$_shl = sprintf(
			'%1$s%2$s%1$s%3$s',
			'+',
			str_repeat( '-', ( 2 * $_sln ) ),
			PHP_EOL
			);

		$_log .= 'String updates' . PHP_EOL . "{$_shl}";
		$_log .= sprintf(
			'%1$s%2$s => %3$s%1$s%4$s',
			'|',
			str_pad( ' Source strings', ( $_sln - 2 ), ' ' ),
			str_pad( ' Target strings', ( $_sln - 2 ), ' ' ),
			PHP_EOL
			);
		$_log .= "{$_shl}";
		$_kid = 0;
		foreach ( $this->dlog['_dta']['dbsr'] as $_key => $_val ) {
			if ( empty( $_val ) || ! is_array( $_val ) ) {
				continue;
			}
			if ( $_kid > 0 ) {
				$_log .= sprintf(
					'%1$s%2$s%1$s%3$s',
					'|',
					str_pad( '', ( 2 * $_sln ), ' ' ),
					PHP_EOL
					);
			}
			$_log .= sprintf(
				'%1$s%2$s%1$s%3$s',
				'|',
				str_pad( " {$_key}:", ( 2 * $_sln ), ' ' ),
				PHP_EOL
				);
			reset( $_val );
			$_log .= implode(
				PHP_EOL,
				array_map(
					function( $lval ) use ( &$_val, $_sln ) {
						$lkey = key( $_val );
						next( $_val );
						return sprintf(
							'%1$s%2$s => %3$s%1$s',
							'|',
							str_pad( "    {$lkey}", ( $_sln - 2 ), ' ' ),
							str_pad( "  {$lval}", ( $_sln - 2 ), ' ' )
							);
						},
					$_val
					)
				);
			$_log .= PHP_EOL;
			$_kid += 1;
		}
		$_log .= "{$_shl}" . PHP_EOL;

		// Record count
		$_rln = max(
			array_merge(
				array( 15 ),
				array_map(
					function( $_val ) {
						$l = strlen( (string) $_val );
						return ( ( 0 === ceil( $l )%10 ) ? ceil( $l ) : ( 10 * ( round( ( $l + 10 / 2 ) / 10 ) ) ) );
						},
					array_keys( $this->dlog['_dta']['dbsi'] )
					)
				)
			);
		$_rln += 1;
		$_rhl = sprintf(
			'%1$s%2$s%1$s%3$s%1$s%3$s%1$s%4$s',
			'+',
			str_repeat( '-', $_rln ),
			str_repeat( '-', 15 ),
			PHP_EOL
			);

		$_log .= 'Update count by table' . PHP_EOL . "{$_rhl}";
		$_log .= sprintf(
			'%1$s%2$s%1$s%3$s%1$s%4$s%1$s%5$s',
			'|',
			str_pad( ' Table', $_rln, ' ' ),
			str_pad( ' Rows found', 15, ' ' ),
			str_pad( ' Rows updated', 15, ' ' ),
			PHP_EOL
			);
		$_log .= "{$_rhl}";
		foreach ( $this->dlog['_dta']['dbsi'] as $_key => $_val ) {
			$f = ( is_array( $_val ) && isset( $_val['rows_found'] ) && is_numeric( $_val['rows_found'] ) ? (string) $_val['rows_found']  : '--' );
			$u = ( is_array( $_val ) && isset( $_val['rows_updated'] ) && is_numeric( $_val['rows_updated'] ) ? (string) $_val['rows_updated']  : '--' );
			$_log .= sprintf(
				'%1$s%2$s%1$s%3$s%1$s%4$s%1$s%5$s',
				'|',
				str_pad( " {$_key}", $_rln, ' ' ),
				str_pad( " {$f}", 15, ' ' ),
				str_pad( " {$u}", 15, ' ' ),
				PHP_EOL
				);
		}
		$_log .= "{$_rhl}";

		return $_log;
	}

	/**
	 * Debug: Get error message.
	 *
	 * @since   0.1.0
	 * @access  private
	 *
	 * @param int $_num			Required. Instance of mysqli_stmt.
	 * @param mixed $_qry		Optional. Array or instance of mysqli[_stmt] varying by context.
	 * @param string $_sql		Optional. SQL statement.
	 * @return string			Error message string.
	 */
	private function get_dbm_error_message( $_num = null, $_qry = null, $_sql = null ) {
		static $_msg;
		if ( ! isset( $_msg ) ) {
			$_msg = array(
				1	=> sprintf( '%1$s requires PHP version >= %2$s, `%3$s` and `%4$s` extensions%5$s', __CLASS__, $this::PHP_VERSION, 'mysqli', 'openssl', '%1$s%2$s' ),
				11	=> 'Database connection set up error %3$s: %4$s%1$s%2$s',
				21	=> 'Incorrect query parameters for query%1$s%2$s`%5$s`%1$s%2$s',
				22	=> 'Failed to bind query parameters for query%1$s%2$s`%5$s`%1$s%2$s',
				23	=> 'Database query error %3$s: %4$s for query%1$s%2$s`%5$s`%1$s%2$s',
				61	=> 'Failed to change name prefix on table `%4$s`.',
				62	=> 'Changed name prefix to `%3$s` on table `%4$s`.',
				63	=> 'Failed to change name prefix on view `%4$s`.',
				64	=> 'Changed name prefix to `%3$s` on view `%4$s`.',
				65	=> 'Failed to drop obsolete view `%4$s`.',
				66	=> 'Dropped obsolete view `%4$s`.',
				71	=> 'Failed to change `%3$s` prefixed option names in `%3$soptions` table.',
				72	=> 'Changed option names prefix to `%3$s` in `%3$soptions` table.',
				73	=> 'Failed to change `%3$s` prefixed meta keys in `%3$susermeta` table.',
				74	=> 'Changed meta keys prefix to `%3$s` in `%3$susermeta` table.',
				91	=> 'PHP function openssl_random_pseudo_bytes() failed. Maybe not enough random bytes available?%1$s%2$sPlease try to increase entropy and try again.%1$s%2$sCannot continue',
				99	=> 'Unknown error',
				);
		}

		$_num = ( is_int( $_num ) && isset( $_msg[$_num] ) ? $_num : 99 );
		$_vsa = array( 0 => PHP_EOL, 1 => "\t" );
		if ( 11 === $_num ) {
			$_vsa[2] = ( empty( $this->sqli->connect_errno ) ? ( empty( $this->sqli->errno ) ? '0' : $this->sqli->errno ) : $this->sqli->connect_errno );
			$_vsa[3] = ( empty( $this->sqli->connect_error ) ? ( empty( $this->sqli->error ) ? ( is_array( $_qry ) ? implode( '|', array_filter( $_qry, 'is_string' ) ) : 'Undefined' ) : $this->sqli->error ) : $this->sqli->connect_error );
		} elseif ( ( 21 === $_num ) || ( 22 === $_num ) ) {
			$_vsa[3] = $_vsa[2] = null;
		} elseif ( 23 === $_num ) {
			$_vsa[2] = ( ( $_qry instanceof mysqli_stmt ) ? $_qry->errno : $this->sqli->errno );
			$_vsa[3] = ( ( $_qry instanceof mysqli_stmt ) ? $_qry->error : $this->sqli->error );
		} elseif ( ( 60 < $_num ) && ( 80 > $_num ) ) {
			$_vsa[2] = ( is_string( $_qry ) ? $_qry : 'Undefined' );
			$_vsa[3] = ( is_string( $_sql ) ? $_sql : 'Undefined' );
		}
		$_vsa[4] = ( ( isset( $_sql ) && is_string( $_sql ) && strlen( $_sql ) ) ? $_sql : 'Undefined' );

		return vsprintf( $_msg[$_num], $_vsa );
	}

} // class ASTX_DB_Migrator

$astx_db_migrator = new ASTX_DB_Migrator();
