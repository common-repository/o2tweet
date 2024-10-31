<?php
require_once(ABSPATH . WPINC . '/class-snoopy.php');

define('TABLE_NAME', $wpdb->prefix.'o2tweets');
class o2t_core {
  public static function update_last_tweets() {
    $username = get_option('o2t_username');
    $password = get_option('o2t_password');
    $tags = get_option('o2t_tags');
    $feed = 'http://twitter.com/statuses/user_timeline.json';
    
    
    $snoop = new Snoopy;
    $snoop->agent = 'O2Tweet http://www.o2sources.com';
    $snoop->user = $username;
    $snoop->pass = $password;
    $snoop->fetch($feed);
    $tweets = json_decode($snoop->results);
    
    foreach($tweets as $tweet) {
      if (get_option('o2t_ignore_replies') == 0 | !self::is_reply($tweet->text)) {
        
        if (empty($tags)) {
          if (!self::tweet_exists($tweet->id, $tweet->text, $tweet->created_at)) {
            self::add_tweet($tweet->id, $tweet->text, self::format_date($tweet->created_at));
          }
        } else {
          $regex = '/' . preg_quote(str_replace('/', '\/', str_replace(',', '|', $tags))) . '/';
          if (preg_match($regex, $tweet->text) && !self::tweet_exists($tweet->id, $tweet->text, $tweet->created_at)) {
            self::add_tweet($tweet->id, $tweet->text, self::format_date($tweet->created_at));
          }
        }
      }
    }
    update_option('o2t_last_update', date('U'));
  }

  public static function format_date($date) {
    return date('Y-m-d G:h:s', strtotime($date));
  }
  
  public static function is_reply($text) {
    return preg_match('/^@/', $text);
  }
  
  public static function add_tweet($id, $content, $date) {
    global $wpdb;    
    $wpdb->query("INSERT INTO `" . TABLE_NAME . "`
      (content, created_at)
      VALUES (
        '".$wpdb->escape($content)."',
        '".$wpdb->escape($date)."'
    )");
  }
  public static function tweet_exists($id, $content, $date) {
    global $wpdb;
    
    $date = strtotime($date);
    $count = count($wpdb->get_results('SELECT * FROM ' . TABLE_NAME . '
      WHERE content = \'' . $wpdb->escape($content) . '\'
      AND YEAR(created_at) = \''.date('Y', $date).'\'
      AND MONTH(created_at) = \''.date('m', $date).'\'
      AND DAY(created_at) = \''.date('d', $date).'\'
      AND HOUR(created_at) = \''.date('G', $date).'\''));
    return $count > 0;
  }
}
