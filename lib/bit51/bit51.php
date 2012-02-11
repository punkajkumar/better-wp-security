<?php

/*
 * Thanks to Yoast (http://www.yoast.com), W3 Total Cache and Ozh Richard (http://planetozh.com) for a lot of the inspiration and some code snipets used in the rewrite of this plugin. Many of the ideas for this class as well as some of the functions of it's functions and the associated CSS are borrowed from the work of these great developers (I don't think anything is verbatim but some is close as I didn't feel it necessary to reinvent the wheel, in particular with regards to admin page layout).
 */

if (!class_exists('Bit51')) {

	class Bit51 {
	
		var $feed = 'http://feeds.feedburner.com/Bit51';
	
		function __construct() {
		}
		
		/**
		 * Register admin javascripts (only for plugin admin page)
		 */
		function config_page_scripts() {
			if (isset($_GET['page']) && strpos($_GET['page'], $this->hook) !== false) {
				wp_enqueue_script('postbox');
				wp_enqueue_script('dashboard');
				wp_enqueue_script('thickbox');
				wp_enqueue_script('media-upload');
			}
		}
		
		/**
		 * Register admin css styles (only for plugin admin page)
		 */
		function config_page_styles() {
			if (isset($_GET['page']) && strpos($_GET['page'], $this->hook) !== false) {
				wp_enqueue_style('dashboard');
				wp_enqueue_style('thickbox');
				wp_enqueue_style('global');
				wp_enqueue_style('wp-admin');
				wp_enqueue_style('bit51-css', plugin_dir_url( __FILE__ ). 'bit51.css');
			}
		}
		
		/**
		 * Register all settings
		 */
		function register_settings(){
			foreach ($this->settings as $group => $settings) {
				foreach($settings as $setting => $option) {
					if (isset($option['callback'])) {
						register_setting($group, $setting, array($this,$option['callback']));
					} else {
						register_setting($group, $setting);
					}
			    }
			}
		}
		
		/**
		 * Add action link to plugin page
		 */
		function add_action_link($links, $file) {
			static $this_plugin;
			
			if (empty($this_plugin)) {
				$this_plugin = $this->pluginbase;
			}
			
			if ($file == $this_plugin) {
				$settings_link = '<a href="' . $this->plugin_options_url() . '">' . __('Settings', $this->hook) . '</a>';
				array_unshift($links, $settings_link);
			}
			
			return $links;
		}
		
		/**
		 * Set general options page
		 */
		function plugin_options_url() {
			return admin_url('options-general.php?page=' . $this->hook);
		}
		
		/**
		 * Set all default settings
		 */
		function default_settings() {
			foreach ($this->settings as $settings) {
				foreach ($settings as $setting => $defaults) {
					$options = get_option($setting);
					
					//set missing options
					foreach ($defaults as $option => $value) {
						if ($option != 'callback' && !isset($options[$option])) {
							$options[$option] = $value;
						}
					}
					
					//remove obsolete options
					foreach ($options as $option => $value) {
						if (!isset($defaults[$option]) && $option != 'version') {
							unset($options[$option]); 
						}
					}
					
					update_option($setting,$options); //save new options
				}
			}
		}
		
		/**
		 * Setup postbox
		 */
		function postbox($id, $title, $content) {
		?>
			<div id="<?php echo $id; ?>" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class="hndle"><span><?php echo $title; ?></span></h3>
				<div class="inside">
					<?php 
						if (!strstr($content, ' ') && method_exists($this, $content)) {
							$this->$content();
						} else {
							echo $content; 
						}
					?>
				</div>
			</div>
		<?php
		}
		
		/**
		 * Setup main admin page box
		 */
		function admin_page($title, $boxes, $icon = '') {
			?>
				<div class="wrap">
					<?php if ($icon == '') { ?>
						<a href="http://bit51.com/"><div id="bit51-icon" style="background: url(<?php echo plugin_dir_url(__FILE__); ?>images/bit51.png) no-repeat;" class="icon32"><br /></div></a>
					<?php } else { ?>
						<a href="http://bit51.com/"><div id="bit51-icon" style="background: url(<?php echo $icon; ?>) no-repeat;" class="icon32"><br /></div></a>
					<?php } ?>
					<h2><?php _e($title) ?></h2>
					<div class="postbox-container" style="width:65%;">
						<div class="metabox-holder">	
							<div class="meta-box-sortables">
								<?php 
									foreach ($boxes as $content) { 
										$this->postbox('adminform', $content[0], $content[1]);
									} 
								?>
							</div>
						</div>
					</div>
					<div class="postbox-container side" style="width:20%;">
						<div class="metabox-holder">	
							<div class="meta-box-sortables">
								<?php
									$this->donate();
									$this->support();
									$this->news(); 
									$this->social();
								?>
							</div>
						</div>
					</div>
				</div>
			<?php
		}
		
		/**
		 * Display tech support information
		 */
		function support() {
			$content = __('If you need help getting this plugin or have found a bug please visit our <a href="' . $this->supportpage . '" target="_blank">support forums</a>.', $this->hook);
			$this->postbox('bit51support', __('Need Help?', $this->hook), $content);
		}
		
		/**
		 * Display Bit51's latest posts
		 */
		function news() {
			include_once(ABSPATH . WPINC . '/feed.php');
			$feed = fetch_feed($this->feed);
			$feeditems = $feed->get_items(0, $feed->get_item_quantity(5));
			$content = '<ul>';
			if (!$feeditems) {
			    $content .= '<li class="bit51">'.__( 'No news items, feed might be broken...', $this->hook ).'</li>';
			} else {
				foreach ($feeditems as $item ) {
					$url = preg_replace( '/#.*/', '', esc_url( $item->get_permalink(), $protocolls=null, 'display' ) );
					$content .= '<li class="bit51"><a class="rsswidget" href="' . $url . '" target="_blank">'. esc_html($item->get_title()) .'</a></li>';
				}
			}						
			$content .= '</ul>';
			$this->postbox('bit51posts', __('The Latest from Bit51', $this->hook), $content);
		}
		
		/**
		 * Display donate box
		 */
		function donate() {
			$content = __('Have you found this plugin useful? Please help support it\'s continued development with a donation of $20, $50, or $100!',$this->hook);
			$content .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="' . $this->paypalcode . '"><input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!"><img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
			$content .= '<p>' . __('Short on funds?',$this->hook) . '</p>';
			$content .= '<ul>';
			$content .= '<li><a href="' . $this->wppage . '" target="_blank">' . __('Rate', $this->hook) . ' ' . $this->pluginname . __(" 5★'s on WordPress.org", $this->hook) . '</a></li>';
			$content .= '<li>' . __('Talk about it on your site and link back to the ', $this->hook) . '<a href="' . $this->homepage . '" target="_blank">' . __('plugin page.', $this->hook) . '</a></li>';
			$content .= '<li><a href="http://twitter.com/home?status=' . urlencode('I use ' . $this->pluginname . ' for WordPress by @bit51 and you should too - ' . $this->homepage) . '" target="_blank">' . __('Tweet about it. ', $this->hook) . '</a></li>';
			$content .= '</ul>';
			$this->postbox('donate', __('Support This Plugin', $this->hook), $content);
		}
		
		/**
		 * Display social links
		 */
		function social() {
			$content = '<ul>';
			$content .= '<li class="facebook"><a href="https://www.facebook.com/bit51" target="_blank">'.__( 'Like Bit51 on Facebook', $this->hook).'</a></li>';
			$content .= '<li class="twitter"><a href="http://twitter.com/Bit51" target="_blank">'.__( 'Follow Bit51 on Twitter', $this->hook).'</a></li>';
			$content .= '<li class="google"><a href="https://plus.google.com/104513012839087985497" target="_blank">'.__( 'Find Bit51 on Google+', $this->hook).'</a></li>';
			$content .= '<li class="subscribe"><a href="http://bit51.com/subscribe" target="_blank">'.__( 'Subscribe with RSS or Email', $this->hook).'</a></li>';
			$content .= '</ul>';
			$this->postbox('bit51social', __('Bit51 on the Web', $this->hook ), $content);
		}
		
		/**
		 * Display (and hide) donation reminder
		 */
		function ask() {
			global $blog_id;
			
			if(is_multisite() && (!$blog_id == 1 || !current_user_can('manage_network_options'))) {
				return;
			}
			$options = get_option($this->plugindata);
			
			//this is called at a strange point in WP so we need to bring in some data
			global $plugname;
			global $plughook;
			global $plugopts;
			$plugname = $this->pluginname;
			$plughook = $this->hook;
			$plugopts = $this->plugin_options_url();
			
			//display the notifcation if they haven't turned it off and they've been using the plugin at least 30 days
			if (!isset($options['no-nag']) && $options['activatestamp'] < (time() - 2952000)) {
				function bit51_plugin_donate_notice(){
					global $plugname;
					global $plughook;
					global $plugopts;
				    echo '<div class="updated">
				       <p>' . __('It looks like you\'ve been enjoying', $plughook) . ' ' . $plugname . ' ' . __('for at least 30 days. Would you consider a small donation to help support continued development of the plugin?', $plughook) . '</p> <p><input type="button" class="button " value="' . __('Support This Plugin', $plughook) . '" onclick="document.location.href=\'?bit51_lets_donate=yes&_wpnonce=' .  wp_create_nonce  ('bit51-nag') . '\';">  <input type="button" class="button " value="' . __('Rate it 5★\'s', $plughook) . '" onclick="document.location.href=\'?bit51_lets_rate=yes&_wpnonce=' .  wp_create_nonce  ('bit51-nag') . '\';">  <input type="button" class="button " value="' . __('Tell Your Followers', $plughook) . '" onclick="document.location.href=\'?bit51_lets_tweet=yes&_wpnonce=' .  wp_create_nonce  ('bit51-nag') . '\';">  <input type="button" class="button " value="' . __('Don\'t Bug Me Again', $plughook) . '" onclick="document.location.href=\'?bit51_donate_nag=off&_wpnonce=' .  wp_create_nonce  ('bit51-nag') . '\';"></p>
				    </div>';
				}
				add_action('admin_notices', 'bit51_plugin_donate_notice'); //register notification
			}
			
			//if they've clicked a button hide the notice
			if ((isset($_GET['bit51_donate_nag']) || isset($_GET['bit51_lets_rate']) || isset($_GET['bit51_lets_tweet']) || isset($_GET['bit51_lets_donate'])) && wp_verify_nonce($_REQUEST['_wpnonce'], 'bit51-nag')) {
				$options = get_option($this->plugindata);
				$options['no-nag'] = 1;
				update_option($this->plugindata,$options);
				remove_action('admin_notices', 'bit51_plugin_donate_notice');
				
				//take the user to paypal if they've clicked donate
				if (isset($_GET['bit51_lets_donate'])) {
					wp_redirect('https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=' . $this->paypalcode, '302');
				}
				
				//Go to the WordPress page to let them rate it.
				if (isset($_GET['bit51_lets_rate'])) {
					wp_redirect($this->wppage, '302');
				}
				
				//Compose a Tweet
				if (isset($_GET['bit51_lets_tweet'])) {
					wp_redirect('http://twitter.com/home?status=' . urlencode('I use ' . $this->pluginname . ' for WordPress by @bit51 and you should too - ' . $this->homepage) , '302');
				}
			}
		}
	}
}