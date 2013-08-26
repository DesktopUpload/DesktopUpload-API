<?php
/**
#########################################################################################################
# Copyright (c) 2010 CustomCode.info. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# URL:              [url]http://customcode.info[/url]
# Function:         ClipBucket Desktop Uploader - Daemon Uninstaller
# Author:           fwhite
# Language:         PHP
# License:          Commercial
# ----------------- THIS IS NOT FREE SOFTWARE ----------------
# Version:          $Id$
# Created:          Sunday, March 28, 2010 / 10:26 AM GMT+1 (fwhite)
# Last Modified:    $Date$
# Notice:           Please maintain this section
#########################################################################################################
*/

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');
require_once(BASEDIR.'/includes/common.php');

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

$sql = "DROP TABLE `".TABLE_PREFIX."desktop_config`";
$res = mysql_query($sql) OR die(mysql_error());

/*$ulpFields  = get_mysql_columns('user_levels_permissions');
$array[]    = 'can_desktop';
$array[]    = 'desktop_max_audio_size';
$array[]    = 'desktop_max_image_size';
$array[]    = 'desktop_max_video_size';
$array[]    = 'moderate_desktop_audio';
$array[]    = 'moderate_desktop_image';
$array[]    = 'moderate_desktop_video';
$array[]    = 'desktop_allow_audio';
$array[]    = 'desktop_allow_image';
$array[]    = 'desktop_allow_video';
$array[]    = 'desktop_allow_youtube_download';
$array[]    = 'desktop_allow_youtube_embed';
$array[]    = 'desktop_allow_torrents';
foreach ($array as $i => $value)
{
    if(in_array($value,$ulpFields))
    {
        $sql    = 'ALTER TABLE `'.TABLE_PREFIX.'user_levels_permissions` ';
        $sql   .= 'DROP `'.$value.'` ;';
        $res    = mysql_query($sql) OR die(mysql_error());
    }
}*/

$sql = 'ALTER TABLE `'.TABLE_PREFIX.'user_levels_permissions`
        DROP `can_desktop`,
        DROP `desktop_max_audio_size`,
        DROP `desktop_max_image_size`,
        DROP `desktop_max_video_size`,
        DROP `moderate_desktop_audio`,
        DROP `moderate_desktop_image`,
        DROP `moderate_desktop_video`,
        DROP `desktop_allow_audio`,
        DROP `desktop_allow_image`,
        DROP `desktop_allow_video`,
        DROP `desktop_allow_youtube_download`,
        DROP `desktop_allow_youtube_embed`,
        DROP `desktop_allow_torrents`;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'ALTER TABLE `'.TABLE_PREFIX.'user_permissions` DROP `input_type`';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'can_desktop\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_max_audio_size\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_max_image_size\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_max_video_size\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'moderate_desktop_audio\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'moderate_desktop_image\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'moderate_desktop_video\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_allow_audio\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_allow_image\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_allow_video\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_allow_youtube_download\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_allow_youtube_embed\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'DELETE FROM `'.TABLE_PREFIX.'user_permissions` WHERE `permission_code` = \'desktop_allow_torrents\' LIMIT 1;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'ALTER TABLE `'.TABLE_PREFIX.'video`
        DROP `media_md5`,
        DROP `original_filename`,
        DROP `embed_url`,
        DROP `is_divx`,
        DROP `is_mp4`,
        DROP `audio_pic`,
        DROP `is_image`,
        DROP `yt_fmt`,
        DROP `from_desktop`,
        DROP `width`,
        DROP `height`,
        DROP `torrent`,
        DROP `torrent_md5`,
        DROP `is_audio`;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'ALTER TABLE `'.TABLE_PREFIX.'users`
        DROP `total_audio`,
        DROP `total_images`;';
$res = mysql_query($sql) OR die(mysql_error());

$sql = 'ALTER TABLE `'.TABLE_PREFIX.'video_categories`
        DROP `category_type`;';
$res = mysql_query($sql) OR die(mysql_error());