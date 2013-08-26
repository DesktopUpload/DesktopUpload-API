<?php
/**
#########################################################################################################
# Copyright (c) 2009 - 2010 CustomCode.info. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# URL:              [url]http://customcode.info[/url]
# Function:         ClipBucket Desktop Uploader Interaction
# Author:           fwhite
# Language:         PHP
# License:          Commercial
# ----------------- THIS IS NOT FREE SOFTWARE ----------------
# Version:          $Id: desktop.php 3 2010-10-17 00:16:20Z Q $
# Created:          Monday, January 25, 2010 / 08:14 PM GMT+1 (fwhite)
# Last Modified:    $Date: 2010-10-17 02:16:20 +0200 (Sun, 17 Oct 2010) $
# Notice:           Please maintain this section
#########################################################################################################
*/

if( !preg_match( '/ClipBucket Desktop Uploader/', $_SERVER['HTTP_USER_AGENT'] ) ) {
    header('Location: http://desktopupload.com');
}

error_reporting( 0 );
ini_set( 'display_errors', false );
header('Content-Type: text/plain; charset=utf-8');

define('_VALID', true);
define('IN_DESKTOP',true);
// 10 years
define('COOKIE_TIMEOUT',315360000);
define('GARBAGE_TIMEOUT',COOKIE_TIMEOUT);
ini_set('session.gc_maxlifetime', GARBAGE_TIMEOUT);
session_set_cookie_params(COOKIE_TIMEOUT,'/');

// setting session dir
$sessdir = dirname(__FILE__).'/tmp/sessions';
// if session dir not exists, create directory
if ( !is_dir( $sessdir ) ) {
    @mkdir( $sessdir, 0777 );
}
//if directory exists, set session.savepath
if( is_dir( $sessdir ) ) {
    ini_set('session.save_path', $sessdir);
}

define( 'MIN_VERSION', '1.4.8.528' );
define( 'GUID', '5428d151f6eac6abf7a90c7b4e7747f7' );
define( 'SALT', md5('X:$rg)7z#R^?AG%m*6vQqw7uP4*g(+Eom=,HpLZR') );
define( 'FULL_GUID', GUID.SALT );
define( 'DEBUG_SHA1', '6c8558200c6d2b0d5a165657914eb656e9e19e4d' );
define( 'EXE_SHA1', 'b946bff27daa8c2a511ac584e86a5f8d1f4f5773 ');
define( 'VER_SHA1', '0b7f1d0c30fba37563e15e667062ce0d60dca3f2' );
define( 'THUMB_COUNT', 3 );

session_start();

if( !defined('CB_SIGN') ) {
	define('CB_SIGN', true);
}

require_once('includes/dbconnect.php');
require_once('includes/classes/desktop/RSA.class.php');
require_once('includes/classes/desktop/RSAResponse.class.php');
require_once('includes/classes/desktop/desktop.class.php');

$rsa            = new RSA;
$rsaResponse    = new RSAResponse;
$desktop        = new desktop;

$response       = array();
$config         = $desktop->fetch_site_config();
$desktop_info   = $desktop->fetch_desktop_details();

define( 'BASEDIR', dirname(__FILE__) );
define( 'BASEURL', $config['baseurl'] );
define( 'SESSION_TIMEOUT', $desktop_info['session_timeout'] );

if( $_SERVER['HTTP_USER_AGENT'] != 'ClipBucket Desktop Uploader/1.4.8.528 (0a1f3e2338d4cf11bb0801178f666c23eef28854d96e6c2e19a2edc93a147e47)' ) {
    $error  = 2;
    $error .= "\nSignKey: ".$rsa->GenerateServerSignKey();
    $error .= "\n";
    $error .= "ErrorMessage: Your client is outdated or not valid for this site.";
    $error .= "<BR>";
    $error .= "Contact the system administrator for an updated version.";
    $pack   = $rsa->Sign($error);
    $output = base64_encode($rsa->Encrypt($pack));

    exit($output);
}

if( !empty( $_POST['xml_data'] ) ) {
    $encrypted  = $_POST['xml_data'];
    $decoded    = base64_decode($encrypted);
    $decrypted  = $rsa->Decrypt($decoded);
}

if( empty( $_POST['xml_data'] ) && empty( $_FILES ) ) {
    header('Location: http://desktopupload.com');
}

if( $desktop_info['upload_allowed'] != 1 ) {
    $response['ErrorMessage'] = 'Desktop upload is disabled.';
    echo $rsaResponse->response( 1, $response );
    exit;
}

$desktop->eval_session();
$desktop->update_online_status();

$dom = new DOMDocument();
$dom->loadXML( $decrypted['message'] );

$ExecutableSHA1 = $dom->getElementsByTagName('ExecutableSHA1')->item(0)->nodeValue;
if( $ExecutableSHA1 != DEBUG_SHA1 && $ExecutableSHA1 != EXE_SHA1 ) {
    $response['ErrorMessage'] = 'Application not authorized.';
    echo $rsaResponse->response(2,$response);
    exit;
}

$VersionSHA1 = $dom->getElementsByTagName('VersionSHA1')->item(0)->nodeValue;
if($VersionSHA1 != VER_SHA1) {
    $response['ErrorMessage'] = 'Application not authorized.';
    echo $rsaResponse->response(2,$response);
    exit;
}

$action = $dom->documentElement->getAttribute('action');

if( !isset( $_SESSION['ClientSignKey'] ) && $action == 'login' ) {
    // load signature key from request
    $SignKey = $dom->getElementsByTagName('SignKey')->item(0)->nodeValue;
    $rsa->SetClientSignKey($SignKey);
}

