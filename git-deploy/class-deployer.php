<?php

class Deployer {

	private $json;
	private $logfile;

	public function __construct( $json, $logfile ) {

		$open = $this->open_log( $logfile );

		if ( $open ) {

			$this->json    = $json;
			$this->logfile = $open;
			$now = new \DateTime();
			$this->log( "=============== " . $now->format( 'Y/m/d h:i:s a e' ) . " ===============" );
	
			if ( $json ) {
	
				$parsed = $this->parse_json( $json );
				if ( $parsed ) {
	
					$this->load_variables( $parsed );
					$this->validate_token();
					if ( $this->ref === BRANCH_REF ) {

						$this->ensure_local_repo();
						if ( $this->valid_repo() ) {
							$this->pull_repo();
							$this->respond( 200, "Generating repo ZIP" );
							$this->archive_repo();
						}
					} else {
						$this->respond( 200, "Expecting ".basename( BRANCH_REF ).", received $this->repo_name" );
					}
				}
			}
		}
	}

	private function open_log( $logfile ) {
		$open = fopen( $logfile, 'a' );
		if ( ! $open ) {
			echo "Could not open logfile: $logfile\n";
			exit( "Could not open logfile: $logfile\n" );
		}
		return $open;
	}
	private function parse_json( $json ) {
		$parsed = json_decode( $json, true );
		if ( ! $parsed ) {
			$this->log( "[PARSE_ERROR] Could not decode json \n$json" );
			$this->respond( 500, "Internal error", true );
		}
		return $parsed;
	}

	private $ref;
	private $repo_name;
	private $repo_ssh_url;
	private function load_variables( $parsed ) {

		$ref           = $parsed['ref'];
		$repo_name     = $parsed['repository']['name'];
		$repo_ssh_url  = $parsed['repository']['ssh_url'];

		$plugins_dir  = preg_match("/\/$/", PLUGINS_DIR) ? PLUGINS_DIR : PLUGINS_DIR . "/";
		$repo_path    = $plugins_dir . $repo_name;

		if ( GIT_OVERWRITE ) {
            $repo_ssh_url = str_replace( 'github.com', "$repo_name.github.com", $repo_ssh_url );
		}

		$this->ref          = $ref;
		$this->repo_name    = $repo_name;
		$this->repo_path    = $repo_path;
		$this->repo_ssh_url = $repo_ssh_url;

	}
	private function retrieve_token() {
		$token = false;
		if ( ! $token && isset($_SERVER["HTTP_X_HUB_SIGNATURE"] ) ) {
			list($algo, $token) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
		} elseif (isset($_SERVER["HTTP_X_GITLAB_TOKEN"])) {
			$token = $_SERVER["HTTP_X_GITLAB_TOKEN"];
		} elseif (isset($_GET["token"])) {
			$token = $_GET["token"];
		}
		if ( ! $token ) {
			$this->respond( 403, "No Access Token", true );
		}
		return $token;
	}
	private function validate_token() {
		$token = $this->retrieve_token();
		// Github Token
		if ( ! empty(TOKEN) && isset( $_SERVER["HTTP_X_HUB_SIGNATURE"]) ) {
			list($algo, $token) = explode("=", $_SERVER["HTTP_X_HUB_SIGNATURE"], 2) + array("", "");
			if ( $token !== hash_hmac( $algo, $this->json, TOKEN ) ) {
				$this->forbid( 'X-Hub-Signature does not match TOKEN' );
			}
		// GitLab token
		} elseif ( ! empty(TOKEN) && isset( $_SERVER[ "HTTP_X_GITLAB_TOKEN" ] ) && $token !== TOKEN ) {
			$this->forbid( 'X-GitLab-Token does not match TOKEN' );
		// $_GET token
		} elseif ( ! empty(TOKEN) && isset( $_GET["token"] ) && $token !== TOKEN ) {
			$this->forbid( '$_GET[token] does not match TOKEN' );
		// if none of the above match, but a token exists, exit
		} elseif ( ! empty(TOKEN) && ! isset( $_SERVER["HTTP_X_HUB_SIGNATURE"] ) && ! isset( $_SERVER[ "HTTP_X_GITLAB_TOKEN" ] ) && ! isset( $_GET [ "token" ] ) ) {
			$this->forbid( 'No token detected' );
		}
	}
	private function ensure_local_repo() {
        if( ! file_exists( $this->repo_path ) ) {
			$this->respond( 200, "Cloning repo" );
			$this->clone_repo();
        } elseif( ! file_exists( $this->repo_path . "/.git" ) ) {
            rmdir( $this->repo_path );
			$this->respond( 200, "Cloning repo" );
			$this->clone_repo();
        }
	}
	private function valid_repo() {
		$result = is_dir( $this->repo_path ) && file_exists( $this->repo_path . '/.git' );
		if ( ! $result ) {
			$this->log( "[ERROR] Invalid repo at $this->repo_path" );
			$this->respond( 500, "Internal Error", true );
		}
		return $result;
	}
	private function clone_repo() {
		$this->exec_and_handle( GIT . ' clone -b ' . BRANCH_NAME . ' ' . $this->repo_ssh_url . ' ' . $this->repo_path . ' 2>&1' );
	}
	private function pull_repo() {
        $this->exec_and_handle( GIT . " --git-dir=$this->repo_path/.git pull 2>&1" );
	}
	private function forbid( $reason ) {
		$this->log( "[ACCESS DENIED] $reason\n" );
		$this->respond( 403, "Access Denied" );
		exit;
	}
	private function respond( $status, $response, $exit = false ) {
		$this->log( "[RESPONSE]$status\n$response" );
		http_response_code( $status );

		header( 'Content-Type: application/json' );
		
		if ( is_callable('fastcgi_finish_request') ) {
	
			echo $response;
			session_write_close();
			fastcgi_finish_request();
	 
		} else {

			ob_start();
			echo $response;
			header('Connection: close');
			ob_end_flush();
		}

		if( $exit ) {
			exit;
		}

	}
	private function log( $log ) {
		fputs($this->logfile, "$log\n\n");
	}
	private function exec_and_handle( $cmd ) {

		exec( $cmd, $output, $exit );
		$output = empty( $output ) ? implode( "\n", $output ) : '[no output]';
		$log_type = $exit === 0 ? 'COMMAND' : 'CMD_ERROR';
		$this->log( "[$log_type][$exit] $cmd \n $output" );

		if ( $exit !== 0 ) {
			$this->respond( 500, 'Internal Error', true );
		}
	}
	private function archive_repo() {
		$this->exec_and_handle( "zip -r " . ZIP_TO .  "$this->repo_name.zip $this->repo_path/" );
		$this->respond( 200, "Archive created successfully", true );
	}

}

ignore_user_abort(true);
new Deployer( get_input(), LOGFILE );
