<?php 
/*******
 Original Plugin & Theme API by Kaspars Dambis (kaspars@konstruktors.com)
 Modified by Jeremy Clark http://clark-technet.com
*******/

require(dirname(__FILE__).'/config.php');
require(dirname(__FILE__).'/wordpress-plugin-readme-parser/markdown.php');
require(dirname(__FILE__).'/wordpress-plugin-readme-parser/parse-readme.php');

// Pull user agent  
$user_agent = $_SERVER['HTTP_USER_AGENT'];

//Kill magic quotes.  Can't unserialize POST variable otherwise
if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

if (stristr($user_agent, 'WordPress') == TRUE){
	// Process API requests
	$action = $_POST['action'];
	$args = unserialize($_POST['request']);

	if (is_array($args))
		$args = array_to_object($args);

	if (isset($packages[$args->slug])) {
		$latest_package = array_shift($packages[$args->slug]['versions']);
	} elseif (($f=PLUGINS_DIR.$args->slug.'/readme.txt') && file_exists($f)) {
		$readme=new Automattic_Readme();
		$latest_package=$readme->parse_readme($f);
		$latest_package['package']=BASE_URL.'/download.php?file='.$args->slug.'.zip';  // The zip file of the plugin update
	} else {
		die();
	}
	
} else {
	/*
	An error message can be displayed to users who go directly to the update url
	*/

	echo 'Whoops, this page doesn\'t exist';
}


// basic_check

if ($action == 'basic_check') {	
	$update_info = array_to_object($latest_package);
	$update_info->slug = $args->slug;
	
	if (version_compare($args->version, $latest_package['version'], '<'))
		$update_info->new_version = $update_info->version;
	else 
		$update_info->new_version = '';
	print serialize($update_info);
}


// plugin_information

if ($action == 'plugin_information') {	
	$data = new stdClass;
	
	$data->slug = $args->slug;
	$data->version = $latest_package['version'];
	$data->last_updated = $latest_package['date'];
	$data->download_link = $latest_package['package'];
	$data->author = $latest_package['author'];
	$data->external = $latest_package['external'];
	$data->requires = $latest_package['requires'];
	$data->tested = $latest_package['tested'];
	$data->homepage = $latest_package['homepage'];
	$data->downloaded = $latest_package['downloaded'];
	$data->sections = $latest_package['sections'];
	print serialize($data);
}


// theme_update

if ($action == 'theme_update') {
	$update_info = array_to_object($latest_package);
	
	//$update_data = new stdClass;
	$update_data = array();
	$update_data['package'] = $update_info->package;	
	$update_data['new_version'] = $update_info->version;
	$update_data['url'] = $packages[$args->slug]['info']['url'];
	if (version_compare($args->version, $latest_package['version'], '<'))
		print serialize($update_data);	
}

if ($action == 'theme_information') {	
	$data = new stdClass;
	
	$data->slug = $args->slug;
	$data->name = $latest_package['name'];	
	$data->version = $latest_package['version'];
	$data->last_updated = $latest_package['date'];
	$data->download_link = $latest_package['package'];
	$data->author = $latest_package['author'];
	$data->requires = $latest_package['requires'];
	$data->tested = $latest_package['tested'];
	$data->screenshot_url = $latest_package['screenshot_url'];
	print serialize($data);
}

function array_to_object($array = array()) {
	if (empty($array) || !is_array($array))
		return false;
		
	$data = new stdClass;
	foreach ($array as $akey => $aval)
			$data->{$akey} = $aval;
	return $data;
}
