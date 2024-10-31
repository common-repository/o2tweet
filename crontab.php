<?php
# We load the wordpress core (to get the $wbdp object)
require(dirname(__FILE__) . '/../../../wp-load.php');
# And the o2tweet main functions
require(dirname(__FILE__) . '/functions.php');

# And we update the tweets
o2t_core::update_last_tweets();