<?php
/**
#########################################################################################################
# Copyright (c) 2009 - 2010 CustomCode.info. All Rights Reserved.
# This file may not be redistributed in whole or significant part.
# URL:              [url]http://customcode.info[/url]
# Function:         Desktop Uploader Interaction
# Author:           fwhite
# Language:         PHP
# License:          Commercial
# ----------------- THIS IS NOT FREE SOFTWARE ----------------
# Version:          $Id: RSAResponse.class.php 1 2010-03-30 05:30:58Z Q $
# Created:          Friday, January 01, 2010 / 07:16 AM GMT+1 (fwhite)
# Last Modified:    $Date: 2010-03-30 07:30:58 +0200 (Tue, 30 Mar 2010) $
# Notice:           Please maintain this section
#########################################################################################################
*/

error_reporting(0);
ini_set('display_errors', false);

class RSAResponse
{
// start RSAResponse.class.php

    function response($status,$response = array())
    {
        global $rsa;

        // generate server signature key
        $response['SignKey'] = $rsa->GenerateServerSignKey();
        $message = $status;

        if(!empty($response))
        {
            foreach($response AS $name=>$value)
            {
                $message .= "\n{$name}: {$value}";
            }
        }

        $pack   = $rsa->Sign($message);
        $output = base64_encode($rsa->Encrypt($pack));

        return($output);
    }

// end RSAResponse.class.php
}