<?php
/**
#########################################################################################################
# Copyright (c) 2010 CustomCode.info. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# URL:              [url]http://customcode.info[/url]
# Function:         ClipBucket Desktop Uploader - Admin UI
# Author:           fwhite
# Language:         PHP
# License:          Commercial
# ----------------- THIS IS NOT FREE SOFTWARE ----------------
# Version:          $Id: desktop_upload.php 4 2010-11-27 12:05:52Z Q $
# Created:          Thursday, March 25, 2010 / 10:02 AM GMT+1 (fwhite)
# Last Modified:    $Date: 2010-11-27 13:05:52 +0100 (Sat, 27 Nov 2010) $
# Notice:           Please maintain this section
#########################################################################################################
*/

define('THIS_PAGE', 'desktop_admin');
error_reporting(0);
ini_set('display_errors', false);

// START define feature set
define('AUDIO_ON',true);
define('IMAGE_ON',true);
define('VIDEO_ON',true);
define('YOUTUBE_ON',true);
define('WATERMARK_ON', true);
// END define feature set

require_once('../includes/admin_config.php');
$userquery->admin_login_check();
require_once('../includes/classes/desktop/desktop.class.php');

if(!empty($_GET['m']))
{
    e('Desktop Uploader Settings updated.','m');
}

// assign values from DB
$desktopConfig                              = desktop::fetch_desktop_details();
$desktopConfig['VideoFormats']              = explode(',',$desktopConfig['Video-Formats']);
$desktopConfig['AudioModerate']             = $desktopConfig['Audio-Moderate'];
$desktopConfig['ImageModerate']             = $desktopConfig['Image-Moderate'];
$desktopConfig['VideoModerate']             = $desktopConfig['Video-Moderate'];
$desktopConfig['VideoAllowExtensions']      = $desktopConfig['Video-AllowExtensions'];
$desktopConfig['VideoAllowYTDownload']      = $desktopConfig['Video-AllowYTDownload'];
$desktopConfig['VideoAllowYTEmbed']         = $desktopConfig['Video-AllowYTEmbed'];
$desktopConfig['VideoResizeType']           = $desktopConfig['Video-ResizeType'];
$desktopConfig['VideoMaxFileSize']          = $desktopConfig['Video-MaxFileSize'];
$desktopConfig['VideoResolution']           = $desktopConfig['Video-Resolution'];
$desktopConfig['AudioAllowUpload']          = $desktopConfig['Audio-AllowUpload'];
$desktopConfig['ImageAllowUpload']          = $desktopConfig['Image-AllowUpload'];
$desktopConfig['VideoAllowUpload']          = $desktopConfig['Video-AllowUpload'];
$desktopConfig['AudioMaxFileSize']          = $desktopConfig['Audio-MaxFileSize'];
$desktopConfig['ImageMaxFileSize']          = $desktopConfig['Image-MaxFileSize'];
$desktopConfig['VideoMaxFileSize']          = $desktopConfig['Video-MaxFileSize'];
$desktopConfig['ImageAllowExtensions']      = $desktopConfig['Image-AllowExtensions'];
$desktopConfig['AudioRequirePic']           = $desktopConfig['Audio-RequirePic'];
$desktopConfig['VideoAllowYTEmbed']         = $desktopConfig['Video-AllowYTEmbed'];
$desktopConfig['VideoAllowYTDownload']      = $desktopConfig['Video-AllowYTDownload'];
$desktopConfig['YTResizeEmbedCode']         = $desktopConfig['YT-ResizeEmbedCode'];
$desktopConfig['YTResizeHeight']            = $desktopConfig['YT-ResizeHeight'];
$desktopConfig['YTResizeWidth']             = $desktopConfig['YT-ResizeWidth'];

$desktopConfig['WatermarkURL']              = $desktopConfig['WatermarkURL'];
$desktopConfig['VideoWatermarkEnabled']     = $desktopConfig['Video-WatermarkEnabled'];
$desktopConfig['VideoWatermarkPosition']    = $desktopConfig['Video-WatermarkPosition'];
$desktopConfig['VideoWatermarkOffsetX']     = $desktopConfig['Video-WatermarkOffsetX'];
$desktopConfig['VideoWatermarkOffsetY']     = $desktopConfig['Video-WatermarkOffsetY'];

