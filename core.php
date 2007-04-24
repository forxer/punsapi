<?php
/***********************************************************************

  This file is part of PunBB Simple API (PunSAPI).

  PunSAPI is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunSAPI is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/


/**
@class		punsapi_core
@version	0.3 for PunBB 1.2.x
@author 	Vincent Garnier

A "toolbox class" to play with PunBB on your website :)

This is an adaptation of various PunBB scripts to the oriented 
object model, so the copyright returns to Rickard Andersson.

@param	string		version					Current version of PunSAPI
@param	string		pun_root				Absolute path to pun root
@param	array		options					Options array stack
@param	array		error					Error array stack
@param	string		start_time				Record of the start time
@param	string		time_diff				Record of the execution time
@param	object		db						Database abstraction layer
@param	array		conf					Configuration datas from config.php
@param	array		config					Configuration datas from database
@param	array		lang					Languages datas
@param	array		bans					Bans datas array
@param	array		_cache					Saved functions results array
@param	boolean		mod_puntoolbar			Support for PunToolBar 1.4 mod
*/
class punsapi_core
{
	var $version;
	var $pun_root;
	var $options;
	var $error;
	var $start_time;
	var $time_diff;
	var $db;
	var $conf;
	var $config;
	var $lang;
	var $bans;
	var $_cache;
	var $mod_puntoolbar;
	
