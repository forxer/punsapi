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
@class		punsapi
@version	0.3 for PunBB 1.2.x
@author 	Vincent Garnier

A "toolbox class" to play with PunBB on your website :)

This is an adaptation of various PunBB scripts to the oriented 
object model, so the copyright returns to Rickard Andersson.

This class contain public methods, we used extends mechanism 
only to separate pubic form core methods.
*/

require dirname(__FILE__).'/core.php';

class punsapi extends punsapi_core
{
	/** Boards infos
	----------------------------------------------------------*/

	/**
	@function config
	
	Return a config value
	
	@param	string	name		Line to return
	@return string
	*/
	function config($name)
	{
		if (!array_key_exists($name, $this->config))
			return false;
		
		return $this->config[$name];
	}

	/**
	@function get_board_title
	
	Display the board title
	
	@param	boolean	return		Return value, not display it (false)
	@return void/string
	*/
	function get_board_title($return=false)
	{
		if ($return)
			return $this->htmlspecialchars($this->config['o_board_title']);
		
		echo $this->htmlspecialchars($this->config['o_board_title']);
		return true;
	}

	/**
	@function get_board_desc
	
	Display the board description
	
	@param	boolean	return		Return value, not display it (false)
	@return void/string
	*/
	function get_board_desc($return=false)
	{
		if ($return)
			return $this->config['o_board_desc'];
		
		echo $this->config['o_board_desc'];
		return true;
	}

	/**
	@function get_board_url
	
	Display the board base url
	
	@param	boolean	return		Return value, not display it (false)
	@return void/string
	*/
	function get_board_url($return=false)
	{
		if ($return)
			return $this->config['o_base_url'];
		
		echo $this->config['o_base_url'];
		return true;
	}

	/**
	@function get_board_lang
	
	Display the board default language
	
	@param	boolean	return		Return value, not display it (false)
	@return void/string
	*/
	function get_board_lang($return=false)
	{
		if ($return)
			return $this->config['o_default_lang'];
		
		echo $this->config['o_default_lang'];
		return true;
	}

	/**
	@function get_board_style
	
	Display the board default style
	
	@param	boolean	return		Return value, not display it (false)
	@return void/string
	*/
	function get_board_style($return=false)
	{
		if ($return)
			return $this->config['o_default_style'];
		
		echo $this->config['o_default_style'];
		return true;
	}


	/** Users infos
	----------------------------------------------------------*/

	/**
	@function user
	
	Return a user value
	
	@param	string	name		Line to return
	@param	integer	user_id		User identifier ('')
	@return string
	*/
	function user($name,$user_id='')
	{
		$user = $this->get_user_infos($user_id);

		if (!array_key_exists($name, $user))
			return false;
		
		return $user[$name];
	}
	
