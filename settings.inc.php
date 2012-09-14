<?

$conf['endpoint']['local'] = 'http://localhost:3030/ds/query';
$conf['updateendpoint']['local'] = 'http://localhost:3030/ds/data';
$conf['home'] = '/var/www/lodspk-bundle/lodspeakr/';
$conf['basedir'] = 'http://alia/lodspk-bundle/';
$conf['debug'] = false;

/*ATTENTION: By default this application is available to
 * be exported and copied (its configuration)
 * by others. If you do not want that, 
 * turn the next option as false
 */ 
$conf['export'] = true;

#If you want to add/overrid a namespace, add it here
$conf['ns']['local']   = 'http://alia/lodspk-bundle/';
$conf['ns']['base']   = 'http://alia/lodspk-bundle/';

$conf['mirror_external_uris'] = $conf['ns']['local'];

$conf['modules']['available'] = array('admin','static','uri','type','service');
?>