	/**
	@function	pun_api
	
	"Constructor"
	
	@param	array	options			An array to enable or disable features. Possibles values :
		- boolean 	check_bans			To check or not if the current user is banned
		- boolean 	quiet_visit			To affect the online list and the users last visit data
		- boolean 	disable_buffering	To disable output buffering
		- boolean 	debug				To enable the debug mode
	*/
	function punsapi_core($options=array())
	{
		define('IN_PUNSAPI', 1);
		
		# inits
		$this->version = '0.3';
		$this->pun_root = dirname(__FILE__).'/../../';		
		$this->error = array();
		$this->_cache = array();
		$this->start_time = NULL;
		$this->time_diff = NULL;
		$this->mod_puntoolbar = false;
		
		# Options
		$default_options = array(
			'punsapi_date_formating' => true,
			'check_bans' => true,
			'quiet_visit' => false,
			'disable_buffering' => false,
			'debug' => false
		);
		$this->options = array_merge($default_options,$options);
		
		# Unregister globals
		$this->_unregister_globals();

		# Debug
		if ($this->options['debug'])
		{
			list($usec, $sec) = explode(' ', microtime());
			$this->start_time = ((float)$usec + (float)$sec);
			
			error_reporting(E_ALL);
		}
		else
			error_reporting(E_ALL ^ E_NOTICE);

		# Get config
		if (file_exists($this->pun_root.'config.php'))
			require $this->pun_root.'config.php';

		if (!defined('PUN'))
			$this->fatal_error('The file \'config.php\' doesn\'t exist or is corrupt.',__FILE__,__LINE__);
		
		# If a cookie name is not specified in config.php, we use the default (punbb_cookie)
		if (empty($cookie_name))
			$cookie_name = 'punbb_cookie';
		
		# For convenience, duplicate conf data
		$this->conf = array();
		$this->conf['db_type'] = &$db_type;
		$this->conf['db_host'] = &$db_host;
		$this->conf['db_name'] = &$db_name;
		$this->conf['db_username'] = &$db_username;
		$this->conf['db_password'] = &$db_password;
		$this->conf['db_prefix'] = &$db_prefix;
		$this->conf['p_connect'] = &$p_connect;

		$this->conf['cookie_name'] = &$cookie_name;
		$this->conf['cookie_domain'] = &$cookie_domain;
		$this->conf['cookie_path'] = &$cookie_path;
		$this->conf['cookie_secure'] = &$cookie_secure;
		$this->conf['cookie_seed'] = &$cookie_seed;

		# Turn off magic_quotes_runtime
		set_magic_quotes_runtime(0);

		# Strip slashes from GET/POST/COOKIE (if magic_quotes_gpc is enabled)
		if (get_magic_quotes_gpc())
		{
			function stripslashes_array($array)
			{
				return is_array($array) ? array_map('stripslashes_array', $array) : stripslashes($array);
			}
		
			$_GET = stripslashes_array($_GET);
			$_POST = stripslashes_array($_POST);
			$_COOKIE = stripslashes_array($_COOKIE);
		}

		# Define a few commonly used constants
		define('PUN_ROOT', $this->pun_root);
		define('PUN_UNVERIFIED', 32000);
		define('PUN_ADMIN', 1);
		define('PUN_MOD', 2);
		define('PUN_GUEST', 3);
		define('PUN_MEMBER', 4);

		# Load DB abstraction layer and connect
		$this->_db_connect();

		# Load cached config
		$this->config = array();
		
		if (file_exists($this->pun_root.'cache/cache_config.php'))
			require $this->pun_root.'cache/cache_config.php';
		
		if (!defined('PUN_CONFIG_LOADED'))
		{
			$this->_generate_config_cache();
			require $this->pun_root.'cache/cache_config.php';
		}
		
		$this->config = &$pun_config;
		
		# support for PunToolBar
		if (!empty($this->config['o_ptb_installed']))
			$this->mod_puntoolbar = true;

		# Enable output buffering
		if (!$this->options['disable_buffering'])
		{
			# For some very odd reason, "Norton Internet Security" unsets this
			$_SERVER['HTTP_ACCEPT_ENCODING'] = isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : '';
		
			# Should we use gzip output compression?
			if ($this->config['o_gzip'] && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
				ob_start('ob_gzhandler');
			else
				ob_start();
		}

		# Check/update/set cookie and fetch user info
		$this->user = array();
		$this->_check_cookie();

		# Attempt to load the common language file
		if (file_exists($this->pun_root.'lang/'.$this->user['language'].'/common.php'))
			require $this->pun_root.'lang/'.$this->user['language'].'/common.php';
		else
			$this->fatal_error('There is no valid language pack \''.htmlspecialchars($this->user['language']).'\' installed. Please reinstall a language of that name', __FILE__, __LINE__);

		$this->lang = array();
		$this->lang['common'] = &$lang_common;

		# Load cached bans
		if (file_exists($this->pun_root.'cache/cache_bans.php'))
			require $this->pun_root.'cache/cache_bans.php';

		if (!defined('PUN_BANS_LOADED'))
		{
			$this->_generate_bans_cache();
			require $this->pun_root.'cache/cache_bans.php';
		}

		$this->bans = &$pun_bans;

		# Check if current user is banned
		if ($this->options['check_bans'])
			$this->_check_bans();

		# Update online list
		$this->_update_users_online();
		
		# Get locales dates if punsapi date formatting is enable
		if ($this->options['punsapi_date_formating'])
		{
			$GLOBALS['locales_dates'] = array();
			
			if (file_exists(dirname(__FILE__).'/locales/'.$this->user['language'].'/date.lang.php'))
				require dirname(__FILE__).'/locales/'.$this->user['language'].'/date.lang.php';
			else
				$this->fatal_error('There is no valid PunSAPI language pack \''.htmlspecialchars($this->user['language']).'\' installed. Please reinstall a language of that name in /include/punsapi/locales/', __FILE__, __LINE__);
		}
	}


	/** Methods used in constructor
	----------------------------------------------------------*/

	/**
	@function _unregister_globals
	
	Unset any variables instantiated as a result of register_globals being enabled
	*/
	function _unregister_globals()
	{
		$register_globals = @ini_get('register_globals');
		if ($register_globals === "" || $register_globals === "0" || strtolower($register_globals) === "off")
			return;

		# Prevent script.php?GLOBALS[foo]=bar
		if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
			exit('I\'ll have a steak sandwich and... a steak sandwich.');

		# Variables that shouldn't be unset
		$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

		# Remove elements in $GLOBALS that are present in any of the superglobals
		$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
		foreach ($input as $k => $v)
		{
			if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
			{
				unset($GLOBALS[$k]);
				unset($GLOBALS[$k]); # Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
			}
		}
	}

	/**
	@function _db_connect
	
	Load DB abstraction layer and connect
	*/
	function _db_connect()
	{
		# Load the appropriate DB layer class
		switch ($this->conf['db_type'])
		{
			case 'mysql':
				require $this->pun_root.'include/dblayer/mysql.php';
				require dirname(__FILE__).'/xdblayer/xmysql.php';
				break;

			case 'mysqli':
				require $this->pun_root.'include/dblayer/mysqli.php';
				require dirname(__FILE__).'/xdblayer/xmysqli.php';
				break;

			case 'pgsql':
				require $this->pun_root.'include/dblayer/pgsql.php';
				require dirname(__FILE__).'/xdblayer/xpgsql.php';
				break;

			case 'sqlite':
				require $this->pun_root.'include/dblayer/sqlite.php';
				require dirname(__FILE__).'/xdblayer/xsqlite.php';
				break;

			default:
				$this->fatal_error('\''.$this->conf['db_type'].'\' is not a valid database type. Please check settings in config.php.', __FILE__, __LINE__);
				break;
		}

		# connect to db
		$this->db = new xDBLayer($this->conf['db_host'], $this->conf['db_username'], $this->conf['db_password'], $this->conf['db_name'], $this->conf['db_prefix'], $this->conf['p_connect']);
		$this->db->start_transaction();
	}


	/** Cache methods
	----------------------------------------------------------*/
	
	/**
	@function _generate_config_cache
	
	Generate the config cache PHP script
	*/
	function _generate_config_cache()
	{
		# Get the forum config from the DB
		$output = array();
		$result = $this->db->query('SELECT * FROM '.$this->db->prefix.'config', true) or $this->fatal_error('Unable to fetch forum config', __FILE__, __LINE__, $this->db->error());
		while ($cur_config_item = $this->db->fetch_row($result))
			$output[$cur_config_item[0]] = $cur_config_item[1];

		# Output config as PHP code
		$fh = @fopen($this->pun_root.'cache/cache_config.php', 'wb');
		if (!$fh)
			$this->fatal_error('Unable to write configuration cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);

		fwrite($fh, '<?php'."\n\n".'define(\'PUN_CONFIG_LOADED\', 1);'."\n\n".'$this->config = '.var_export($output, true).';'."\n\n".'?>');

		fclose($fh);
	}

	/**
	@function _generate_bans_cache
	
	Generate the bans cache PHP script
	*/
	function _generate_bans_cache()
	{
		# Get the ban list from the DB
		$result = $this->db->query('SELECT * FROM '.$this->db->prefix.'bans', true) or $this->fatal_error('Unable to fetch ban list', __FILE__, __LINE__, $this->db->error());

		$output = array();
		while ($cur_ban = $this->db->fetch_assoc($result))
			$output[] = $cur_ban;

		# Output ban list as PHP code
		$fh = @fopen($this->pun_root.'cache/cache_bans.php', 'wb');
		if (!$fh)
			$this->fatal_error('Unable to write bans cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);

		fwrite($fh, '<?php'."\n\n".'define(\'PUN_BANS_LOADED\', 1);'."\n\n".'$pun_bans = '.var_export($output, true).';'."\n\n".'?>');

		fclose($fh);
	}

	/**
	@function _generate_ranks_cache
	
	Generate the ranks cache PHP script
	*/
	function _generate_ranks_cache()
	{
		# Get the rank list from the DB
		$result = $this->db->query('SELECT * FROM '.$this->db->prefix.'ranks ORDER BY min_posts', true) or $this->fatal_error('Unable to fetch rank list', __FILE__, __LINE__, $this->db->error());
	
		$output = array();
		while ($cur_rank = $this->db->fetch_assoc($result))
			$output[] = $cur_rank;
	
		# Output ranks list as PHP code
		$fh = @fopen($this->pun_root.'cache/cache_ranks.php', 'wb');
		if (!$fh)
			$this->fatal_error('Unable to write ranks cache file to cache directory. Please make sure PHP has write access to the directory \'cache\'', __FILE__, __LINE__);
	
		fwrite($fh, '<?php'."\n\n".'define(\'PUN_RANKS_LOADED\', 1);'."\n\n".'$pun_ranks = '.var_export($output, true).';'."\n\n".'?>');
	
		fclose($fh);
	}

	/**
	@function _set_cache
	
	Set result of a function
	
	@param	string	function		Name of the function
	@param	integer	id				Identifier
	@param	mixed	data			Data to save
	*/
	function _set_cache($function, $id, $data)
	{
		$this->_cache[$function][$id] = $data;
	}

	/**
	@function _get_cache
	
	Get result of cached datas
	
	@param	string	function		Name of the function
	@param	integer	id				Identifier
	*/
	function _get_cache($function, $id)
	{
		if (array_key_exists($function, $this->_cache))
			return array_key_exists($id, $this->_cache[$function]) ? $this->_cache[$function][$id] : false;
		else
			return false;
	}


	/** PunBB auth methods
	----------------------------------------------------------*/

	/**
	@function _check_cookie
	
	Cookie stuff!
	*/
	function _check_cookie()
	{
		$now = time();
		$expire = $now + 31536000;	# The cookie expires after a year

		# We assume it's a guest
		$cookie = array('user_id' => 1, 'password_hash' => 'Guest');

		# If a cookie is set, we get the user_id and password hash from it
		if (isset($_COOKIE[$this->conf['cookie_name']]))
			list($cookie['user_id'], $cookie['password_hash']) = @unserialize($_COOKIE[$this->conf['cookie_name']]);

		if ($cookie['user_id'] > 1)
		{
			# Check if there's a user with the user ID and password hash from the cookie
			$result = $this->db->query('SELECT u.*, g.*, o.logged, o.idle FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$this->db->prefix.'online AS o ON o.user_id=u.id WHERE u.id='.intval($cookie['user_id'])) or $this->fatal_error('Unable to fetch user information', __FILE__, __LINE__, $this->db->error());
			$this->user = $this->db->fetch_assoc($result);

			# If user authorisation failed
			if (!isset($this->user['id']) || md5($this->conf['cookie_seed'].$this->user['password']) !== $cookie['password_hash'])
			{
				$this->_set_cookie(0, $this->_random_pass(8), $expire);
				$this->_set_default_user();
	
				return;
			}

			# Set a default language if the user selected language no longer exists
			if (!file_exists($this->pun_root.'lang/'.$this->user['language']))
				$this->user['language'] = $this->config['o_default_lang'];

			# Set a default style if the user selected style no longer exists
			if (!file_exists($this->pun_root.'style/'.$this->user['style'].'.css'))
				$this->user['style'] = $this->config['o_default_style'];

			if (!$this->user['disp_topics'])
				$this->user['disp_topics'] = $this->config['o_disp_topics_default'];
			if (!$this->user['disp_posts'])
				$this->user['disp_posts'] = $this->config['o_disp_posts_default'];

			if ($this->user['save_pass'] == '0')
				$expire = 0;

			# Define this if you want this visit to affect the online list and the users last visit data
			if (!$this->options['quiet_visit'])
			{
				# Update the online list
				if (!$this->user['logged'])
					$this->db->query('INSERT INTO '.$this->db->prefix.'online (user_id, ident, logged) VALUES('.$this->user['id'].', \''.$this->db->escape($this->user['username']).'\', '.$now.')') or $this->fatal_error('Unable to insert into online list', __FILE__, __LINE__, $this->db->error());
				else
				{
					# Special case: We've timed out, but no other user has browsed the forums since we timed out
					if ($this->user['logged'] < ($now-$this->config['o_timeout_visit']))
					{
						$this->db->query('UPDATE '.$this->db->prefix.'users SET last_visit='.$this->user['logged'].' WHERE id='.$this->user['id']) or $this->fatal_error('Unable to update user visit data', __FILE__, __LINE__, $this->db->error());
						$this->user['last_visit'] = $this->user['logged'];
					}
	
					$idle_sql = ($this->user['idle'] == '1') ? ', idle=0' : '';
					$this->db->query('UPDATE '.$this->db->prefix.'online SET logged='.$now.$idle_sql.' WHERE user_id='.$this->user['id']) or $this->fatal_error('Unable to update online list', __FILE__, __LINE__, $this->db->error());
				}
			}

			$this->user['is_guest'] = false;
		}
		else
			$this->_set_default_user();
	}

	/**
	@function _set_default_user
	
	Fill $this->user with default values (for guests)
	*/
	function _set_default_user()
	{
		# Fetch guest user
		$result = $this->db->query('SELECT u.*, g.*, o.logged FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$this->db->prefix.'online AS o ON o.ident=\''.$_SERVER['REMOTE_ADDR'].'\' WHERE u.id=1') or $this->fatal_error('Unable to fetch guest information', __FILE__, __LINE__, $this->db->error());
		if (!$this->db->num_rows($result))
			exit('Unable to fetch guest information. The table \''.$this->db->prefix.'users\' must contain an entry with id = 1 that represents anonymous users.');
	
		$this->user = $this->db->fetch_assoc($result);
	
		# Update online list
		if (!$this->user['logged'])
			$this->db->query('INSERT INTO '.$this->db->prefix.'online (user_id, ident, logged) VALUES(1, \''.$this->db->escape($_SERVER['REMOTE_ADDR']).'\', '.time().')') or $this->fatal_error('Unable to insert into online list', __FILE__, __LINE__, $this->db->error());
		else
			$this->db->query('UPDATE '.$this->db->prefix.'online SET logged='.time().' WHERE ident=\''.$this->db->escape($_SERVER['REMOTE_ADDR']).'\'') or $this->fatal_error('Unable to update online list', __FILE__, __LINE__, $this->db->error());
	
		$this->user['disp_topics'] = $this->config['o_disp_topics_default'];
		$this->user['disp_posts'] = $this->config['o_disp_posts_default'];
		$this->user['timezone'] = $this->config['o_server_timezone'];
		$this->user['language'] = $this->config['o_default_lang'];
		$this->user['style'] = $this->config['o_default_style'];
		$this->user['is_guest'] = true;
	}

	/**
	@function _set_cookie
	
	Set a cookie, PunBB style!
	
	@param	integer	user_id			ID of user
	@param	string	password_hash	Password hash
	@param	string	expire			Cookie expiration date
	*/
	function _set_cookie($user_id, $password_hash, $expire)
	{
		# Enable sending of a P3P header by removing // from the following line (try this if login is failing in IE6)
	//	@header('P3P: CP="CUR ADM"');

		if (version_compare(PHP_VERSION, '5.2.0', '>='))
			setcookie($this->conf['cookie_name'], serialize(array($user_id, md5($this->conf['cookie_seed'].$password_hash))), $expire, $this->conf['cookie_path'], $this->conf['cookie_domain'], $this->conf['cookie_secure'], true);
		else
			setcookie($this->conf['cookie_name'], serialize(array($user_id, md5($this->conf['cookie_seed'].$password_hash))), $expire, $this->conf['cookie_path'].'; HttpOnly', $this->conf['cookie_domain'], $this->conf['cookie_secure']);
	}

	/**
	@function _check_bans
	
	Check whether the connecting user is banned (and delete any expired bans while we're at it)
	*/
	function _check_bans()
	{
		# Admins aren't affected
		if ($this->user['g_id'] == PUN_ADMIN || !$this->bans)
			return;
	
		# Add a dot at the end of the IP address to prevent banned address 192.168.0.5 from matching e.g. 192.168.0.50
		$user_ip = $_SERVER['REMOTE_ADDR'].'.';
		$bans_altered = false;
	
		foreach ($this->bans as $cur_ban)
		{
			# Has this ban expired?
			if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
			{
				$this->db->query('DELETE FROM '.$this->db->prefix.'bans WHERE id='.$cur_ban['id']) or $this->fatal_error('Unable to delete expired ban', __FILE__, __LINE__, $this->db->error());
				$bans_altered = true;
				continue;
			}
	
			if ($cur_ban['username'] != '' && !strcasecmp($this->user['username'], $cur_ban['username']))
			{
				$this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE ident=\''.$this->db->escape($this->user['username']).'\'') or $this->fatal_error('Unable to delete from online list', __FILE__, __LINE__, $this->db->error());
				
				$this->fatal_error($this->lang['common']['Ban message'].' '.(($cur_ban['expire'] != '') ? $this->lang['common']['Ban message 2'].' '.strtolower($this->format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? $this->lang['common']['Ban message 3'].'<br /><br /><strong>'.$this->htmlspecialchars($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$this->lang['common']['Ban message 4'].' <a href="mailto:'.$this->config['o_admin_email'].'">'.$this->config['o_admin_email'].'</a>.');
			}
	
			if ($cur_ban['ip'] != '')
			{
				$cur_ban_ips = explode(' ', $cur_ban['ip']);
	
				for ($i = 0; $i < count($cur_ban_ips); ++$i)
				{
					$cur_ban_ips[$i] = $cur_ban_ips[$i].'.';
	
					if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
					{
						$this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE ident=\''.$this->db->escape($this->user['username']).'\'') or $this->fatal_error('Unable to delete from online list', __FILE__, __LINE__, $this->db->error());
						$this->fatal_error($this->lang['common']['Ban message'].' '.(($cur_ban['expire'] != '') ? $this->lang['common']['Ban message 2'].' '.strtolower($this->format_time($cur_ban['expire'], true)).'. ' : '').(($cur_ban['message'] != '') ? $this->lang['common']['Ban message 3'].'<br /><br /><strong>'.$this->htmlspecialchars($cur_ban['message']).'</strong><br /><br />' : '<br /><br />').$this->lang['common']['Ban message 4'].' <a href="mailto:'.$this->config['o_admin_email'].'">'.$this->config['o_admin_email'].'</a>.');
					}
				}
			}
		}
	
		# If we removed any expired bans during our run-through, we need to regenerate the bans cache
		if ($bans_altered)
			$this->_generate_bans_cache();
	}

	/**
	@function _update_users_online
	
	Update "Users online"
	*/
	function _update_users_online()
	{
		$now = time();
	
		# Fetch all online list entries that are older than "o_timeout_online"
		$result = $this->db->query('SELECT * FROM '.$this->db->prefix.'online WHERE logged<'.($now-$this->config['o_timeout_online'])) or $this->fatal_error('Unable to fetch old entries from online list', __FILE__, __LINE__, $this->db->error());
		while ($cur_user = $this->db->fetch_assoc($result))
		{
			# If the entry is a guest, delete it
			if ($cur_user['user_id'] == '1')
				$this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE ident=\''.$this->db->escape($cur_user['ident']).'\'') or $this->fatal_error('Unable to delete from online list', __FILE__, __LINE__, $this->db->error());
			else
			{
				# If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
				if ($cur_user['logged'] < ($now-$this->config['o_timeout_visit']))
				{
					$this->db->query('UPDATE '.$this->db->prefix.'users SET last_visit='.$cur_user['logged'].' WHERE id='.$cur_user['user_id']) or $this->fatal_error('Unable to update user visit data', __FILE__, __LINE__, $this->db->error());
					$this->db->query('DELETE FROM '.$this->db->prefix.'online WHERE user_id='.$cur_user['user_id']) or $this->fatal_error('Unable to delete from online list', __FILE__, __LINE__, $this->db->error());
				}
				else if ($cur_user['idle'] == '0')
					$this->db->query('UPDATE '.$this->db->prefix.'online SET idle=1 WHERE user_id='.$cur_user['user_id']) or $this->fatal_error('Unable to insert into online list', __FILE__, __LINE__, $this->db->error());
			}
		}
	}


	/** Interns post methods
	----------------------------------------------------------*/

	/*
	@function _pre_post_user
	
	Check and clean up username an email before posting
	
	@param string username 	Username to check (ref)
	@param string email 	Email address to check (ref)
	@return boolean
	*/
	function _pre_post_user(&$username,&$email)
	{
		# If the user is logged in we get the username and e-mail from $this->user
		if (!$this->user['is_guest'])
		{
			$username = $this->user['username'];
			$email = $this->user['email'];
		}
		# Otherwise it should be in $_POST (passed in parameters)
		else
		{
			$username = trim($username);
			$email = strtolower(trim($email));
	
			# Load the register.php/profile.php language files
			$this->load_lang('prof_reg');
			$this->load_lang('register');
	
			# It's a guest, so we have to validate the username
			if (strlen($username) < 2)
			{
				$this->set_error($this->lang['prof_reg']['Username too short']);
				return false;
			}
			else if (!strcasecmp($username, 'invité') || !strcasecmp($username, $this->lang['common']['Guest']))
			{
				$this->set_error($this->lang['prof_reg']['Username guest']);
				return false;
			}
			else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
			{
				$this->set_error($this->lang['prof_reg']['Username IP']);
				return false;
			}
	
			if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
			{
				$this->set_error($this->lang['prof_reg']['Username reserved chars']);
				return false;
			}

			if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[quote=|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]#i', $username))
			{
				$this->set_error($this->lang['prof_reg']['Username BBCode']);
				return false;
			}
	
			# Check username for any censored words
			$temp = $this->censor_words($username);
			if ($temp != $username)
			{
				$this->set_error($this->lang['register']['Username censor']);
				return false;
			}
	
			# Check that the username (or a too similar username) is not already registered
			$busy = $this->db->select('SELECT username FROM '.$this->db->prefix.'users WHERE username=\''.$this->db->escape($username).'\' OR username=\''.$this->db->escape(preg_replace('/[^\w]/', '', $username)).'\'') or $this->fatal_error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
			
			if (!$busy->isEmpty())
			{
				$this->set_error($this->lang['register']['Username dupe 1'].' '.$this->htmlspecialchars($busy->f('username')).'. '.$this->lang['register']['Username dupe 2']);
				return false;
			}
	
			if ($this->config['p_force_guest_email'] == '1' || $email != '')
			{
				if (!$this->is_valid_email($email))
				{
					$this->set_error($this->lang['common']['Invalid e-mail']);
					return false;
				}
			}
		}
		return true;
	}	
	
	
	/**
	@function _pre_post_title
	 
	Check and clean up title before posting
	 
	@param string subject (ref)		The title
	@return boolean
	*/
	function _pre_post_title(&$subject)
	{
		$this->load_lang('post');
		
		$subject = trim($subject);

		if ($subject == '')
		{
			$this->set_error($this->lang['post']['No subject']);
			return false;
		}
		else if (strlen($subject) > 70)
		{
			$this->set_error($this->lang['post']['Too long subject']);
			return false;
		}
		else if ($this->config['p_subject_all_caps'] == '0' && strtoupper($subject) == $subject && $this->user['g_id'] > PUN_MOD)
		{
			$subject = ucwords(strtolower($subject));
		}
		
		return true;
	}
	
	
	/**
	@function _pre_post_message
	 
	Check and clean up message before posting
	 
	@param	string	message	(ref)	The message
	@return boolean
	*/
	function _pre_post_message(&$message)
	{
		$this->load_lang('post');
		
		$message = $this->linebreaks($this->trim($message));
	
		if ($message == '')
		{
			$this->set_error($this->lang['post']['No message']);
			return false;
		}
		else if (strlen($message) > 65535)
		{
			$this->set_error($this->lang['post']['Too long message']);
			return false;
		}
		else if ($this->config['p_message_all_caps'] == '0' && strtoupper($message) == $message && $this->user['g_id'] > PUN_MOD)
			$message = ucwords(strtolower($message));
	
		# Validate BBCode syntax
		if ($this->config['p_message_bbcode'] == '1' && strpos($message, '[') !== false && strpos($message, ']') !== false)
			$message = $this->_pre_parse_bbcode($message);
		
		return true;
	}


	/** Emulate PunBB parser
	Yes we have to do this simply for error message handling,
	PunBB parser must be a class to not do this :/
	----------------------------------------------------------*/
	
	/**
	@function _pre_parse_bbcode
	
	Make sure all BBCodes are lower case and do a little cleanup
	*/
	function _pre_parse_bbcode($text, $is_signature=false)
	{
		if ($this->mod_puntoolbar)
		{
			// Change all simple BBCodes to lower case
			$a = array('[B]', '[I]', '[U]', '[S]', '[/B]', '[/I]', '[/U]', '[/S]');
			$b = array('[b]', '[i]', '[u]', '[s]', '[/b]', '[/i]', '[/u]', '[/s]');
			$text = str_replace($a, $b, $text);
		
			// Do the more complex BBCodes (also strip excessive whitespace and useless quotes)
			$a = array( '#\[url=("|\'|)(.*?)\\1\]\s*#i',
						'#\[url\]\s*#i',
						'#\s*\[/url\]#i',
						'#\[email=("|\'|)(.*?)\\1\]\s*#i',
						'#\[email\]\s*#i',
						'#\s*\[/email\]#i',
						'#\[nospam=("|\'|)(.*?)\\1\]\s*#is',
						'#\[nospam\]\s*#i',
						'#\s*\[/nospam\]#i',
						'#\[acronym=("|\'|)(.*?)\\1\]\s*#is',
						'#\[acronym\]\s*#i',
						'#\s*\[/acronym\]#i',
						'#\[img\]\s*(.*?)\s*\[/img\]#is',
						'#\[colou?r=("|\'|)(.*?)\\1\](.*?)\[/colou?r\]#is');
		
			$b = array(	'[url=$2]',
						'[url]',
						'[/url]',
						'[email=$2]',
						'[email]',
						'[/email]',
						'[nospam=$2]',
						'[nospam]',
						'[/nospam]',
						'[acronym=$2]',
						'[acronym]',
						'[/acronym]',
						'[img]$1[/img]',
						'[color=$2]$3[/color]');
		}
		else {
			# Change all simple BBCodes to lower case
			$a = array('[B]', '[I]', '[U]', '[/B]', '[/I]', '[/U]');
			$b = array('[b]', '[i]', '[u]', '[/b]', '[/i]', '[/u]');
			$text = str_replace($a, $b, $text);
		
			# Do the more complex BBCodes (also strip excessive whitespace and useless quotes)
			$a = array( '#\[url=("|\'|)(.*?)\\1\]\s*#i',
						'#\[url\]\s*#i',
						'#\s*\[/url\]#i',
						'#\[email=("|\'|)(.*?)\\1\]\s*#i',
						'#\[email\]\s*#i',
						'#\s*\[/email\]#i',
						'#\[img\]\s*(.*?)\s*\[/img\]#is',
						'#\[colou?r=("|\'|)(.*?)\\1\](.*?)\[/colou?r\]#is');
		
			$b = array(	'[url=$2]',
						'[url]',
						'[/url]',
						'[email=$2]',
						'[email]',
						'[/email]',
						'[img]$1[/img]',
						'[color=$2]$3[/color]');
		}
		
		if (!$is_signature)
		{
			# For non-signatures, we have to do the quote and code tags as well
			$a[] = '#\[quote=(&quot;|"|\'|)(.*?)\\1\]\s*#i';
			$a[] = '#\[quote\]\s*#i';
			$a[] = '#\s*\[/quote\]\s*#i';
			$a[] = '#\[code\][\r\n]*(.*?)\s*\[/code\]\s*#is';
	
			$b[] = '[quote=$1$2$1]';
			$b[] = '[quote]';
			$b[] = '[/quote]'."\n";
			$b[] = '[code]$1[/code]'."\n";
		}
	
		# Run this baby!
		$text = preg_replace($a, $b, $text);
	
		if (!$is_signature)
		{
			$overflow = $this->_check_tag_order($text);
	
			if (!$this->has_error() && $overflow)
			{
				# The quote depth level was too high, so we strip out the inner most quote(s)
				$text = substr($text, 0, $overflow[0]).substr($text, $overflow[1], (strlen($text) - $overflow[0]));
			}
		}
		else
		{
			if (preg_match('#\[quote=(&quot;|"|\'|)(.*)\\1\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]#i', $text))
			{
				$this->set_error($this->lang['prof_reg']['Signature quote/code']);
				return false;
			}
		}
	
		return trim($text);
	}

	/**
	@function _check_tag_order
	
	Parse text and make sure that [code] and [quote] syntax is correct
	*/
	function _check_tag_order($text)
	{
		# The maximum allowed quote depth
		$max_depth = 3;
	
		$cur_index = 0;
		$q_depth = 0;
	
		while (true)
		{
			# Look for regular code and quote tags
			$c_start = strpos($text, '[code]');
			$c_end = strpos($text, '[/code]');
			$q_start = strpos($text, '[quote]');
			$q_end = strpos($text, '[/quote]');
	
			# Look for [quote=username] style quote tags
			if (preg_match('#\[quote=(&quot;|"|\'|)(.*)\\1\]#sU', $text, $matches))
				$q2_start = strpos($text, $matches[0]);
			else
				$q2_start = 65536;
	
			# Deal with strpos() returning false when the string is not found
			# (65536 is one byte longer than the maximum post length)
			if ($c_start === false) $c_start = 65536;
			if ($c_end === false) $c_end = 65536;
			if ($q_start === false) $q_start = 65536;
			if ($q_end === false) $q_end = 65536;
	
			# If none of the strings were found
			if (min($c_start, $c_end, $q_start, $q_end, $q2_start) == 65536)
				break;
	
			# We are interested in the first quote (regardless of the type of quote)
			$q3_start = ($q_start < $q2_start) ? $q_start : $q2_start;
	
			# We found a [quote] or a [quote=username]
			if ($q3_start < min($q_end, $c_start, $c_end))
			{
				$step = ($q_start < $q2_start) ? 7 : strlen($matches[0]);
	
				$cur_index += $q3_start + $step;
	
				# Did we reach $max_depth?
				if ($q_depth == $max_depth)
					$overflow_begin = $cur_index - $step;
	
				++$q_depth;
				$text = substr($text, $q3_start + $step);
			}
	
			# We found a [/quote]
			else if ($q_end < min($q_start, $c_start, $c_end))
			{
				if ($q_depth == 0)
				{
					$this->set_error($this->lang['common']['BBCode error'].' '.$this->lang['common']['BBCode error 1']);
					return false;
				}
	
				$q_depth--;
				$cur_index += $q_end+8;
	
				# Did we reach $max_depth?
				if ($q_depth == $max_depth)
					$overflow_end = $cur_index;
	
				$text = substr($text, $q_end+8);
			}
	
			# We found a [code]
			else if ($c_start < min($c_end, $q_start, $q_end))
			{
				# Make sure there's a [/code] and that any new [code] doesn't occur before the end tag
				$tmp = strpos($text, '[/code]');
				$tmp2 = strpos(substr($text, $c_start+6), '[code]');
				if ($tmp2 !== false)
					$tmp2 += $c_start+6;
	
				if ($tmp === false || ($tmp2 !== false && $tmp2 < $tmp))
				{
					$this->set_error($this->lang['common']['BBCode error'].' '.$this->lang['common']['BBCode error 2']);
					return false;
				}
				else
					$text = substr($text, $tmp+7);
	
				$cur_index += $tmp+7;
			}
	
			# We found a [/code] (this shouldn't happen since we handle both start and end tag in the if clause above)
			else if ($c_end < min($c_start, $q_start, $q_end))
			{
				$this->set_error($this->lang['common']['BBCode error'].' '.$this->lang['common']['BBCode error 3']);
				return false;
			}
		}
	
		# If $q_depth <> 0 something is wrong with the quote syntax
		if ($q_depth)
		{
			$this->set_error($this->lang['common']['BBCode error'].' '.$this->lang['common']['BBCode error 4']);
			return false;
		}
		else if ($q_depth < 0)
		{
			$this->set_error($this->lang['common']['BBCode error'].' '.$this->lang['common']['BBCode error 5']);
			return false;
		}
	
		# If the quote depth level was higher than $max_depth we return the index for the
		# beginning and end of the part we should strip out
		if (isset($overflow_begin))
			return array($overflow_begin, $overflow_end);
		else
			return null;
	}

	/**
	@function _split_text
	
	Split text into chunks ($inside contains all text inside $start and $end, and $outside contains all text outside)
	*/
	function _split_text($text, $start, $end)
	{
		$tokens = explode($start, $text);
	
		$outside[] = $tokens[0];
	
		$num_tokens = count($tokens);
		for ($i = 1; $i < $num_tokens; ++$i)
		{
			$temp = explode($end, $tokens[$i]);
			$inside[] = $temp[0];
			$outside[] = $temp[1];
		}
	
		if ($this->config['o_indent_num_spaces'] != 8 && $start == '[code]')
		{
			$spaces = str_repeat(' ', $this->config['o_indent_num_spaces']);
			$inside = str_replace("\t", $spaces, $inside);
		}
	
		return array($inside, $outside);
	}

	/**
	@function _handle_url_tag
	
	Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
	*/
	function _handle_url_tag($url, $link = '')
	{
		$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);
		if (strpos($url, 'www.') === 0)			# If it starts with www, we add http://
			$full_url = 'http://'.$full_url;
		else if (strpos($url, 'ftp.') === 0)	# Else if it starts with ftp, we add ftp://
			$full_url = 'ftp://'.$full_url;
		else if (!preg_match('#^([a-z0-9]{3,6})://#', $url, $bah)) 	# Else if it doesn't start with abcdef://, we add http://
			$full_url = 'http://'.$full_url;
	
		# Ok, not very pretty :-)
		$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' &hellip; '.substr($url, -10) : $url) : stripslashes($link);
	
		return '<a href="'.$full_url.'">'.$link.'</a>';
	}

	/**
	@function _handle_img_tag
	Turns an URL from the [img] tag into an <img> tag or a <a href...> tag
	*/
	function _handle_img_tag($url, $is_signature=false)
	{
		$img_tag = '<a href="'.$url.'">&lt;'.$this->lang['common']['Image link'].'&gt;</a>';
	
		if ($is_signature && $this->user['show_img_sig'] != '0')
			$img_tag = '<img class="sigimage" src="'.$url.'" alt="'.htmlspecialchars($url).'" />';
		else if (!$is_signature && $this->user['show_img'] != '0')
			$img_tag = '<img class="postimg" src="'.$url.'" alt="'.htmlspecialchars($url).'" />';
	
		return $img_tag;
	}

	/**
	@function _do_bbcode
	
	Convert BBCodes to their HTML equivalent
	*/
	function _do_bbcode($text)
	{
		if (strpos($text, 'quote') !== false)
		{
			$text = str_replace('[quote]', '</p><blockquote><div class="incqbox"><p>', $text);
			$text = preg_replace('#\[quote=(&quot;|"|\'|)(.*)\\1\]#seU', '"</p><blockquote><div class=\"incqbox\"><h4>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), \'$2\')." ".$this->lang[\'common\'][\'wrote\'].":</h4><p>"', $text);
			$text = preg_replace('#\[\/quote\]\s*#', '</p></div></blockquote><p>', $text);
		}
	
		if ($this->mod_puntoolbar)
		{
			$pattern = array('#\[b\](.*?)\[/b\]#s',
							 '#\[i\](.*?)\[/i\]#s',
							 '#\[u\](.*?)\[/u\]#s',
							 '#\[s\](.*?)\[/s\]#s',
							 '#\[q\](.*?)\[/q\]#s',
							 '#\[c\](.*?)\[/c\]#s',
							 '#\[url\]([^\[]*?)\[/url\]#e',
							 '#\[url=([^\[]*?)\](.*?)\[/url\]#e',
							 '#\[nospam\]([^\[]*?)\[/nospam\]#e',
							 '#\[nospam=([^\[]*?)\](.*?)\[/nospam\]#e',
							 '#\[email\]([^\[]*?)\[/email\]#',
							 '#\[email=([^\[]*?)\](.*?)\[/email\]#',
							 '#\[acronym\]([^\[]*?)\[/acronym\]#',
							 '#\[acronym=([^\[]*?)\](.*?)\[/acronym\]#',
							 '#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s',
							 '#\[---\]#s',
							 '#\[left\](.*?)\[/left\]#s',
							 '#\[right\](.*?)\[/right\]#s',
							 '#\[center\](.*?)\[/center\]#s',
							 '#\[justify\](.*?)\[/justify\]#s');
		
			$replace = array('<strong>$1</strong>',
							 '<em>$1</em>',
							 '<ins>$1</ins>',
							 '<del>$1</del>',
							 '<q>$1</q>',
							 '<code>$1</code>',
							 'handle_url_tag(\'$1\')',
							 'handle_url_tag(\'$1\', \'$2\')',
							 'nospam_tag(\'$1\')',
							 'nospam_tag(\'$1\', \'$2\')',
							 '<a href="mailto:$1">$1</a>',
							 '<a href="mailto:$1">$2</a>',
							 '<acronym>$1</acronym>',
							 '<acronym title="$1">$2</acronym>',
							 '<span style="color: $1">$2</span>',
							 '</p><hr /><p>',
							 '</p><p style="text-align:left">$1</p><p>',
							 '</p><p style="text-align:right">$1</p><p>',
							 '</p><p style="text-align:center">$1</p><p>',
							 '</p><p style="text-align:justify">$1</p><p>');
		}
		else {
			$pattern = array('#\[b\](.*?)\[/b\]#s',
							 '#\[i\](.*?)\[/i\]#s',
							 '#\[u\](.*?)\[/u\]#s',
							 '#\[url\]([^\[]*?)\[/url\]#e',
							 '#\[url=([^\[]*?)\](.*?)\[/url\]#e',
							 '#\[email\]([^\[]*?)\[/email\]#',
							 '#\[email=([^\[]*?)\](.*?)\[/email\]#',
							 '#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s');
		
			$replace = array('<strong>$1</strong>',
							 '<em>$1</em>',
							 '<span class="bbu">$1</span>',
							 '$this->_handle_url_tag(\'$1\')',
							 '$this->_handle_url_tag(\'$1\', \'$2\')',
							 '<a href="mailto:$1">$1</a>',
							 '<a href="mailto:$1">$2</a>',
							 '<span style="color: $1">$2</span>');
		}
		
		# This thing takes a while! :)
		$text = preg_replace($pattern, $replace, $text);
	
		return $text;
	}
	
	# <!-- PunToolBar compatibility
	
	/**
	@function nospam_tag
	
	Parse nospam tag
	*/
	function nospam_tag($adresse, $text='')
	{
		global $pun_user;
		
		$enc_adress = __antiSpam($adresse);
		$adresse = str_replace('@', ' [A] ', $adresse);
		$adresse = str_replace('.', ' [.] ', $adresse);
		
		if ($text == '')
			$text = $adresse;
			
		return '<a href="mailto:'.$enc_adress.'">'.$text.'</a>';
	}
	# Antispam (Jérôme Lipowicz) from wiki2xhtml class
	function __antiSpam($str)
	{
		$encoded = bin2hex($str);
		$encoded = chunk_split($encoded, 2, '%');
		$encoded = '%'.substr($encoded, 0, strlen($encoded) - 1);
		return $encoded;
	}
	
	# PunToolBar compatibility -->
	
	/**
	@function _do_clickable
	
	Make hyperlinks clickable
	*/
	function _do_clickable($text)
	{
		$text = ' '.$text;
	
		$text = preg_replace('#([\s\(\)])(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^"\s\(\)<\[]*)?)#ie', '\'$1\'.$this->_handle_url_tag(\'$2://$3\')', $text);
		$text = preg_replace('#([\s\(\)])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^"\s\(\)<\[]*)?)#ie', '\'$1\'.$this->_handle_url_tag(\'$2.$3\', \'$2.$3\')', $text);
	
		return substr($text, 1);
	}

	/**
	@function _do_smilies
	
	Convert a series of smilies to images
	*/
	function _do_smilies($text)
	{
		static $smiley_text, $smiley_img;
		
		if (!isset($smiley_text))
			require $this->pun_root.'include/parser.php';
	
		$text = ' '.$text.' ';
	
		$num_smilies = count($smiley_text);
		for ($i = 0; $i < $num_smilies; ++$i)
			$text = preg_replace("#(?<=.\W|\W.|^\W)".preg_quote($smiley_text[$i], '#')."(?=.\W|\W.|\W$)#m", '$1<img src="'.$this->config['o_base_url'].'/img/smilies/'.$smiley_img[$i].'" alt="'.substr($smiley_img[$i], 0, strrpos($smiley_img[$i], '.')).'" />$2', $text);
	
		return substr($text, 1, -1);
	}


	/** Search indexes methods
	
	Yes we have to duplicate this part of PunBB too ! 
	A class... Please, give us somes classes... :(
	----------------------------------------------------------*/

	/**
	@function _split_words
	
	"Cleans up" a text string and returns an array of unique words
	 This function depends on the current locale setting
	*/
	function _split_words($text)
	{
		static $noise_match, $noise_replace, $stopwords;
	
		if (empty($noise_match))
		{
			if ($this->mod_puntoolbar)
			{
				$noise_match = array('[quote', '[code', '[url', '[img', '[email', '[color', '[colour', '[acronym', '[nospam', 'quote]', 'code]', 'url]', 'img]', 'email]', 'color]', 'colour]', 'acronym]', 'nospam]', '^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '+', '[', ']', '{', '}', ':', '\\', '/', '=', '#', ';', '!', '*');
				$noise_replace = array('',       '',      '',     '',     '',       '',       '',        '',       '',      '',     '',     '',       '',       '',        ' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',   ' ', ' ', ' ', ' ', '',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '' ,  ' ', ' ', ' ', ' ', ' ', ' ');
			}
			else {
				$noise_match = 		array('[quote', '[code', '[url', '[img', '[email', '[color', '[colour', 'quote]', 'code]', 'url]', 'img]', 'email]', 'color]', 'colour]', '^', '$', '&', '(', ')', '<', '>', '`', '\'', '"', '|', ',', '@', '_', '?', '%', '~', '+', '[', ']', '{', '}', ':', '\\', '/', '=', '#', ';', '!', '*');
				$noise_replace =	array('',       '',      '',     '',     '',       '',       '',        '',       '',      '',     '',     '',       '',       '',        ' ', ' ', ' ', ' ', ' ', ' ', ' ', '',  '',   ' ', ' ', ' ', ' ', '',  ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', '' ,  ' ', ' ', ' ', ' ', ' ', ' ');
			}				
	
			$stopwords = (array)@file($this->pun_root.'lang/'.$this->user['language'].'/stopwords.txt');
			$stopwords = array_map('trim', $stopwords);
		}
	
		# Clean up
		$patterns[] = '#&[\#a-z0-9]+?;#i';
		$patterns[] = '#\b[\w]+:\/\/[a-z0-9\.\-]+(\/[a-z0-9\?\.%_\-\+=&\/~]+)?#';
		$patterns[] = '#\[\/?[a-z\*=\+\-]+(\:?[0-9a-z]+)?:[a-z0-9]{10,}(\:[a-z0-9]+)?=?.*?\]#';
		$text = preg_replace($patterns, ' ', ' '.strtolower($text).' ');
	
		# Filter out junk
		$text = str_replace($noise_match, $noise_replace, $text);
	
		# Strip out extra whitespace between words
		$text = trim(preg_replace('#\s+#', ' ', $text));
	
		# Fill an array with all the words
		$words = explode(' ', $text);
	
		if (!empty($words))
		{
			while (list($i, $word) = @each($words))
			{
				$words[$i] = trim($word, '.');
				$num_chars = $this->strlen($word);
	
				if ($num_chars < 3 || $num_chars > 20 || in_array($word, $stopwords))
					unset($words[$i]);
			}
		}
	
		return array_unique($words);
	}

	/**
	@function _update_search_index
	
	Updates the search index with the contents of $post_id (and $subject)
	*/
	function _update_search_index($mode, $post_id, $message, $subject = null)
	{
		# Split old and new post/subject to obtain array of 'words'
		$words_message = $this->_split_words($message);
		$words_subject = ($subject) ? $this->_split_words($subject) : array();
	
		if ($mode == 'edit')
		{
			$result = $this->db->query('SELECT w.id, w.word, m.subject_match FROM '.$this->db->prefix.'search_words AS w INNER JOIN '.$this->db->prefix.'search_matches AS m ON w.id=m.word_id WHERE m.post_id='.$post_id, true) or $this->fatal_error('Unable to fetch search index words', __FILE__, __LINE__, $this->db->error());
	
			# Declare here to stop array_keys() and array_diff() from complaining if not set
			$cur_words['post'] = array();
			$cur_words['subject'] = array();
	
			while ($row = $this->db->fetch_row($result))
			{
				$match_in = ($row[2]) ? 'subject' : 'post';
				$cur_words[$match_in][$row[1]] = $row[0];
			}
	
			$this->db->free_result($result);
	
			$words['add']['post'] = array_diff($words_message, array_keys($cur_words['post']));
			$words['add']['subject'] = array_diff($words_subject, array_keys($cur_words['subject']));
			$words['del']['post'] = array_diff(array_keys($cur_words['post']), $words_message);
			$words['del']['subject'] = array_diff(array_keys($cur_words['subject']), $words_subject);
		}
		else
		{
			$words['add']['post'] = $words_message;
			$words['add']['subject'] = $words_subject;
			$words['del']['post'] = array();
			$words['del']['subject'] = array();
		}
	
		unset($words_message);
		unset($words_subject);
	
		# Get unique words from the above arrays
		$unique_words = array_unique(array_merge($words['add']['post'], $words['add']['subject']));
	
		if (!empty($unique_words))
		{
			$result = $this->db->query('SELECT id, word FROM '.$this->db->prefix.'search_words WHERE word IN('.implode(',', preg_replace('#^(.*)$#', '\'\1\'', $unique_words)).')', true) or $this->fatal_error('Unable to fetch search index words', __FILE__, __LINE__, $this->db->error());
	
			$word_ids = array();
			while ($row = $this->db->fetch_row($result))
				$word_ids[$row[1]] = $row[0];
	
			$this->db->free_result($result);
	
			$new_words = array_diff($unique_words, array_keys($word_ids));
			unset($unique_words);
	
			if (!empty($new_words))
			{
				switch ($this->conf['db_type'])
				{
					case 'mysql':
					case 'mysqli':
						$this->db->query('INSERT INTO '.$this->db->prefix.'search_words (word) VALUES'.implode(',', preg_replace('#^(.*)$#', '(\'\1\')', $new_words))) or $this->fatal_error('Unable to insert search index words', __FILE__, __LINE__, $this->db->error());
						break;
	
					default:
						while (list(, $word) = @each($new_words))
							$this->db->query('INSERT INTO '.$this->db->prefix.'search_words (word) VALUES(\''.$word.'\')') or $this->fatal_error('Unable to insert search index words', __FILE__, __LINE__, $this->db->error());
						break;
				}
			}
	
			unset($new_words);
		}
	
		# Delete matches (only if editing a post)
		while (list($match_in, $wordlist) = @each($words['del']))
		{
			$subject_match = ($match_in == 'subject') ? 1 : 0;
	
			if (!empty($wordlist))
			{
				$sql = '';
				while (list(, $word) = @each($wordlist))
					$sql .= (($sql != '') ? ',' : '').$cur_words[$match_in][$word];
	
				$this->db->query('DELETE FROM '.$this->db->prefix.'search_matches WHERE word_id IN('.$sql.') AND post_id='.$post_id.' AND subject_match='.$subject_match) or $this->fatal_error('Unable to delete search index word matches', __FILE__, __LINE__, $this->db->error());
			}
		}
	
		# Add new matches
		while (list($match_in, $wordlist) = @each($words['add']))
		{
			$subject_match = ($match_in == 'subject') ? 1 : 0;
	
			if (!empty($wordlist))
				$this->db->query('INSERT INTO '.$this->db->prefix.'search_matches (post_id, word_id, subject_match) SELECT '.$post_id.', id, '.$subject_match.' FROM '.$this->db->prefix.'search_words WHERE word IN('.implode(',', preg_replace('#^(.*)$#', '\'\1\'', $wordlist)).')') or $this->fatal_error('Unable to insert search index word matches', __FILE__, __LINE__, $this->db->error());
		}
	
		unset($words);
	}

	/**
	@fucntion _strip_search_index
	
	Strip search index of indexed words in $post_ids
	*/
	function _strip_search_index($post_ids)
	{
		switch ($this->conf['db_type'])
		{
			case 'mysql':
			case 'mysqli':
			{
				$result = $this->db->query('SELECT word_id FROM '.$this->db->prefix.'search_matches WHERE post_id IN('.$post_ids.') GROUP BY word_id') or $this->fatal_error('Unable to fetch search index word match', __FILE__, __LINE__, $this->db->error());
	
				if ($this->db->num_rows($result))
				{
					$word_ids = '';
					while ($row = $this->db->fetch_row($result))
						$word_ids .= ($word_ids != '') ? ','.$row[0] : $row[0];
	
					$result = $this->db->query('SELECT word_id FROM '.$this->db->prefix.'search_matches WHERE word_id IN('.$word_ids.') GROUP BY word_id HAVING COUNT(word_id)=1') or $this->fatal_error('Unable to fetch search index word match', __FILE__, __LINE__, $this->db->error());
	
					if ($this->db->num_rows($result))
					{
						$word_ids = '';
						while ($row = $this->db->fetch_row($result))
							$word_ids .= ($word_ids != '') ? ','.$row[0] : $row[0];
	
						$this->db->query('DELETE FROM '.$this->db->prefix.'search_words WHERE id IN('.$word_ids.')') or $this->fatal_error('Unable to delete search index word', __FILE__, __LINE__, $this->db->error());
					}
				}
	
				break;
			}
	
			default:
				$this->db->query('DELETE FROM '.$this->db->prefix.'search_words WHERE id IN(SELECT word_id FROM '.$this->db->prefix.'search_matches WHERE word_id IN(SELECT word_id FROM '.$this->db->prefix.'search_matches WHERE post_id IN('.$post_ids.') GROUP BY word_id) GROUP BY word_id HAVING COUNT(word_id)=1)') or $this->fatal_error('Unable to delete from search index', __FILE__, __LINE__, $this->db->error());
				break;
		}
	
		$this->db->query('DELETE FROM '.$this->db->prefix.'search_matches WHERE post_id IN('.$post_ids.')') or $this->fatal_error('Unable to delete search index word match', __FILE__, __LINE__, $this->db->error());
	}


	/** Private utilities methods
	----------------------------------------------------------*/

	/**
	@function _update_forum
	 
	Update posts, topics, last_post, last_post_id and last_poster for a forum (redirect topics are not included)
	 
	@param	integer	forum_id		ID of the forum to update
	@return void
	*/
	function _update_forum($forum_id)
	{
		$result = $this->db->query('SELECT COUNT(id), SUM(num_replies) FROM '.$this->db->prefix.'topics WHERE moved_to IS NULL AND forum_id='.$forum_id) or $this->fatal_error('Unable to fetch forum topic count', __FILE__, __LINE__, $this->db->error());
		list($num_topics, $num_posts) = $this->db->fetch_row($result);
	
		$num_posts = $num_posts + $num_topics;		# $num_posts is only the sum of all replies (we have to add the topic posts)
	
		$result = $this->db->query('SELECT last_post, last_post_id, last_poster FROM '.$this->db->prefix.'topics WHERE forum_id='.$forum_id.' AND moved_to IS NULL ORDER BY last_post DESC LIMIT 1') or $this->fatal_error('Unable to fetch last_post/last_post_id/last_poster', __FILE__, __LINE__, $this->db->error());
		if ($this->db->num_rows($result))		# There are topics in the forum
		{
			list($last_post, $last_post_id, $last_poster) = $this->db->fetch_row($result);
	
			$this->db->query('UPDATE '.$this->db->prefix.'forums SET num_topics='.$num_topics.', num_posts='.$num_posts.', last_post='.$last_post.', last_post_id='.$last_post_id.', last_poster=\''.$this->db->escape($last_poster).'\' WHERE id='.$forum_id) or $this->fatal_error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $this->db->error());
		}
		else	# There are no topics
			$this->db->query('UPDATE '.$this->db->prefix.'forums SET num_topics=0, num_posts=0, last_post=NULL, last_post_id=NULL, last_poster=NULL WHERE id='.$forum_id) or $this->fatal_error('Unable to update last_post/last_post_id/last_poster', __FILE__, __LINE__, $this->db->error());
	}
	
	/**
	@function _random_pass
	
	Generate a random password of length $len
	
	@param	integer	len		Length of password to generate
	@return	string
	*/
	function _random_pass($len)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		$password = '';
		for ($i = 0; $i < $len; ++$i)
			$password .= substr($chars, (mt_rand() % strlen($chars)), 1);

		return $password;
	}

	/**
	@function _hash
	
	Compute a hash of $str
	Uses sha1() if available. If not, SHA1 through mhash() if available. If not, fall back on md5().
	
	@param	string	str		String to hash
	@return	string
	*/
	function _hash($str)
	{
		if (function_exists('sha1'))	# Only in PHP 4.3.0+
			return sha1($str);
		else if (function_exists('mhash'))	# Only if Mhash library is loaded
			return bin2hex(mhash(MHASH_SHA1, $str));
		else
			return md5($str);
	}

	/**
	@function _get_title
	
	Determines the correct title for $user
	$user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
	@return	string
	*/
	function _get_title($user)
	{
		static $ban_list, $pun_ranks;
	
		# If not already built in a previous call, build an array of lowercase banned usernames
		if (empty($ban_list))
		{
			$ban_list = array();
	
			foreach ($this->bans as $cur_ban)
				$ban_list[] = strtolower($cur_ban['username']);
		}
	
		# If not already loaded in a previous call, load the cached ranks
		if ($this->config['o_ranks'] == '1' && empty($pun_ranks))
		{
			if (file_exists($this->pun_root.'cache/cache_ranks.php'))
				require $this->pun_root.'cache/cache_ranks.php';
			
			if (!defined('PUN_RANKS_LOADED'))
			{
				$this->_generate_ranks_cache();
				require $this->pun_root.'cache/cache_ranks.php';
			}
		}
	
		# If the user has a custom title
		if ($user['title'] != '')
			$user_title = $this->htmlspecialchars($user['title']);
		# If the user is banned
		else if (in_array(strtolower($user['username']), $ban_list))
			$user_title = $this->lang['common']['Banned'];
		# If the user group has a default user title
		else if ($user['g_user_title'] != '')
			$user_title = $this->htmlspecialchars($user['g_user_title']);
		# If the user is a guest
		else if ($user['g_id'] == PUN_GUEST)
			$user_title = $this->lang['common']['Guest'];
		else
		{
			# Are there any ranks?
			if ($this->config['o_ranks'] == '1' && !empty($pun_ranks))
			{
				@reset($pun_ranks);
				while (list(, $cur_rank) = @each($pun_ranks))
				{
					if (intval($user['num_posts']) >= $cur_rank['min_posts'])
						$user_title = $this->htmlspecialchars($cur_rank['rank']);
				}
			}
	
			# If the user didn't "reach" any rank (or if ranks are disabled), we assign the default
			if (!isset($user_title))
				$user_title = $this->lang['common']['Member'];
		}
	
		return $user_title;
	}

	/**
	@function _delete_topic
	 
	Delete a topic and all of it's posts
	 
	@param	integer	topic_id		Topic ID to delete
	@return void
	*/
	function _delete_topic($topic_id)
	{
		$topic_id = intval($topic_id);
		
		# Delete the topic and any redirect topics
		$this->db->query('DELETE FROM '.$this->db->prefix.'topics WHERE id='.$topic_id.' OR moved_to='.$topic_id) or $this->fatal_error('Unable to delete topic', __FILE__, __LINE__, $this->db->error());
	
		# Create a list of the post ID's in this topic
		$post_ids = '';
		$row = $this->db->select('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id='.$topic_id) or $this->fatal_error('Unable to fetch posts', __FILE__, __LINE__, $this->db->error());
		
		while ($row->fetch())
			$post_ids .= ($post_ids != '') ? ','.$row->f('id') : $row->f('id');
	
		# Make sure we have a list of post ID's
		if ($post_ids != '')
		{
			$this->_strip_search_index($post_ids);
	
			# Delete posts in topic
			$this->db->query('DELETE FROM '.$this->db->prefix.'posts WHERE topic_id='.$topic_id) or $this->fatal_error('Unable to delete posts', __FILE__, __LINE__, $this->db->error());
		}
	
		# Delete any subscriptions for this topic
		$this->db->query('DELETE FROM '.$this->db->prefix.'subscriptions WHERE topic_id='.$topic_id) or $this->fatal_error('Unable to delete subscriptions', __FILE__, __LINE__, $this->db->error());
	}

	/**
	@function _delete_post
	 
	Delete a single post
	 
	@param	integer	post_id			Post ID to delete
	@param	integer	topic_id		Topic ID of post to delete
	@return void
	*/
	function _delete_post($post_id, $topic_id)
	{
		$post_id = intval($post_id);
		$topic_id = intval($topic_id);
		
		$rs = $this->db->select('SELECT id, poster, posted FROM '.$this->db->prefix.'posts WHERE topic_id='.$topic_id.' ORDER BY id DESC LIMIT 2') or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
		
		$rs->moveStart();
		$last_id = $rs->f('id');
		$rs->moveNext();
		$second_last_id = $rs->f('id');
		$second_poster = $rs->f('poster');
		$second_posted = $rs->f('posted');
	
		# Delete the post
		$this->db->query('DELETE FROM '.$this->db->prefix.'posts WHERE id='.$post_id) or $this->fatal_error('Unable to delete post', __FILE__, __LINE__, $this->db->error());
		
		$this->_strip_search_index($post_id);
	
		# Count number of replies in the topic
		$rs = $this->db->select('SELECT COUNT(id) AS num_replies FROM '.$this->db->prefix.'posts WHERE topic_id='.$topic_id) or $this->fatal_error('Unable to fetch post count for topic', __FILE__, __LINE__, $this->db->error());
		$num_replies = $rs->f('num_replies') - 1;
	
		# If the message we deleted is the most recent in the topic (at the end of the topic)
		if ($last_id == $post_id)
		{
			# If there is a $second_last_id there is more than 1 reply to the topic
			if (!empty($second_last_id))
			{
				$this->db->query('UPDATE '.$this->db->prefix.'topics SET last_post='.$second_posted.', last_post_id='.$second_last_id.', last_poster=\''.$this->db->escape($second_poster).'\', num_replies='.$num_replies.' WHERE id='.$topic_id) or $this->fatal_error('Unable to update topic', __FILE__, __LINE__, $this->db->error());
			}
			else {
				# We deleted the only reply, so now last_post/last_post_id/last_poster is posted/id/poster from the topic itself
				$this->db->query('UPDATE '.$this->db->prefix.'topics SET last_post=posted, last_post_id=id, last_poster=poster, num_replies='.$num_replies.' WHERE id='.$topic_id) or $this->fatal_error('Unable to update topic', __FILE__, __LINE__, $this->db->error());
			}
		}
		else {
			# Otherwise we just decrement the reply counter
			$this->db->query('UPDATE '.$this->db->prefix.'topics SET num_replies='.$num_replies.' WHERE id='.$topic_id) or $this->fatal_error('Unable to update topic', __FILE__, __LINE__, $this->db->error());
		}
	}


	/** Send email throught an SMTP server
	----------------------------------------------------------*/

	function _server_parse($socket, $expected_response)
	{
		$server_response = '';
		while (substr($server_response, 3, 1) != ' ')
		{
			if (!($server_response = fgets($socket, 256)))
				$this->fatal_error('Couldn\'t get mail server response codes. Please contact the forum administrator.', __FILE__, __LINE__);
		}
	
		if (!(substr($server_response, 0, 3) == $expected_response))
			$this->fatal_error('Unable to send e-mail. Please contact the forum administrator with the following error message reported by the SMTP server: "'.$server_response.'"', __FILE__, __LINE__);
	}

	function _smtp_mail($to, $subject, $message, $headers = '')
	{
		$recipients = explode(',', $to);
	
		# Are we using port 25 or a custom port?
		if (strpos($this->config['o_smtp_host'], ':') !== false)
			list($smtp_host, $smtp_port) = explode(':', $this->config['o_smtp_host']);
		else {
			$smtp_host = $this->config['o_smtp_host'];
			$smtp_port = 25;
		}
	
		if (!($socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15)))
			$this->fatal_error('Could not connect to smtp host "'.$this->config['o_smtp_host'].'" ('.$errno.') ('.$errstr.')', __FILE__, __LINE__);
	
		$this->_server_parse($socket, '220');
	
		if ($this->config['o_smtp_user'] != '' && $this->config['o_smtp_pass'] != '')
		{
			fwrite($socket, 'EHLO '.$smtp_host."\r\n");
			$this->_server_parse($socket, '250');
	
			fwrite($socket, 'AUTH LOGIN'."\r\n");
			$this->_server_parse($socket, '334');
	
			fwrite($socket, base64_encode($this->config['o_smtp_user'])."\r\n");
			$this->_server_parse($socket, '334');
	
			fwrite($socket, base64_encode($this->config['o_smtp_pass'])."\r\n");
			$this->_server_parse($socket, '235');
		}
		else
		{
			fwrite($socket, 'HELO '.$smtp_host."\r\n");
			$this->_server_parse($socket, '250');
		}
	
		fwrite($socket, 'MAIL FROM: <'.$this->config['o_webmaster_email'].'>'."\r\n");
		$this->_server_parse($socket, '250');
	
		$to_header = 'To: ';
	
		@reset($recipients);
		while (list(, $email) = @each($recipients))
		{
			fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
			$this->_server_parse($socket, '250');
	
			$to_header .= '<'.$email.'>, ';
		}
	
		fwrite($socket, 'DATA'."\r\n");
		$this->_server_parse($socket, '354');
	
		fwrite($socket, 'Subject: '.$subject."\r\n".$to_header."\r\n".$headers."\r\n\r\n".$message."\r\n");
	
		fwrite($socket, '.'."\r\n");
		$this->_server_parse($socket, '250');
	
		fwrite($socket, 'QUIT'."\r\n");
		fclose($socket);
	
		return true;
	}


	/** Intern date formating
	----------------------------------------------------------*/

	/**
	@function _dates_callback
	@author 	Olivier Meunier and contributors
	
	Perform date format
	
	@param	array	args
	*/
	function _dates_callback($args)
	{
		$b = array(1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
		7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec');
		
		$B = array(1=>'January',2=>'February',3=>'March',4=>'April',
		5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',
		10=>'October',11=>'November',12=>'December');
		
		$a = array(1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',
		6=>'Sat',0=>'Sun');
		
		$A = array(1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',
		5=>'Friday',6=>'Saturday',0=>'Sunday');
		
		return punsapi_core::_locales_dates(${$args[1]}[(integer) $args[2]]);
	}

	/**
	@function _locales_dates
	@author 	Olivier Meunier and contributors
	
	Replace string to the localized equivalent
	
	@param	array	args
	*/
	function _locales_dates($str)
	{
		return (!empty($GLOBALS['locales_dates'][$str])) ? $GLOBALS['locales_dates'][$str] : $str;
	}


	/** Old functions for backward compatibility
	----------------------------------------------------------*/
	
	/**
	@function deprecated
	
	Display a fatal error for deprecated functions
	*/
	function deprecated($old_name,$new_name)
	{
		$this->fatal_error('Use of deprecated "<strong>'.$old_name.'</strong>" function. This method may not be supported in future versions of PunSAPI. Please update your scripts to use "<strong>'.$new_name.'</strong>" instead');
	}
	
	/** Old Functions which are now deprecated */
	
	function set_post() { # deprecated in 0.2
		$this->deprecated('set_post','add_post');
	}

	function get_posts_topic() { # deprecated in 0.2
		$this->deprecated('get_posts_topic','get_posts');
	}

	function resetError() { # deprecated in 0.2
		$this->deprecated('resetError','reset_error');
	}

	function hasError() { # deprecated in 0.2
		$this->deprecated('hasError','has_error');
	}

	function setError() { # deprecated in 0.2
		$this->deprecated('setError','set_error');
	}

	function error() { # deprecated in 0.2
		$this->deprecated('error','get_error');
	}

	function fatalError() { # deprecated in 0.2
		$this->deprecated('fatalError','fatal_error');
	}

} /** class punsapi_core */

?>