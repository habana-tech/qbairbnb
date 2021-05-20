<?php

use App\Http\Lumberjack;

// Create the Application Container
$app = require_once('bootstrap/app.php');

// Bootstrap Lumberjack from the Container
$lumberjack = $app->make(Lumberjack::class);
$lumberjack->bootstrap();

// Import our routes file
require_once('routes.php');

// Set global params in the Timber context
add_filter('timber_context', [$lumberjack, 'addToContext']);

# Finding handle for your plugins
//function display_script_handles() {
//    global $wp_scripts;
//    if(current_user_can('manage_options')){ # Only load when user is admin
//        foreach( $wp_scripts->queue as $handle ) :
//            $obj = $wp_scripts->registered [$handle];
//            echo $filename = $obj->src;
//            echo ' : <b>Handle for this script is:</b> <span style="color:green"> '.$handle.'</span><br/><br/>';
//        endforeach;
//    }
//}
//add_action( 'wp_print_scripts', 'display_script_handles' );

function conditionally_load_plugin_js_css(){
    wp_dequeue_script('solidres_bootstrap');
    wp_dequeue_style('solidres_bootstrap');


//    if(! is_page( array(4,12) ) ){	# Load CSS and JS only on Pages with ID 4 and 12
//        wp_dequeue_script('contact-form-7'); # Restrict scripts.
//        wp_dequeue_style('contact-form-7'); # Restrict css.
//    }
}
add_action( 'wp_enqueue_scripts', 'conditionally_load_plugin_js_css' );
