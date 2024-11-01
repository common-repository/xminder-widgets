<?php
/*
Plugin Name: Xminder Widgets
Plugin URI: http://wiki.xminder.com
Description: Import your Xminder Widgets directly in your Wordpress site.
Version: 0.0.3
Author: Xminder Team
Author URI: http://www.xminder.com/
License: GPLv2 or later
*/

if ( !function_exists( 'add_action' ) ) {
	echo "No direct access allowed";
	exit;
}

/**
 * Settings of the xminder widgets plugin 
 */


/**
 * Prints the form in the settings
 * The form has 2 fields: username, password and a submit button
 */
function settings_print(){
	$Username = get_option('Username');
	$Password = get_option('Password');
	?>
	<form method="POST">
		<label for="Username">Username
		<input type="text" name="Username" value="<?php echo $Username?>"
		</label><br />
		<label for="Password">Password
		<input type="password" name="Password" value="<?php echo $Password?>"
		</label><br />
		<input type="submit" name="submit" value="Submit" />
	</form>
	<?php 
};

/**
 * Save or update the settings
 * when submiting settigns form this function is called
 */
function settings_update(){
	if ($_REQUEST['Username'] && $_REQUEST['Password']){
		update_option('Username',$_REQUEST['Username']);
		update_option('Password',$_REQUEST['Password']);
		?>
		<div id="message" class="updated fade"><p>Options saved.</p></div>
		<?php 
	} else {
		?>
			<div id="message" class="error fade"><p>Failed to save.</p></div>
		<?php
	}
};

/**
 * Function that handles the display of the settings form
 */
function settings_page(){
	?>
	<div class="wrap"><h2>Xminder Widgets Settings</h2></div>
	<?php 
	if ($_REQUEST['submit']){
		settings_update();
	};
	settings_print();
};

/**
 * Function that registers the plugin in settings 
 */
function settings(){
	add_options_page(
		'Xminder Widgets',
		'Xminder Widgets',
		'manage_options',
		__FILE__,
		'settings_page'
	);
}

/**
 * Call the function to add the settings for the plugin 
 */
add_action('admin_menu','settings');






/**
 * Widget class
 */

class Xminder_Widgets extends WP_Widget{
 
	public function Xminder_Widgets() {
		$widget_ops = array( 'classname' => 'xminder', 
		'description' => 'Displays Xminder Widget');
		$control_ops = array('width' => 250, 'height' => 250, 'id_base' => 'xminder');
		parent::__construct( 'xminder', 'Xminder Widgets',
		$widget_ops, $control_ops );
	}
 	
	/**
	 * Appearance->Widgets form
	 */
	public function form($instance) {
		//get username and pass saved in settings
		$Username = get_option('Username');
		$Password = get_option('Password');
		echo '<div class="initial_view">';
		//if no settings found tell the user to set them in settings 
		if (empty($Username) || empty($Password)){
		echo "<h4>Please set you account details in the Settigns section</h4>";
		};
		
		//if the username and pass are set try to authentificate on xminder dekstop
		$url="desktop.xminder.com/";
		$postdata = "Username=". $Username ."&Password=". $Password;
		$ch = @curl_init();
		$agent = $_SERVER["HTTP_USER_AGENT"];
		@curl_setopt($ch, CURLOPT_USERAGENT, $agent);
		@curl_setopt ($ch, CURLOPT_URL, $url . "login.html");
		@curl_setopt ($ch, CURLOPT_POST, 1);
		@curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
		
		@curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		
		@curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
		@curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
		@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		
		$result = @curl_exec ($ch);
		$result = json_decode($result,true);		
		
		//if the credentials are not valid tell the user to change them in the settings
		if ($result['success']==false) {
		echo "<h4>Invalid account details</h4>"; 
		// got good credentials, now get the widget list
		} else {
			@curl_setopt ($ch, CURLOPT_URL, $url . "/applications/datacollector/wordpresswidgetlist.html");
			$result = @curl_exec ($ch);
			$result = json_decode($result,true);
			if (empty($result)){
				echo "<h4>No widgets found</h4>";
			} else {
				$html="";
		    	$html.= "Please select a widget:<br />";
		    	$html.= "<select onchange=\"jQuery('input.html_code').val('<script type=\'text/javascript\' src=\'http://widget.xminder.com/widget_show.js?id='+this.value+'\'></script>'); jQuery('input.unique').val(this.value)  \">";
		    	$html.= "<option>None</option>";
		    	
	    		foreach ($result as $table){
		    		if (count($table['widgets'])>0){
		    			$html.= "<optgroup label='".$table['text']."'>";
		    			foreach($table['widgets'] as $widget){
		    				$html .= "<option ".($instance['unique']==$widget['Unique']?"selected='selected'":"")."value='".$widget['Unique']."'>".$widget['Name']."</option>";
		    			}
		    			$html.= "</optgroup>";
		    		}
		    	}
		    	$html.= "</select><br /><br />";
		    	echo $html;
			}
		};
		?>
		<input type="hidden" class="html_code" name="<?php echo $this->get_field_name('html_code');?>" value="<?php echo $instance['html_code'];?>"/>
		<input type="hidden" class="unique" name="<?php echo $this->get_field_name('unique');?>" value="<?php echo $instance['unique'];?>"/>
		</div>
		<?php
		@curl_close($ch);
	}
 
	public function update($new_instance, $old_instance) {
		return $new_instance;
	}
 
	public function widget($args, $instance) {
		echo '<div style="width:333px;margin-left:-60px;">';
		if ( $instance['html_code'] ){
			echo $instance['html_code'];
		}
		echo '</div>';
	}
}



/**
 * Register the widget
 */
add_action('widgets_init', create_function('','return register_widget("Xminder_Widgets");'));

?>
