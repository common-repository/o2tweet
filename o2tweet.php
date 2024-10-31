<?php
/*
Plugin Name: O2Tweet
Plugin URI: http://www.o2sources.com
Description: A plugin allowing to display tweets on the blog with options to filter by tags
Version: 0.0.4
Author: Damien MATHIEU
Author URI: http://www.o2sources.com/
*/
require_once(dirname(__FILE__).'/functions.php');

$wpdb->o2t = $wpdb->prefix.'o2tweets';

define('O2T_CRONTAB_URL', get_bloginfo('wpurl') . '/wp-content/plugins/o2tweet/crontab.php');

function o2tweet_install() {
  global $wpdb;
  
  $wpdb->o2t = $wpdb->prefix.'o2tweets';  
  add_option('o2t_username', '', '', 0);
  add_option('o2t_password', '', '', 0);
  add_option('o2t_user_id', '', '', 0);
  add_option('o2t_tags', '', '', 0);
  add_option('o2t_tweets_count', 10, '', 0);
  add_option('o2t_ignore_replies', '0', '', 0);
  add_option('o2t_update', '15', '', 0);
  add_option('o2t_last_update', '0', '', 0);
  add_option('o2t_ulid', '', '', 0);
  add_option('o2t_ulclasses', '', '', 0);
  
  
  $result = $wpdb->query("
    CREATE TABLE `" . $wpdb->o2t . "` (
      `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
      `content` VARCHAR( 255 ) NOT NULL ,
      `created_at` DATETIME NOT NULL
    )");
}
register_activation_hook(__FILE__, 'o2tweet_install');

function o2tweet_init() {
  add_action('admin_menu', 'o2tweet_config_page');
}
add_action('init', 'o2tweet_init');

function o2tweet_config_page() {
  if ( function_exists('add_submenu_page') )
    add_submenu_page('options-general.php', __('O2Tweet Configuration'), __('O2Tweet Configuration'), 'manage_options', 'o2tweet', 'o2tweet_conf');

}

function o2tweet_update_conf() {
  if (!empty($_POST['o2t_action'])) {
    switch($_POST['o2t_action']) {
      case 'o2t_update_settings': {
        update_option('o2t_username', $_POST['o2t_username']);
        update_option('o2t_password', $_POST['o2t_password']);
        update_option('o2t_tags', $_POST['o2t_tags']);
        update_option('o2t_tweets_count', $_POST['o2t_tweets_count']);
        update_option('o2t_ignore_replies', $_POST['o2t_ignore_replies']);
        update_option('o2t_update', $_POST['o2t_update']);
        update_option('o2t_ulid', $_POST['o2t_ulid']);
        update_option('o2t_ulclasses', $_POST['o2t_ulclasses']);
        
        wp_redirect(get_bloginfo('wpurl').'/wp-admin/options-general.php?page=o2tweet&updated=true');
        break;
      }
      
      case 'o2t_login_test': {
        $test = o2t_login_test(stripslashes($_POST['o2t_username']), stripslashes($_POST['o2t_password']));
        die($test);
        break;
      }
    }
  }
}
add_action('init', 'o2tweet_update_conf', 10);


function o2t_login_test($username, $password) {
	$snoop = new Snoopy;
	$snoop->agent = 'O2Tweet http://www.o2sources.com';
	$snoop->user = $username;
	$snoop->pass = $password;
	$snoop->fetch('http://twitter.com/statuses/user_timeline.json');
	if (strpos($snoop->response_code, '200')) {
		return __("Login succeeded, you're good to go.", 'o2tweet');
	} else {
		$results = json_decode($snoop->results);
		return sprintf(__('Sorry, login failed. Error message from Twitter: %s %s %s', 'o2tweet'), $results->error, $username, $password);
	}
}

function o2tweet_conf() {
  ?>
    <script type="text/javascript">
      function o2TestLogin() {
        var username = jQuery('#o2t_username').val();
	var password = jQuery('#o2t_password').val();
        jQuery('#o2t_login_test_result').html('<?php _e('Testing...', 'o2tweet'); ?>');
	new jQuery.post(
          "<?php bloginfo('wpurl'); ?>/wp-admin/options-general.php",
          {
            o2t_action: 'o2t_login_test',
            o2t_username: username,
            o2t_password: password,
          },
          function o2TestLoginResult(datas) {
            jQuery('#o2t_login_test_result').html(datas);
          }
        );
      }
    </script>
    
    <style type="text/css">
      #o2tweet_conf {
	margin: 0;
	padding: 5px 0;
      }
      
      #o2tweet_conf fieldset {
	border: 0;
      }
      #o2tweet_conf fieldset textarea {
	width: 95%;
      }
      #o2tweet_conf fieldset #o2t_submit {
	float: right;
	margin-right: 50px;
      }
      #o2tweet_conf fieldset {
	color: #666;
      }
      #o2tweet_conf .options {
	overflow: hidden;
	border: none;
      }
      #o2tweet_conf .option {
	overflow: hidden;
	border-bottom: dashed 1px #ccc;
	padding-bottom: 9px;
	padding-top: 9px;
      }
      #o2tweet_conf .option label {
	display: block;
	float: left;
	width: 200px;
	margin-right: 24px;
	text-align: right;
      }
      #o2tweet_conf .option span {
	display: block;
	float: left;
	margin-left: 230px;
	margin-top: 6px;
	clear: left;
      }
      #o2tweet_conf select, #o2tweet_conf input {
	float: left;
	display: block;
	margin-right: 6px;
      }
      #o2tweet_conf p.submit {
	overflow: hidden;
      }
      #o2tweet_conf .option span {
	color: #666;
	display: block;
      }
      #o2tweet_conf fieldset.options .option span.aktt_login_result_wait {
	background: #ffc;
      }
      #o2tweet_conf fieldset.options .option span.aktt_login_result {
	background: #CFEBF7;
	color: #000;
      }
      #o2tweet_conf .timepicker, #ak_twittertools .daypicker {
	display: none;
      }
      #o2tweet_conf .active .timepicker, #o2tweet_conf .active .daypicker {
	display: block
      }
    </style>
    
    
    <div class="wrap" id="aktt_options_page">
      <h2><?php echo __('O2Tweet Options', 'o2tweet'); ?></h2>
      
      <form id="o2tweet_conf" action="<?php echo get_bloginfo('wpurl'); ?>/wp-admin/options-general.php" method="post">
        <input type="hidden" name="o2t_action" value="o2t_update_settings" />
        <fieldset class="options">
          <div class="option">
            <label for="o2t_username"><?php echo __('Twitter Username', 'o2tweet').'/'.__('Password', 'o2tweet'); ?></label>
            <input type="text" size="25" name="o2t_username" id="o2t_username" value="<?php echo get_option('o2t_username'); ?>" autocomplete="off" />
            <input type="password" size="25" name="o2t_password" id="o2t_password" value="<?php echo get_option('o2t_password'); ?>" autocomplete="off" />
            <input type="button" name="o2t_login_test" id="o2t_login_test" value="<?php echo __('Test Login Info', 'o2tweet'); ?>" onclick="o2TestLogin(); return false;" />
            <span id="o2t_login_test_result"></span>
          </div>
          
          <div class="option">
            <label for="o2t_tags"><?php echo __('Filter Tags', 'o2tweet'); ?></label>
            <input type="text" size="25" name="o2t_tags" id="o2t_tags" value="<?php echo get_option('o2t_tags'); ?>" />
            <span>
              <?php echo __('Every word, splitted by a comma that need to be to be included in the tweets for them to be shown.', 'o2tweet'); ?><br />
              <?php echo __('Note : it is a OR, not a AND between every word. If any of them is present, the tweet will be shown in the widget.', 'o2tweet'); ?><br />
            </span>
            
          </div>
          
          <div class="option">
            <label for="o2t_tweets_count"><?php echo __('Number of Tweets to show', 'o2tweet'); ?></label>
            <input type="text" size="25" name="o2t_tweets_count" id="o2t_tags" value="<?php echo get_option('o2t_tweets_count'); ?>" />
            <span><?php echo __('How many tweets do you want to show ?', 'o2tweet'); ?></span>
          </div>
          
          <div class="option">
            <label for="o2t_ignore_replies"><?php echo __('Don\'t show replies', 'o2tweet'); ?></label>
            <input type="checkbox" name="o2t_ignore_replies" id="o2t_ignore_replies" value="1" <?php if (get_option('o2t_ignore_replies') == '1') echo 'checked="checked"'; ?> />
            <span><?php echo __('Do you want to show the tweets starting with an @ ?', 'o2tweet'); ?></span>
          </div>
          
          <div class="option">
            <label for="o2t_ignore_replies"><?php echo __('Update Frequency', 'o2tweet'); ?></label>
            <input type="text" size="10" name="o2t_update" id="o2t_update" value="<?php echo get_option('o2t_update'); ?>" /> <?php echo __('minutes', 'o2tweet'); ?>
            <span>
              <?php echo __('Interval, in minutes to update the last tweets.', 'o2tweet'); ?><br />
              <?php echo __('If you set it to 0, the tweets will never be updated while loading the main page.', 'o2tweet'); ?><br />
              <?php echo __(sprintf('You can, then, load the url <strong>%s</strong> with a crontab to update the tweets.', O2T_CRONTAB_URL), 'o2tweet'); ?>
            </span>
          </div>
          
          <div class="option">
            <label for="o2t_ulid"><?php echo __('CSS id for the list', 'o2tweet'); ?></label>
            <input type="text" size="25" name="o2t_ulid" id="o2t_ulid" value="<?php echo get_option('o2t_ulid'); ?>" />
          </div>
          
          <div class="option">
            <label for="o2t_ulclasses"><?php echo __('CSS classe(s) for the list', 'o2tweet'); ?></label>
            <input type="text" size="25" name="o2t_ulclasses" id="o2t_ulclasses" value="<?php echo get_option('o2t_ulclasses'); ?>" />
          </div>
          
          
        </fieldset>        
        <p class="submit">
          <input type="submit" id="o2t_submit" value="<?php echo __('Update O2Tweet Options', 'o2tweet'); ?>" />
        </p>
      </form>
    </div>
  <?php
}


class o2tweet_widget {
  public static function display() {
    global $wpdb;
    
    $update = get_option('o2t_update');
    
    if ($update > 0) {
      $last_update = get_option('o2t_last_update');
      
      //if (date('U') - $last_update >= $update * 60) {
        o2t_core::update_last_tweets();
      //}
    }
    
    $tweets = $wpdb->get_results('SELECT * FROM ' . $wpdb->o2t . ' ORDER BY created_at DESC LIMIT ' . get_option('o2t_tweets_count'));
    
    echo '<ul id="' . get_option('o2t_ulid') . '" class="' . get_option('o2t_ulclasses') . '">';
    foreach($tweets as $tweet) {
      echo '<li>' . $tweet->content . '</li>';
    }
    echo '</ul>';
  }
  
  public static function register() {
    register_sidebar_widget('O2Tweet Widget', array('o2tweet_widget', 'display'));
  }
}
add_action('init', array('o2tweet_widget', 'register'));
