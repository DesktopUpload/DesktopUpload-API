<?php
/**
#########################################################################################################
# Copyright (c) 2010 CustomCode.info. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# URL:              [url]http://customcode.info[/url]
# Function:         ClipBucket Desktop Uploader - Daemon
# Author:           fwhite
# Language:         PHP
# License:          Commercial
# ----------------- THIS IS NOT FREE SOFTWARE ----------------
# Version:          $Id$
# Created:          Sunday, March 28, 2010 / 10:00 AM GMT+1 (fwhite)
# Last Modified:    $Date$
# Notice:           Please maintain this section
#########################################################################################################
*/

error_reporting(0);
ini_set('display_errors', false);
require_once(BASEDIR.'/includes/common.php');

// define feature set
//define('ENABLE_AUDIO',true);
//define('ENABLE_IMAGES',true);
define('ENABLE_VIDEO',true);
//define('ENABLE_YOUTUBE',true);

if(!function_exists('get_mysql_columns'))
{
    function get_mysql_columns($table)
    {
        // LIMIT 1 means to only read rows before row 1 (0-indexed)
        $result     = mysql_query("SELECT * FROM $table LIMIT 1");
        $describe   = mysql_query("SHOW COLUMNS FROM $table");
        $num        = mysql_num_fields($result);
        $output     = array();
        for ($i = 0; $i < $num; ++$i)
        {
            $field = mysql_fetch_field($result, $i);
            // Analyze 'extra' field
            $field->auto_increment = (strpos(mysql_result($describe, $i, 'Extra'), 'auto_increment') === FALSE ? 0 : 1);
            // Create the column_definition
            $field->definition = mysql_result($describe, $i, 'Type');

            if ($field->not_null && !$field->primary_key)
            {
                $field->definition .= ' NOT NULL';
            }

            if ($field->def)
            {
                $field->definition .= " DEFAULT '" . mysql_real_escape_string($field->def) . "'";
            }

            if ($field->auto_increment)
            {
                $field->definition .= ' AUTO_INCREMENT';
            }

            if ($key = mysql_result($describe, $i, 'Key'))
            {
                if ($field->primary_key)
                {
                    $field->definition .= ' PRIMARY KEY';
                }
                else
                {
                    $field->definition .= ' UNIQUE KEY';
                }
            }
            // Create the field length
            $field->len = mysql_field_len($result, $i);
            // Store the field into the output
            $output[$field->name] = $field;
        }
        return array_keys($output);
    }
}

/*
--
-- Table structure for table `cb_desktop_config`
--
*/

$sql = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."desktop_config` (
  `id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `value` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
$res = mysql_query($sql) OR die(mysql_error());

/*
--
-- Dumping data for table `cb_desktop_config`
--
*/

$sql = "INSERT INTO `".TABLE_PREFIX."desktop_config` (`id`, `name`, `value`) VALUES
(1, 'upload_allowed', '1'),
(2, 'default_lang', 'en'),
(3, 'min_version', '1.3.2.415'),
(4, 'AutoPlayYTEmbed', '1'),
(5, 'AllowTorrents', '1'),
(6, 'ErrorHandling', 'PostProcessing=warning, PreProcessing=warning'),
(7, 'AllowDuplicates', '0'),
(8, 'DisplayAvatar', '0'),
(9, 'Audio-AllowUpload', '0'),
(10, 'Image-AllowUpload', '1'),
(11, 'Video-AllowUpload', '1'),
(12, 'Audio-MaxFileSize', '20971520'),
(13, 'Image-MaxFileSize', '5242880'),
(14, 'Video-MaxFileSize', '2147483648'),
(15, 'Audio-AllowExtensions', 'mp3'),
(16, 'Image-AllowExtensions', 'bmp,jpg,jpeg,png'),
(17, 'Video-AllowExtensions', 'asf,avi,dat,divx,flv,mkv,mov,mpeg,mpg,mp4,mts,m2ts,vob,wmv,xvid'),
(18, 'Audio-Formats', 'mp3'),
(19, 'Video-Formats', 'divx,flv,mp4'),
(20, 'Video-Resolution', '320x240'),
(21, 'Video-ThumbResolution', '160x120'),
(22, 'Video-ThumbPadding', '1'),
(23, 'Video-BigThumbResolution', '320x240'),
(24, 'Video-ResizeType', '0'),
(25, 'Video-AllowYTDownload', '1'),
(26, 'Video-AllowYTEmbed', '1'),
(27, 'Audio-RequirePic', '0'),
(28, 'UploadQuota', 'per_user'),
(29, 'session_limit', '2'),
(30, 'session_timeout', '0'),
(31, 'Audio-Moderate', '0'),
(32, 'Image-Moderate', '0'),
(33, 'Video-Moderate', '0'),
(34, 'ModerateRules', 'global'),
(NULL, 'YT-ResizeEmbedCode', 'global'),
(NULL, 'YT-ResizeHeight', '343'),
(NULL, 'YT-ResizeWidth', '657');";
$res = mysql_query($sql) OR die(mysql_error());

/*
--
-- Altering table `cb_user_levels_permissions`
--
*/

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `can_desktop` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_max_audio_size` BIGINT NOT NULL DEFAULT '20971520' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_max_image_size` BIGINT NOT NULL DEFAULT '5242880' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_max_video_size` BIGINT NOT NULL DEFAULT '2147483648' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `moderate_desktop_audio` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `moderate_desktop_image` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `moderate_desktop_video` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_allow_audio` ENUM('yes','no') NOT NULL DEFAULT 'yes' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_allow_image` ENUM('yes','no') NOT NULL DEFAULT 'yes' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_allow_video` ENUM('yes','no') NOT NULL DEFAULT 'yes' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_allow_youtube_download` ENUM('yes','no') NOT NULL DEFAULT 'yes' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_allow_youtube_embed` ENUM('yes','no') NOT NULL DEFAULT 'yes' ; ";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_levels_permissions`  ADD `desktop_allow_torrents`  ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'no' ;";
$res    = mysql_query($sql) OR die(mysql_error());

