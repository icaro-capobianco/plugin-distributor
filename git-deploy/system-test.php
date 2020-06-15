<?php

define("TOKEN", "TESTTOKEN");                                          // The secret token to add as a GitHub or GitLab secret, or otherwise as https://www.example.com/?token=secret-token
define("DIR", "../plugins/");                                          // The path to where you store your plugins
define("BRANCH", "refs/heads/master");                                // The branch route
define("BRANCH_NAME", "master");                                      // The branch route
define("LOGFILE", "deploy.log");                                       // The name of the file you want to log to.
define("GIT", "git");                                         // The path to the git executable
define("GIT_OVERWRITE", false);                                         // The path to the git executable
define("MAX_EXECUTION_TIME", 180);                                     // Override for PHP's max_execution_time (may need set in php.ini)
define("BEFORE_PULL", '');                                             // A command to execute before pulling
define("AFTER_PULL", '');                                              // A command to execute after successfully pulling
define("INSTALL_CMD", 'composer install --prefer-source');             // A command intall required packages
define("ZIP_TO", '../wp-update-server/packages/');                     // Path where the plugin will be zip will be sent to

$_GET['token'] = 'TESTTOKEN';
function get_input() {
	return json_encode( [
		'ref' => 'refs/heads/master',
		'repository' => [ 'ssh_url' => 'https://github.com/getsmartgroup/gsg-onboard', 'name' => 'gsg-onboard' ]
	] );
}

require_once("deployer.php");
