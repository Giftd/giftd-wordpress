<?php
/*
Plugin Name: Giftd
Description: Giftd - Russia's first B2B-service that combines electronic products for sale gift cards and motivational marketing tools based on them.
Version: 1.0.0
Author: Giftd
Author URI: http://giftd.ru
*/

/*  Copyright 2015  Joomline  (email: sale@joomline.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'GIFTD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GIFTD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once (GIFTD_PLUGIN_DIR . 'includes/giftd.class.php');
require_once (GIFTD_PLUGIN_DIR . 'lib/GiftdApiClient.php');

//Инициализация
add_action( 'init', array( 'Giftd', 'init' ) );
load_plugin_textdomain( 'giftd', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );