<?php
/**
#########################################################################################################
# Copyright (c) 2009 - 2010 CustomCode.info. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# URL:              [url]http://customcode.info[/url]
# Function:         ClipBucket Desktop Uploader Handler
# Author:           fwhite
# Language:         PHP
# License:          Commercial
# ----------------- THIS IS NOT FREE SOFTWARE ----------------
# Version:          $Id: desktop.class.php 1 2010-03-30 05:30:58Z Q $
# Created:          Thursday, January 07, 2010 / 05:30 PM GMT+1 (fwhite)
# Last Modified:    $Date: 2010-03-30 07:30:58 +0200 (Tue, 30 Mar 2010) $
# Notice:           Please maintain this section
#########################################################################################################
*/

error_reporting(0);
ini_set('display_errors', false);

class desktop
{
// START desktop.class.php
    function __construct( $tablePrefix = '' )
    {
        if( !defined('TABLE_PREFIX') ) {
            define( 'TABLE_PREFIX', $tablePrefix );
        }
    }
	
    function login($username,$password)
    {
        $sql    = "SELECT * FROM ".TABLE_PREFIX."_users WHERE username = '".mysql_real_escape_string($username)."'";
        $sql   .= " AND password = '".mysql_real_escape_string($password)."'";
        $sql   .= " OR email = '".mysql_real_escape_string($username)."'";
        $sql   .= " AND password = '".mysql_real_escape_string($password)."' LIMIT 1";
        $res    = mysql_query($sql) OR die(mysql_error());

        if(mysql_num_rows($res) > 0)
        {
            $data = mysql_fetch_array($res) OR die(mysql_error());

            if($data['ban_status'] != 'no')
            {
                return 'account_banned';
            }

            if($data['active'] != 'yes')
            {
                return 'not_active';
            }

            $perms = self::fetch_usergroup_permissions($data['level']);

            if($perms['can_desktop'] != 'yes')
            {
                return 'no_desktop';
            }

            $_SESSION['email']          = $data['email'];
            $_SESSION['userid']         = $data['userid'];
            $_SESSION['username']       = $username;
            $_SESSION['level']          = $data['level'];
            $_SESSION['start_time']     = time();
            $_SESSION['last_active']    = time();
            $_SESSION['max_audio_size'] = $perms['desktop_max_audio_size'];
            $_SESSION['max_image_size'] = $perms['desktop_max_image_size'];
            $_SESSION['max_video_size'] = $perms['desktop_max_video_size'];
            $_SESSION['Audio-Moderate'] = $perms['moderate_desktop_audio'];
            $_SESSION['Image-Moderate'] = $perms['moderate_desktop_image'];
            $_SESSION['Video-Moderate'] = $perms['moderate_desktop_video'];
            $_SESSION['allow_audio']    = $perms['desktop_allow_audio'];
            $_SESSION['allow_image']    = $perms['desktop_allow_image'];
            $_SESSION['allow_video']    = $perms['desktop_allow_video'];
            $_SESSION['allow_ytdl']     = $perms['desktop_allow_youtube_download'];
            $_SESSION['allow_ytembed']  = $perms['desktop_allow_youtube_embed'];

            $sql    = "UPDATE ".TABLE_PREFIX."users SET last_logged = '".self::current_mysql_timestamp()."'";
            $sql   .= " WHERE userid = '".$_SESSION['userid']."' LIMIT 1";
            mysql_query($sql) OR die(mysql_error());

            self::update_online_status();
            return 'OK';
        }
        else
        {
            return 'invalid';
        }
    }

    function logout()
    {
        global $rsa;

        self::drop_online_status();
        $pack = $rsa->Sign(0);
        echo base64_encode($rsa->Encrypt($pack));
        setcookie('PHPSESSID','',time()-3600,'/');
        session_unset();
        session_destroy();
    }

    function fetch_categories($type = 'video')
    {
        $sql    = "SELECT * FROM ".TABLE_PREFIX."video_categories ";
        $sql   .= "WHERE category_type = '".$type."' ";
        $sql   .= "ORDER BY category_name ASC";
        $res    = mysql_query($sql) or die(mysql_error());
        if(mysql_num_rows($res) > 0)
        {
            $data = array();
            while($row = mysql_fetch_array($res))
            {
                $data[] = $row;
            }
            return $data;
        }
    }

    function media_md5_exists($md5)
    {
        $sql    = "SELECT media_md5 FROM ".TABLE_PREFIX."video";
        $sql   .= " WHERE media_md5 = '".$md5."' LIMIT 1";
        $res    = mysql_query($sql) or die(mysql_error());
        if(mysql_num_rows($res) > 0)
        {
            return true;
        }
    }

    function torrent_md5_exists($md5)
    {
        $sql    = "SELECT torrent_md5 FROM ".TABLE_PREFIX."video";
        $sql   .= " WHERE torrent_md5 = '".$md5."' LIMIT 1";
        $res    = mysql_query($sql) or die(mysql_error());
        if(mysql_num_rows($res) > 0)
        {
            return true;
        }
    }

    function add_video($entry=array())
    {
        global $config, $desktop_info;

        extract($entry);

        $torrent = ($has_torrent == 1) ? $file_name.'.torrent' : NULL;

        switch($desktop_info['ModerateRules'])
        {
            case 'global':
                $active = ($desktop_info['Video-Moderate'] == 1) ? 'no' : 'yes';
                break;
            case 'per_user':
                $active = ($_SESSION['Video-Moderate'] == 'yes') ? 'no' : 'yes';
                break;
            case 'site':
                $active = ($config['activation'] == 1) ? 'no' : 'yes';
                break;
            default:
                $active = 1;
        }

        $sql    = "INSERT INTO ".TABLE_PREFIX."video(videokey,userid,title,file_name,original_filename,";
        $sql   .= "description,tags,category,broadcast,location,datecreated,";
        $sql   .= "country,allow_embedding,allow_comments,comment_voting,";
        $sql   .= "allow_rating,active,date_added,duration,status,embed_code,embed_url,";
        $sql   .= "yt_fmt,from_desktop,width,height,aspect_ratio,torrent,torrent_md5,is_audio,";
        $sql   .= "is_image,audio_pic,uploader_ip,media_md5,is_divx,is_mp4";
        $sql   .= ") VALUES ('".mysql_real_escape_string($videokey)."',";
        $sql   .= "'".mysql_real_escape_string($userid)."',";
        $sql   .= "'".mysql_real_escape_string($title)."',";
        $sql   .= "'".mysql_real_escape_string($file_name)."',";
        $sql   .= "'".mysql_real_escape_string($original_filename)."',";
        $sql   .= "'".mysql_real_escape_string($description)."',";
        $sql   .= "'".mysql_real_escape_string($tags)."',";
        $sql   .= "'".mysql_real_escape_string($category)."',";
        $sql   .= "'".mysql_real_escape_string($broadcast)."',";
        $sql   .= "'".mysql_real_escape_string(@$location)."',";
        $sql   .= "'".mysql_real_escape_string(@$datecreated)."',";
        $sql   .= "'".mysql_real_escape_string(@$country)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_embedding)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_comments)."',";
        $sql   .= "'".mysql_real_escape_string(@$comment_voting)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_rating)."',";
        $sql   .= "'".mysql_real_escape_string($active)."',";
        $sql   .= "'".self::current_mysql_timestamp()."',";
        $sql   .= "'".mysql_real_escape_string($duration)."',";
        $sql   .= "'Successful',";
        $sql   .= "'".mysql_real_escape_string(@$embed_code)."',";
        $sql   .= "'".mysql_real_escape_string(@$embed_url)."',";
        $sql   .= "'".@$yt_fmt."','1',";
        $sql   .= "'".@$width."','".@$height."','".@$aspect_ratio."','".$torrent."',";
        $sql   .= "'".@$torrent_md5."','".@$is_audio."','".@$is_image."',";
        $sql   .= "'".$audio_pic."','".$_SERVER['REMOTE_ADDR']."',";
        $sql   .= "'".$media_md5."','".$is_divx."','".$is_mp4."'";
        $sql   .= ")";

        mysql_query($sql) OR die(mysql_error());
        self::increment_video_count();
    }

    function add_image($entry=array())
    {
        global $config, $desktop_info;

        extract($entry);
        $torrent = ($has_torrent == 1) ? $file_name.'.torrent' : '';

        switch($desktop_info['ModerateRules'])
        {
            case 'global':
                $active = ($desktop_info['Image-Moderate'] == 1) ? 'no' : 'yes';
                break;
            case 'per_user':
                $active = ($_SESSION['Image-Moderate'] == 'yes') ? 'no' : 'yes';
                break;
            case 'site':
                $active = ($config['activation'] == 1) ? 'no' : 'yes';
                break;
            default:
                $active = 'yes';
        }

        $sql    = "INSERT INTO ".TABLE_PREFIX."video(videokey,userid,title,file_name,original_filename,";
        $sql   .= "description,tags,category,broadcast,location,datecreated,";
        $sql   .= "country,allow_embedding,allow_comments,comment_voting,";
        $sql   .= "allow_rating,active,date_added,status,";
        $sql   .= "from_desktop,width,height,torrent,torrent_md5,";
        $sql   .= "is_image,uploader_ip,media_md5";
        $sql   .= ") VALUES ('".mysql_real_escape_string($videokey)."',";
        $sql   .= "'".mysql_real_escape_string($userid)."',";
        $sql   .= "'".mysql_real_escape_string($title)."',";
        $sql   .= "'".mysql_real_escape_string($file_name)."',";
        $sql   .= "'".mysql_real_escape_string($original_filename)."',";
        $sql   .= "'".mysql_real_escape_string($description)."',";
        $sql   .= "'".mysql_real_escape_string($tags)."',";
        $sql   .= "'".mysql_real_escape_string($category)."',";
        $sql   .= "'".mysql_real_escape_string($broadcast)."',";
        $sql   .= "'".mysql_real_escape_string(@$location)."',";
        $sql   .= "'".mysql_real_escape_string(@$datecreated)."',";
        $sql   .= "'".mysql_real_escape_string(@$country)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_embedding)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_comments)."',";
        $sql   .= "'".mysql_real_escape_string(@$comment_voting)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_rating)."',";
        $sql   .= "'".mysql_real_escape_string($active)."',";
        $sql   .= "'".self::current_mysql_timestamp()."',";
        $sql   .= "'Successful',";
        $sql   .= "'1',";
        $sql   .= "'".@$width."','".@$height."','".$torrent."',";
        $sql   .= "'".@$torrent_md5."','1',";
        $sql   .= "'".$_SERVER['REMOTE_ADDR']."',";
        $sql   .= "'".$media_md5."'";
        $sql   .= ")";

        mysql_query($sql) OR die(mysql_error());
    }

    function add_audio($entry=array())
    {
        global $config, $desktop_info;

        extract($entry);
        $torrent = ($has_torrent == 1) ? $file_name.'.torrent' : '';

        switch($desktop_info['ModerateRules'])
        {
            case 'global':
                $active = ($desktop_info['Audio-Moderate'] == 1) ? 'no' : 'yes';
                break;
            case 'per_user':
                $active = ($_SESSION['Audio-Moderate'] == 'yes') ? 'no' : 'yes';
                break;
            case 'site':
                $active = ($config['activation'] == 1) ? 'no' : 'yes';
                break;
            default:
                $active = 'yes';
        }

        $sql    = "INSERT INTO ".TABLE_PREFIX."video(videokey,userid,title,file_name,original_filename,";
        $sql   .= "description,tags,category,broadcast,location,datecreated,";
        $sql   .= "country,allow_embedding,allow_comments,comment_voting,";
        $sql   .= "allow_rating,active,date_added,status,";
        $sql   .= "from_desktop,width,height,torrent,torrent_md5,";
        $sql   .= "is_audio,audio_pic,uploader_ip,media_md5,duration";
        $sql   .= ") VALUES ('".mysql_real_escape_string($videokey)."',";
        $sql   .= "'".mysql_real_escape_string($userid)."',";
        $sql   .= "'".mysql_real_escape_string($title)."',";
        $sql   .= "'".mysql_real_escape_string($file_name)."',";
        $sql   .= "'".mysql_real_escape_string($original_filename)."',";
        $sql   .= "'".mysql_real_escape_string($description)."',";
        $sql   .= "'".mysql_real_escape_string($tags)."',";
        $sql   .= "'".mysql_real_escape_string($category)."',";
        $sql   .= "'".mysql_real_escape_string($broadcast)."',";
        $sql   .= "'".mysql_real_escape_string(@$location)."',";
        $sql   .= "'".mysql_real_escape_string(@$datecreated)."',";
        $sql   .= "'".mysql_real_escape_string(@$country)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_embedding)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_comments)."',";
        $sql   .= "'".mysql_real_escape_string(@$comment_voting)."',";
        $sql   .= "'".mysql_real_escape_string(@$allow_rating)."',";
        $sql   .= "'".mysql_real_escape_string($active)."',";
        $sql   .= "'".self::current_mysql_timestamp()."',";
        $sql   .= "'Successful',";
        $sql   .= "'1',";
        $sql   .= "'".@$width."','".@$height."','".$torrent."',";
        $sql   .= "'".@$torrent_md5."','1','".@$audio_pic."',";
        $sql   .= "'".$_SERVER['REMOTE_ADDR']."',";
        $sql   .= "'".$media_md5."','".$duration."'";
        $sql   .= ")";

        mysql_query($sql) OR die(mysql_error());
    }

	function fetch_desktop_details()
    {
	    $query = mysql_query("SELECT * FROM ".TABLE_PREFIX."desktop_config");
	        while($row = mysql_fetch_array($query))
            {
	            $name = $row['name'];
	            $data[$name] = $row['value'];
	        }
	    return $data;
	}

	function fetch_site_config()
    {
	    $query = mysql_query("SELECT * FROM ".TABLE_PREFIX."config");
	        while($row = mysql_fetch_array($query))
            {
	            $name = $row['name'];
	            $data[$name] = $row['value'];
	        }
	    return $data;
	}

	function set_desktop_config($name,$value)
    {
	    mysql_query("UPDATE ".TABLE_PREFIX."desktop_config SET value = '".$value."' WHERE name ='".$name."'");
	}

	function fetch_desktop_lang($iso_code)
    {
	    $query = mysql_query("SELECT * FROM desktop_phrase WHERE lang_iso = '".$iso_code."'");
	        while($row = mysql_fetch_array($query))
            {
	            $varname = $row['varname'];
	            $data[$varname] = $row['text'];
	        }
	    return $data;
	}

	function user_avatar($userid,$size='')
	{
	    $query  = "SELECT avatar, avatar_url FROM ".TABLE_PREFIX."users ";
        $query .= "WHERE userid = '".$userid."' LIMIT 1";
        $res    = mysql_query($query) OR die(mysql_error());
	    $data   = mysql_fetch_array($res);

        if(!strlen($data['avatar']) && !strlen($data['avatar_url']))
        {
            return BASEURL.'/images/avatars/no_avatar-small.png';
        }

        if(strlen($data['avatar_url']))
        {
            return $data['avatar_url'];
        }

		$thumb_file = BASEDIR.'/images/avatars/'.$data['avatar'];
		if(file_exists($thumb_file))
        {
		    $thumb_file = BASEURL.'/images/avatars/'.$data['avatar'];
        }
		else
        {
			$thumb_file = BASEURL.'/images/avatars/no_avatar-small.png';
        }
		$ext    = self::fetch_file_extension($thumb_file);
		$file   = self::fetch_filename($thumb_file);
		if(!empty($size))
        {
			$thumb = $file.'-'.$size.'.'.$ext;
        }
		else
        {
			$thumb = $file.'.'.$ext;
        }
		return $thumb;
	}

    function WriteLog($filename, $msg, $mode = 'a')
    {
	    $fp = fopen($filename, $mode);
        fwrite($fp, "[" .date('l, F j, Y / h:i:s A T (\G\M\TO)'). "] -- ");
	    fwrite($fp, $msg. "\n");
	    fclose($fp);
    }

    function fetch_file_extension($file)
    {
        return substr($file, strrpos($file,'.') + 1);
    }

    function fetch_filename($file)
    {
        $new_name = substr($file, 0, strrpos($file, '.'));
        return $new_name;
    }

    // generate a random character string
    function generate_random_string($length = 20, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
    {
        // Length of character list
        $chars_length = (strlen($chars) - 1);

        // Start our string
        $string = $chars{rand(0, $chars_length)};

        // Generate random string
        for ($i = 1; $i < $length; $i = strlen($string))
        {
            // Grab a random character from our list
            $r = $chars{rand(0, $chars_length)};

            // Make sure the same two characters don't appear next to each other
            if ($r != $string{$i - 1})
            {
                $string .=  $r;
            }
        }

        // return the string
        return $string;
    }

    function generate_random_number($length = 9, $chars = '0123456789')
    {
        // Length of character list
        $chars_length = (strlen($chars) - 1);

        // Start our string
        $string = $chars{rand(0, $chars_length)};

        // Generate random string
        for ($i = 1; $i < $length; $i = strlen($string))
        {
            // Grab a random character from our list
            $r = $chars{rand(0, $chars_length)};

            // Make sure the same two characters don't appear next to each other
            if ($r != $string{$i - 1})
            {
                $string .=  $r;
            }
        }

        // return the string
        return $string;
    }

    function current_mysql_timestamp()
    {
        return date('Y-m-d H:i:s', time());
    }

    function current_mysql_date()
    {
        return date('Y-m-d', time());
    }

    function fetch_mysql_date($time)
    {
        return date('Y-m-d', $time);
    }

    function update_online_status()
    {
        if(!isset($_SESSION['userid']))
        {
            return;
        }

        $sql    = "UPDATE ".TABLE_PREFIX."users SET last_active = '".self::current_mysql_timestamp()."',";
        $sql   .= "ip = '".$_SERVER['REMOTE_ADDR']."' ";
        $sql   .= "WHERE userid = '".mysql_real_escape_string($_SESSION['userid'])."' LIMIT 1";
        mysql_query($sql) OR die(mysql_error());
    }

    function drop_online_status()
    {
        if(!isset($_SESSION['userid']))
        {
            return;
        }
    }

    function category_count($type = 'video')
    {
        $sql    = "SELECT COUNT(type) AS count FROM ".TABLE_PREFIX."video_categories";
        $sql   .= " WHERE type = '".$type."'";

        $res    = mysql_query($sql) or die(mysql_error());
        $total  = mysql_fetch_array($res);

        return $total['count'];
    }

    function eval_session()
    {
        global $rsa, $rsaResponse;
        $response = array();

        if(!isset($_SESSION))
        {
            return;
        }

        // check session timeout
        if(isset($_SESSION) && isset($_SESSION['start_time']))
        {
            $_SESSION['last_active'] = time();
            if(SESSION_TIMEOUT > 0)
            {
	            $session_life = $_SESSION['last_active'] - $_SESSION['start_time'];
	            if($session_life > SESSION_TIMEOUT)
                {
                    $error  = 202;
                    $error .= "\nErrorMessage: Session timed out. Please re-login.";
                    $pack   = $rsa->Sign($error);
                    $output = base64_encode($rsa->Encrypt($pack));
                    echo($output);
                    exit;
                }
                if($session_life < SESSION_TIMEOUT)
                {
                    setcookie(session_name(), $_COOKIE[session_name()], time()+COOKIE_TIMEOUT, '/');
                    setcookie('PHPSESSID',$_COOKIE['PHPSESSID'],time()+COOKIE_TIMEOUT,'/');
                    self::update_user_session();
                }
            }
            else
            {
                @setcookie(session_name(), $_COOKIE[session_name()], time()+COOKIE_TIMEOUT, '/');
                setcookie('PHPSESSID',$_COOKIE['PHPSESSID'],time()+COOKIE_TIMEOUT,'/');
                self::update_user_session();
            }
        }
    }

    function check_phpini_value($bytes,$value)
    {
        $check  = ini_get($value);
        if(preg_match('/M/i', $check))
        {
            $check = $check * 1048576;
            $check = str_replace('M','',$check);
            if($bytes > $check)
            {
                return true;
            }
        }
        elseif(preg_match('/G/i', $check))
        {
            $check = str_replace('G','',$check);
            $check = $check * 1073741824;
            if($bytes > $check)
            {
                return true;
            }
        }
        elseif(preg_match('/K/i', $check))
        {
            $check = str_replace('K','',$check);
            $check = $check * 1024;
            if($bytes > $check)
            {
                return true;
            }
        }
        else
        {
            if($bytes > $check)
            {
                return true;
            }
        }
    }

    function get_phpini_byte_value($value)
    {
        $check = ini_get($value);
        if(preg_match('/M/i', $check))
        {
            $check = $check * 1048576;
            $check = str_replace('M','',$check);
            return $check;
        }
        elseif(preg_match('/G/i', $check))
        {
            $check = str_replace('G','',$check);
            $check = $check * 1073741824;
            return $check;
        }
        elseif(preg_match('/K/i', $check))
        {
            $check = str_replace('K','',$check);
            $check = $check * 1024;
            return $check;
        }
        else
        {
            return $check;
        }
    }

    function convert_filesize($size)
    {
        $filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }

    function update_user_session()
    {
        global $rsa, $rsaResponse;
        $response = array();

        if(!isset($_SESSION['userid']))
        {
            return;
        }

        $sql    = "SELECT * FROM ".TABLE_PREFIX."users WHERE userid = '".mysql_real_escape_string($_SESSION['userid'])."'";
        $sql   .= " LIMIT 1";
        $res    = mysql_query($sql) OR die(mysql_error());

        if(mysql_num_rows($res) > 0)
        {
            $data   = mysql_fetch_array($res) or die(mysql_error());
            $perms  = self::fetch_usergroup_permissions($data['level']);

            if($data['ban_status'] != 'no')
            {
                $error  = 2;
                $error .= "\nErrorMessage: This user account (".$data['username'].")";
                $error .= " is banned.<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                self::drop_php_session();
                exit;
            }

            if($perms['can_desktop'] != 'yes')
            {
                $error  = 2;
                $error .= "\nErrorMessage: Desktop upload rights revoked for this user account ";
                $error .= "(".$data['username'].").";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                self::drop_php_session();
                exit;
            }

            if($data['active'] != 'yes')
            {
                $error  = 2;
                $error .= "\nErrorMessage: This user account (".$data['username'].")";
                $error .= " is not active.<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                self::drop_php_session();
                exit;
            }

            // update $_SESSION
            $_SESSION['email']          = $data['email'];
            $_SESSION['level']          = $data['level'];
            $_SESSION['username']       = $data['username'];
            $_SESSION['last_active']    = time();
            $_SESSION['max_audio_size'] = $perms['desktop_max_audio_size'];
            $_SESSION['max_image_size'] = $perms['desktop_max_image_size'];
            $_SESSION['max_video_size'] = $perms['desktop_max_video_size'];
            $_SESSION['Audio-Moderate'] = $perms['moderate_desktop_audio'];
            $_SESSION['Image-Moderate'] = $perms['moderate_desktop_image'];
            $_SESSION['Video-Moderate'] = $perms['moderate_desktop_video'];
            $_SESSION['allow_audio']    = $perms['desktop_allow_audio'];
            $_SESSION['allow_image']    = $perms['desktop_allow_image'];
            $_SESSION['allow_video']    = $perms['desktop_allow_video'];
            $_SESSION['allow_ytdl']     = $perms['desktop_allow_youtube_download'];
            $_SESSION['allow_ytembed']  = $perms['desktop_allow_youtube_embed'];
        }
        else
        {
            $error  = 2;
            $error .= "\nErrorMessage: This user account (".$_SESSION['username'].")";
            $error .= " has been removed.<BR>";
            $error .= "Contact the system administrator for further assistance.";
            $pack   = $rsa->Sign($error);
            $output = base64_encode($rsa->Encrypt($pack));
            echo($output);
            self::drop_php_session();
            exit;
        }
    }

    function drop_php_session()
    {
        self::drop_online_status();
        setcookie('PHPSESSID','',time()-3600,'/');
        session_unset();
        session_destroy();
    }

    /*
     * usage: fetch_last_mysql_id('flv','video');
     */
    function fetch_last_mysql_id($search,$table,$order = 'VID DESC')
    {
        $query  = mysql_query("SELECT $search FROM $table ORDER BY $order LIMIT 1") or die(mysql_error());
        $res    = mysql_fetch_array($query);
        return $res[$search];
    }

    function notify_subscribers($video_id, $title, $type = 'video', $anonymous = 0)
    {
        global $config, $conn, $smarty;

        require_once($config['BASE_DIR'].'/include/function_global.php');
        require_once($config['BASE_DIR'].'/classes/email.class.php');

        switch($type)
        {
            case 'photo':
                $video_url  = $config['BASE_URL']. '/photo/' .$video_id. '/' .prepare_string($title);
		        $video_link = '<a href="'.$video_url.'">'.$video_url.'</a>';
                $mailSQL    = "SELECT * FROM emailinfo WHERE email_id = 'subscribe_email_photo' LIMIT 1";
                break;
            case 'video':
                $video_url  = $config['BASE_URL']. '/video/' .$video_id. '/' .prepare_string($title);
		        $video_link = '<a href="'.$video_url.'">'.$video_url.'</a>';
                $mailSQL    = "SELECT * FROM emailinfo WHERE email_id = 'subscribe_email' LIMIT 1";
                break;
            default:
                $video_url  = $config['BASE_URL']. '/video/' .$video_id. '/' .prepare_string($title);
		        $video_link = '<a href="'.$video_url.'">'.$video_url.'</a>';
                $mailSQL    = "SELECT * FROM emailinfo WHERE email_id = 'subscribe_email' LIMIT 1";
        }

        $sql    = "SELECT sv.SUID, s.username, s.email FROM video_subscribe AS sv, signup AS s
                           WHERE sv.UID = " .$_SESSION['UID']. " AND sv.UID = s.UID";
        $rs     = $conn->execute($sql);

        if ($conn->Affected_Rows() > 0)
        {
            $subscribers    = $rs->getrows();
            $mail           = new VMail();
            $mail->setNoReply();
            $rs             = $conn->execute($mailSQL);
            $email_path     = $config['BASE_DIR']. '/templates/' .$rs->fields['email_path'];
            $sender         = ($anonymous == 1) ? 'anonymous' : $_SESSION['USERNAME'];
            $mail->Subject  = str_replace('$sender_name', $sender, $rs->fields['email_subject']);
            foreach ($subscribers as $subscriber)
            {
                $smarty->assign('video_link', $video_link);
                $smarty->assign('username', $subscriber['username']);
                $smarty->assign('sender_name', $_SESSION['USERNAME']);
                $body               = $smarty->fetch($email_path);
                $mail->AltBody      = $body;
                $mail->Body         = nl2br($body);
                $mail->AddAddress($subscriber['email']);
                $mail->Send();
                $mail->ClearAddresses();
            }
        }
    }

    function fetch_usergroup_permissions($user_level_id)
    {
        $sql    = "SELECT * FROM ".TABLE_PREFIX."user_levels_permissions ";
        $sql   .= "WHERE user_level_id = '".mysql_real_escape_string($user_level_id)."' ";
        $sql   .= "LIMIT 1";
        $res    = mysql_query($sql) or die(mysql_error());
        if(mysql_num_rows($res) > 0)
        {
            return mysql_fetch_array($res);
        }
    }

	/**
	 * Video Key Gen
	 * * it is use to generate video key
	 */
	function video_keygen()
	{
		$char_list = "ABDGHKMNORSUXWY";
		$char_list .= "123456789";
		while(1)
		{
			$vkey = '';
			srand((double)microtime()*1000000);
			for($i = 0; $i < 12; $i++)
			{
			$vkey .= substr($char_list,(rand()%(strlen($char_list))), 1);
			}

			if(!self::vkey_exists($vkey))
			break;
		}

		return $vkey;
	}

	/**
	 * Function used to check videokey exists or not
	 * key_exists
	 */
	function vkey_exists($key)
	{
        $sql    = "SELECT videokey FROM ".TABLE_PREFIX."video ";
        $sql   .= "WHERE videokey = '".mysql_real_escape_string($key)."' ";
        $sql   .= "LIMIT 1";
        $res    = mysql_query($sql) or die(mysql_error());

        if(mysql_num_rows($res) > 0)
        {
            return true;
        }
	}

    function php_file_upload_error($error_code)
    {
        switch ($error_code)
        {
            case UPLOAD_ERR_INI_SIZE:
                return 'UPLOAD_ERR_INI_SIZE:  Value: 1; The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'UPLOAD_ERR_FORM_SIZE:  Value: 2; The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'UPLOAD_ERR_PARTIAL:  Value: 3; The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'UPLOAD_ERR_NO_FILE:  Value: 4; No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'UPLOAD_ERR_NO_TMP_DIR:  Value: 6; Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'UPLOAD_ERR_CANT_WRITE:  Value: 7; Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'UPLOAD_ERR_EXTENSION:  Value: 8; A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    function increment_video_count()
    {
        $sql    = "UPDATE ".TABLE_PREFIX."users SET total_videos = total_videos+1 ";
        $sql   .= "WHERE userid = '".$_SESSION['userid']."' LIMIT 1";
        mysql_query($sql) OR die(mysql_error());
    }

// END desktop.class.php
}
