<?php

if (!isset($PHPR_NO_SESSION) || !$PHPR_NO_SESSION)
{
    // Override CMS security object
    Phpr::$frontend_security = new Cms_Security();

    // Override admin security object
    Phpr::$security = new Admin_Security();

    // Start session object
    Phpr::$session->start();
}

// Include routing
require_once('routes.php');

// Default application config
if (!isset($APP_CONF))
    $APP_CONF = array();

$APP_CONF['UPDATE_SEQUENCE'] = array('core', 'email', 'admin', 'cms', 'user', 'payment', 'service');
$APP_CONF['DB_CONFIG_MODE'] = 'insecure';
$APP_CONF['UPDATE_CENTER'] = 'api.phproad.com/update_gateway';
$APP_CONF['JAVASCRIPT_URL'] = "framework/assets/scripts/js";
$APP_CONF['PHPR_URL'] = "framework";