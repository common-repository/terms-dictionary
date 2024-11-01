<?php
/*
	Plugin Name: Terms Dictionary
	Description: Plugin to create a small dictionary with automatic grouping by letters.
	Version: 1.5.1
	Author: Somonator
	Author URI: mailto:somonator@gmail.com
	Text Domain: terms-dictionary
	Domain Path: /lang
*/

/*  
	Copyright 2016  Alexsandr  (email: somonator@gmail.com)

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

function __td_pugin_lang_load() { 
	load_plugin_textdomain('terms-dictionary', false, dirname(plugin_basename(__FILE__)) . '/lang/'); 
}
add_action('plugins_loaded', '__td_pugin_lang_load');



function __td_post_type_register() {
	$labels = array(
		'name' =>  __('Dictionary','terms-dictionary'),
		'singular_name' => __('Terms','terms-dictionary'),
		'add_new' => __('Add term','terms-dictionary'),
		'add_new_item' => __('Add new terms','terms-dictionary'),
		'edit_item' => __('Edit term','terms-dictionary'),
		'new_item' => __('New term','terms-dictionary'),
		'all_items' => __('All terms','terms-dictionary'),
		'view_item' => __('View the term online','terms-dictionary'),
		'search_items' => __('Search terms','terms-dictionary'),
		'not_found' =>  __('Terms not found.','terms-dictionary'),
		'not_found_in_trash' => __('The basket does not have the terms.','terms-dictionary'),
		'menu_name' => __('Dictionary','terms-dictionary')	
	);
	$args = array(
		'labels' => $labels,
		'public' => false,
		'show_ui' => true,
		'menu_icon' => 'dashicons-media-spreadsheet',
		'menu_position' => 3,
		'supports' => array( 'title', 'editor', 'thumbnail')
	);

	register_post_type('dict-terms', $args);
	register_taxonomy( 'dict-terms-letter', 'dict-terms', 
		array(
			'hierarchical' => true, 
			'label' => __('All letters','terms-dictionary') 
		) 
	);
	register_taxonomy('dict-terms-tag', 'dict-terms', 
		array(
			'hierarchical' => true, 
			'label' => __('Tags','terms-dictionary') 
		) 
	);
}
add_action('init', '__td_post_type_register');

function __td_post_type_messages($messages) {
	global $post;

	$messages['dict-terms'] = array( 
		0 => '', 
		1 => __('Term updated.', 'terms-dictionary'),
		2 => __('The parameter is updated.', 'terms-dictionary'),
		3 => __('The parameter is removed.', 'terms-dictionary'),
		4 => __('Term is updated', 'terms-dictionary'),
		5 => isset($_GET['revision'])?sprintf(__('Terms  restored from the editorial: %s', 'terms-dictionary'), wp_post_revision_title((int)$_GET['revision'], false)):false,
		6 => __('Term published on the website.', 'terms-dictionary'),
		7 => __('Term saved.','terms-dictionary'),
		8 => __('Term submitted for review.', 'terms-dictionary'),
		9 => sprintf(__('Scheduled for publication: <strong>%1$s</strong>.', 'terms-dictionary'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date))),
		10 => __('Draft updated terms.', 'terms-dictionary'),
	);
	
	return $messages;
}
add_filter('post_updated_messages', '__td_post_type_messages');



function __td_posts_custom_columns($columns) {
	$columns = array(
		'cb' => '<input type="checkbox">',
		'title' => __('Title', 'terms-dictionary'),
		'letter' => __('Letter', 'terms-dictionary')
	);
	
	return $columns;
}
add_filter('manage_edit-dict-terms_columns', '__td_posts_custom_columns');

function __td_posts_custom_columns_manage($column) {
	global $post;
	
	if ($column == 'letter') {
		$term = get_the_terms($post->ID, 'dict-terms-letter');

		echo isset($term[0]->name) ? $term[0]->name : '—';
	}
}
add_action('manage_posts_custom_column', '__td_posts_custom_columns_manage');

function __td_posts_remove_filter_by_date($disable, $post_type) {
	if ($post_type == 'dict-terms') {
		$disable = true;
	}
	
	return $disable;
}
add_filter('disable_months_dropdown', '__td_posts_remove_filter_by_date', 10, 2);

function __td_posts_custom_columns_css() {
	if (get_current_screen()->id === 'edit-dict-terms') {
		echo '<style>.column-letter{width:10%;font-weight:bold!important;text-align:center!important;}</style>';
	}
}
add_action('admin_head', '__td_posts_custom_columns_css');



function __td_remove_post_fields() {
	remove_meta_box('slugdiv' , 'dict-terms' , 'normal');
	remove_meta_box('dict-terms-letterdiv' , 'dict-terms' , 'normal');
}
add_action('admin_menu', '__td_remove_post_fields');



function __td_remove_view_link_taxonomy($actions, $tag) {
	unset($actions['view']);

	return $actions;
}
add_filter('dict-terms-letter_row_actions', '__td_remove_view_link_taxonomy', 10, 2);



add_image_size('dictionary-thumbnail', 150, 150);

function __td_set_image_size_label($sizes) {
	return array_merge($sizes, array(
		'dictionary-thumbnail' => __('Dictionary Thumbnail', 'terms-dictionary')
	));
}
add_filter('image_size_names_choose', '__td_set_image_size_label');



function __td_create_term_by_first_letter($post_ID, $post) {
	if ($post->post_type == 'dict-terms') {
		$one = mb_substr($post->post_title, 0, 1);
		$set = wp_set_object_terms($post_ID, $one, 'dict-terms-letter');

		wp_update_term($set[0], 'dict-terms-letter', array(
			'name' => mb_strtoupper($one),
		));
	}
	
	return;
}
add_action('post_updated', '__td_create_term_by_first_letter', 10, 2);



function __td_load_styles() {
	wp_register_style('td-styles', plugin_dir_url(__FILE__). '/td-styles.css');
}
add_action('wp_enqueue_scripts', '__td_load_styles');

function __td_shortcode_dictionary($atts) {
	$atts = shortcode_atts(array(
		'show_search' => true,
		'terms_per_page' => get_option('posts_per_page')
	), $atts);

	if (!wp_script_is('td-styles', 'enqueued')) {
		wp_enqueue_style('td-styles');
	}
	
	ob_start();
	require_once('frontend.php'); 
	$content = ob_get_clean();
	
	return $content;
}
add_shortcode('terms-dictionary', '__td_shortcode_dictionary');