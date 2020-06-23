<?php
define('TOKEN', 'secret-token-shouldntbecommited');                    // The secret token to add as a GitHub or GitLab secret, or otherwise as https://www.example.com/?token=secret-token
define('PLUGINS_DIR', '../plugins/');                                  // The path to where you store your plugins
define('BRANCH_REF', 'refs/heads/release');                            // The branch route
define('BRANCH_NAME', 'release');                                      // The branch route
define('LOGFILE', 'deploy.log');                                       // The name of the file you want to log to.
define('GIT', '/usr/bin/git');                                         // The path to the git executable
define('GIT_OVERWRITE', true);                                         // The path to the git executable
define('INSTALL_CMD', 'composer install --prefer-dist --no-dev');      // A command intall required packages
define('ZIP_TO', '../wp-update-server/packages/');                     // Path where the plugin will be zip will be sent to

function get_input() {
	return file_get_contents('php://input');
}

require_once('class-deployer.php');
