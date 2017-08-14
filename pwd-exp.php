<?php

/*
Plugin Name: WordPress Password Expiry
Description: This plugin expires a user's access to the site after every specified number of days (initially set to 30 days).You can select the type of user/users for whom password should expire.After expiration, users needs to reset their password by clicking on 'Reset' link on the login page.  
Plugin URI:http://wordpress.org/extend/plugins/wordpresspasswordexpiry/
Author: WisdmLabs
Author URI:http://wisdmlabs.com
Version: 1.4
License: GPLv2
Network: true
Text Domain: pran

"WordPress Password Expiry"
Copyright (C) 2012  WisdmLabs

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require(ABSPATH .'wp-includes/pluggable.php');

add_action('admin_init', 'reg_setting' );

//Admin menu for this plugin
add_action('admin_menu', 'register_password_expire_period');

function reg_setting()
{
    register_setting('exp_options','exp_user');
}

function register_password_expire_period() {
    $iconurl=plugins_url()."/wordpresspasswordexpiry/img/icon.png";
    add_menu_page('wordpresspasswordexpiry', '<div style="font-size:12px">Password Expiry</div>', 'administrator', 'wordpresspasswordexpiry','password_expire_period',$iconurl);
	
	}
 
function password_expire_period() 
{
    global $current_user;
    get_currentuserinfo();
    $note=0;
?>

<!-- Set password expiry period and custom error message -->
<div class="wrap" style="margin-left:15px;width:800px">
<h3 style="font-size:1.7em;color:#993333">WordPress Password Expiry</h3>
<br>
<div style="float:left;width:45%;">
<form name="exp_form" method="POST" style="padding:15px 15px 0 15px;background-color:#99CCFF;border-radius:3px" action="options.php">

<?php settings_fields('exp_options'); 

  $site_users =   array('exp_day'=>30,
			'exp_msg'=>'Your Password Has Expired.',
			'first_admin'=>0
		       );

$exp_data = get_option( 'exp_user', $site_users);?>

<b style="color:#003333">Set password expiry period here :</b> &nbsp;<input type="text" name="exp_user[exp_day]" size="2" value="<?php echo $exp_data['exp_day'];?>" /> days
<br><br><br>
<b style="color:#003333">Set custom error message here :</b> 
<br><br>
<input type="text" name="exp_user[exp_msg]" size="33" value="<?php echo $exp_data['exp_msg'];?>" />
<br><br>

<ul>
<?php if($current_user->ID==1) 
{
?>
<li><input type="checkbox" name="exp_user[first_admin]" value="1" <?php checked( '1', $exp_data['first_admin'] ); $note=1; ?> />&nbsp;Main Administrator (Only you can manage this)</li>
<?php 
}

    if ( !isset( $wp_roles ) )
    $wp_roles = new WP_Roles();

    $all_roles = $wp_roles->roles;
	foreach ( $all_roles as $role => $details ) 
	{
		$name = translate_user_role($details['name'] );
		?>
		<li><input type="checkbox" name="exp_user[<?php echo esc_attr($role);?>]" value="1" <?php checked( '1', $exp_data[esc_attr($role)] ); ?> />
		&nbsp;<?php echo $name;?></li>
	<?php
	}
	
	?>
</ul>

<p class="submit">
<input type="submit" class="button-primary" value="Save Changes" name="submit"/>
</p>
</form>

             
<?php           

//Validation of expiry field


if ( is_numeric( $exp_data['exp_day'] ) == TRUE )
{

if( $exp_data['exp_day'] > 0 )
{
    echo "<p style='color:#003366'>Your site user's password will expire after <b>".$exp_data['exp_day']."</b> day(s) of their login.</p>";
}

else
{
    echo "<p style='color:#990000'>Please enter valid number of days.</p>";
}

}

else
{
    echo "<p style='color:#990000'>Please enter a number.</p>";
}

?>

</div>

<?php if($note==1) {?>
<div style="float:right;width:50%;background-color:#FAFAD2;padding:10px;border-radius:3px;">
<b>Please Note:</b><br><br>
Here,<br>
<ul style="list-style:square;padding-left:15px;">
<li>'Main Administrator' option is for -<br>
(<i>very first admin at the time of site install & configuration</i>).</li>
<li>'Administrators' option is for -<br>
(<i>admin users created after 'Main Administrator'</i>).</li>
<li>Other Administrative users can not see first option i.e. 'Main Administrator' and hence can not manage it, even if one of them has installed this plugin.</li>
<li>You are able to see this NOTE and can manage this option because <strong>you are detected as 'Main Administrator'</strong>.</li>
</ul>
</div>
<?php }?>
</div>

<?php 
}

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * If we're in the WordPress Admin, hook into profile update
 *
 * @since 1.0
 */