$verifyRes = $rsa->Verify( $decrypted );
if ( $verifyRes == 1 ) {
    // signature OK
} elseif ( $verifyRes == 0 ) {
    $response['ErrorMessage'] = 'Invalid signature.';
    echo $rsaResponse->response(1,$response);
    exit;
} else {
    $response['ErrorMessage'] = 'Signature verification error: '.openssl_error_string();
    echo $rsaResponse->response( 1, $response );
    exit;
}

if($action == 'login') {
    $username = $dom->getElementsByTagName('UserName')->item(0)->nodeValue;
    $password = $dom->getElementsByTagName('Password')->item(0)->nodeValue;

    $result   = $desktop->login($username,$password);

    if( $result == 'account_banned' ) {
        $error  = 2;
        $error .= "\nSignKey: ".$rsa->GenerateServerSignKey();
        $error .= "\nErrorMessage: This user account (".$username.")";
        $error .= " is banned.<BR>";
        $error .= "Contact the system administrator for further assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        $desktop->drop_php_session();
        exit;
    } elseif($result == 'not_active') {
        $error  = 2;
        $error .= "\nSignKey: ".$rsa->GenerateServerSignKey();
        $error .= "\nErrorMessage: This user account (".$username.") is not active.";
        $error .= "<BR>Check your registered e-mail address for activation instructions or ";
        $error .= "contact the system administrator for further assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        exit;
    } elseif($result == 'not_verified') {
        $error  = 199;
        $error .= "\nSignKey: ".$rsa->GenerateServerSignKey();
        $error .= "\nErrorMessage: The e-mail address";
        $error .= " associated with this user account (".$username.")";
        $error .= " is not verified.<BR>";
        $error .= "Check your e-mail or ";
        $error .= "contact the system administrator for further assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        exit;
    } elseif($result == 'no_desktop') {
        $error  = 2;
        $error .= "\nSignKey: ".$rsa->GenerateServerSignKey();
        $error .= "\nErrorMessage: Desktop upload not authorized for this user account ";
        $error .= "(".$username.").<BR>";
        $error .= "Contact the system administrator for further assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        $desktop->drop_php_session();
        exit;
    } elseif($result == 'invalid') {
        $response['ErrorMessage'] = 'Invalid username or password.';
        echo $rsaResponse->response(102,$response);
        exit;
    } elseif($result == 'OK') {
        $response['AllowTorrents'] = $desktop_info['AllowTorrents'];
        if($desktop_info['Audio-AllowUpload'] == 1 && $_SESSION['allow_audio'] == 'yes') {
            $audioCategory                      = $desktop->fetch_categories('audio');
            $response['Audio-AllowExtensions']  = $desktop_info['Audio-AllowExtensions'];
            $response['Audio-AllowUpload']      = $desktop_info['Audio-AllowUpload'];
            if(!empty($audioCategory)) {
                foreach($audioCategory AS $key) {
                    $response['Audio-Category '.$key['category_id']] = htmlspecialchars_decode($key['category_name'],ENT_QUOTES);
                }
            }
            $response['Audio-Formats'] = $desktop_info['Audio-Formats'];
            if($desktop_info['UploadQuota'] == 'global') {
                $response['Audio-MaxFileSize'] = $desktop_info['Audio-MaxFileSize'];
            } else {
                $response['Audio-MaxFileSize'] = $_SESSION['max_audio_size'];
            }
            $response['Audio-RequirePic']       = $desktop_info['Audio-RequirePic'];
        }
        if($desktop_info['DisplayAvatar'] == 1) {
            $response['AvatarURL'] = $desktop->user_avatar($_SESSION['userid']);
        }
        $response['DisplayAvatar']              = $desktop_info['DisplayAvatar'];
        $response['ErrorHandling']              = $desktop_info['ErrorHandling'];
        if($desktop_info['Image-AllowUpload'] == 1 && $_SESSION['allow_image'] == 'yes') {
            $response['Image-AllowUpload']     = $desktop_info['Image-AllowUpload'];
            $imageCategory                     = $desktop->fetch_categories('image');
            $response['Image-AllowExtensions'] = $desktop_info['Image-AllowExtensions'];
            if(!empty($imageCategory)) {
                $response['Image-AllowUpload'] = $desktop_info['Image-AllowUpload'];
                foreach($imageCategory AS $key) {
                    $response['Image-Category '.$key['category_id']] = htmlspecialchars_decode($key['category_name'],ENT_QUOTES);
                }
            }

            if($desktop_info['UploadQuota'] == 'global') {
                $response['Image-MaxFileSize'] = $desktop_info['Image-MaxFileSize'];
            } else {
                $response['Image-MaxFileSize'] = $_SESSION['max_image_size'];
            }

            switch($response['Image-MaxFileSize']) {
                case ($response['Image-MaxFileSize'] > $desktop->get_phpini_byte_value('post_max_size')):
                    $response['Image-MaxFileSize'] = $desktop->get_phpini_byte_value('post_max_size');
                    break;
                case ($response['Image-MaxFileSize'] > $desktop->get_phpini_byte_value('upload_max_filesize')):
                    $response['Image-MaxFileSize'] = $desktop->get_phpini_byte_value('upload_max_filesize');
                    break;
            }
        }
        $response['MaxCategories']              = $config['video_categories'];
        $response['Video-AllowExtensions']      = $desktop_info['Video-AllowExtensions'];

        if($desktop_info['Video-AllowUpload'] == 1 && $_SESSION['allow_video'] == 'yes') {
            $response['Video-AllowUpload'] = 1;
        } else {
            $response['Video-AllowUpload'] = 0;
        }

        $response['Video-BigThumbResolution']   = $desktop_info['Video-BigThumbResolution'];
        $videoCategory                          = $desktop->fetch_categories();
        if(!empty($videoCategory)) {
            foreach($videoCategory AS $key) {
                $response['Video-Category '.$key['category_id']] = htmlspecialchars_decode($key['category_name'],ENT_QUOTES);
            }
        }
        $response['Video-Formats']              = $desktop_info['Video-Formats'];
        if($desktop_info['UploadQuota'] == 'global') {
            $response['Video-MaxFileSize'] = $desktop_info['Video-MaxFileSize'];
        } else {
            $response['Video-MaxFileSize'] = $_SESSION['max_video_size'];
        }

        switch($response['Video-MaxFileSize']) {
            case ($response['Video-MaxFileSize'] > $desktop->get_phpini_byte_value('post_max_size')):
                $response['Video-MaxFileSize'] = $desktop->get_phpini_byte_value('post_max_size');
                break;
            case ($response['Video-MaxFileSize'] > $desktop->get_phpini_byte_value('upload_max_filesize')):
                $response['Video-MaxFileSize'] = $desktop->get_phpini_byte_value('upload_max_filesize');
                break;
        }

        $response['Video-ResizeType']           = $desktop_info['Video-ResizeType'];
        $response['Video-Resolution']           = $desktop_info['Video-Resolution'];
        $response['Video-ThumbPadding']         = $desktop_info['Video-ThumbPadding'];
        $response['Video-ThumbResolution']      = $desktop_info['Video-ThumbResolution'];
        if($desktop_info['Video-AllowYTDownload'] == 1 && $_SESSION['allow_ytdl'] == 'yes') {
            $response['Video-AllowYTDownload'] = $desktop_info['Video-AllowYTDownload'];
        } else {
            $response['Video-AllowYTDownload'] = 0;
        }

        if($desktop_info['Video-AllowYTEmbed'] == 1 && $_SESSION['allow_ytembed'] == 'yes') {
            $response['Video-AllowYTEmbed'] = $desktop_info['Video-AllowYTEmbed'];
        } else {
            $response['Video-AllowYTEmbed'] = 0;
        }
        echo $rsaResponse->response(0,$response);
        exit;
	} else {
        $error  = 2;
        $error .= "\nSignKey: ".$rsa->GenerateServerSignKey();
        $error .= "\nErrorMessage: An unhandled exception has occured during login.";
        $error .= "<BR>";
        $error .= "Contact the system administrator for further assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        $desktop->drop_php_session();
        exit;
    }
}

