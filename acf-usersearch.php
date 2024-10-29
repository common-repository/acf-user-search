<?php
/*
	Plugin Name: Advanced Custom Fields: User search
	Plugin URI: http://kubiq.sk
	Description: User serach for advanced custom fields
	Version: 1.2
	Author: Jakub Novák
	Author URI: http://kubiq.sk
*/

add_action('acf/register_fields', 'acf_register_user_search');
function acf_register_user_search(){
	include_once('user-search.php');
}