/*
--
-- Altering table `cb_user_permissions`
--
*/

$sql    = "ALTER TABLE `".TABLE_PREFIX."user_permissions` ADD `input_type` ENUM( 'text', 'radio', 'select', 'textarea' ) NOT NULL DEFAULT 'radio';";
$res    = mysql_query($sql) OR die(mysql_error());

// Can use Desktop Uploader

$sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
$sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`) ";
$sql   .= "VALUES (NULL, '2', 'Can use Desktop Uploader', 'can_desktop', 'User can upload with Desktop Upload application', 'yes');";
$res    = mysql_query($sql) OR die(mysql_error());

if(defined('ENABLE_AUDIO'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL , '2', 'Desktop Upload - Max Audio Size', 'desktop_max_audio_size', 'Maximum audio size allowed for Desktop Upload in bytes.', 'yes', 'text');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_IMAGE'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Max Image Size', 'desktop_max_image_size', 'Maximum image size allowed for Desktop Upload in bytes.', 'yes', 'text');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_VIDEO'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Max Video Size', 'desktop_max_video_size', 'Maximum video size allowed for Desktop Upload in bytes.', 'yes', 'text');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_AUDIO'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Moderate Audio Uploads', 'moderate_desktop_audio', 'Moderate all audio uploads by this usergroup. ";
    $sql   .= "This will require admin approval for each upload.', 'no', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_IMAGE'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Moderate Image Uploads', 'moderate_desktop_image', 'Moderate all image uploads by this usergroup. ";
    $sql   .= "This will require admin approval for each upload.', 'no', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_VIDEO'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Moderate Video Uploads', 'moderate_desktop_video', 'Moderate all video uploads by this usergroup. ";
    $sql   .= "This will require admin approval for each upload.', 'no', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_AUDIO'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Allow Audio Upload', 'desktop_allow_audio', 'Allow upload of audio files.', 'yes', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_IMAGE'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Allow Image Upload', 'desktop_allow_image', 'Allow upload of image files.', 'yes', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_VIDEO'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` ";
    $sql   .= "(`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Allow Video Upload', 'desktop_allow_video', 'Allow upload of video files.', 'yes', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

if(defined('ENABLE_YOUTUBE'))
{
    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` (`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Allow YouTube Download', 'desktop_allow_youtube_download', 'Allow download of videos from YouTube.', 'yes', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());

    $sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` (`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
    $sql   .= "VALUES (NULL, '2', 'Desktop Upload - Allow Audio Upload', 'desktop_allow_youtube_embed', 'Allow embed of videos from YouTube.', 'yes', 'radio');";
    $res    = mysql_query($sql) OR die(mysql_error());
}

$sql    = "INSERT INTO `".TABLE_PREFIX."user_permissions` (`permission_id`, `permission_type`, `permission_name`, `permission_code`, `permission_desc`, `permission_default`, `input_type`) ";
$sql   .= "VALUES (NULL, '2', 'Desktop Upload - Allow Torrents', 'desktop_allow_torrents', 'Allow attachment of .torrent files to uploads.', 'no', 'radio');";
$res    = mysql_query($sql) OR die(mysql_error());

/*
--
-- Altering table `cb_users`
--
*/

$sql    = "ALTER TABLE `".TABLE_PREFIX."users` CHANGE `total_videos` `total_videos` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0' ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."users` ADD `total_audio` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `total_videos` ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."users` ADD `total_images` BIGINT( 20 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `total_audio` ;";
$res    = mysql_query($sql) OR die(mysql_error());

/*
--
-- Altering table `cb_video`
--
*/

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `original_filename` TEXT NULL DEFAULT NULL AFTER `file_name` ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `embed_url` TEXT NULL DEFAULT NULL AFTER `embed_code`;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `yt_fmt` INT( 2 ) NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `from_desktop` ENUM( '0', '1' ) NOT NULL DEFAULT '0';";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `width` INT NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `height` INT NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `torrent` TEXT NULL DEFAULT NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `torrent_md5` TEXT NULL DEFAULT NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `media_md5` TEXT NULL DEFAULT NULL AFTER `flv` ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `is_audio` ENUM( '0', '1' ) NOT NULL DEFAULT '0';";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `is_image` ENUM( '0', '1' ) NOT NULL DEFAULT '0';";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `is_divx` ENUM( '0', '1' ) NOT NULL DEFAULT '0' ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `is_mp4` ENUM( '0', '1' ) NOT NULL DEFAULT '0' ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video` ADD `audio_pic` TEXT NULL DEFAULT NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());

/*
--
-- Altering table `".TABLE_PREFIX."video_categories`
--
*/

$sql    = "ALTER TABLE `".TABLE_PREFIX."video_categories` ADD `category_type` ENUM( 'audio', 'image', 'video' ) NOT NULL DEFAULT 'video' AFTER `category_name` ;";
$res    = mysql_query($sql) OR die(mysql_error());

$sql    = "ALTER TABLE `".TABLE_PREFIX."video_categories` CHANGE `category_name` `category_name`  TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;";
$res    = mysql_query($sql) OR die(mysql_error());