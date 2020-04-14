<?php
$content = file_get_contents("php://input");
$json    = json_decode($content, true);
$file    = fopen(LOGFILE, "a");
$time    = time();
$token   = false;
$sha     = false;
$DIR     = preg_match("/\/$/", DIR) ? DIR : DIR . "/";

// retrieve the token
if (!$token && isset($_SERVER["HTTP_X_HUB_SIGNATURE"])) {
    list($algo, $token) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
} elseif (isset($_SERVER["HTTP_X_GITLAB_TOKEN"])) {
    $token = $_SERVER["HTTP_X_GITLAB_TOKEN"];
} elseif (isset($_GET["token"])) {
    $token = $_GET["token"];
}

// write the time to the log
date_default_timezone_set("UTC");
fputs($file, date("d-m-Y (H:i:s)", $time) . "\n");

// specify that the response does not contain HTML
header("Content-Type: text/plain");

// use user-defined max_execution_time
if (!empty(MAX_EXECUTION_TIME)) {
    ini_set("max_execution_time", MAX_EXECUTION_TIME);
}

// function to forbid access
function forbid($file, $reason) {
    // format the error
    $error = "=== ERROR: " . $reason . " ===\n*** ACCESS DENIED ***\n";

    // forbid
    http_response_code(403);

    // write the error to the log and the body
    fputs($file, $error . "\n\n");
    echo $error;

    // close the log
    fclose($file);

    // stop executing
    exit;
}

function exec_and_handle( $cmd ) {
    exec($cmd, $output, $exit);

    // reformat the output as a string
    $output = (!empty($output) ? implode("\n", $output) : "[no output]") . "\n";

    // if an error occurred, return 500 and log the error
    if ($exit !== 0) {
        http_response_code(500);
        $output = "=== ERROR: exec failed command: $cmd ===\n" . $output;
    }

    // write the output to the log and the body
    fputs($file, $output);
    echo $output;
}

function validate_repo( $repo_path ) {
    return is_dir( $repo_path ) && file_exists( $repo_path . '/.git' );
}

function pull_repo( $repo_url, $repo_path ) {
    exec_and_handle( GIT . ' clone -b ' . BRANCH_NAME . ' ' . $repo_url . ' ' . $repo_path . ' 2>&1' );
}

function handle_error( $message ) {
    global $file;
    fputs($file, $error);
    echo $error;
}

// Check for a GitHub signature
if (!empty(TOKEN) && isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && $token !== hash_hmac($algo, $content, TOKEN)) {
    forbid($file, "X-Hub-Signature does not match TOKEN");
// Check for a GitLab token
} elseif (!empty(TOKEN) && isset($_SERVER["HTTP_X_GITLAB_TOKEN"]) && $token !== TOKEN) {
    forbid($file, "X-GitLab-Token does not match TOKEN");
// Check for a $_GET token
} elseif (!empty(TOKEN) && isset($_GET["token"]) && $token !== TOKEN) {
    forbid($file, "\$_GET[\"token\"] does not match TOKEN");
// if none of the above match, but a token exists, exit
} elseif (!empty(TOKEN) && !isset($_SERVER["HTTP_X_HUB_SIGNATURE"]) && !isset($_SERVER["HTTP_X_GITLAB_TOKEN"]) && !isset($_GET["token"])) {
    forbid($file, "No token detected");
} else {
    // check if pushed branch matches branch specified in config
    if ($json["ref"] === BRANCH) {
        
        $repo_url = $json['repository']['ssh_url'];
        $repo_name = $json['repository']['name'];
        $repo_dir_path = $DIR . $repo_name;

        fputs($file, $content . PHP_EOL);

        // ensure directory is a repository
        if( ! file_exists( $repo_dir_path ) ) {
            pull_repo( $repo_url, $repo_dir_path );
        } elseif( ! file_exists( $repo_dir_path . "/.git" ) ) {
            rmdir( $repo_dir_path );
            pull_repo( $repo_url, $repo_dir_path );
        }

        if( ! validate_repo( $repo_dir_path ) ) {
            http_response_code(500);
            handle_error( 'Could make a valid repo' );
        }

        fputs($file, "*** AUTO PULL INITIATED ***" . "\n");

        if (!empty(BEFORE_PULL)) {
            fputs($file, "*** BEFORE_PULL INITIATED ***" . "\n");
            exec_and_handle( BEFORE_PULL . " 2>&1" );
        }

        exec_and_handle( GIT . " pull 2>&1" );

        $zipCommand = "git --git-dir=" . $repo_dir_path . "/.git archive " . BRANCH_NAME . " --prefix=$repo_name/ -o " . ZIP_TO . "$repo_name.zip";
        exec_and_handle( $zipCommand );

        if (!empty(AFTER_PULL)) {
            fputs($file, "*** AFTER_PULL INITIATED ***" . "\n");
            exec_and_handle( AFTER_PULL . " 2>&1" );
        }

        fputs($file, "*** AUTO PULL COMPLETE ***" . "\n");
        
    } else{
        $error = "=== ERROR: Pushed branch `" . $json["ref"] . "` does not match BRANCH `" . BRANCH . "` ===\n";

        // bad request
        http_response_code(400);

        // write the error to the log and the body
        fputs($file, $error);
        echo $error;
    }
}

// close the log
fputs($file, "\n\n" . PHP_EOL);
fclose($file);