	/**
	@function get_user_infos
	
	Returns an array with information on member user_id. If user_id 
	is not specified, informations on the member currently logged in 
	will be returned.
	
	@param	integer	user_id		User identifier ('')
	@return array
	*/
	function get_user_infos($user_id='')
	{
		$user_id = intval($user_id);
		if ($user_id === 0 || $this->user['id'] === $user_id)
			return $this->user;
		
		if ($cache = $this->_get_cache('get_user_infos', $user_id))
			return $cache;
		else {
			$result = $this->db->query('SELECT u.*, g.*, o.logged, o.idle FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'groups AS g ON u.group_id=g.g_id LEFT JOIN '.$this->db->prefix.'online AS o ON o.user_id=u.id WHERE u.id='.$user_id) or $this->fatal_error('Unable to fetch user information', __FILE__, __LINE__, $this->db->error());
			$user = $this->db->fetch_assoc($result);
			
			$this->_set_cache('get_user_infos', $user_id, $user);
			return $user;
		}
	}
	
	/**
	@function add_user
	
	Add a user
	
	@param	string	username		The username
	@param	string	email1			The user email
	@param	string	pwd1			The user password ('')
	@param	string	pwd2			Password confirmation ('')
	@param	string	email2			Email confirmation ('')
	@param	string	language		The user language ('')
	@param	string	timezone		The user timezone ('')
	@param	boolean	save_pass		Save pass between visits (true)
	@param	integer	email_setting	Privacy preference (1)
	@return boolean
	*/
	function add_user($username, $email1, $pwd1='', $pwd2='', $email2='', $language='', $timezone='', $save_pass=true, $email_setting=1)
	{
		$this->load_lang('prof_reg');
		$this->load_lang('register');

		# Check that someone from this IP didn't register a user within the last hour (DoS prevention)
		$result = $this->db->query('SELECT 1 FROM '.$this->db->prefix.'users WHERE registration_ip=\''.$_SERVER['REMOTE_ADDR'].'\' AND registered>'.(time() - 3600)) or $this->fatal_error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());

		if ($this->db->num_rows($result))
		{
			$this->set_error('A new user was registered with the same IP address as you within the last hour. To prevent registration flooding, at least an hour has to pass between registrations from the same IP. Sorry for the inconvenience.');
			return false;
		}

		$username = $this->trim($username);
		$email1 = strtolower(trim($email1));

		if ($this->config['o_regs_verify'] == '1')
		{
			$email2 = strtolower(trim($email2));
	
			$password1 = random_pass(8);
			$password2 = $password1;
		}
		else {
			$password1 = trim($pwd1);
			$password2 = trim($pwd2);
		}

		# Convert multiple whitespace characters into one (to prevent people from registering with indistinguishable usernames)
		$username = preg_replace('#\s+#s', ' ', $username);
		
		# Validate username and passwords
		if (strlen($username) < 2)
		{
			$this->set_error($this->lang['prof_reg']['Username too short']);
			return false;
		}
		else if ($this->strlen($username) > 25)	// This usually doesn't happen since the form element only accepts 25 characters
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		else if (strlen($password1) < 4)
		{
			$this->set_error($this->lang['prof_reg']['Pass too short']);
			return false;
		}
		else if ($password1 != $password2)
		{
			$this->set_error($this->lang['prof_reg']['Pass not match']);
			return false;
		}
		else if (!strcasecmp($username, 'Guest') || !strcasecmp($username, $this->lang['common']['Guest']))
		{
			$this->set_error($this->lang['prof_reg']['Username guest']);
			return false;
		}
		else if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $username))
		{
			$this->set_error($this->lang['prof_reg']['Username IP']);
			return false;
		}
		else if ((strpos($username, '[') !== false || strpos($username, ']') !== false) && strpos($username, '\'') !== false && strpos($username, '"') !== false)
		{
			$this->set_error($this->lang['prof_reg']['Username reserved chars']);
			return false;
		}
		else if (preg_match('#\[b\]|\[/b\]|\[u\]|\[/u\]|\[i\]|\[/i\]|\[color|\[/color\]|\[quote\]|\[quote=|\[/quote\]|\[code\]|\[/code\]|\[img\]|\[/img\]|\[url|\[/url\]|\[email|\[/email\]|\[s\]|\[/s\]|\[nospam|\[/nospam\]|\[acronym|\[/acronym\]|\[left|\[/left\]|\[right|\[/right\]|\[center|\[/center\]|\[justify|\[/justify\]|\[small|\[/small\]|\[large|\[/large\]|\[sup|\[/sup\]|\[sub|\[/sub\]|\[---\]#i', $username))
		{
			$this->set_error($this->lang['prof_reg']['Username BBCode']);
			return false;
		}

		# Check username for any censored words
		if ($this->config['o_censoring'] == '1')
		{
			# If the censored username differs from the username
			if ($this->censor_words($username) != $username)
			{
				$this->set_error($this->lang['register']['Username censor']);
				return false;
			}
		}

		# Check that the username (or a too similar username) is not already registered
		$result = $this->db->query('SELECT username FROM '.$this->db->prefix.'users WHERE UPPER(username)=UPPER(\''.$this->db->escape($username).'\') OR UPPER(username)=UPPER(\''.$this->db->escape(preg_replace('/[^\w]/', '', $username)).'\')') or $this->fatal_error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());

		if ($this->db->num_rows($result))
		{
			$busy = $this->db->result($result);
			$this->set_error($this->lang['register']['Username dupe 1'].' '.$this->htmlspecialchars($busy).'. '.$this->lang['register']['Username dupe 2']);
			return false;
		}

		# Validate e-mail
		if (!$this->is_valid_email($email1))
		{
			$this->set_error($this->lang['common']['Invalid e-mail']);
			return false;
		}
		else if ($this->config['o_regs_verify'] == '1' && $email1 != $email2)
		{
			$this->set_error($this->lang['register']['E-mail not match']);
			return false;
		}

		# Check it it's a banned e-mail address
		if ($this->is_banned_email($email1))
		{
			if ($this->config['p_allow_banned_email'] == '0')
			{
				$this->set_error($this->lang['prof_reg']['Banned e-mail']);
				return false;
			}

			$banned_email = true;	// Used later when we send an alert e-mail
		}
		else
			$banned_email = false;

		# Check if someone else already has registered with that e-mail address
		$dupe_list = array();

		$result = $this->db->query('SELECT username FROM '.$this->db->prefix.'users WHERE email=\''.$email1.'\'') or $this->fatal_error('Unable to fetch user info', __FILE__, __LINE__, $this->db->error());
		if ($this->db->num_rows($result))
		{
			if ($this->config['p_allow_dupe_email'] == '0')
			{
				$this->set_error($this->lang['prof_reg']['Dupe e-mail']);
				return false;
			}

			while ($cur_dupe = $this->db->fetch_assoc($result))
				$dupe_list[] = $cur_dupe['username'];
		}

		# Make sure we got a valid language string
		if ($language != '')
		{
			$language = preg_replace('#[\.\\\/]#', '', $language);
			if (!file_exists(PUN_ROOT.'lang/'.$language.'/common.php'))
			{
				$this->set_error($this->lang['common']['Bad request']);
				return false;
			}
		}
		else
			$language = $this->config['o_default_lang'];

		if ($timezone != '')
			$timezone = round($timezone, 1);
		else
			$timezone = $this->config['o_server_timezone'];

		$save_pass = ($save_pass === false) ? '0' : '1';

		if ($email_setting < 0 || $email_setting > 2)
			$email_setting = 1;

		# Insert the new user into the database. We do this now to get the last inserted id for later use.
		$now = time();
	
		$intial_group_id = ($this->config['o_regs_verify'] == '0') ? $this->config['o_default_user_group'] : PUN_UNVERIFIED;
		$password_hash = $this->_hash($password1);
	
		# Add the user
		$this->db->query('INSERT INTO '.$this->db->prefix.'users (username, group_id, password, email, email_setting, save_pass, timezone, language, style, registered, registration_ip, last_visit) VALUES(\''.$this->db->escape($username).'\', '.$intial_group_id.', \''.$password_hash.'\', \''.$email1.'\', '.$email_setting.', '.$save_pass.', '.$timezone.' , \''.$this->db->escape($language).'\', \''.$this->config['o_default_style'].'\', '.$now.', \''.$_SERVER['REMOTE_ADDR'].'\', '.$now.')') or $this->fatal_error('Unable to create user', __FILE__, __LINE__, $this->db->error());
		$new_uid = $this->db->insert_id();

		# If we previously found out that the e-mail was banned
		if ($banned_email && $this->config['o_mailing_list'] != '')
		{
			$mail_subject = 'Alert - Banned e-mail detected';
			$mail_message = 'User \''.$username.'\' registered with banned e-mail address: '.$email1."\n\n".'User profile: '.$this->config['o_base_url'].'/profile.php?id='.$new_uid."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';
	
			$this->mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
		}

		# If we previously found out that the e-mail was a dupe
		if (!empty($dupe_list) && $this->config['o_mailing_list'] != '')
		{
			$mail_subject = 'Alert - Duplicate e-mail detected';
			$mail_message = 'User \''.$username.'\' registered with an e-mail address that also belongs to: '.implode(', ', $dupe_list)."\n\n".'User profile: '.$this->config['o_base_url'].'/profile.php?id='.$new_uid."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';
	
			$this->mail($this->config['o_mailing_list'], $mail_subject, $mail_message);
		}

		# Should we alert people on the admin mailing list that a new user has registered?
		if ($this->config['o_regs_report'] == '1')
		{
			$mail_subject = 'Alert - New registration';
			$mail_message = 'User \''.$username.'\' registered in the forums at '.$this->config['o_base_url']."\n\n".'User profile: '.$this->config['o_base_url'].'/profile.php?id='.$new_uid."\n\n".'-- '."\n".'Forum Mailer'."\n".'(Do not reply to this message)';
	
			$this->mail($$this->config['o_mailing_list'], $mail_subject, $mail_message);
		}

		# Must the user verify the registration or do we log him/her in right now?
		if ($this->config['o_regs_verify'] == '1')
		{
			# Load the "welcome" template
			$mail_tpl = trim(file_get_contents(PUN_ROOT.'lang/'.$this->user['language'].'/mail_templates/welcome.tpl'));

			# The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = trim(substr($mail_tpl, $first_crlf));

			$mail_subject = str_replace('<board_title>', $this->config['o_board_title'], $mail_subject);
			$mail_message = str_replace('<base_url>', $this->config['o_base_url'].'/', $mail_message);
			$mail_message = str_replace('<username>', $username, $mail_message);
			$mail_message = str_replace('<password>', $password1, $mail_message);
			$mail_message = str_replace('<login_url>', $this->config['o_base_url'].'/login.php', $mail_message);
			$mail_message = str_replace('<board_mailer>', $this->config['o_board_title'].' '.$this->lang['common']['Mailer'], $mail_message);

			$this->mail($email1, $mail_subject, $mail_message);

			return true;
		}

		$this->_set_cookie($new_uid, $password_hash, ($save_pass != '0') ? $now + 31536000 : 0);
		return true;
	}

	/**
	@function get_user_id
	
	Display the user id.
	
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_id($return=false)
	{
		if ($return)
			return $this->user['id'];
		
		echo $this->user['id'];
		return true;
	}

	/**
	@function get_user_gid
	
	Display the user group id of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_gid($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $user['group_id'];
		
		echo $user['group_id'];
		return true;
	}
	
	/**
	@function get_user_name
	
	Display the username of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_name($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['username']);
		
		echo $this->htmlspecialchars($user['username']);
		return true;
	}
	
	/**
	@function get_user_email
	
	Display the email address of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_email($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $user['email'];
		
		echo $user['email'];
		return true;
	}
	
	/**
	@function get_user_title
	
	Display the title of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_title($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->_get_title($user);
		
		echo $this->_get_title($user);
		return true;
	}
	
	/**
	@function get_user_realname
	
	Display the realname of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_realname($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['realname']);
		
		echo $this->htmlspecialchars($user['realname']);
		return true;
	}
	
	/**
	@function get_user_profession
	
	Display the profession of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_profession($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['profession']);
		
		echo $this->htmlspecialchars($user['profession']);
		return true;
	}
	
	/**
	@function get_user_url
	
	Display the url of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_url($user_id='',$return=false)
	{		
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['url']);
		
		echo $this->htmlspecialchars($user['url']);
		return true;
	}
	
	/**
	@function get_user_jabber
	
	Display the jabber address of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_jabber($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['jabber']);
		
		echo $this->htmlspecialchars($user['jabber']);
		return true;
	}
	
	/**
	@function get_user_icq
	
	Display the icq address of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_icq($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['icq']);
		
		echo $this->htmlspecialchars($user['icq']);
		return true;
	}
	
	/**
	@function get_user_msn
	
	Display the msn address of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_msn($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['msn']);
		
		echo $this->htmlspecialchars($user['msn']);
		return true;
	}
	
	/**
	@function get_user_aim
	
	Display the aim address of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_aim($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['aim']);
		
		echo $this->htmlspecialchars($user['aim']);
		return true;
	}
	
	/**
	@function get_user_yahoo
	
	Display the yahoo address of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_yahoo($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['yahoo']);
		
		echo $this->htmlspecialchars($user['yahoo']);
		return true;
	}
	
	/**
	@function get_user_location
	
	Display the location of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_location($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->htmlspecialchars($user['location']);
		
		echo $this->htmlspecialchars($user['location']);
		return true;
	}
	
	/**
	@function get_user_lang
	
	Display the lang of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_lang($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $user['language'];
		
		echo $user['language'];
		return true;
	}
	
	/**
	@function get_user_style
	
	Display the style of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_style($user_id='',$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $user['style'];
		
		echo $user['style'];
		return true;
	}
	
	/**
	@function get_user_avatar
	
	Display the HTML avatar of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	return		Return value, not display it (false)
	@return string
	*/
	function get_user_avatar($user_id='',$return=false)
	{
		if ($user_id == '')
			$user_id = $this->user['id'];
			
		$user_avatar = NULL;
			
		if ($this->config['o_avatars'] == '1')
		{
			$avatar_url = $this->config['o_base_url'].'/'.$this->config['o_avatars_dir'].'/'.$user_id;
			
			if ($img_size = @getimagesize($avatar_url.'.gif'))
				$user_avatar = '<img src="'.$avatar_url.'.gif" '.$img_size[3].' alt="" />';
			else if ($img_size = @getimagesize($avatar_url.'.jpg'))
				$user_avatar = '<img src="'.$avatar_url.'.jpg" '.$img_size[3].' alt="" />';
			else if ($img_size = @getimagesize($avatar_url.'.png'))
				$user_avatar = '<img src="'.$avatar_url.'.png" '.$img_size[3].' alt="" />';
		}
			
		if ($return)
			return $user_avatar;
		
		echo $user_avatar;
		return true;
	}
	
	/**
	@function get_user_last_post
	
	Display the last post date of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	date_only	Only return date (false)
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_last_post($user_id='',$date_only=false, $return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->format_time($user['last_post'],$date_only);
		
		echo $this->format_time($user['last_post'],$date_only);
		return true;
	}
	
	/**
	@function get_user_registered
	
	Display the registered date of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	date_only	Only return date (false)
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_registered($user_id='',$date_only=false,$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->format_time($user['registered'],$date_only);
		
		echo $this->format_time($user['registered'],$date_only);
		return true;
	}
	
	/**
	@function get_user_last_visit
	
	Display the last visit date of member user_id.
	
	@param	integer	user_id		User identifier ('')
	@param	boolean	date_only	Only return date (false)
	@param	boolean	return		Return value, not display it (false)
	@return void
	*/
	function get_user_last_visit($user_id='',$date_only=false,$return=false)
	{
		$user = $this->get_user_infos($user_id);
		
		if ($return)
			return $this->format_time($user['last_visit'],$date_only);
		
		echo $this->format_time($user['last_visit'],$date_only);
		return true;
	}

	/**
	@function is_guest
	
	Returns whether the current user is a guest
	@return boolean
	*/
	function is_guest()
	{
		return $this->user['is_guest'];
	}

	/**
	@function is_logged
	
	Returns whether the current user is logged
	@return boolean
	*/
	function is_logged()
	{
		return !$this->user['is_guest'];
	}

	/**
	@function is_admin
	
	Returns whether the current user is an administrator
	@return boolean
	*/
	function is_admin()
	{
		if ($this->user['g_id'] == PUN_ADMIN)
			return true;
		
		return false;
	}

	/**
	@function is_mod
	
	Returns whether the current user is a moderator
	@return boolean
	*/
	function is_mod()
	{
		if ($this->user['g_id'] == PUN_MOD)
			return true;
		
		return false;
	}

	/**
	@function is_mod
	
	Returns whether the current user is an administrator or a moderator
	@return boolean
	*/
	function is_admod()
	{
		if ($this->user['g_id'] < PUN_GUEST)
			return true;
		
		return false;
	}


	/** Groups
	----------------------------------------------------------*/

	/**
	@function get_group_infos
	
	Retrieve infos of all user groups or one group if $fid specified.
	
	$gid should be the integer identifiant of the group or the
	name of the group.
	
	@param	mixed	group_id	Group ID to retrieve ('')
	@param	string	order_by	How to order results ('g_id')
	@return recordset
	*/
	function get_group_infos($group_id='',$order_by='id')
	{
		$order_by = (strtolower($order_by) == 'title') ? 'g_title' : 'g_id';
		
		if ($cache = $this->_get_cache('get_group_infos', $this->_hash($group_id.$order_by)))
			return $cache;
		else {
			$reqPlus = 'WHERE 1 ';

			if ($group_id != '')
			{
				if (preg_match('/^[0-9]+$/',$group_id))
					$reqPlus .= 'AND g_id='.intval($group_id).' ';
				else
					$reqPlus .= 'AND g_title=\''.$this->db->escape($group_id).'\' ';
			}

			$rs = $this->db->select('SELECT * FROM '.$this->db->prefix.'groups '.$reqPlus.'ORDER BY '.$order_by) or $this->fatal_error('Unable to fetch group infos', __FILE__, __LINE__, $this->db->error());
			
			$this->_set_cache('get_group_infos', $this->_hash($group_id.$order_by), $rs);
			return $rs;		
		}
	}


	/** Categories & forums
	----------------------------------------------------------*/

	/**
	@function get_forum_infos
	
	Retrieve infos of all forums or one forum if $fid specified.
	
	$fid should be the integer identifiant of the forum or the
	name of the forum.
	
	@param	integer	fid			Forum ID to retrieve ('')
	@param	string	order_by	How to order results ('disp_position')
	@return recordset
	*/
	function get_forum_infos($fid='',$order_by='disp_position')
	{
		$reqPlus = 'WHERE 1 ';

		if ($fid != '')
		{
			if (preg_match('/^[0-9]+$/',$fid))
				$reqPlus .= 'AND id='.intval($fid).' ';
			else
				$reqPlus .= 'AND forum_name=\''.$this->db->escape($fid).'\' ';
		}

		$rs = $this->db->select('SELECT * FROM '.$this->db->prefix.'forums '.$reqPlus.'ORDER BY '.$order_by) or $this->fatal_error('Unable to fetch forum infos', __FILE__, __LINE__, $this->db->error());

		return $rs;
	}

	/**
	@function get_cat_infos
	
	Retrieve infos of all cat or one cat if $cid specified.
	
	$cid should be the integer identifiant of the category or the
	name of the category.
	
	@param	integer	cid			Category ID to retrieve ('')
	@param	string	order_by	How to order results ('disp_position')
	@return recordset
	*/
	function get_cat_infos($cid='',$order_by='disp_position')
	{
		$reqPlus = 'WHERE 1 ';

		if ($cid != '')
		{
			if (preg_match('/^[0-9]+$/',$cid))
				$reqPlus .= 'AND id='.intval($cid).' ';
			else
				$reqPlus .= 'AND cat_name=\''.$this->db->escape($cid).'\' ';
		}

		$rs = $this->db->select('SELECT * FROM '.$this->db->prefix.'categories '.$reqPlus.'ORDER BY '.$order_by) or $this->fatal_error('Unable to fetch category infos', __FILE__, __LINE__, $this->db->error());

		return $rs;
	}

	/**
	@function get_cats_and_forums
	
	Retrieve infos of catégories and forums.
	
	@param	integer	cid			Category ID to retrieve ('')
	@param	integer	fid			Forum ID to retrieve ('')
	@return recordset
	*/
	function get_cat_and_forum($cid='',$fid='')
	{
		$reqPlus = 'WHERE 1 ';

		if ($cid != '')
		{
			if (preg_match('/^[0-9]+$/',$cid))
				$reqPlus .= 'AND c.id='.intval($cid).' ';
			else
				$reqPlus .= 'AND c.cat_name=\''.$this->db->escape($cid).'\' ';
		}
		elseif ($fid != '')
		{
			if (preg_match('/^[0-9]+$/',$fid))
				$reqPlus .= 'AND f.id='.intval($fid).' ';
			else
				$reqPlus .= 'AND f.forum_name=\''.$this->db->escape($fid).'\' ';
		}
		
		$strReq = 
		'SELECT c.id AS cid, c.cat_name, c.disp_position, '.
		'f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, '.
		'f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, '.
		'f.sort_by, f.disp_position '.
		'FROM '.$this->db->prefix.'categories AS c '.
			'INNER JOIN '.$this->db->prefix.'forums AS f ON c.id=f.cat_id '.
		$reqPlus.
		'ORDER BY c.disp_position, c.id, f.disp_position';

		$rs = $this->db->select($strReq) or $this->fatal_error('Unable to fetch category/forum list', __FILE__, __LINE__, $this->db->error());

		return $rs;
	}


	/** Topics
	----------------------------------------------------------*/

	/**
	@function get_topics
	
	Return a recordset containing topics
	
	@param	integer forum_id	Forum(s) from wich extract topics
	@param	string	limit		Limit number of topic to return
	@param	string	order_by	How to order result
	@param	string	sotrt		How to sort results
	*/
	function get_topics($forum_id='',$limit='',$order_by='last_post',$sort='desc',$bypassperm=false)
	{
		$forum_sql = '';
		
		if ($forum_id != '')
		{
			$fids = explode(',', $forum_id);
			$fids = array_map('intval', $fids);
	
			if (!empty($fids))
				$forum_sql = 'AND f.id IN('.implode(',', $fids).') ';
		}
		
		$strReq = 
		'SELECT t.id, t.poster, t.subject, t.posted, t.last_post, t.last_post_id, '.
		't.last_poster, t.num_views, t.num_replies, t.closed, t.sticky, t.moved_to, '.
		'f.id AS fid, f.forum_name, f.redirect_url, f.moderators, f.num_topics, '.
		'f.sort_by ';
		
		if ($bypassperm)
		{
			$strReq .= 'FROM '.$this->db->prefix.'topics AS t '.
			'INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id '.
			'WHERE t.moved_to IS NULL ';
		}
		else {
			$strReq .= ', fp.post_topics '.
			'FROM '.$this->db->prefix.'topics AS t '.
			'INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id '.
			'LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') '.
			'WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.moved_to IS NULL ';
		}
		
		$strReq .= $forum_sql.'ORDER BY '.
		(strtolower($order_by) == 'posted' ? 't.posted' : ' t.last_post').
		(strtolower($sort) == 'asc' ? ' ASC ' : ' DESC ');
		
		if ($limit != '')
		{
			$limit = (preg_match('/^[0-9]+$/',$limit)) ? '0,'.$limit : $limit;
			$strReq .= 'LIMIT '.$limit.' ';
		}
		
		$rs = $this->db->select($strReq) or $this->fatal_error('Unable to fetch topic list', __FILE__, __LINE__, $this->db->error());
		
		return $rs;
	}

	/**
	@function add_topic
	
	Lance un sujet dans un forum et retourne
	les ID du sujet et du message créés :
	
	array('tid' => $new_tid, 'pid' => $new_pid);
	
	@param	integer	forum_id		ID du forum dans lequel il faut poster
	@param	string	subject			le sujet du topic
	@param	string	message			le message du topic
	@param	boolean	increment		incremente ou non le compteur de post de l'utilisateur (true)
	@param	boolean	hide_smilies	transforme les smiley ou non (false)
	@param	boolean	subscribe		abonnement à la discussion ou non (false)
	@param	string	username		le nom d'utilisateur ('')
	@param	string	email			l'adresse email de l'utilisateur ('')
	@param	boolean	bypassperm		passe outre les permissions (false)
	@return array('tid' => $new_tid, 'pid' => $new_pid);
	*/
	function add_topic($forum_id,$subject,$message,$increment=true,$hide_smilies=false,$subscribe=false,$username='',$email='',$bypassperm=false)
	{
		# Transforme hide_smiley and subscirbe bool in integer for sql query
		$hide_smilies = ($hide_smilies == true || $hide_smilies == 1) ? 1 : 0;
		$subscribe = ($subscribe == true || $subscribe == 1) ? 1 : 0;
		
		# Fetch some infos
		$cur_posting = $this->db->select('SELECT f.id, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics FROM '.$this->db->prefix.'forums AS f LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND f.id='.$forum_id) or $this->fatal_error('Unable to fetch forum info', __FILE__, __LINE__, $this->db->error());
			
		if ($cur_posting->isEmpty())
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		# Is someone trying to post into a redirect forum?
		if ($cur_posting->f('redirect_url') != '')
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		# Sort out who the moderators are and if we are currently a moderator (or an admin)
		$mods_array = ($cur_posting->f('moderators') != '') ? unserialize($cur_posting->f('moderators')) : array();
		$is_admmod = ($this->user['g_id'] == PUN_ADMIN || ($this->user['g_id'] == PUN_MOD && array_key_exists($this->user['username'], $mods_array))) ? true : false;
		
		# Do we have permission to post?		
		$post_topic = (string)$cur_posting->f('post_topics');
		if (!$bypassperm && !$is_admmod && ((($post_topic === '' && $this->user['g_post_topics'] == '0') || $post_topic === '0')))
		{
			$this->set_error($this->lang['common']['No permission']);
			return false;
		}
		
		# Load the post.php language file
		$this->load_lang('post');
		
		# Flood protection
		if (!$this->user['is_guest'] && $this->user['last_post'] != '' && (time() - $this->user['last_post']) < $this->user['g_post_flood'])
		{
			$this->set_error($this->lang['post']['Flood start'].' '.$this->user['g_post_flood'].' '.$this->lang['post']['flood end']);
			return false;
		}
		
		# Check user infos
		if (!$this->_pre_post_user($username,$email))
			return false;
		
		# Check title
		if (!$this->_pre_post_title($subject))
			return false;
		
		# Check and clean up message
		if (!$this->_pre_post_message($message))
			return false;
		
		# Did everything go according to plan?
		if ($this->has_error())
			return false;
		else 
		{
			$now = time();
			
			# Create the topic
			$this->db->query('INSERT INTO '.$this->db->prefix.'topics (poster, subject, posted, last_post, last_poster, forum_id) VALUES(\''.$this->db->escape($username).'\', \''.$this->db->escape($subject).'\', '.$now.', '.$now.', \''.$this->db->escape($username).'\', '.$forum_id.')') or $this->fatal_error('Unable to create topic', __FILE__, __LINE__, $this->db->error());
			
			$new_tid = $this->db->insert_id();

			if (!$this->user['is_guest'])
			{
				# To subscribe or not to subscribe, that ...
				if ($this->config['o_subscriptions'] == '1' && $subscribe == 1)
				{
					$this->db->query('INSERT INTO '.$this->db->prefix.'subscriptions (user_id, topic_id) VALUES('.$this->user['id'].' ,'.$new_tid.')') or $this->fatal_error('Unable to add subscription', __FILE__, __LINE__, $this->db->error());
				}
				
				# Create the post ("topic post")
				$this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($username).'\', '.$this->user['id'].', \''.$_SERVER['REMOTE_ADDR'].'\', \''.$this->db->escape($message).'\', \''.$hide_smilies.'\', '.$now.', '.$new_tid.')') or $this->fatal_error('Unable to create post', __FILE__, __LINE__, $this->db->error());
				
				$new_pid = $this->db->insert_id();
			}
			else {
				# Create the post ("topic post")
				$email_sql = ($this->config['p_force_guest_email'] == '1' || $email != '') ? '\''.$email.'\'' : 'NULL';
				$this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($username).'\', \''.$_SERVER['REMOTE_ADDR'].'\', '.$email_sql.', \''.$this->db->escape($message).'\', \''.$hide_smilies.'\', '.$now.', '.$new_tid.')') or $this->fatal_error('Unable to create post', __FILE__, __LINE__, $this->db->error());
				
				$new_pid = $this->db->insert_id();
			}
			
			# Update the topic with last_post_id
			$this->db->query('UPDATE '.$this->db->prefix.'topics SET last_post_id='.$new_pid.' WHERE id='.$new_tid) or $this->fatal_error('Unable to update topic', __FILE__, __LINE__, $this->db->error());
							
			$this->_update_search_index('post', $new_pid, $message, $subject);
	
			$this->_update_forum($forum_id);

			# If the posting user is logged in, increment his/her post count
			if (!$this->user['is_guest'] && $increment)
			{
				$low_prio = ($this->conf['db_type'] == 'mysql') ? 'LOW_PRIORITY ' : '';
				$this->db->query('UPDATE '.$low_prio.$this->db->prefix.'users SET num_posts=num_posts+1, last_post='.$now.' WHERE id='.$this->user['id']) or $this->fatal_error('Unable to update user', __FILE__, __LINE__, $this->db->error());
			}
			
			return array('tid' => $new_tid, 'pid' => $new_pid);
		}
	}

	/*
	@function edit_topic
	
	An alias of edit_post because you have to edit first post
	of a topic to edit it.
	
	@param integer post_id			ID of the first post of the topic to edit
	@param string message			New message
	@param string subject			The subject of topic
	@param boolean hide_smilies		Transforme smilies or not (false)
	@param boolean silent			Silent edit (false)
	@param boolean bypassperm		Bypass permissions (false)
	@return boolean
	*/
	function edit_topic($post_id,$message,$subject,$hide_smilies=false,$silent=false,$bypassperm=false)
	{
		return $this->edit_post($post_id,$message,$subject,$hide_smilies,$silent,$bypassperm);
	}

	/**
	@fucntion del_topic
	
	Delete a given topic
	
	@param	integer	topic_id		Topic ID to delete
	@param	boolean	bypassperm		Bypass permissions (false)
	@return boolean
	*/
	function del_topic($topic_id,$bypassperm=false)
	{
		$result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id='.intval($topic_id).' ORDER BY posted LIMIT 1') or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
		$topic_post_id = $this->db->result($result);

		return $this->del_post($topic_post_id,$bypassperm);
	}


	/** Posts
	----------------------------------------------------------*/

	/**
	@function get_post_sql
	
	Build a query with varaible parameters
	
	@param	integer	tid			Topic ID from wich post must be retrieves (0)
	@param	integer	pid			Post ID of the post we want to retrieve (0)
	@param	mixed	limit		Number of post to fetch ('')
	@param	string	order_by	How to order results ('p.id')
	@param	integer	npid		Post to not return
	@return string
	*/
	function get_post_sql($tid=0,$pid=0,$limit='',$order_by='p.id',$npid=0)
	{
		if ($tid > 0)
			$reqPlus = 'WHERE p.topic_id='.intval($tid).' ';
		elseif ($pid > 0)
			$reqPlus = 'WHERE p.id='.intval($pid).' ';
		else
			return false;
		
		if ($npid > 0)
			$reqPlus .= 'AND p.id!='.$npid.' ';
		
		$strReq = 
		'SELECT u.email, u.title, u.url, u.location, u.use_avatar, u.signature, '.
		'u.email_setting, u.num_posts, u.registered, u.admin_note, '.
		'p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, '.
		'p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, '.
		'g.g_id, g.g_user_title, '.
		'o.user_id AS is_online '.
		'FROM '.$this->db->prefix.'posts AS p '.
			'INNER JOIN '.$this->db->prefix.'users AS u ON u.id=p.poster_id '.
			'INNER JOIN '.$this->db->prefix.'groups AS g ON g.g_id=u.group_id '.
			'LEFT JOIN '.$this->db->prefix.'online AS o ON (o.user_id=u.id AND o.user_id!=1 AND o.idle=0) '.
		$reqPlus.
		'ORDER BY '.$order_by;
				
		if ($limit != '')
		{
			$limit = (preg_match('/^[0-9]+$/',$limit)) ? '0,'.$limit : $limit;
			$strReq .= ' LIMIT '.$limit.' ';
		}
		
		return $strReq;
	}

	/**
	@function get_posts
	
	Return a recordset containing all the posts of a given topic.
	
	@param	integer	tid			Identifier of the topic
	@param	string	limit		Limit to a number of posts ('')
	@param	string	limit		How to order results ('p.id')
	@param	boolean npid		Post to not return (0)
	@return recordset
	*/
	function get_posts($tid,$limit='',$order_by='p.id',$npid=0)
	{
		$strReq = $this->get_post_sql($tid,'',$limit,$order_by,$npid);
		
		$rs = $this->db->select($strReq) or $this->fatal_error('Unable to fetch topic posts', __FILE__, __LINE__, $this->db->error());
		
		return $rs;		
	}

	/**
	@function get_post_infos
	
	Return a recordset containing informations about a given post.
	
	@param	integer	pid			Identifier of the post
	@return recordset
	*/
	function get_post_infos($pid)
	{
		if ($cache = $this->_get_cache('get_post_infos', $pid))
			return $cache;
		else {
			$strReq = $this->get_post_sql('',$pid,1,'p.id');
					
			$rs = $this->db->select($strReq) or $this->fatal_error('Unable to fetch post infos', __FILE__, __LINE__, $this->db->error());
			
			$this->_set_cache('get_post_infos', $pid, $rs);
			return $rs;		
		}
	}

	/**
	@function add_post
	
	Add a post to a topic $tid and return new post ID
	
	@param	integer	tid				ID du topic dans lequel il faut poster
	@param	string	message			le message du post
	@param	boolean	increment		incremente ou non le compteur de post de l'utilisateur (true)
	@param	boolean	hide_smilies	transforme les smiley ou non (false)
	@param	boolean	subscribe		abonnement à la discussion ou non (false)
	@param	string	username		le nom d'utilisateur ('')
	@param	string	email			l'adresse email de l'utilisateur ('')
	@param	boolean	bypassperm		passe outre les permissions (false)
	@return integer
	*/
	function add_post($tid,$message,$increment=true,$hide_smilies=false,$subscribe=false,$username='',$email='',$bypassperm=false)
	{
		$tid = intval($tid);
		
		if ($tid < 1)
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		# Transforme hide_smiley and subscirbe bool in integer for sql query
		$hide_smilies = ($hide_smilies == true || $hide_smilies == 1) ? 1 : 0;
		$subscribe = ($subscribe == true || $subscribe == 1) ? 1 : 0;
		
		# Fetch some infos
		$strReq = 
		'SELECT f.id, f.forum_name, f.moderators, f.redirect_url, '.
		'fp.post_replies, fp.post_topics, t.subject, t.closed '.
		'FROM '.$this->db->prefix.'topics AS t '.
		'INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id '.
		'LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') '.
		'WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$tid;
		
		$cur_posting = $this->db->select($strReq) or $this->fatal_error('Unable to fetch topic infos', __FILE__, __LINE__, $this->db->error());
					
		if ($cur_posting->isEmpty())
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		# Is someone trying to post into a redirect forum?
		if ($cur_posting->f('redirect_url') != '')
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		# Sort out who the moderators are and if we are currently a moderator (or an admin)
		$mods_array = ($cur_posting->f('moderators') != '') ? unserialize($cur_posting->f('moderators')) : array();
		$is_admmod = ($this->user['g_id'] == PUN_ADMIN || ($this->user['g_id'] == PUN_MOD && array_key_exists($this->user['username'], $mods_array))) ? true : false;
		
		# Do we have permission to post?		
		if (!$bypassperm && !$is_admmod && ((($cur_posting->f('post_replies') == '' && $this->user->f('g_post_replies') == '0') || $cur_posting->f('post_replies') == '0') || (isset($cur_posting['closed']) && $cur_posting['closed'] == '1')))
		{
			$this->set_error($this->lang['common']['No permission']);
			return false;
		}
		
		# Load the post.php language file
		$this->load_lang('post');
		
		# Flood protection
		if (!$this->user['is_guest'] && $this->user['last_post'] != '' && (time() - $this->user['last_post']) < $this->user['g_post_flood'])
		{
			$this->set_error($this->lang['post']['Flood start'].' '.$this->user['g_post_flood'].' '.$this->lang['post']['flood end']);
			return false;
		}
		
		# Check user infos
		if (!$this->_pre_post_user($username,$email))
			return false;
				
		# Clean up message
		if (!$this->_pre_post_message($message))
			return false;
		
		# Did everything go according to plan?
		if ($this->has_error())
			return false;
		else 
		{
			$now = time();
			if (!$this->user['is_guest'])
			{
				# Insert the new post
				$this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($username).'\', '.$this->user['id'].', \''.$_SERVER['REMOTE_ADDR'].'\', \''.$this->db->escape($message).'\', \''.$hide_smilies.'\', '.$now.', '.$tid.')') or $this->fatal_error('Unable to create post', __FILE__, __LINE__, $this->db->error());
				
				$new_pid = $this->db->insert_id();

				# To subscribe or not to subscribe, that ...
				if ($this->config['o_subscriptions'] == '1' && $subscribe)
				{
					$result = $this->db->select('SELECT 1 FROM '.$this->db->prefix.'subscriptions WHERE user_id='.$this->user['id'].' AND topic_id='.$tid) or $this->fatal_error('Unable to fetch subscription info', __FILE__, __LINE__, $this->db->error());
					
					if ($result->isEmpty())
						$this->db->query('INSERT INTO '.$this->db->prefix.'subscriptions (user_id, topic_id) VALUES('.$this->user['id'].' ,'.$tid.')') or $this->fatal_error('Unable to add subscription', __FILE__, __LINE__, $this->db->error());
				}
			}
			else
			{
				# It's a guest. Insert the new post
				$email_sql = ($this->config['p_force_guest_email'] == '1' || $email != '') ? '\''.$email.'\'' : 'NULL';
				$this->db->query('INSERT INTO '.$this->db->prefix.'posts (poster, poster_ip, poster_email, message, hide_smilies, posted, topic_id) VALUES(\''.$this->db->escape($username).'\', \''.$_SERVER['REMOTE_ADDR'].'\', '.$email_sql.', \''.$this->db->escape($message).'\', \''.$hide_smilies.'\', '.$now.', '.$tid.')') or $this->fatal_error('Unable to create post', __FILE__, __LINE__, $this->db->error());
				
				$new_pid = $this->db->insert_id();
			}

			# Count number of replies in the topic
			$result = $this->db->select('SELECT COUNT(id) AS num_replies FROM '.$this->db->prefix.'posts WHERE topic_id='.$tid) or $this->fatal_error('Unable to fetch post count for topic', __FILE__, __LINE__, $this->db->error());
			$num_replies = $result->f('num_replies') - 1;

			# Update topic
			$this->db->query('UPDATE '.$this->db->prefix.'topics SET num_replies='.$num_replies.', last_post='.$now.', last_post_id='.$new_pid.', last_poster=\''.$this->db->escape($username).'\' WHERE id='.$tid) or $this->fatal_error('Unable to update topic', __FILE__, __LINE__, $this->db->error());

			$this->_update_search_index('post', $new_pid, $message);

			$this->_update_forum($cur_posting->f('id'));

			# Should we send out notifications?
			if ($this->config['o_subscriptions'] == '1')
			{
				# Get the post time for the previous post in this topic
				$result = $this->db->select('SELECT posted FROM '.$this->db->prefix.'posts WHERE topic_id='.$tid.' ORDER BY id DESC LIMIT 1, 1') or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
				$previous_post_time = $result->f('posted');

				# Get any subscribed users that should be notified (banned users are excluded)
				$subscriber = $this->db->select('SELECT u.id, u.email, u.notify_with_post, u.language FROM '.$this->db->prefix.'users AS u INNER JOIN '.$this->db->prefix.'subscriptions AS s ON u.id=s.user_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id='.$cur_posting->f('id').' AND fp.group_id=u.group_id) LEFT JOIN '.$this->db->prefix.'online AS o ON u.id=o.user_id LEFT JOIN '.$this->db->prefix.'bans AS b ON u.username=b.username WHERE b.username IS NULL AND COALESCE(o.logged, u.last_visit)>'.$previous_post_time.' AND (fp.read_forum IS NULL OR fp.read_forum=1) AND s.topic_id='.$tid.' AND u.id!='.intval($this->user['id'])) or $this->fatal_error('Unable to fetch subscription info', __FILE__, __LINE__, $this->db->error());
				
				if (!$subscriber->isEmpty())
				{
					$notification_emails = array();

					# Loop through subscribed users and send e-mails
					while ($subscriber->fetch())
					{
						# Is the subscription e-mail for $subscriber->f('language') cached or not?
						if (!isset($notification_emails[$subscriber->f('language')]))
						{
							if (file_exists($this->pun_root.'lang/'.$subscriber->f('language').'/mail_templates/new_reply.tpl'))
							{
								# Load the "new reply" template
								$mail_tpl = trim(file_get_contents($this->pun_root.'lang/'.$subscriber->f('language').'/mail_templates/new_reply.tpl'));

								# Load the "new reply full" template (with post included)
								$mail_tpl_full = trim(file_get_contents($this->pun_root.'lang/'.$subscriber->f('language').'/mail_templates/new_reply_full.tpl'));

								# The first row contains the subject (it also starts with "Subject:")
								$first_crlf = strpos($mail_tpl, "\n");
								$mail_subject = trim(substr($mail_tpl, 8, $first_crlf-8));
								$mail_message = trim(substr($mail_tpl, $first_crlf));

								$first_crlf = strpos($mail_tpl_full, "\n");
								$mail_subject_full = trim(substr($mail_tpl_full, 8, $first_crlf-8));
								$mail_message_full = trim(substr($mail_tpl_full, $first_crlf));

								$mail_subject = str_replace('<topic_subject>', '\''.$cur_posting->f('subject').'\'', $mail_subject);
								$mail_message = str_replace('<topic_subject>', '\''.$cur_posting->f('subject').'\'', $mail_message);
								$mail_message = str_replace('<replier>', $username, $mail_message);
								$mail_message = str_replace('<post_url>', $this->config['o_base_url'].'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message);
								$mail_message = str_replace('<unsubscribe_url>', $this->config['o_base_url'].'/misc.php?unsubscribe='.$tid, $mail_message);
								$mail_message = str_replace('<board_mailer>', $this->config['o_board_title'].' '.__('Mailer'), $mail_message);

								$mail_subject_full = str_replace('<topic_subject>', '\''.$cur_posting->f('subject').'\'', $mail_subject_full);
								$mail_message_full = str_replace('<topic_subject>', '\''.$cur_posting->f('subject').'\'', $mail_message_full);
								$mail_message_full = str_replace('<replier>', $username, $mail_message_full);
								$mail_message_full = str_replace('<message>', $message, $mail_message_full);
								$mail_message_full = str_replace('<post_url>', $this->config['o_base_url'].'/viewtopic.php?pid='.$new_pid.'#p'.$new_pid, $mail_message_full);
								$mail_message_full = str_replace('<unsubscribe_url>', $this->config['o_base_url'].'/misc.php?unsubscribe='.$tid, $mail_message_full);
								$mail_message_full = str_replace('<board_mailer>', $this->config['o_board_title'].' '.__('Mailer'), $mail_message_full);

								$notification_emails[$subscriber->f('language')][0] = $mail_subject;
								$notification_emails[$subscriber->f('language')][1] = $mail_message;
								$notification_emails[$subscriber->f('language')][2] = $mail_subject_full;
								$notification_emails[$subscriber->f('language')][3] = $mail_message_full;

								$mail_subject = $mail_message = $mail_subject_full = $mail_message_full = null;
							}
						}

						# We have to double check here because the templates could be missing
						if (isset($notification_emails[$subscriber->f('language')]))
						{
							if ($subscriber->f('notify_with_post') == '0')
								$this->mail($subscriber->f('email'), $notification_emails[$subscriber->f('language')][0], $notification_emails[$subscriber->f('language')][1]);
							else
								$this->mail($subscriber->f('email'), $notification_emails[$subscriber->f('language')][2], $notification_emails[$subscriber->f('language')][3]);
						}
					}
				}
			}
		}

		# If the posting user is logged in, increment his/her post count
		if (!$this->user['is_guest'] && $increment)
		{
			$low_prio = ($this->conf['db_type'] == 'mysql') ? 'LOW_PRIORITY ' : '';
			$this->db->query('UPDATE '.$low_prio.$this->db->prefix.'users SET num_posts=num_posts+1, last_post='.$now.' WHERE id='.$this->user['id']) or $this->fatal_error('Unable to update user', __FILE__, __LINE__, $this->db->error());
		}
		
		return $new_pid;
	}

	/*
	@function edit_post
	
	Edit a post 
	
	@param integer post_id			ID of the post toedit
	@param string message			New message
	@param string subject			The subject if it's a post-topic ('')
	@param boolean hide_smilies		Transforme smilies or not (false)
	@param boolean silent			Silent edit (false)
	@param boolean bypassperm		Bypass permissions (false)
	@return boolean
	*/
	function edit_post($post_id,$message,$subject='',$hide_smilies=false,$silent=false,$bypassperm=false)
	{
		# Transforme hide_smiley bool in integer for sql query
		$hide_smilies = ($hide_smilies == true || $hide_smilies == 1) ? 1 : 0;

		# Fetch some info about the post, the topic and the forum
		$cur_post = $this->db->select('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$this->db->prefix.'posts AS p INNER JOIN '.$this->db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id) or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
			
		if ($cur_post->isEmpty())
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		# Sort out who the moderators are and if we are currently a moderator (or an admin)
		$mods_array = ($cur_post->f('moderators') != '') ? unserialize($cur_post->f('moderators')) : array();
		$is_admmod = ($this->user['g_id'] == PUN_ADMIN || ($this->user['g_id'] == PUN_MOD && array_key_exists($this->user['username'], $mods_array))) ? true : false;
		
		# Determine whether this post is the "topic post" or not
		$rs = $this->db->select('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id='.$cur_post->f('tid').' ORDER BY posted LIMIT 1') or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
		$topic_post_id = $rs->f('id');
		
		$can_edit_subject = ($id == $topic_post_id && (($this->user['g_edit_subjects_interval'] == '0' || (time() - $cur_post->f('posted')) < $this->user['g_edit_subjects_interval']) || $is_admmod)) ? true : false;
		
		# Do we have permission to edit this post?
		if (($this->user['g_edit_posts'] == '0' ||
			$cur_post->f('poster_id') != $this->user['id'] ||
			$cur_post->f('closed') == '1') &&
			!$is_admmod && !$bypassperm)
		{
			$this->set_error($this->lang['common']['No permission']);
			return false;
		}
		
		# Load the post.php language file
		require PUN_ROOT.'lang/'.$this->user['language'].'/post.php';
		
		# If it is a topic it must contain a subject and we have to check it
		if ($can_edit_subject)
		{
			if (!$this->_pre_post_title($subject))
				return false;
		}
		
		# Clean up message
		if (!$this->_pre_post_message($message))
			return false;
		
		# Did everything go according to plan?
		if ($this->has_error())
			return false;
		else 
		{
			$edited_sql = (!$silent || !$is_admmod) ? $edited_sql = ', edited='.time().', edited_by=\''.$this->db->escape($this->user['username']).'\'' : '';
	
			if ($can_edit_subject)
			{
				# Update the topic and any redirect topics
				$this->db->query('UPDATE '.$this->db->prefix.'topics SET subject=\''.$this->db->escape($subject).'\' WHERE id='.$cur_post->f('tid').' OR moved_to='.$cur_post->f('tid')) or $this->fatal_error('Unable to update topic', __FILE__, __LINE__, $this->db->error());
	
				# We changed the subject, so we need to take that into account when we update the search words
				$this->_update_search_index('edit', $id, $message, $subject);
			}
			else
				$this->_update_search_index('edit', $id, $message);
	
			# Update the post
			$this->db->query('UPDATE '.$this->db->prefix.'posts SET message=\''.$this->db->escape($message).'\', hide_smilies=\''.$hide_smilies.'\''.$edited_sql.' WHERE id='.$id) or $this->fatal_error('Unable to update post', __FILE__, __LINE__, $this->db->error());
		}
	
		return true;
	}

	
	/**
	@fucntion del_post
	
	Delete a given post
	
	@param	integer	post_id			Post ID to delete
	@param	boolean	bypassperm		Bypass permissions (false)
	@return boolean
	*/
	function del_post($post_id,$bypassperm=false)
	{
		$post_id = intval($post_id);
		
		# Fetch some info about the post, the topic and the forum
		$result = $this->db->query('SELECT f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.posted, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies FROM '.$this->db->prefix.'posts AS p INNER JOIN '.$this->db->prefix.'topics AS t ON t.id=p.topic_id INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id='.$this->user['g_id'].') WHERE (fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$post_id) or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
		
		if (!$this->db->num_rows($result))
		{
			$this->set_error($this->lang['common']['Bad request']);
			return false;
		}
		
		$cur_post = $this->db->fetch_assoc($result);
		
		# Sort out who the moderators are and if we are currently a moderator (or an admin)
		$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
		$is_admmod = ($this->user['g_id'] == PUN_ADMIN || ($this->user['g_id'] == PUN_MOD && array_key_exists($this->user['username'], $mods_array))) ? true : false;
		
		# Determine whether this post is the "topic post" or not
		$result = $this->db->query('SELECT id FROM '.$this->db->prefix.'posts WHERE topic_id='.$cur_post['tid'].' ORDER BY posted LIMIT 1') or $this->fatal_error('Unable to fetch post info', __FILE__, __LINE__, $this->db->error());
		$topic_post_id = $this->db->result($result);
		
		$is_topic_post = ($post_id == $topic_post_id) ? true : false;
		
		# Do we have permission to edit this post?
		if (!$bypassperm && (($this->user['g_delete_posts'] == '0' ||
			($this->user['g_delete_topics'] == '0' && $is_topic_post) ||
			$cur_post['poster_id'] != $this->user['id'] ||
			$cur_post['closed'] == '1') &&
			!$is_admmod))
		{
			$this->set_error($this->lang['common']['No permission']);
			return false;
		}

		if ($is_topic_post)
		{
			# Delete the topic and all of it's posts
			$this->_delete_topic($cur_post['tid']);
			$this->_update_forum($cur_post['fid']);
		}
		else {
			# Delete just this one post
			$this->_delete_post($post_id, $cur_post['tid']);
			$this->_update_forum($cur_post['fid']);
		}
		
		return true;
	}


	/** News
	----------------------------------------------------------*/

	/**
	@function get_news
	@author Morph1er (from Puntal project)
	
	Return topics and posts, like some news
	
	@param	string	forum_id	Forum(s) ID(s) ; seperate multiples by comma
	@param	mixed	limit		Number of news to return ('10')
	@return recordset
	*/
	function get_news($forum_id,$limit=10)
	{
		$fids = explode(',', $forum_id);
		foreach ($fids as $fid)
			$tmp_news_id[] = " t.forum_id='".$fid."'";

		$strReq = 
		'SELECT u.use_avatar, p.message, p.hide_smilies, p.poster_id, t.id as tid, p.poster, '.
		't.subject, t.posted, t.last_post,t.num_replies,t.num_views, f.id AS fid, f.forum_name '.
		'FROM '.$this->db->prefix.'posts AS p '.
		'	INNER JOIN '.$this->db->prefix.'users AS u ON u.id=p.poster_id,	'.$this->db->prefix.'topics AS t '.
		'	INNER JOIN '.$this->db->prefix.'forums AS f ON f.id=t.forum_id '.
		'	LEFT JOIN '.$this->db->prefix.'forum_perms AS fp ON (fp.forum_id=f.id AND fp.group_id=3) '.
		'WHERE p.posted=t.posted '.
		'AND (fp.read_forum IS NULL OR fp.read_forum=1) '.
		'AND t.moved_to IS NULL '.
		'AND  t.sticky=0 '.
		'AND ('.implode(' OR ', $tmp_news_id).') '.
		'ORDER BY t.posted DESC';

		if (!empty($limit))
		{
			$limit = (preg_match('/^[0-9]+$/',$limit)) ? '0,'.$limit : $limit;
			$strReq .= ' LIMIT '.$limit.' ';
		}

		$rs = $this->db->select($strReq) or $this->fatal_error('Unable to fetch news', __FILE__, __LINE__, $this->db->error());

		return $rs;		
	}


	/** Public emails methods
	----------------------------------------------------------*/

	/**
	@function is_valid_email
	
	Validate an e-mail address
	
	@param	string	email	Email to check
	@return	boolean
	*/
	function is_valid_email($email)
	{
		if (strlen($email) > 50)
			return false;
	
		return preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email);
	}

	/**
	@function is_banned_email
	
	Check if $email is banned
	
	@param	string	email	Email to check
	@return	boolean
	*/
	function is_banned_email($email)
	{
		foreach ($this->bans as $cur_ban)
		{
			if ($cur_ban['email'] != '' &&
				($email == $cur_ban['email'] ||
				(strpos($cur_ban['email'], '@') === false && stristr($email, '@'.$cur_ban['email']))))
				return true;
		}
	
		return false;
	}

	/**
	@function mail
	
	Wrapper for PHP's mail()
	
	@param	string	to			Recipient of the email
	@param	string	subject		Subject of the email to send
	@param	string	message		Content of the email to send
	@param	string	from		Sender/return address ('')
	*/
	function mail($to, $subject, $message, $from = '')
	{
		# Default sender/return address
		if (!$from)
			$from = '"'.str_replace('"', '', $this->config['o_board_title'].' '.$this->lang['common']['Mailer']).'" <'.$this->config['o_webmaster_email'].'>';
	
		# Do a little spring cleaning
		$to = trim(preg_replace('#[\n\r]+#s', '', $to));
		$subject = trim(preg_replace('#[\n\r]+#s', '', $subject));
		$from = trim(preg_replace('#[\n\r:]+#s', '', $from));
	
		$headers = 'From: '.$from."\r\n".'Date: '.date('r')."\r\n".'MIME-Version: 1.0'."\r\n".'Content-transfer-encoding: 8bit'."\r\n".'Content-type: text/plain; charset='.$this->lang['common']['lang_encoding']."\r\n".'X-Mailer: PunBB Mailer';
	
		# Make sure all linebreaks are CRLF in message
		$message = str_replace("\n", "\r\n", $this->linebreaks($message));
	
		if ($this->config['o_smtp_host'] != '')
			$this->_smtp_mail($to, $subject, $message, $headers);
		else
		{
			# Change the linebreaks used in the headers according to OS
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'MAC')
				$headers = str_replace("\r\n", "\r", $headers);
			else if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
				$headers = str_replace("\r\n", "\n", $headers);
	
			mail($to, $subject, $message, $headers);
		}
	}


	/** Dates
	----------------------------------------------------------*/

	/**
	@function format_time
	
	Format a time string according to $time_format and timezones
	
	@param	string	timestamp		The time to format
	@param	boolean	date_only		Only return date (false)
	@param	boolean	relatives		Display or not relatives date "Today" / "Yesterday" (true)
	@return	string
	*/
	function format_time($timestamp, $date_only=false, $relatives=true)
	{
		if ($timestamp == '')
			return $this->lang['common']['Never'];

		$diff = ($this->user['timezone'] - $this->config['o_server_timezone']) * 3600;
		$timestamp += $diff;
		$now = time();

		if ($this->options['punsapi_date_formating'])
		{
			$date = punsapi::str_date($GLOBALS['locales_dates']['default_date_format'], $timestamp);
			$today = punsapi::str_date($GLOBALS['locales_dates']['default_date_format'], $now+$diff);
			$yesterday = punsapi::str_date($GLOBALS['locales_dates']['default_date_format'], $now+$diff-86400);
		}
		else {
			$date = date($this->config['o_date_format'], $timestamp);
			$today = date($this->config['o_date_format'], $now+$diff);
			$yesterday = date($this->config['o_date_format'], $now+$diff-86400);
		}
		
		if ($relatives)
		{
			if ($date == $today)
				$date = $this->lang['common']['Today'];
			else if ($date == $yesterday)
				$date = $this->lang['common']['Yesterday'];
		}

		if (!$date_only)
		{
			if ($this->options['punsapi_date_formating'])
				return $date.' '.punsapi::str_date($GLOBALS['locales_dates']['default_time_format'], $timestamp);
			else
				return $date.' '.date($this->config['o_time_format'], $timestamp);
		}
		else
			return $date;
	}

	/**
	@function format_date
	
	Alias of format_time.
	
	@param	string	timestamp		The time to format
	@param	boolean	date_only		Only return date (false)
	@param	boolean	relatives		Display or not relatives date "Today" / "Yesterday" (true)
	@return	string
	*/
	function format_date($timestamp, $date_only=false, $relatives=true)
	{
		return $this->format_time($timestamp, $date_only, $relatives);
	}

	/**
	@function str_date
	@author 	Olivier Meunier and contributors
	
	Format a date according to locales settings
	
	@param	string	format		Format, using strftime string formats
	@return string
	*/
	function str_date($format,$ts=NULL)
	{
		if ($ts == NULL)
			$ts = time();
	
		$hash = '799b4e471dc78154865706469d23d512';
		$format = preg_replace('/(?<!%)%(a|A)/','{{'.$hash.'__$1%w__}}',$format);
		$format = preg_replace('/(?<!%)%(b|B)/','{{'.$hash.'__$1%m__}}',$format);
		
		$res = strftime($format,$ts);
		
		$res = preg_replace_callback('/{{'.$hash.'__(a|A|b|B)([0-9]{1,2})__}}/',array('punsapi_core','_dates_callback'),$res);
		
		return $res;
	}

	/**
	@function date_to_str
	@author 	Olivier Meunier and contributors
	
	Transform a date to the equivalent string according to localized settings
	
	@param	string	p		Format, using strftime string formats
	@param	string	date	The date to format
	@return string
	*/
	function date_to_str($format,$date)
	{
		return punsapi::str_date($format,strtotime($date));
	}

	/**
	@function iso8601_date
	@author 	Olivier Meunier and contributors
	
	Return a date formated to the iso8601 standard
	
	@param	string	ts		Timestamp to format ('')
	@return string
	*/
	function iso8601_date($ts=NULL)
	{
		if ($ts == NULL)
			$ts = time();
		
		$tz = date('O',$ts);
		$tz = substr($tz,0,-2).':'.substr($tz,-2);
		return date('Y-m-d\\TH:i:s',$ts).$tz;
	}


	/** Public utilities methods
	----------------------------------------------------------*/

	/**
	@function load_lang
	
	Load a language file an pit data in $this->lang array.
	
	@param	string	part	Part/file to load
	@return void
	*/
	function load_lang($part)
	{
		if (!empty($this->lang[$part]))
			return false;

		require $this->pun_root.'lang/'.$this->user['language'].'/'.$part.'.php';
		$var_name = 'lang_'.$part;
		$this->lang[$part] = &$$var_name;
		return true;
	}

	/**
	@function censor_words
	
	Replace censored words in $text
	
	@param	string	text		Text to censor
	@return	string
	*/
	function censor_words($text)
	{
		static $search_for, $replace_with;
	
		# If not already built in a previous call, build an array of censor words and their replacement text
		if (!isset($search_for))
		{
			$result = $this->db->query('SELECT search_for, replace_with FROM '.$this->db->prefix.'censoring')  or $this->fatal_error('Unable to fetch censor word list', __FILE__, __LINE__, $this->db->error());
			$num_words = $this->db->num_rows($result);
	
			$search_for = array();
			for ($i = 0; $i < $num_words; ++$i)
			{
				list($search_for[$i], $replace_with[$i]) = $this->db->fetch_row($result);
				$search_for[$i] = '/\b('.str_replace('\*', '\w*?', preg_quote($search_for[$i], '/')).')\b/i';
			}
		}
	
		if (!empty($search_for))
			$text = substr(preg_replace($search_for, $replace_with, ' '.$text.' '), 1, -1);
	
		return $text;
	}

	/**
	@function parse_message
	
	Parse message text
	*/
	function parse_message($text, $hide_smilies, $enclose_in_paragraph=true)
	{
		if ($this->config['o_censoring'] == '1')
			$text = $this->censor_words($text);
	
		# Convert applicable characters to HTML entities
		$text = $this->htmlspecialchars($text);
	
		# If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
		if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
		{
			list($inside, $outside) = $this->_split_text($text, '[code]', '[/code]');
			$outside = array_map('ltrim', $outside);
			$text = implode('<">', $outside);
		}
	
		if ($this->config['o_make_links'] == '1')
			$text = $this->_do_clickable($text);
	
		if ($this->config['o_smilies'] == '1' && $this->user['show_smilies'] == '1' && !$hide_smilies)
			$text = $this->_do_smilies($text);
	
		if ($this->config['p_message_bbcode'] == '1' && strpos($text, '[') !== false && strpos($text, ']') !== false)
		{
			$text = $this->_do_bbcode($text);
	
			if ($this->config['p_message_img_tag'] == '1')
				$text = preg_replace('#\[img( align=([^\[]*?))?\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e', '$this->_handle_img_tag(\'$3$5\', false, \'$2\')', $text);
		}
	
		# Deal with newlines, tabs and multiple spaces
		$pattern = array("\n", "\t", '  ', '  ');
		$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
		$text = str_replace($pattern, $replace, $text);
	
		# If we split up the message before we have to concatenate it together again (code tags)
		if (isset($inside))
		{
			$outside = explode('<">', $text);
			$text = '';
	
			$num_tokens = count($outside);
	
			for ($i = 0; $i < $num_tokens; ++$i)
			{
				$text .= $outside[$i];
				if (isset($inside[$i]))
				{
					$num_lines = ((substr_count($inside[$i], "\n")) + 3) * 1.5;
					$height_str = ($num_lines > 35) ? '35em' : $num_lines.'em';
					$text .= '</p><div class="codebox"><div class="incqbox"><h4>'.$this->lang['common']['Code'].':</h4><div class="scrollbox" style="height: '.$height_str.'"><pre>'.$inside[$i].'</pre></div></div></div><p>';
				}
			}
		}
	
		# Add paragraph tag around post if needed, but make sure there are no empty paragraphs
		if ($enclose_in_paragraph)
			$text = str_replace('<p></p>', '', '<p>'.$text.'</p>');
		else
			$text = str_replace('<p></p>', '', $text);
	
		return $text;
	}

	/**
	@function parse_signature
	
	Parse signature text
	*/
	function parse_signature($text)
	{
		if ($this->config['o_censoring'] == '1')
			$text = $this->censor_words($text);
	
		$text = $this->htmlspecialchars($text);
	
		if ($this->config['o_make_links'] == '1')
			$text = $this->_do_clickable($text);
	
		if ($this->config['o_smilies_sig'] == '1' && $this->user['show_smilies'] != '0')
			$text = $this->_do_smilies($text);
	
		if ($this->config['p_sig_bbcode'] == '1' && strpos($text, '[') !== false && strpos($text, ']') !== false)
		{
			$text = $this->_do_bbcode($text);
	
			if ($this->config['p_sig_img_tag'] == '1')
				$text = preg_replace('#\[img( align=([^\[]*?))?\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e', '$this->_handle_img_tag(\'$3$5\', true, \'$2\')', $text);
		}
	
		# Deal with newlines, tabs and multiple spaces
		$pattern = array("\n", "\t", '  ', '  ');
		$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
		$text = str_replace($pattern, $replace, $text);
	
		return $text;
	}

	/**
	@function xhtml_to_bbcode
	
	Alias of parse_message but not enclose result in a paragraph
	*/
	function bbcode_to_xhtml($text, $hide_smilies)
	{
		return $this->parse_message($text, $hide_smilies, false);
	}

    /**
    @function xhtml_to_bbcode
	@author Nicolas (nicolas2k10 on punbb.fr)
    
    Transforme les balises XHTML en BBcode et 
    supprime celles qui n'ont pas d'équivalent.
    
    @param string str    Chaîne à convertir
    @return string
    */
    function xhtml_to_bbcode($str)
    {
		# Valeur non-vide (exemple> src="'.$value.'" matchera src="qqchose")
        $value = '[^"]+';
		
		# 0 ou + paramètres et/ou espacements avant UN AUTRE PARAMETRE
        $attr = '(?:\s|[a-zA-Z-]+="'.$value.'")*';
		
		# Idem pour du CSS (exemple> text-decoration: none;)
        $attr_css_not_color = '(?:\s|(?!color)[a-zA-Z-]+\s*\:\s*[^;]*;)*';
		
		# 0 ou + paramètres et/ou espacements avant le > final
        $attr_avant_crochet = '[^>]*';
		
		# 0 ou + paramètres et/ou espacements avant le / (pour les balises seules)
        $attr_avant_slash = '[^/]*';
		
		# Espacements (retours de ligne, tabulations, espaces, etc.)
        $blank = '\s+';
		
		# Format des adresses e-mail
        $format_email = '\w+@\w+\.[a-z]{2,4}'; 


       /*
			- Pour les éléments dont on A BESOIN DE CAPTURER LES ATTRIBUTS 
				COMME src="..." href="..." pour le remplacement
            - Pour les exceptions (tel que la balise "p" qui n'est pas convertie
			  en BBCode)
		*/
        $pattern = array(
            '#<span'.$attr.'style="'.$attr_css_not_color.'color\s*\:\s*([a-zA-Z-]+|\#?[[:xdigit:]]{6})\b[^"]*">(.*)</span>#Us',
            '#<a'.$attr.'href="(?=mailto)mailto:('.$format_email.')"'.$attr_avant_crochet.'>(.*)</a>#Us',
            '#<a'.$attr.'href="(?!mailto)('.$value.')"'.$attr_avant_crochet.'>(.*)</a>#Us',
            '#<img'.$attr.'src="('.$value.')"'.$attr_avant_slash.'/>#Us',
            '#<li'.$attr_avant_crochet.'>(.*)</li>#Us',
            '#<p'.$attr_avant_crochet.'>(.*)</p>#Us'
        );
        $replace = array(
            '[color=$1]$2[/color]',
            '[email=$1]$2[/email]',
            '[url=$1]$2[/url]',
            '[img]$1[/img]',
            '* $1',
            "$1\n\n"
        );

        /*
			Pour tous les élements dont ON N'A PAS BESOIN DE CAPTURER LES
			ATTRIBUTS COMME src="..." href="..." pour le remplacement
		*/
        $xhtml  = array('h[1-6]', 'strong', 'em', 'blockquote');
        $bbcode = array('b', 'b', 'i', 'quote');

        $count  = count($xhtml);
        for ($i=0; $i<$count; $i++)
		{
			$pattern[] = '#<('.$xhtml[$i].')'.$attr_avant_crochet.'>(.*)</\1>#Us';
			$replace[] = '['.$bbcode[$i].']$2[/'.$bbcode[$i].']';
        }

        /*
			Pour permettre de traiter des pages entières 
			via une URL (supprime script, style, title, etc.)
		*/
        $pattern[] = '#<head>.*</head>#Us';
        $replace[] = '<head></head>';

        /*
			Action !
		*/
        $str = preg_replace($pattern, $replace, $str);

        # Espacements, retours de ligne et caracères spéciaux
        $pattern = array('<br />', '    ', '	', '&amp;', '&lt;', '&gt;');
        $replace = array("\n", "\t", "\t", '&', '<', '>');
        $str = str_replace($pattern, $replace, $str);

        return strip_tags($str);
    }

	/**
	@function htmlspecialchars
	
	Equivalent to PHP htmlspecialchars(), but allows &#[0-9]+ (for unicode)
	
	@param	string	str		String to format
	@return	string
	*/
	function htmlspecialchars($str)
	{
		$str = preg_replace('/&(?!#[0-9]+;)/s', '&amp;', $str);
		$str = str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $str);

		return $str;
	}

	/**
	@function strlen
	
	Equivalent to PHP strlen(), but counts &#[0-9]+ as one character (for unicode)
	
	@param	string	str		String to format
	@return	string
	*/
	function strlen($str)
	{
		return strlen(preg_replace('/&#([0-9]+);/', '!', $str));
	}

	/**
	@function linebreaks
	
	Convert \r\n and \r to \n
	
	@param	string	str		String to format
	@return	string
	*/
	function linebreaks($str)
	{
		return str_replace("\r", "\n", str_replace("\r\n", "\n", $str));
	}

	/**
	@function trim
	
	A more aggressive version of trim()
	
	@param	string	str		String to format
	@return	string
	*/
	function trim($str)
	{
		if (strpos($this->lang['common']['lang_encoding'], '8859') !== false)
		{
			$fishy_chars = array(chr(0x81), chr(0x8D), chr(0x8F), chr(0x90), chr(0x9D), chr(0xA0));
			return trim(str_replace($fishy_chars, ' ', $str));
		}
		else
			return trim($str);
	}

	/**
	@function finish
	
	Finalize a page with this methods
	@return void
	*/
	function finish()
	{		
		$this->db->end_transaction();
		$this->db->close();
		exit();
	}


	/** Errors management methods
	----------------------------------------------------------*/

	/**
	@function reset_error
	
	Reset error array stack
	*/
	function reset_error()
	{
		$this->error = array();
	}

	/**
	@function has_error
	
	Retourne vrai si il y a des erreurs.
	*/
	function has_error()
	{
		if (!empty($this->error))
			return true;
		
		return false;
	}

	/**
	@function set_error
	
	Add an error to array stack
	
	@param	string	msg		Message
	@return void
	*/
	function set_error($message)
	{
		$this->error[] = $message;
	}

	/**
	@function get_error
	
	Return the error array stack or false if no error.
	
	This method could return error in HTML format by passing 
	the parameter to true.
		
	@param	boolean	html		HTML format (false)
	@return mixed
	*/
	function get_error($html=false)
	{
		if (count($this->error) > 0)
		{
			if ($html)
			{
				$res = '';
				foreach ($this->error as $err)
					$res .= '<li class="erritem"><span class="errmsg">'.$err.'</span></li>'."\n";
				
				return '<ul class="errlist">'."\n".$res."</ul>\n";
			}
			else 
				return $this->error;
		}
		else
			return false;
	}

	/**
	@function fatal_error
	
	Display a fatal error message.
	
	@param	string	fatalError		Error message
	@param	string	file			File name
	@param	string	line			Line
	@param	boolean	db_error		Display database error
	@return void
	*/
	function fatal_error($message, $file='', $line='', $db_error=false)
	{
		# Set a default title if the script failed before $this->config could be populated
		if (empty($this->config))
			$this->config['o_board_title'] = 'PunBB';
	
		# Empty all output buffers and stop buffering
		while (@ob_end_clean());
	
		# "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
		if (!empty($this->config['o_gzip']) && extension_loaded('zlib') && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
			ob_start('ob_gzhandler');
		
		# Display fatal error page
		require dirname(__FILE__).'/fatal_error.php';
			
		# If a database connection was established (before this error) we close it
		if ($db_error) $this->db->close();
	
		exit;
	}


	/** Debug methods
	----------------------------------------------------------*/

	/**
	@function get_exec_time
	
	Display the execution time of the script
	
	@param	boolean	return		Return value, not display it (false)
	*/
	function get_exec_time($return=false)
	{
		if (!$this->options['debug'])
			return false;
		
		list($usec, $sec) = explode(' ', microtime());
		$this->time_diff = sprintf('%.3f', ((float)$usec + (float)$sec) - $this->start_time);
		
		if ($return)
			return $this->time_diff;
		
		echo $this->time_diff;
		return true;
	}

	/**
	@function get_num_queries
	
	Display the total of queries executed
	
	@param	boolean	return		Return value, not display it (false)
	*/
	function get_num_queries($return=false)
	{
		if (!$this->options['debug'])
			return false;
		
		if ($return)
			return $this->db->get_num_queries();
		
		echo $this->db->get_num_queries();
		return true;
	}

	/**
	@function get_debug_line
	
	Display a debug line
	
	@param	string	str			Format string of the line
	@param	boolean	return		Return value, not display it (false)
	*/
	function get_debug_line($str='<p id="debug_line">[ Generated in %1$s seconds, %2$s queries executed ]</p>',$return=false)
	{
		if (!$this->options['debug'])
			return false;
				
		if ($return)
			return sprintf($str, $this->get_exec_time(true), $this->get_num_queries(true));
		
		printf($str, $this->get_exec_time(true), $this->get_num_queries(true));
		return true;
	}

	/**
	@function var_export
	
	Display a PHP representation of $datas
	
	@param	mixed	datas		Datas to display
	@param	boolean	return		Return value, not display it (false)
	*/
	function var_export($datas, $return=false)
	{
		if (!$this->options['debug'])
			return false;
		
		$str = "<pre>\n".var_export($datas,true)."</pre>\n";
		
		if ($return)
			return $str;
		
		echo $str;
		return true;
	}


} /** class punsapi */

?>