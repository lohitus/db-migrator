# Database Migrator
This is a command line PHP utility for replacement of strings in tables of MariaDB/MySQL databases to enable migration of websites from one server or domain to another. String replacements are performed whilst safely handling PHP serialized data.

The utility can also change table prefix strings (`$table_prefix` in `wp-config.php`) in [WordPress](https://wordpress.org/) databases.

Once set up, string replacements in database tables are performed using the command
```
$ php /path/to/db-migrator.php dbm=/path/to/db-migrator-parameters.php
```


### Getting Started
Download the latest release archive and extract its contents into a directory.

The two necessary components of the Database Migrator are
* `db-migrator.php` that performs string replacements and
* `db-migrator-parameters.php` that contains the database and string replacement parameters.

The `dbm-params` sub-directory is a container for reusable parameter files. File `db-migrator-parameters-template.php` in this sub-directory is a copy of `db-migrator-parameters.php` stripped of annotations.


### Migrating Databases
For a database to be migrated
1. export the source database in SQL format to a file using [`mysqldump`](https://mariadb.com/kb/en/making-backups-with-mysqldump/) or [`phpMyAdmin`](http://docs.phpmyadmin.net/) or any other convenient method
2. create a new target database for the Database Migrator to process
3. import the source database from 1. above into the target database at 2. above
4. open `dbm-params/db-migrator-parameters-template.php` in a text editor
5. set parameter values
6. save the file in the `dbm-params` sub-directory with a new name
7. execute `php /path/to/db-migrator.php dbm=/path/to/dbm-params/file.php`
8. export the target database from 2. above for import to the database of the migrated website



----
# Prerequisites
The system on which the Database Migrator is run must have the [PHP](https://www.php.net/) package installed with the [`mysqli`](https://www.php.net/manual/en/book.mysqli.php) and [`openssl`](https://www.php.net/manual/en/book.openssl.php) extensions enabled.

The directory where this utility is downloaded/installed should only be accessible to the user downloading/installing it. Under no circumstances should the directory be accessible to a webserver or over HTTP. If the directory is accessible over HTTP, redirect all requests to the included `index.php` file.



----
# Database Migrator Parameters
The processing parameters of this utility are contained in a single array named `$_dbm` that comprises the contents of the parameters file:
```
<?php
$_dbm = array(
  'parameter 1'    => 'parameter value 1',
  'parameter 2'    => 'parameter value 2',
  'parameter 3'    => 'parameter value 3',
  ...
  );
```
Setting parameter values is described below.

- [Connection parameters](#connection-parameters)
	- [Required parameters](#required-parameters)
	- [Optional parameters](#optional-parameters)
- [String replacement filter parameters](#string-replacement-filter-parameters)
- [Processing options](#processing-options)
- [String replacement parameters](#string-replacement-parameters)
- [Logging options](#logging-options)
- [WordPress table prefix replacement option](#wordpress-table-prefix-replacement-option)

#
### Connection parameters
This is the first block of parameters comprising `db_*` keys whose values are required to make a connection to the database for migration.


##### Required parameters
```
<?php
$_dbm = array(
	'db_name'	=> 'DATABASE_NAME',
	'db_user'	=> 'DATABASE_USER',
	'db_pass'	=> 'USER_PASSWORD',
  	...
  );
```
To set

* ***db_name***, name of the database subject to string replacement,

* ***db_user***, name of user with full access to the the database (all privileges) and

* ***db_pass***, password of ***db_user*** above

replace the respective strings DATABASE_NAME, DATABASE_USER and USER_PASSWORD above.


##### Optional parameters
```
<?php
$_dbm = array(
	...
	'db_host'	=> 'DATABASE_HOST',
	'db_port'	=> 'DATABASE_PORT',
	'db_cset'	=> 'DATABASE_CONNECTION_CHARACTER_SET',
	'db_coll'	=> 'DATABASE_CONNECTION_COLLATION',
  	...
  );
```
* ***db_host*** is the hostname or IP address of the database server: defaults to `localhost` when left blank. This value may also be specified in the ***host:port:/socket*** format in which case, the port specified overrides any value in ***db_port*** below.

* ***db_port*** is the TCP port number of the database server to use for connection: usually defaults to `3306` (see [`mysqli.default_port`](https://www.php.net/manual/en/mysqli.configuration.php#ini.mysqli.default-port)). It is always overridden by the port number in the ***db_host*** parameter when specified there.

* ***db_cset*** is the character set to use for the connection.

* ***db_coll*** is the collation to use for the connection.

To set these parameters, replace the respective strings DATABASE_HOST, DATABASE_PORT, DATABASE_CONNECTION_CHARACTER_SET and DATABASE_CONNECTION_COLLATION above.

When ***db_cset*** is not specified, the character set of the operating system (of the machine running the utility) is used and may lead to unexpected results. It is recommended to explicitly set this to the default character set of the database in ***db_name***.

***db_coll*** has no effect no effect on the Database Migrator.

* ***db_sslc*** is only required for the utility to connect to a remote database over TLS/SSL. The database server determines if this parameter is necessary. See [Data-in-Transit Encryption](https://mariadb.com/kb/en/data-in-transit-encryption/) for details. This parameter is an array with values as below.
```
<?php
$_dbm = array(
	...
	'db_sslc'	=> array(
				'key'		=> '/path/to/client/private/key/file',
				'cert'		=> '/path/to/client/public/key/certificate/file',
				'ca'		=> '/path/to/certificate/authority/file',
				'capath'	=> '/path/to/directory/containing/trusted/TLS/CA/certificates',
				'cipher'	=> 'Space delimited cipher list for TLS encryption',
				),
  	...
  );
```
To set these parameters, replace the values for `'key'`, `'cert'`, `'ca'`, `'capath'` and `'cipher'` as indicated above.

It is not common for the remote server to be set up to verify the client certificate. Client verification of the server certificate usually suffices for preventing the man-in-the-middle.

For client verification of the server certificate, set this parameter as
```
	'db_sslc'	=> array(
				'ca'		=> '/path/to/certificate/authority/file',
				),
```
and when not necessary, set this parameter as
```
	'db_sslc'	=> '',
```



#
### String replacement filter parameters
This block of parameters determines the tables and columns subject to string replacement. All of these parameters are optional and need not be explicitly specified.
```
<?php
$_dbm = array(
	...
	'table_prefix'		=> 'TABLE_NAME_PREFIX',
  	...
  );
```
* ***table_prefix*** is the string at the beginning of table names. To specify this, replace TABLE_NAME_PREFIX above with the required string. When specified, only tables whose names begin with this string are subject to string replacement and other tables are ignored. Leaving this blank (`'table_prefix' => '',`) subjects all tables in ***db_name*** to string replacement.

```
<?php
$_dbm = array(
	...
	'exclude_columns'	=> array(
					'table_name_1' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					'table_name_2' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					'table_name_3' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					'table_name_n' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					),
  	...
  );
```
* ***exclude_columns*** is an array of table names each with a corresponding array of column names.

Specified columns corresponding to the respective table names are excluded from string replacement. In the example above, columns `'column_name_1'`, `'column_name_2'`, `'column_name_3'`, `'column_name_n'` of tables `'table_name_1'`, `'table_name_2'`, `'table_name_3'`, `'table_name_n'` would be excluded from string replacement.

To specify exclusion of entire tables, leave blank the array of column names thus:
```
	'exclude_columns'	=> array(
					'table_name_1' => '',
					'table_name_2' => '',
					'table_name_3' => '',
					),
```
In the example above, all columns of tables `'table_name_1'`, `'table_name_2'` and `'table_name_3'` would be excluded from string replacement.

Any number of table names each with or without an array of any number of column names may be specified.

```
<?php
$_dbm = array(
	...
	'include_columns'	=> array(
					'table_name_1' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					'table_name_2' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					'table_name_3' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					'table_name_n' => array(
								'column_name_1',
								'column_name_2',
								'column_name_3',
								'column_name_n',
								),
					),
  	...
  );
```
* ***include_columns*** is set up exactly as ***exclude_columns*** except that *only* specified columns corresponding to the respective table names are included for string replacement.  Leaving blank the array of column names includes all columns of the corresponding table.

***include_columns*** is always processed after ***exclude_columns*** meaning that if a table/column is excluded, having it in ***include_columns*** has no effect.

```
<?php
$_dbm = array(
	...
	'include_blob_columns'	=> false,
  	...
  );
```
* ***include_blob_columns*** defaults to `false` and excludes columns of type `[TINY|MEDIUM|LONG]BLOB` from string replacements. Set this parameter to boolean `true` to force their inclusion (if not specifically excluded by `[exclude|include]_columns` above).


#
### Processing options
Parameters of this block are optional and need not be explicitly specified.
```
<?php
$_dbm = array(
	...
	'record_retrieval_limit' 	=> 0,
	'protect_email'			=> true,
  	...
  );
```
* ***record_retrieval_limit*** specifies the number of records of a table to process at a time. The ideal number for this value is dependent on the machine running the utility (processor/memory availability, I/O performance etc.) and may only be determined by testing over time. This value defaults to 10000.

* ***protect_email*** may be set to `true` or `false`. When `true`, the default value, the `@` character is prefixed to the host names of URL search strings specified in the ***url_strings*** parameter and the resulting `@hostname.tld` strings are excluded from being replaced. When `false`, the `@hostname.tld` strings derived from the URL search strings are replaced by the corresponding `@replacement_hostname.replacement_tld` strings derived by prefixing `@` to the host names of corresponding URL replacement strings.


#
### String replacement parameters
At least one of these three parameters is required for the utility to run. In the absence of these parameters, ***table_prefix_replace*** must be specified. When so, the utility does not perform string replacements but only table name prefix replacement.

```
<?php
$_dbm = array(
	...
	'url_strings'	=> array(
				'http://www.domain.tld/'	=> 'https://new.domain.tld/',
				'http://www.example.com/'	=> 'https://www.actual_domain.com/',
				'https://some.domain.tld/'	=> 'http://another.domain.tld/',
				),
	'dir_strings'	=> array(
				'\\NAS\username\httpdocs\'	=> '/home/user/htdocs/',
				'C:\LAMP\htdocs\project\'	=> '/home/projects/html/website/',
				'/srv/www/htdocs/site/'		=> 'N:\userid\http\docs\site',
				),
	'txt_strings'	=> array(
				'arbitrary string 1'		=> 'replacement string 1',
				'arbitrary string 2'		=> 'replacement string 2',
				'arbitrary string 3'		=> 'replacement string 3',
				),
  	...
  );
```
* ***url_strings*** is an array of `search` URL strings with corresponding `replace` URL strings. The example above has `'http://www.domain.tld/'`, `'http://www.example.com/'` and `'https://some.domain.tld/'` as `search` URL strings with `'https://new.domain.tld/'`, `'https://www.actual_domain.com/'` and `'http://another.domain.tld/'` as the respective `replacement` URL strings. Any number of search/replace URL string pairs may be specified. It is recommended to terminate all URL strings with a `/` to get best results from the utility.

* ***dir_strings*** is an array of `search` directory strings with corresponding `replace` directory strings. The example above has `'\\NAS\username\httpdocs\'`, `'C:\LAMP\htdocs\project\'` and `'/srv/www/htdocs/site/'` as `search` directory strings with `'/home/user/htdocs/'`, `'/home/projects/html/website/'` and `'N:\userid\http\docs\site'` as the respective `replacement` directory strings. Any number of search/replace directory string pairs may be specified. It is recommended to use full directory paths terminated by the directory separator (`/` or `\`) to get best results from the utility.

* ***txt_strings*** is an array of arbitrary text strings to `search` and `replace` with corresponding text strings. This parameter should be only used for absolute strings that do not satisfy the need to be included in ***url_strings*** or ***dir_strings***. Any number of search/replace text string pairs may be specified as in the example above.

The order of precedence of string replacements by the utility is ***txt_strings*** first, then ***dir_strings*** and then ***url_strings***.


#
### Logging options
```
<?php
$_dbm = array(
	...
	'log'				=> '/path/to/log/file',
  	...
  );
```
* ***log*** can be one of `true`, `false` or full path to a file. When set to `false`, the utility outputs no information. If `true`, migration information including replaced strings, number of replacements performed per table and renamed tables, if any, is output to the console. When a path to a writable file is specified, the log as when `true`, is appended to the specified file.


#
### WordPress table prefix replacement option
```
<?php
$_dbm = array(
	...
	'table_prefix_replace' 		=> 'REPLACEMENT_TABLE_NAME_PREFIX',
  	...
  );
```
* ***table_prefix_replace*** is only valid for [WordPress](https://wordpress.org/) databases and only works when ***table_prefix*** parameter is specified. To specify the replacement prefix, replace TABLE_NAME_PREFIX above with the required string.


----
# Notes

##### Secure the utility from access over HTTP
As mentioned in [`Prerequisites`](#prerequisites) above, this utility should not be accessible over HTTP.

Files `.htaccess` for [Apache](https://httpd.apache.org/) and `web.config` for [IIS](https://www.iis.net/) webservers are included as suggestions to redirect all requests to the included `index.php` file.

Additionally, the first line of the `db-migrator.php` script has `( ( false === http_response_code() ) || exit );` to prevent such access.

Implementation of the exact mechanism of securing the utility from possible web exploits is left to the user under terms of the included license.

##### Single quotes in parameters
Single quotes `'` in parameters need to be escaped thus `\'`. Using this method instead of switching to double quotes (`"`) is recommended to maintain consistency.

##### PHP invocation options
This utility requires the PHP setting `register_argc_argv` to be set to `On`. On some systems, this is not so. On such systems, `register_argc_argv` needs to be set on the command line thus:
```
$ "/path/to/php/binary/executable/php" -d register_argc_argv=On "/path/to/db-migrator.php" dbm="/path/to/dbm-params/file.php"
```


----
# License
This project is licensed under the [GNU General Public License](LICENSE) as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.


----
# Acknowledgements

This project incorporates adaptations of work covered by [WordPress](https://wordpress.org/) Web publishing software, copyright 2011-2019 by the contributors and released under the terms of the same GNU General Public License.

[WordPress](https://wordpress.org/), [PHP](https://www.php.net/), [MariaDB](https://mariadb.org/), [MySQL](https://www.mysql.com/), [Apache](https://httpd.apache.org/) and [IIS](https://www.iis.net/) trademarks, logos and copyrights are property of their respective owners.


----