if($action == 'logout') {
    $desktop->logout();
    exit;
}

if($action == 'ping') {
    $pack   = $rsa->Sign(0);
    $output = base64_encode($rsa->Encrypt($pack));
    echo($output);
    exit;
}

if($action == 'preupload') {
    $is_audio       = 0;
    $is_image       = 0;
    $filename       = $dom->getElementsByTagName('FileName')->item(0)->nodeValue;
    $filesize       = $dom->getElementsByTagName('FileSize')->item(0)->nodeValue;
    $md5            = $dom->getElementsByTagName('MediaMD5')->item(0)->nodeValue;
    $torrentmd5     = @$dom->getElementsByTagName('TorrentMD5')->item(0)->nodeValue;
    $embed          = @$dom->getElementsByTagName('IsEmbedCode')->item(0)->nodeValue;

    $media_ext      = strtolower(substr($filename, strrpos($filename,'.') + 1));
    $media_noext    = substr($filename, strrpos($filename,'.') + 1);

    $audio          = array('mp3');
    $images         = array('bmp','gif','jpeg','jpg','png');

    if(in_array($media_ext,$images)) {
        $is_image = 1;
    }

    if(in_array($media_ext,$audio)) {
        $is_audio = 1;
    }

    if($is_image == 1) {
        if($desktop_info['UploadQuota'] == 'per_user') {
            if($filesize > $_SESSION['max_image_size']) {
                $error  = 204;
                $error .= "\nErrorMessage: ".$filename."'s file size of ";
                $error .= $desktop->convert_filesize($filesize);
                $error .= " exceeds your allowed file size limit of ";
                $error .= $desktop->convert_filesize($_SESSION['max_image_size']).".<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        } else {
            if($filesize > $desktop_info['Image-MaxFileSize']) {
                $error  = 204;
                $error .= "\nErrorMessage: ".$filename."'s file size of ";
                $error .= $desktop->convert_filesize($filesize);
                $error .= " exceeds your allowed file size limit of ";
                $error .= $desktop->convert_filesize($desktop_info['Image-MaxFileSize']).".<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        }
    } elseif($is_audio == 1) {
        if($desktop_info['UploadQuota'] == 'per_user') {
            if($filesize > $_SESSION['max_audio_size']) {
                $error  = 204;
                $error .= "\nErrorMessage: ".$filename."'s file size of ";
                $error .= $desktop->convert_filesize($filesize);
                $error .= " exceeds your allowed file size limit of ";
                $error .= $desktop->convert_filesize($_SESSION['max_audio_size']).".<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        } else {
            if($filesize > $desktop_info['Audio-MaxFileSize']) {
                $error  = 204;
                $error .= "\nErrorMessage: ".$filename."'s file size of ";
                $error .= $desktop->convert_filesize($filesize);
                $error .= " exceeds your allowed file size limit of ";
                $error .= $desktop->convert_filesize($desktop_info['Audio-MaxFileSize']).".<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        }
    } else {
        if($desktop_info['UploadQuota'] == 'per_user') {
            if($filesize > $_SESSION['max_video_size']) {
                $error  = 204;
                $error .= "\nErrorMessage: ".$filename."'s file size of ";
                $error .= $desktop->convert_filesize($filesize);
                $error .= " exceeds your allowed file size limit of ";
                $error .= $desktop->convert_filesize($_SESSION['max_video_size']).".<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        } else {
            if($filesize > $desktop_info['Video-MaxFileSize']) {
                $error  = 204;
                $error .= "\nErrorMessage: ".$filename."'s file size of ";
                $error .= $desktop->convert_filesize($filesize);
                $error .= " exceeds your allowed file size limit of ";
                $error .= $desktop->convert_filesize($desktop_info['Video-MaxFileSize']).".<BR>";
                $error .= "Contact the system administrator for further assistance.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        }
    }

    if($desktop->check_phpini_value($filesize,'post_max_size')) {
        $error  = 299;
        $error .= "\nErrorMessage: ".$filename."'s file size of ";
        $error .= $desktop->convert_filesize($filesize);
        $error .= " exceeds php.ini's post_max_size of ";
        $error .= ini_get('post_max_size').".<BR>";
        $error .= "Increase php.ini's value for post_max_size or contact the system ";
        $error .= "administrator for assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        exit;
    }

    if($desktop->check_phpini_value($filesize,'upload_max_filesize')) {
        $error  = 299;
        $error .= "\nErrorMessage: ".$filename."'s file size of ";
        $error .= $desktop->convert_filesize($filesize);
        $error .= " exceeds php.ini's upload_max_filesize of ";
        $error .= ini_get('upload_max_filesize').".<BR>";
        $error .= "Increase php.ini's value for upload_max_filesize or contact the system ";
        $error .= "administrator for assistance.";
        $pack   = $rsa->Sign($error);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        exit;
    }

    if($desktop_info['AllowDuplicates'] == 0) {
        if($desktop->media_md5_exists($md5)) {
            $error  = 205;
            $error .= "\nErrorMessage: This media already exists on the server.";
            $error .= "<BR>Duplicate uploads are forbidden by the site administrator.";
            $pack   = $rsa->Sign($error);
            $output = base64_encode($rsa->Encrypt($pack));
            echo($output);
            exit;
        }

        if(strlen($torrentmd5)) {
            if($desktop->torrent_md5_exists($torrentmd5)) {
                $error  = 205;
                $error .= "\nErrorMessage: This torrent already exists on the server.";
                $error .= "<BR>Duplicate uploads are forbidden by the site administrator.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        }
    }

    $pack = $rsa->Sign(0);
    $output = base64_encode($rsa->Encrypt($pack));
    echo($output);
    exit;
}

if($action == 'upload') {
    $entry = array();
    $entry['filename']          = $dom->getElementsByTagName('FileName')->item(0)->nodeValue;
    $entry['original_filename'] = $entry['filename'];
    $entry['media_ext']         = substr($entry['filename'], strrpos($entry['filename'],'.') + 1);
    $entry['media_noext']       = substr($entry['filename'], 0, strrpos($entry['filename'], '.'));
    $entry['is_divx']           = ($entry['media_ext'] == 'divx') ? 1 : 0;
    $entry['is_mp4']            = ($entry['media_ext'] == 'mp4') ? 1 : 0;
    $entry['FileSize']          = $dom->getElementsByTagName('FileSize')->item(0)->nodeValue;
    $entry['embedfile']         = @$dom->getElementsByTagName('EmbedFileName')->item(0)->nodeValue;
    $entry['media_md5']         = $dom->getElementsByTagName('MediaMD5')->item(0)->nodeValue;
    $entry['torrent_md5']       = @$dom->getElementsByTagName('TorrentMD5')->item(0)->nodeValue;

    if($desktop_info['AllowDuplicates'] == 0) {
        if($desktop->media_md5_exists($entry['media_md5'])) {
            $error  = 205;
            $error .= "\nErrorMessage: This media already exists on the server.";
            $error .= "<BR>Duplicate uploads are forbidden by the site administrator.";
            $pack   = $rsa->Sign($error);
            $output = base64_encode($rsa->Encrypt($pack));
            echo($output);
            exit;
        }

        if(strlen($entry['torrent_md5'])) {
            if($desktop->torrent_md5_exists($entry['torrent_md5'])) {
                $error  = 205;
                $error .= "\nErrorMessage: This torrent already exists on the server.";
                $error .= "<BR>Duplicate uploads are forbidden by the site administrator.";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        }
    }

    if(!empty($_FILES)) {
        if(isset($_FILES['upfile']['tmp_name'])) {
            $media_ext      = strtolower(substr($_FILES['upfile']['name'], strrpos($_FILES['upfile']['name'],'.') + 1));
            $media_noext    = substr($_FILES['upfile']['name'], 0, strrpos($_FILES['upfile']['name'], '.'));;
            $audio          = array('mp3');
            $images         = array('bmp','gif','jpeg','jpg','png');

            if(in_array($media_ext,$images)) {
                $entry['is_image']          = 1;
                $entry['broadcast']         = $dom->getElementsByTagName('Broadcast')->item(0)->nodeValue;
                $categories                 = $dom->getElementsByTagName('Categories')->item(0)->nodeValue;
                $categories                 = explode('|',$categories);
                if(count($categories) > 1) {
                    foreach($categories AS $key=>$value) {
                        $split[] = '#'.$value.'# ';
                    }
                    $entry['category'] = join($split);
                } else {
                    $entry['category'] = '#'.$categories[0].'# ';
                }
                $entry['file_name']         = $entry['media_noext'];
                $entry['videokey']          = $desktop->video_keygen();
                $entry['userid']	        = $_SESSION['userid'];
                $entry['title']             = $dom->getElementsByTagName('Title')->item(0)->nodeValue;
                $entry['description']       = $dom->getElementsByTagName('Description')->item(0)->nodeValue;
                $entry['tags']              = $dom->getElementsByTagName('Tags')->item(0)->nodeValue;
                $entry['datecreated']       = @$desktop->fetch_mysql_date(@$dom->getElementsByTagName('Date')->item(0)->nodeValue);
                $entry['datecreated']       = (!strlen(@$dom->getElementsByTagName('Date')->item(0)->nodeValue)) ? '0000-00-00' : $entry['datecreated'];
                $entry['location']          = @$dom->getElementsByTagName('Location')->item(0)->nodeValue;
                $entry['country']           = @$dom->getElementsByTagName('Country')->item(0)->nodeValue;
                $entry['duration']          = $dom->getElementsByTagName('Duration')->item(0)->nodeValue;
                $entry['allow_comments']    = $dom->getElementsByTagName('AllowComments')->item(0)->nodeValue;
                $entry['comment_voting']    = $dom->getElementsByTagName('AllowVoting')->item(0)->nodeValue;
                $entry['allow_rating']      = $dom->getElementsByTagName('AllowRating')->item(0)->nodeValue;
                $entry['allow_embedding']   = $dom->getElementsByTagName('AllowEmbedding')->item(0)->nodeValue;
                $entry['active']            = ($_SESSION['Image-Moderate'] == 'yes') ? 'no' : 'yes';
                $entry['has_torrent']       = $dom->getElementsByTagName('HasTorrent')->item(0)->nodeValue;
                $image_size                 = @getimagesize($_FILES['upfile']['tmp_name']);
                $entry['width']             = $image_size[0];
                $entry['height']            = $image_size[1];

                require_once('includes/classes/PHPThumb/ThumbLib.inc.php');
                $output   = BASEDIR.'/files/thumbs/'.$media_noext.'-1.jpg';
                $PHPThumb = PhpThumbFactory::create($_FILES['upfile']['tmp_name']);
                $PHPThumb->resize($config['thumb_width'], $config['thumb_height']);
                $PHPThumb->save($output,'jpg');
                @chmod($output, 0777);

                copy($output,BASEDIR.'/files/thumbs/'.$media_noext.'-2.jpg');
                @chmod(BASEDIR.'/files/thumbs/'.$media_noext.'-2.jpg', 0777);
                copy($output,BASEDIR.'/files/thumbs/'.$media_noext.'-3.jpg');
                @chmod(BASEDIR.'/files/thumbs/'.$media_noext.'-3.jpg', 0777);

                $output   = BASEDIR.'/files/images/'.$media_noext.'-mid.jpg';
                $PHPThumb = PhpThumbFactory::create($_FILES['upfile']['tmp_name']);
                $PHPThumb->resize(670, 535);
                $PHPThumb->save($output,'jpg');
                @chmod($output, 0777);

                if($_FILES['upfile']['error'] == 0) {
                    if(!@move_uploaded_file($_FILES['upfile']['tmp_name'],BASEDIR.'/files/images/original/'.$_FILES['upfile']['name'])) {
                        $error  = 399;
                        $error .= "\nErrorMessage: An error occured during the image upload of: ";
                        $error .= $_FILES['upfile']['name'];
                        $error .= "<BR>";
                        $error .= "PHP reports:";
                        $error .= "<BR><BR>";
                        $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                        $error .= "<BR><BR>";
                        $error .= "Contact the system administrator if you do not have direct access ";
                        $error .= "to the server's configuration settings.";
                        $pack   = $rsa->Sign($error);
                        $output = base64_encode($rsa->Encrypt($pack));
                        echo($output);
                        exit;
                    }
                    @chmod(BASEDIR.'/files/images/original/'.$_FILES['upfile']['name'], 0777);
                } else {
                    $error  = 399;
                    $error .= "\nErrorMessage: An error occured during the file upload.";
                    $error .= "<BR>";
                    $error .= "PHP reports:";
                    $error .= "<BR><BR>";
                    $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                    $error .= "<BR><BR>";
                    $error .= "Contact the system administrator if you do not have direct access ";
                    $error .= "to the server's configuration settings.";
                    $pack   = $rsa->Sign($error);
                    $output = base64_encode($rsa->Encrypt($pack));
                    echo($output);
                    exit;
                }

                if($media_ext != 'gif') {

                } else {

                }

                if(isset($_FILES['torrent']['tmp_name'])) {
                    if($_FILES['torrent']['error'] == 0)
                    {
                        if(!move_uploaded_file($_FILES['torrent']['tmp_name'],BASEDIR.'/files/torrents/'.$_FILES['torrent']['name']))
                        {
                            $response['ErrorMessage'] = $_FILES['torrent']['name'].' failed to upload.';
                            echo $rsaResponse->response(199,$response);
                            exit;
                        }
                        @chmod(BASEDIR.'/files/torrents/'.$_FILES['torrent']['name'], 0777);
                    }
                    else
                    {
                        $error  = 399;
                        $error .= "\nErrorMessage: An error occured during the torrent upload.";
                        $error .= "<BR>";
                        $error .= "PHP reports:";
                        $error .= "<BR><BR>";
                        $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                        $error .= "<BR><BR>";
                        $error .= "Contact the system administrator if you do not have direct access ";
                        $error .= "to the server's configuration settings.";
                        $pack   = $rsa->Sign($error);
                        $output = base64_encode($rsa->Encrypt($pack));
                        echo($output);
                        exit;
                    }
                }

                if(!isset($error))
                {
                    $desktop->add_image($entry);
                    $pack   = $rsa->Sign(0);
                    $output = base64_encode($rsa->Encrypt($pack));
                    echo($output);
                    exit;
                }
            }
            elseif(in_array($media_ext,$audio))
            {
                $entry['is_audio'] = 1;
                if($_FILES['upfile']['error'] == 0)
                {
                    if(!move_uploaded_file($_FILES['upfile']['tmp_name'],BASEDIR.'/files/audio/'.$_FILES['upfile']['name']))
                    {
                        $response['ErrorMessage'] = $_FILES['upfile']['name'].' failed to upload.';
                        echo $rsaResponse->response(199,$response);
                        exit;
                    }
                    @chmod(BASEDIR.'/files/audio/'.$_FILES['upfile']['name'], 0777);
                    require_once('includes/classes/getid3/getid3.php');
                    $getID3     = new getID3;
                    $mp3        = BASEDIR.'/files/audio/'.$_FILES['upfile']['name'];
                    $mp3info    = $getID3->analyze($mp3);
                    /*
                     *  Optional: copies data from all subarrays of [tags] into [comments] so
                     *  metadata is all available in one location for all tag formats
                     *  metainformation is always available under [tags] even if this is not called
                     */
                    getid3_lib::CopyTagsToComments($mp3info);

                    $entry['broadcast']         = $dom->getElementsByTagName('Broadcast')->item(0)->nodeValue;
                    $categories                 = $dom->getElementsByTagName('Categories')->item(0)->nodeValue;
                    $categories                 = explode('|',$categories);
                    if(count($categories) > 1)
                    {
                        foreach($categories AS $key=>$value)
                        {
                            $split[] = '#'.$value.'# ';
                        }
                        $entry['category'] = join($split);
                    }
                    else
                    {
                        $entry['category'] = '#'.$categories[0].'# ';
                    }
                    $entry['duration']          = $dom->getElementsByTagName('Duration')->item(0)->nodeValue;
                    $entry['file_name']         = $entry['media_noext'];
                    $entry['videokey']          = $desktop->video_keygen();
                    $entry['userid']	        = $_SESSION['userid'];
                    $entry['title']             = (strlen(@$mp3info['comments_html']['title'][0])) ? @$mp3info['comments_html']['title'][0] : htmlspecialchars_decode($dom->getElementsByTagName('Title')->item(0)->nodeValue,ENT_QUOTES);
                    $entry['description']       = htmlspecialchars_decode($dom->getElementsByTagName('Description')->item(0)->nodeValue,ENT_QUOTES);
                    $entry['tags']              = $dom->getElementsByTagName('Tags')->item(0)->nodeValue;
                    $entry['datecreated']       = @$desktop->fetch_mysql_date(@$dom->getElementsByTagName('Date')->item(0)->nodeValue);
                    $entry['datecreated']       = (!strlen(@$dom->getElementsByTagName('Date')->item(0)->nodeValue)) ? '0000-00-00' : $entry['datecreated'];
                    $entry['location']          = @$dom->getElementsByTagName('Location')->item(0)->nodeValue;
                    $entry['country']           = @$dom->getElementsByTagName('Country')->item(0)->nodeValue;
                    $entry['allow_comments']    = $dom->getElementsByTagName('AllowComments')->item(0)->nodeValue;
                    $entry['comment_voting']    = $dom->getElementsByTagName('AllowVoting')->item(0)->nodeValue;
                    $entry['allow_rating']      = $dom->getElementsByTagName('AllowRating')->item(0)->nodeValue;
                    $entry['allow_embedding']   = $dom->getElementsByTagName('AllowEmbedding')->item(0)->nodeValue;
                    $entry['active']            = ($_SESSION['Audio-Moderate'] == 'yes') ? 'no' : 'yes';
                    $entry['has_torrent']       = $dom->getElementsByTagName('HasTorrent')->item(0)->nodeValue;
                }
                else
                {
                    $error  = 399;
                    $error .= "\nErrorMessage: An error occured during the file upload.";
                    $error .= "<BR>";
                    $error .= "PHP reports:";
                    $error .= "<BR><BR>";
                    $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                    $error .= "<BR><BR>";
                    $error .= "Contact the system administrator if you do not have direct access ";
                    $error .= "to the server's configuration settings.";
                    $pack   = $rsa->Sign($error);
                    $output = base64_encode($rsa->Encrypt($pack));
                    echo($output);
                    exit;
                }

                if(isset($_FILES['audio_pic']['tmp_name']))
                {
                    $entry['has_pic'] = 1;
                    $pic_ext = strtolower(substr($_FILES['audio_pic']['name'], strrpos($_FILES['audio_pic']['name'],'.') + 1));
                    if($_FILES['audio_pic']['error'] == 0)
                    {
                        $entry['audio_pic'] = $_FILES['audio_pic']['name'];
/*                        if($pic_ext != 'gif')
                        {
                            $output   = BASEDIR.'/files/thumbs/'.'1_'.$media_noext.'.jpg';
                            $PHPThumb = PhpThumbFactory::create($_FILES['audio_pic']['tmp_name']);
                            $PHPThumb->adaptiveResize(120, 90);
                            $PHPThumb->save($output,'jpg');
                            @chmod($output, 0777);

                            copy($small_thumb,BASEDIR.'/files/thumbs/'.'2_'.$media_noext.'.jpg');
                            @chmod(BASEDIR.'/files/thumbs/'.'2_'.$media_noext.'.jpg', 0777);
                            copy($small_thumb,BASEDIR.'/files/thumbs/'.'3_'.$media_noext.'.jpg');
                            @chmod(BASEDIR.'/files/thumbs/'.'3_'.$media_noext.'.jpg', 0777);
                        }
                        else
                        {
                            move_uploaded_file($_FILES['audio_pic']['tmp_name'],BASEDIR.'/uploads/audio/thumbs/'.$_FILES['audio_pic']['name']);
                            @chmod(BASEDIR.'/uploads/audio/thumbs/'.$_FILES['audio_pic']['name'], 0777);
                        }*/
                        $output   = BASEDIR.'/files/thumbs/'.$media_noext.'-1.jpg';
                        require_once('includes/classes/PHPThumb/ThumbLib.inc.php');
                        $PHPThumb = PhpThumbFactory::create($_FILES['audio_pic']['tmp_name']);
                        $PHPThumb->adaptiveResize(120, 90);
                        $PHPThumb->save($output,'jpg');
                        @chmod($output, 0777);

                        copy($output,BASEDIR.'/files/thumbs/'.$media_noext.'-2.jpg');
                        @chmod(BASEDIR.'/files/thumbs/'.$media_noext.'-2.jpg', 0777);
                        copy($output,BASEDIR.'/files/thumbs/'.$media_noext.'-3.jpg');
                        @chmod(BASEDIR.'/files/thumbs/'.$media_noext.'-3.jpg', 0777);
                    }
                    else
                    {
                        $error  = 399;
                        $error .= "\nErrorMessage: An error occured during the file upload.";
                        $error .= "<BR>";
                        $error .= "PHP reports:";
                        $error .= "<BR><BR>";
                        $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                        $error .= "<BR><BR>";
                        $error .= "Contact the system administrator if you do not have direct access ";
                        $error .= "to the server's configuration settings.";
                        $pack   = $rsa->Sign($error);
                        $output = base64_encode($rsa->Encrypt($pack));
                        echo($output);
                        exit;
                    }
                }

                if(isset($_FILES['torrent']['tmp_name']))
                {
                    if($_FILES['torrent']['error'] == 0)
                    {
                        if(!move_uploaded_file($_FILES['torrent']['tmp_name'],BASEDIR.'/files/torrents/'.$_FILES['torrent']['name']))
                        {
                            $response['ErrorMessage'] = $_FILES['torrent']['name'].' failed to upload.';
                            echo $rsaResponse->response(199,$response);
                            exit;
                        }
                        @chmod(BASEDIR.'/files/torrents/'.$_FILES['torrent']['name'], 0777);
                    }
                    else
                    {
                        $error  = 399;
                        $error .= "\nErrorMessage: An error occured during the torrent upload.";
                        $error .= "<BR>";
                        $error .= "PHP reports:";
                        $error .= "<BR><BR>";
                        $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                        $error .= "<BR><BR>";
                        $error .= "Contact the system administrator if you do not have direct access ";
                        $error .= "to the server's configuration settings.";
                        $pack   = $rsa->Sign($error);
                        $output = base64_encode($rsa->Encrypt($pack));
                        echo($output);
                        exit;
                    }
                }

                if(!isset($error))
                {
                    $desktop->add_audio($entry);
                    $pack   = $rsa->Sign(0);
                    $output = base64_encode($rsa->Encrypt($pack));
                    echo($output);
                    exit;
                }
            }
            else
            {
                if($_FILES['upfile']['error'] == 0)
                {
                    $entry['flv'] = $_FILES['upfile']['name'];
                    if(!move_uploaded_file($_FILES['upfile']['tmp_name'],BASEDIR.'/files/videos/'.$_FILES['upfile']['name']))
                    {
                        $response['ErrorMessage'] = $_FILES['upfile']['name'].' failed to upload.';
                        echo $rsaResponse->response(199,$response);
                        exit;
                    }
                    @chmod(BASEDIR.'/files/videos/'.$_FILES['upfile']['name'], 0777);
                }
                else
                {
                    $error  = 399;
                    $error .= "\nErrorMessage: An error occured during the file upload.";
                    $error .= "<BR>";
                    $error .= "PHP reports:";
                    $error .= "<BR><BR>";
                    $error .= $desktop->php_file_upload_error($_FILES['upfile']['error']);
                    $error .= "<BR><BR>";
                    $error .= "Contact the system administrator if you do not have direct access ";
                    $error .= "to the server's configuration settings.";
                    $pack   = $rsa->Sign($error);
                    $output = base64_encode($rsa->Encrypt($pack));
                    echo($output);
                    exit;
                }
            }
        }

        // start for loop
        for($x=1;$x<=THUMB_COUNT;$x++)
        {
            if(isset($_FILES['thumb_'. $x]['tmp_name']))
            {
                $move = move_uploaded_file($_FILES['thumb_'. $x]['tmp_name'],BASEDIR.'/files/thumbs/'.$_FILES['thumb_'. $x]['name']);
                // check if successfully moved
                if($move)
                {
                    @chmod(BASEDIR.'/files/thumbs/'.$_FILES['thumb_'. $x]['name'], 0777);
                }
                else
                {
                    $response['ErrorMessage'] = $_FILES['thumb_'. $x]['name'].' failed to upload.';
                    echo $rsaResponse->response(199,$response);
                    exit;
                }
            }
        }
        // end of loop

        if(isset($_FILES['thumb_big']['tmp_name']))
        {
        	$finalBig = basename( $_FILES['thumb_big']['name'] , '.jpg' );
        	$finalBig = $finalBig.'-big-1.jpg';
        	
            if( !move_uploaded_file( $_FILES['thumb_big']['tmp_name'], BASEDIR.'/files/thumbs/'.$finalBig ) )
            {
                $response['ErrorMessage'] = $_FILES['thumb_big']['name'].' failed to upload.';
                echo $rsaResponse->response(199,$response);
                exit;
            }
            @chmod(BASEDIR.'/files/thumbs/'.BASEDIR.'/files/thumbs/'.$finalBig, 0777);
        }

        if(isset($_FILES['torrent']['tmp_name']))
        {
            if($_FILES['torrent']['error'] == 0)
            {
                if(!move_uploaded_file($_FILES['torrent']['tmp_name'],BASEDIR.'/files/torrents/'.$_FILES['torrent']['name']))
                {
                    $response['ErrorMessage'] = $_FILES['torrent']['name'].' failed to upload.';
                    echo $rsaResponse->response(199,$response);
                    exit;
                }
                @chmod(BASEDIR.'/files/torrents/'.$_FILES['torrent']['name'], 0777);
            }
            else
            {
                $error  = 399;
                $error .= "\nErrorMessage: An error occured during the torrent upload.<BR>";
                $error .= "Check the following php.ini values:<BR><BR>";
                $error .= "post_max_size<BR>";
                $error .= "upload_max_filesize";
                $pack   = $rsa->Sign($error);
                $output = base64_encode($rsa->Encrypt($pack));
                echo($output);
                exit;
            }
        }
    }

    $entry['broadcast']         = $dom->getElementsByTagName('Broadcast')->item(0)->nodeValue;
    $categories                 = $dom->getElementsByTagName('Categories')->item(0)->nodeValue;
    $categories                 = explode('|',$categories);
    if(count($categories) > 1)
    {
        foreach($categories AS $key=>$value)
        {
            $split[] = '#'.$value.'# ';
        }
        $entry['category'] = join($split);
    }
    else
    {
        $entry['category'] = '#'.$categories[0].'# ';
    }
    $entry['file_name']         = $entry['media_noext'];
    $entry['videokey']          = $desktop->video_keygen();
    $entry['userid']	        = $_SESSION['userid'];
    $entry['title']             = $dom->getElementsByTagName('Title')->item(0)->nodeValue;
    $entry['description']       = $dom->getElementsByTagName('Description')->item(0)->nodeValue;
    $entry['tags']              = $dom->getElementsByTagName('Tags')->item(0)->nodeValue;
    $entry['datecreated']       = @$desktop->fetch_mysql_date(@$dom->getElementsByTagName('Date')->item(0)->nodeValue);
    $entry['datecreated']       = (!strlen(@$dom->getElementsByTagName('Date')->item(0)->nodeValue)) ? '0000-00-00' : $entry['datecreated'];
    $entry['location']          = @$dom->getElementsByTagName('Location')->item(0)->nodeValue;
    $entry['country']           = @$dom->getElementsByTagName('Country')->item(0)->nodeValue;
    $entry['duration']          = $dom->getElementsByTagName('Duration')->item(0)->nodeValue;
    $entry['allow_comments']    = $dom->getElementsByTagName('AllowComments')->item(0)->nodeValue;
    $entry['comment_voting']    = $dom->getElementsByTagName('AllowVoting')->item(0)->nodeValue;
    $entry['allow_rating']      = $dom->getElementsByTagName('AllowRating')->item(0)->nodeValue;
    $entry['allow_embedding']   = $dom->getElementsByTagName('AllowEmbedding')->item(0)->nodeValue;
    $entry['embed_code']        = @$dom->getElementsByTagName('EmbedCode')->item(0)->nodeValue;
    if(strlen($entry['embed_code']))
    {
        if($desktop_info['AutoPlayYTEmbed'] == 1)
        {
            $entry['embed_code'] = str_replace("&fs=1", "&fs=1&autoplay=1", $entry['embed_code']);
        }

        if($desktop_info['YT-ResizeEmbedCode'] == 1)
        {
            preg_match("/width=\"(.*)\"/Ui",$entry['embed_code'],$width);
            preg_match("/height=\"(.*)\"/Ui",$entry['embed_code'],$height);
            $entry['embed_code'] = str_replace($width[0], 'width="'.$desktop_info['YT-ResizeWidth'].'"', $entry['embed_code']);
            $entry['embed_code'] = str_replace($height[0], 'height="'.$desktop_info['YT-ResizeHeight'].'"', $entry['embed_code']);
        }
    }
    $entry['embed_url']         = @$dom->getElementsByTagName('EmbedURL')->item(0)->nodeValue;
    $entry['active']            = ($_SESSION['Video-Moderate'] == 'yes') ? 'no' : 'yes';
    $entry['yt_fmt']            = @$dom->getElementsByTagName('YoutubeFormat')->item(0)->nodeValue;
    $entry['width']             = @$dom->getElementsByTagName('Width')->item(0)->nodeValue;
    $entry['height']            = @$dom->getElementsByTagName('Height')->item(0)->nodeValue;
    $entry['aspect_ratio']      = @$dom->getElementsByTagName('AspectRatio')->item(0)->nodeValue;
    $entry['has_torrent']       = $dom->getElementsByTagName('HasTorrent')->item(0)->nodeValue;
    $entry['audio_pic']         = $dom->getElementsByTagName('AudioHasPic')->item(0)->nodeValue;
    $entry['is_embed']          = @$dom->getElementsByTagName('IsEmbedCode')->item(0)->nodeValue;


    if(!isset($error))
    {
        $desktop->add_video($entry);

        $pack   = $rsa->Sign(0);
        $output = base64_encode($rsa->Encrypt($pack));
        echo($output);
        exit;
    }
}

?>