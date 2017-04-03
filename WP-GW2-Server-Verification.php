<?php
/*
Plugin Name: WP GW2 Server Verification
Plugin URI: https://github.com/IronGhost63/WP-GW2-Server-Verification
Author: Jirayu Yingthawornsuk
Author URI: https://jirayu.in.th
Description: Automatically verify GW2 server and grant/remove capabilities
Version: 1.0
Text Domain: gveri
*/

require_once("server-name.php");

add_action('init', 'sb_start_session', 1);
add_action('init', 'sb_check_verification', 10);
add_action('wp_logout', 'sb_end_session');
add_action('wp_login', 'sb_end_session');
add_action('wp_ajax_gw2verification', 'sb_server_verification');

add_shortcode('gw2form', 'sb_shortcode_verification_form');
add_shortcode('gw2form-test', 'sb_shortcode_cap_test');

function sb_start_session() {
	if(!session_id()) {
		session_start();
	}
}

function sb_end_session() {
	session_destroy ();
}

// This function need to be refactored with sb_server_verification()
function sb_check_verification(){
	// Reduce query by save state in session
	if($_SESSION['nsp_session_verification'] !== true){

	}
}

function sb_server_verification(){
	global $gw2server;

	$apikey = $_GET['apikey'];
	$home = get_option("home_server", "1018");
	$user_id = get_current_user_id();

	if(!$apikey){
		wp_send_json_error( __('API Key is missing'), 200 );
	}

	$url = "https://api.guildwars2.com/v2/account?access_token=" . $apikey;
	$json = json_decode(file_get_contents($url), true);

	/*
	$reponse = array(
		'url' => $url,
		'response' => $json
	);
	wp_send_json($reponse);
	*/

	if(@json['text'] == 'invalid key'){
		wp_send_json_error( __('API Key is invalid'), 200 );
	}

	// Update current user server
	$user_server = get_user_meta($user_id, 'current_server', true);
	$user_apikey = get_user_meta($user_id, 'gw2_apikey', true);
	update_user_meta( $user_id, 'current_server', $json['world'], $user_server );
	update_user_meta( $user_id, 'gw2_apikey', $apikey, $user_apikey );

	if($json['world'] == $home){
		$user = new WP_User($user_id);
		$user->add_cap("citizen_nsp");
		$response = array(
			'message' => __('You are now verified'),
			'server' => $gw2server[$json['world']]
		);

		wp_send_json_success( $response );
	}else{
		$user = new WP_User($user_id);
		$user->remove_cap("citizen_nsp");
		$response = array(
			'message' => __("You are not ".$gw2server[$home]." citizen."),
			'server' => $gw2server[$json['world']]
		);
		wp_send_json_error( $response, 200 );
	}
}

function sb_shortcode_verification_form(){
	global $gw2server;

	$jslocalize = array(
		'ajax' => admin_url( 'admin-ajax.php' ) . "?action=gw2verification"
	);

	wp_enqueue_script( 'gw2server', plugin_dir_url( __FILE__ ) . 'gw2server.js', array('jquery'), true );
	wp_localize_script( 'gw2server', 'gw2', $jslocalize );

	$return = '<div class="gw2-api-box">';
	if(is_user_logged_in()){
		$user_id = get_current_user_id();
		$home = get_option("home_server", "1018");
		$paired = get_option("paired_server", array());
		$user_server = get_user_meta($user_id, 'current_server', true);
		$user_apikey = get_user_meta($user_id, 'gw2_apikey', true);

		if(!$user_server){
			$user_server = 0;
			$current_server_status = "server-undefined";
		}elseif($user_server == $home){
			$current_server_status = "server-home";
		}elseif(in_array($user_server, $paired)){
			$current_server_status = "server-paired";
		}else{
			$current_server_status = "server-outsider";
		}

		$return .= '<p class="gw2-info">';
		$return .= '<span class="server">Current Server:</span> ';
		$return .= '<span class="current-server '.$current_server_status.'">'.$gw2server[$user_server].'</span>';
		$return .= '</p>';

		$return .= '<p class="gw2-verification">';
		$return .= '<span class="message"></span>';
		$return .= '<input type="text" name="gw2-api-key" class="gw2-api-key" id="gw2-api-key" placeholder="Please enter the API Key" value="'.$user_apikey.'">';
		$return .= '<button type="button" class="gw2-verify" id="gw2-verify">Verify</button>';
		$return .= '</p>';
	}else{
		$return .= '<span class="warning">'. __("Please login before verify server.") .'</span>';
	}
	$return .= "</div>";

	return $return;
}

function sb_shortcode_cap_test(){
	global $gw2server;
	$home = get_option("home_server", "1018");
	if(current_user_can( 'citizen_nsp' )){
		$message = "You are verified as ".$gw2server[$home]." citizen";
	}else{
		$message = "Yop are not ".$gw2server[$home]." citizen!";
	}

	$return = '<div id="gw2-capability-test">';
	$return .= '<p class="message">Capability test</p>';
	$return .= '<h2>'.$message.'</h2>';
	$return .= '</div>';

	return $return;
}
?>