// check submit
if(!empty($_POST['commit']))
{
    if(!empty($_POST['VideoFormatDivX']))
    {
        $_POST['VideoFormats'][] = 'divx';
    }
    if(!empty($_POST['VideoFormatFLV']))
    {
        $_POST['VideoFormats'][] = 'flv';
    }
    if(!empty($_POST['VideoFormatMP4']))
    {
        $_POST['VideoFormats'][] = 'mp4';
    }

    $values                         = array();
    $values['upload_allowed']       = mysql_real_escape_string($_POST['upload_allowed']);
    $values['AllowDuplicates']      = mysql_real_escape_string($_POST['AllowDuplicates']);
    $values['AllowTorrents']        = mysql_real_escape_string($_POST['AllowTorrents']);
    $values['UploadQuota']          = mysql_real_escape_string($_POST['UploadQuota']);
    $values['DisplayAvatar']        = mysql_real_escape_string($_POST['DisplayAvatar']);
    $values['ModerateRules']        = mysql_real_escape_string($_POST['ModerateRules']);
    $values['AllowEmptyFields']     = mysql_real_escape_string($_POST['AllowEmptyFields']);

    if(defined('AUDIO_ON'))
    {
        $values['Audio-AllowUpload']        = mysql_real_escape_string($_POST['AudioAllowUpload']);
        $values['Audio-MaxFileSize']        = mysql_real_escape_string($_POST['AudioMaxFileSize']);
        $values['Audio-RequirePic']         = mysql_real_escape_string($_POST['AudioRequirePic']);
        $values['Audio-Moderate']           = mysql_real_escape_string($_POST['AudioModerate']);
    }

    if(defined('IMAGE_ON'))
    {
        $values['Image-AllowUpload']        = mysql_real_escape_string($_POST['ImageAllowUpload']);
        $values['Image-MaxFileSize']        = mysql_real_escape_string($_POST['ImageMaxFileSize']);
        $values['Image-AllowExtensions']    = mysql_real_escape_string($_POST['ImageAllowExtensions']);
        $values['Image-Moderate']           = mysql_real_escape_string($_POST['ImageModerate']);
    }

    if(defined('VIDEO_ON'))
    {
        $values['Video-Formats']            = mysql_real_escape_string(implode(',', $_POST['VideoFormats']));
        $values['Video-AllowExtensions']    = mysql_real_escape_string($_POST['VideoAllowExtensions']);
        $values['Video-ResizeType']         = mysql_real_escape_string($_POST['VideoResizeType']);
        $values['Video-Resolution']         = mysql_real_escape_string($_POST['VideoResolution']);
        $values['Video-MaxFileSize']        = mysql_real_escape_string($_POST['VideoMaxFileSize']);
        $values['Video-AllowUpload']        = mysql_real_escape_string($_POST['VideoAllowUpload']);
        $values['Video-Moderate']           = mysql_real_escape_string($_POST['VideoModerate']);
    }

    if(defined('YOUTUBE_ON'))
    {
        $values['Video-AllowYTEmbed']       = mysql_real_escape_string($_POST['VideoAllowYTEmbed']);
        $values['Video-AllowYTDownload']    = mysql_real_escape_string($_POST['VideoAllowYTDownload']);
        $values['YT-ResizeEmbedCode']       = mysql_real_escape_string($_POST['YTResizeEmbedCode']);
        $values['YT-ResizeHeight']          = mysql_real_escape_string($_POST['YTResizeHeight']);
        $values['YT-ResizeWidth']           = mysql_real_escape_string($_POST['YTResizeWidth']);
    }

    if(defined('WATERMARK_ON'))
    {
        $values['WatermarkURL']             = mysql_real_escape_string($_POST['WatermarkURL']);
        $values['Video-WatermarkEnabled']   = mysql_real_escape_string($_POST['VideoWatermarkEnabled']);
        $values['Video-WatermarkPosition']  = mysql_real_escape_string($_POST['VideoWatermarkPosition']);
        $values['Video-WatermarkOffsetX']   = mysql_real_escape_string($_POST['VideoWatermarkOffsetX']);
        $values['Video-WatermarkOffsetY']   = mysql_real_escape_string($_POST['VideoWatermarkOffsetY']);
    }

    foreach($values AS $key=>$value)
    {
        if(!strlen(trim($value)))
        {
            //unset($values[$key]);
            // do nothing instead
        }
        else
        {
	        desktop::set_desktop_config($key,$value);
        }
	}
    // redirect
    header('Location: '.BASEURL.'/admin_area/desktop_upload.php?m=1');
}

Assign('desktopConfig',$desktopConfig);
subtitle('Desktop Upload Admin');
template_files('desktop_upload.html');
display_it();