function pran_admin() 
{
	if ( is_admin() )
	{	add_action( 'user_profile_update_errors', 'pran_profile_update', 11, 3 );
               
	}
}

add_action( 'init', 'pran_admin' );


/**
 * When user successfully changes their password, set the timestamp in user meta.
 *
 * @param WP_Error $errors Errors, by ref.
 * @param bool $update Unknown, by ref.
 * @param object $user User object, by ref.
 * @since 1.0
 */
function pran_profile_update( $errors, $update, $user ) {
	/**
	 * Bail out if there are errors attached to the change password profile field,
	 * or if the password is not being changed.
	 */
	if ( $errors->get_error_data( 'pass' ) || empty( $_POST['pass1'] ) || empty( $_POST['pass2'] ) )
		return;

	// Store timestamp
	update_user_meta( $user->ID, 'pran', time());
}

/**
 * When user successfully resets their password, re-set the timestamp.
 *
 * @param object $user User object
 * @since 1.0
 */
function pran_password_reset( $user ) {
	update_user_meta( $user->ID, 'pran', time());
}
add_action( 'password_reset', 'pran_password_reset' );

/**
 * When the user logs in, check that their meta timestamp is still in the allowed range.
 * If it isn't, prevent log in.
 *
 * @param WP_Error|WP_User $user WP_User object if login was successful, otherwise WP_Error object.
 * @param string $username
 * @param string $password
 * @return WP_Error|WP_User WP_User object if login was successful and had not expired, otherwise WP_Error object.
 * @since 1.0
 */

function pran_handle_log_in( $user, $username, $password ) {

	// Check if an error has already been set
	if ( is_wp_error( $user ) )
		return $user;


	// Check we're dealing with a WP_User object
	if ( ! is_a( $user, 'WP_User' ) )
		return $user;

	// This is a log in which would normally be succesful
	$user_id = $user->data->ID;


	// If no timestamp set, it's probably the user's first log in attempt since this plugin was installed, so set the timestamp to now
	$timestamp = (int)get_user_meta( $user_id, 'pran', true ); 

	if ( empty( $timestamp ) ) {
		$timestamp = time();		
	}
              update_user_meta( $user_id, 'pran', $timestamp );

        
        $exp_data=get_option( 'exp_user');
         
        $diff=time()-$timestamp;
         
        $mess_exp=$exp_data['exp_msg'];

        $day=$exp_data['exp_day'];
         
	$login_expiry =defined( 'PRAN_EXPIRY' ) ? PRAN_EXPIRY : 60 * 60 * 24 * $day  ; 

	$cur_user = new WP_User( $user_id );

	if ( !empty( $cur_user->roles ) && is_array( $cur_user->roles ) ) 
	{
	    foreach ( $cur_user->roles as $role )
	    $cur_user_role = $role;
	}

// first admin
if($exp_data['first_admin']==1)
{
	if($cur_user_role == 'administrator' && $user_id ==1)
	{
	    $user = wdm_pwd_expire($diff, $login_expiry, $mess_exp, $user);
	    return $user;
	}
}

// other users
if ( !isset( $wp_roles ) )
$wp_roles = new WP_Roles();

$all_roles = $wp_roles->roles;

 
foreach( $all_roles as $role => $details ) 
{
    if($exp_data[esc_attr($role)]==1)
    { 
	if($cur_user_role == esc_attr($role) && $user_id !=1)
	{
	    $user = wdm_pwd_expire($diff, $login_expiry, $mess_exp, $user);
	    return $user;
	}
    }
}

return $user;
}            
add_filter( 'authenticate', 'pran_handle_log_in', 30, 3 );

// Error message to user after expiry  

function wdm_pwd_expire($diff, $login_expiry, $mess_exp, $user)
{ 
    if ( $diff >= $login_expiry )
	{  
		$get_err = new WP_Error( 'authentication_failed', sprintf( __( '<strong>ERROR</strong>: <br>%s<br>
	Please <a href="%s">Reset</a> your password.', 'pran' ), $mess_exp,site_url( 'wp-login.php?action=lostpassword', 'login' ) ) );
	return $get_err;
	}
    else
    return $user;
}
?>