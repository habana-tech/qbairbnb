<?php

namespace App\PostTypes;

use Rareloop\Lumberjack\Post;

class Asset extends Post
{
    /**
     * Return the key used to register the post type with WordPress
     * First parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     *
     * @return string
     */
    public static function getPostType()
    {
        return 'asset';
    }

    /**
    * Return the config to use to register the post type with WordPress
     * Second parameter of the `register_post_type` function:
     * https://codex.wordpress.org/Function_Reference/register_post_type
     *
     * @return array|null
     */
    protected static function getPostTypeConfig()
    {
        return [
            'labels' => [
                'name' => __('Asset Pages'),
                'singular_name' => __('Asset Page'),
                'add_new_item' => __('Add New Asset Page'),
            ],
            'public' => true,
            'show_in_rest' => true,
            'supports' => array('editor'),
            'rewrite'     => array( 'slug' => 'assets' )
        ];
    }
}
