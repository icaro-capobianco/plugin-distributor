<?php
define('TOKEN', 'TESTTOKEN');                    			           // The secret token to add as a GitHub or GitLab secret, or otherwise as https://www.example.com/?token=secret-token
define('PLUGINS_DIR', '../plugins/');                                  // The path to where you store your plugins
define('BRANCH_REF', 'refs/heads/master');                             // The branch route
define('BRANCH_NAME', 'master');                                       // The branch route
define('LOGFILE', 'deploy.log');                                       // The name of the file you want to log to.
define('GIT', 'git');                                                  // The path to the git executable
define('GIT_OVERWRITE', false);                                         // The path to the git executable
define('INSTALL_CMD', 'composer install --prefer-dist --no-dev');      // A command intall required packages
define('ZIP_TO', '../wp-update-server/packages/');                     // Path where the plugin will be zip will be sent to

$_GET['token'] = 'TESTTOKEN';
function get_input() {
	return json_encode( [
		'ref' => 'refs/heads/master',
		'repository' => [ 'ssh_url' => 'https://github.com/getsmartgroup/gsg-onboard', 'name' => 'gsg-onboard' ]
	] );
}

require_once("class-deployer.php");
