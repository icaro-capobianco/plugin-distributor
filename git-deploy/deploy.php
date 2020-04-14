<?php
define("TOKEN", "secret-token");                                       // The secret token to add as a GitHub or GitLab secret, or otherwise as https://www.example.com/?token=secret-token
define("DIR", "../plugins/");                                          // The path to where you store your plugins
define("BRANCH", "refs/heads/release");                                // The branch route
define("BRANCH_NAME", "release");                                      // The branch route
define("LOGFILE", "deploy.log");                                       // The name of the file you want to log to.
define("GIT", "/usr/bin/git");                                         // The path to the git executable
define("MAX_EXECUTION_TIME", 180);                                     // Override for PHP's max_execution_time (may need set in php.ini)
define("BEFORE_PULL", '');                                             // A command to execute before pulling
define("AFTER_PULL", '');                                              // A command to execute after successfully pulling
define("ZIP_TO", '../wp-update-server/packages/');                     // Path where the plugin will be zip will be sent to

require_once("deployer.php");
