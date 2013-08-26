<?php
/**
	Plugin Name: Desktop Upload - Daemon
	Description: Enable interaction with <a href="http://desktopupload.com" target="_blank">ClipBucket Desktop Uploader</a>
	Author: DesktopUpload.com
	ClipBucket Version: 2.0
	Plugin Version: 1.0
	Website: http://desktopupload.com/

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
# Created:          Sunday, March 28, 2010 / 07:06 AM GMT+1 (fwhite)
# Last Modified:    $Date$
# Notice:           Please maintain this section
#########################################################################################################
*/

$Cbucket->watch_video_functions['detect_type']      = 'detect_type';
$Cbucket->on_delete_video['cleanup_desktop_files']  = 'cleanup_desktop_files';
register_anchor_function('display_torrent', 'before_watch_player');

function detect_type($video)
{	
    if($video['is_image'] == 1)
    {
    }

    if($video['is_audio'] == 1)
    {

    }
    //header('Location: '.BASEURL.'');
}

function display_torrent($video)
{
    if(strlen($video['torrent']))
    {
        $torrent    = '<a href="'.BASEURL.'/files/torrents/'.$video['torrent'].'">';
        $torrent   .= '<img src="'.BASEURL.'/images/icons/download_torrent_small.gif" border="0" ';
        $torrent   .= 'alt="Download this video via BitTorrent" title="Download this video via BitTorrent" />';
        $torrent   .= '</a>';

        echo $torrent;
    }
}

function cleanup_desktop_files($video)
{
    if(strlen($video['torrent']))
    {
        @unlink(BASEDIR.'/files/torrents/'.$video['file_name'].'.torrent');
    }

    if($video['is_audio'] == 1)
    {
        @unlink(BASEDIR.'/files/audio/'.$video['file_name'].'.mp3');
    }

    if($video['is_image'] == 1)
    {
        @unlink(BASEDIR.'/files/images/'.$video['file_name'].'-mid.jpg');
        @unlink(BASEDIR.'/files/images/original/'.$video['original_filename']);
    }
}