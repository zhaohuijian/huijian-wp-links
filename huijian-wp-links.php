<?php
/*
Plugin Name: Huijian WP Links
Description: Technology website sharing
Version: 1.6.3
Author: Huijian
*/

include_once(ABSPATH . 'wp-admin/includes/plugin.php');

$upload_dir = wp_upload_dir();
$wplf_upload = $upload_dir['basedir'] . '/huijian-wp-links/';
if (! file_exists($wplf_upload))
	wp_mkdir_p($wplf_upload);

if (!defined('WPLP_UPLOAD_DIR')) {
	define('WPLP_UPLOAD_DIR', $wplf_upload);
}

if (!defined('WPLP_UPLOAD_URL')) {
	define('WPLP_UPLOAD_URL', $upload_dir['baseurl'] . '/' . 'huijian-wp-links/');
}

/** Require dependencies */
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-includes/media-template.php');

add_filter('cron_schedules', 'wp_links_page_free_add_intervals');

// add_option('wplp_screenshot_size', 'large', '', 'yes');
add_option('wplp_screenshot_refresh', 'weekly', '', 'yes');


add_action('wp_links_page_free_event', 'wp_links_page_free_event_hook');

register_activation_hook(__FILE__, 'wp_links_page_free_setup_schedule');
register_deactivation_hook(__FILE__, 'wp_links_page_free_deactivation');



/** Admin Init **/
if (is_admin()) {
	add_action('admin_init', 'wp_links_page_free_settings');
	add_action('add_meta_boxes_wplp_link', 'wplf_links_metaboxes');
	add_action('admin_menu', 'wplf_menu');
	add_action('admin_enqueue_scripts', 'wplf_admin_enqueue_scripts');
}



function wp_links_page_free_setup_schedule()
{
	$screenshot_refresh = esc_attr(get_option('wplp_screenshot_refresh'));
	wp_clear_scheduled_hook('wp_links_page_event');
	wp_clear_scheduled_hook('wp_links_page_free_event');
	wp_schedule_event(time(), $screenshot_refresh, 'wp_links_page_free_event');
}

function wplf_enqueue_shortcode_scripts($posts)
{
	wp_register_script('wplf-display-js', plugins_url('huijian-wp-links/js/wp-links-display.js', 'wp-links-page'), array('jquery'), false, true);
	wp_localize_script('wplf-display-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ajax-nonce')));
	wp_register_style('wplf-display-style',  plugins_url('huijian-wp-links/css/wp-links-display.css', 'wp-links-page'), array(), false, 'all');
}

add_action('wp_enqueue_scripts', 'wplf_enqueue_shortcode_scripts');

function wplf_admin_enqueue_scripts($hook)
{
	global $typenow;
	$plugin_data = get_plugin_data(__FILE__, false);
	if (($hook == 'post-new.php' || $hook == 'edit.php' || $hook == 'post.php')  && $typenow == 'wplp_link') {
		wp_enqueue_script('jquery-ui-progressbar');
		wp_enqueue_script('wplf-js', plugins_url('huijian-wp-links/js/wp-links-page.js', 'wp-links-page'), array('jquery', 'jquery-ui-progressbar'), $plugin_data['Version'], true);
		wp_enqueue_script('wplf-qe-js', plugins_url('huijian-wp-links/js/wp-links-page-quick-edit.js', 'wp-links-page'), array('jquery', 'inline-edit-post'), $plugin_data['Version'], true);
		wp_localize_script('wplf-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ajax-nonce')));
		$translation_array = array('pluginUrl' => plugins_url('wp-links-page'));
		//after wp_enqueue_script
		wp_localize_script('wplf-js', 'wplf', $translation_array);
		wp_enqueue_style('wplf-admin-ui-css', plugins_url('huijian-wp-links/css/jquery-ui.css', 'wp-links-page'), false, $plugin_data['Version'], false);
		wp_enqueue_style('wplf-style',  plugins_url('huijian-wp-links/css/wp-links-page.css', 'wp-links-page'), null, $plugin_data['Version'], false);
	} else if ($hook == 'wplp_link_page_wplf_subpage-menu') {
		wp_enqueue_script('jquery-ui-progressbar');
		wp_enqueue_script('wplf-js', plugins_url('huijian-wp-links/js/wp-links-page.js', 'wp-links-page'), array('jquery', 'jquery-ui-progressbar'), $plugin_data['Version'], true);
		wp_localize_script('wplf-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ajax-nonce')));
		wp_enqueue_media();
		wp_enqueue_style('wplf-admin-ui-css', plugins_url('huijian-wp-links/css/jquery-ui.css', 'wp-links-page'), false, $plugin_data['Version'], false);
		wp_enqueue_style('wplf-style',  plugins_url('huijian-wp-links/css/wp-links-page.css', 'wp-links-page'), null, $plugin_data['Version'], false);
	} else if ($hook == 'wplp_link_page_wplf_subpage3-menu') {
		wp_enqueue_script('wplf-shortcode-js', plugins_url('huijian-wp-links/js/wp-links-shortcode.js', 'wp-links-page'), array('jquery', 'jquery-ui-tabs'), $plugin_data['Version'], true);
		wp_enqueue_style('wplf-style',  plugins_url('huijian-wp-links/css/wp-links-page.css', 'wp-links-page'), null, $plugin_data['Version'], false);
		wp_enqueue_style('ti-style',  plugins_url('huijian-wp-links/css/themify-icons.css', 'wp-links-page'), null, $plugin_data['Version'], false);
	} else if ($hook == 'wplp_link_page_wplf_subpage2-menu') {
		wp_enqueue_style('wplf-style',  plugins_url('huijian-wp-links/css/wp-links-page.css', 'wp-links-page'), null, $plugin_data['Version'], false);
	}
}

function wp_links_page_free_add_intervals($schedules)
{
	$schedules['threedays'] = array(
		'interval' => 259200,
		'display' => __('Every Three Days')
	);
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => __('Weekly')
	);
	$schedules['biweekly'] = array(
		'interval' => 1209600,
		'display' => __('Every Two Weeks')
	);
	$schedules['monthly'] = array(
		'interval' => 2635200,
		'display' => __('Monthly')
	);
	return $schedules;
}

function wp_links_page_free_deactivation()
{
	wp_clear_scheduled_hook('wp_links_page_free_event');
}

function wp_links_page_free_event_hook()
{
	global $wpdb;
	$custom_post_type = 'wplp_link';
	$results = $wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'", $custom_post_type), ARRAY_A);
	$total = '';

	foreach ($results as $index => $post) {
		$arg = array($post['ID'], false);
		wp_schedule_single_event(time(), 'wp_ajax_wplf_ajax_update_screenshots', $arg);
	}
}


function wplf_ajax_update_screenshots($id = '', $override = false)
{
	if (! wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
		die('Nonce verification failed.');
	}
	if (!current_user_can('manage_options')) {
		die('You do not have sufficient permission permission to do this.');
	}
	if (isset($_REQUEST['id'])) {
		$id = $_REQUEST['id'];
		$id = sanitize_text_field($id);
	} elseif (empty($id)) {
		die(json_encode(array('message' => 'ERROR', 'code' => 1336)));
	}

	$post = get_post($id);
	$mk = wplf_filter_metadata(get_post_meta($id));

	if (!empty($mk['wplp_screenshot_url'])) {
		$url = $mk['wplp_screenshot_url'];
	} else {
		$url = $post->post_title;
	}

	if (!empty($mk['wplp_display'])) {
		$display = $mk['wplp_display'];
	} else {
		$display = $post->post_title;
	}

	if (isset($url)) {
		if (!(substr($url, 0, 4) == 'http')) {
			$url = 'https://' . $url;
		}
	} else {
		die();
	}


	if ($mk['wplp_no_update'] != 'no' && $mk['wplp_media_image'] != 'true') {


		$wplp_featured_image = "https://s0.wp.com/mshots/v1/" . $url . "?w=1280";


		// Add Featured Image to Post
		$image_url        = $wplp_featured_image; // Define the image URL here
		$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $display);
		wplf_large_screenshot($image_url, $image_name, $id);
	}
}

add_action('wp_ajax_wplf_ajax_update_screenshots', 'wplf_ajax_update_screenshots');


/**
 * 获取网站titie和description
 */
function wplf_get_url_meta()
{
	// var_dump($_POST);
	if (! wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
		wp_send_json_error(array('message' => 'Nonce verification failed.'), 403);
	}

	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'You do not have sufficient permission to do this.'), 403);
	}

	// 获取传递的URL
	$url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

	// 确保URL不为空
	if (empty($url)) {
		wp_send_json_error(array('message' => 'URL is empty'), 400);
	}
	if ($_POST['post_action'] === 'new') {
		global $wpdb;
		$title = $url;
		$post_exists = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'wplp_link' AND post_status = 'publish' LIMIT 1", $title));
		if ($post_exists) {
			wp_send_json_error(array('message' => 'URL already exists -> ' . $post_exists), 400);
		}
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);  // 设置超时时间
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 忽略SSL证书问题
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟浏览器请求

	$data = curl_exec($ch);

	// 检查cURL错误
	if (curl_errno($ch)) {
		wp_send_json_error(array('message' => 'cURL Error: ' . curl_error($ch)), 500);
	}

	curl_close($ch);

	if (empty($data)) {
		wp_send_json_error(array('message' => 'Failed to retrieve content from the URL.'), 500);
	}

	// Load HTML to DOM object 
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);  // 避免HTML解析警告
	if (!@$dom->loadHTML($data)) {
		wp_send_json_error(array('message' => 'Failed to parse HTML content.'), 500);
	}
	libxml_clear_errors();

	// 获取Title
	$nodes = $dom->getElementsByTagName('title');
	$title = $nodes->length > 0 ? trim($nodes->item(0)->nodeValue) : $url;

	// 解析Meta数据
	$metas = $dom->getElementsByTagName('meta');
	$description = '';
	$keywords = '';

	for ($i = 0; $i < $metas->length; $i++) {
		$meta = $metas->item($i);
		$nameAttr = strtolower($meta->getAttribute('name'));

		if ($nameAttr == 'description') {
			$description = trim($meta->getAttribute('content'));
		}

		if ($nameAttr == 'keywords') {
			$keywords = trim($meta->getAttribute('content'));
		}
	}

	if (empty($title) && empty($description)) {
		wp_send_json_error(array('message' => 'No meta information found.'), 404);
	}

	// 发送成功的JSON响应
	wp_send_json_success(array(
		'title' => $title ?: '',
		'description' => $description ?: ''
	));
}

add_action('wp_ajax_wplf_get_url_meta', 'wplf_get_url_meta');

function wplf_menu()
{

	add_submenu_page(
		'edit.php?post_type=wplp_link',
		'WP Links Page | Shortcode',
		'Shortcode',
		'manage_options',
		'wplf_subpage3-menu',
		'wplf_shortcode_page'
	);
	add_submenu_page(
		'edit.php?post_type=wplp_link',
		'WP Links Page | Settings',
		'Settings',
		'manage_options',
		'wplf_subpage-menu',
		'wplf_subpage_options'
	);
}


function my_custom_post_wplf_link()
{

	$labels = array(
		'name'               => _x('Links', 'post type general name'),
		'singular_name'      => _x('Link', 'post type singular name'),
		'add_new'            => _x('Add New', 'Link'),
		'add_new_item'       => __('Add New Link'),
		'edit_item'          => __('Edit Link'),
		'new_item'           => __('New Link'),
		'all_items'          => __('All Links'),
		'view_item'          => __('View Link'),
		'search_items'       => __('Search Links'),
		'not_found'          => __('No Links found'),
		'not_found_in_trash' => __('No Links found in the Trash'),
		'parent_item_colon'  => '',
		'menu_name'          => 'WP Links Page'
	);
	$args = array(
		'labels'        => $labels,
		'description'   => 'Holds our links and link specific data',
		'public'        => false,
		'menu_position' => 5,
		'supports'      => array('editor', 'thumbnail'),
		'has_archive'   => true,
		'show_in_menu'	=> true,
		'show_ui'		=> true,
		'menu_icon'     => 'dashicons-admin-links',
		'capabilities' => array(
			'edit_post'          => 'manage_options',
			'read_post'          => 'manage_options',
			'delete_post'        => 'manage_options',
			'edit_posts'         => 'manage_options',
			'edit_others_posts'  => 'manage_options',
			'delete_posts'       => 'manage_options',
			'publish_posts'      => 'manage_options',
			'read_private_posts' => 'manage_options'
		),
		'taxonomies' => array('category', 'post_tag')


	);
	register_post_type('wplp_link', $args);
}
add_action('init', 'my_custom_post_wplf_link');



/**
 * Query Filter for Custom Post Types
 */
function wplf_query_filter($query)
{
	if (! is_admin() && $query->is_main_query() && is_post_type_archive('wplp_link')) {
		$query->set('orderby', 'ID');
		$query->set('order', 'DESC');
		return;
	}
}

add_action('pre_get_posts', 'wplf_query_filter');

/**
 * Change edit.php page  define( 'WP_DEBUG_DISPLAY', false );
 */





add_action('load-edit.php', function () {
	add_filter('views_edit-wplp_link', 'wplf_link_edit');
});

function wplf_link_edit($views)
{
	global $wpdb;
	$custom_post_type = 'wplp_link';
	$results = $wpdb->get_results($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = %s and post_status = 'publish'", $custom_post_type), ARRAY_A);
	$total = '';

	foreach ($results as $index => $post) {
		if ($total == '') {
			$total = $post['ID'];
		} else {
			$total .= ',' . $post['ID'];
		}
	}


	echo '

  <button id="update-screenshots" class="button button-primary button-large" style="float:left; margin-right: 20px;" data-total="' . $total . '">Update Screenshots</button>
	<div id="progressbar">
              <div class="progress-label"></div>
        </div><div class="clearfix" style="clear:both"></div>

 ';
	return $views;
}

add_action('admin_head-edit.php', 'wplf_quick_edit_remove');

function wplf_quick_edit_remove()
{

	global $current_screen;
	if ('edit-wplp_link' != $current_screen->id)
		return;
?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('span.title:contains("Title")').each(function(i) {
				$(this).html('Link Url');
				$(this).parent().parent().append('<label><span class="title">Link Display</span><span class="input-text-wrap"><input type="text" name="wplp_display" value="" /></span></label><label><span class="title">Description</span><textarea name="wplf_description"></textarea></label><br class="clear">');
			});
			$('span:contains("Slug")').each(function(i) {
				$(this).parent().remove();
			});
			$('span:contains("Password")').each(function(i) {
				$(this).parent().parent().remove();
			});
			$('span:contains("Date")').each(function(i) {
				$(this).parent().remove();
			});
			$('.inline-edit-date').each(function(i) {
				$(this).remove();
			});
			$('#wplf-custom.inline-edit-col-left').each(function(i) {
				$(this).css('font-weight:bold;');
			});
		});
	</script>
<?php
}
/**
 * Edit Custom Post Type List
 */

add_filter('manage_wplp_link_posts_columns', 'set_custom_edit_wplf_link_columns');
add_action('manage_wplp_link_posts_custom_column', 'wplf_custom_columns', 10, 2);

function set_custom_edit_wplf_link_columns($columns)
{
	unset($columns['author']);
	unset($columns['date']);
	$columns['screenshot'] = 'Screenshot';
	$columns['description'] = 'Description';
	$columns['title'] = 'Link Display';
	$columns['id'] = 'ID';

	$a = $columns;
	$b = array('cb', 'screenshot', 'title', 'description', 'id'); // rule indicating new key order
	$c = array();
	foreach ($b as $index) {
		$c[$index] = $a[$index];
	}
	$columns = $c;

	return $columns;
}

function wplf_custom_columns($column, $post_id)
{
	switch ($column) {
		case 'id':
			echo $post_id;
			break;
		case 'screenshot':
			$display = get_post_meta($post_id, 'wplp_display', true);
			$image = get_the_post_thumbnail($post_id, 'thumbnail');
			echo $image . '<p id="wplp_display_' . $post_id . '" class="hidden">' . $display . '</p>';
			break;


		case 'description':
			$content_post = get_post($post_id);
			$content = $content_post->post_content;
			$content = apply_filters('the_content', $content);
			$content = str_replace(']]>', ']]&gt;', $content);
			echo '<div id="wplf_description_' . $post_id . '">' . $content . '</div>';
			break;
	}
}


function wplf_link_display_title($title, $id = null)
{
	if (is_admin()) {
		if (get_post_type($id) == 'wplp_link') {
			$display = get_post_meta($id, 'wplp_display', true);
			if ($display == '') {
				$display = $title;
			}
			return $display;
		} else {
			return $title;
		}
	} else {
		return $title;
	}
}
add_filter('the_title', 'wplf_link_display_title', 10, 2);


/**
 *   Adds a metabox
 */
function wplf_links_metaboxes()
{

	add_meta_box(
		'wplp_screenshot',
		'Screenshot',
		'wplf_post_thumbnail_meta_box',
		'wplp_link',
		'advanced',
		'default'
	);

	add_meta_box(
		'wplp_display',
		'Link Display',
		'wplf_display_func',
		'wplp_link',
		'advanced',
		'default'
	);
}

/* Move Screenshot Metabox to before title */

add_action('edit_form_after_title', function () {
	global $post, $wp_meta_boxes, $typenow;
	if ($typenow == 'wplp_link') {
		do_meta_boxes(get_current_screen(), 'advanced', $post);
		unset($wp_meta_boxes[get_post_type($post)]['advanced']);
	}
});


function wplf_display_func()
{

	global $post;
	// Nonce field to validate form request came from current site
	wp_nonce_field(basename(__FILE__), 'wplf_fields');

	// Get the display data if it's already been entered
	$display = get_post_meta($post->ID, 'wplp_display', true);
	if ($display == "Auto Draft") {
		$display = '';
	}

	echo '<label for="display">Link Display</label>
    <p class="description">This field defaults to the link domain.</p>
    <input id="wplp_display" name="wplp_display" maxlength="255" type="text" value="' . $display . '">';
}

function wplf_post_thumbnail_meta_box($post)
{
	$mk = wplf_filter_metadata(get_post_meta($post->ID));
	$thumb_id = get_post_thumbnail_id($post->ID);
	$thumb = wp_get_attachment_url($thumb_id);
	if ($thumb != '') {
		$display = '';
	} else $display = 'display:none;';

	$loading = plugin_dir_url(__FILE__) . 'images/loading.gif';
	// $screenshot_size = get_option('wplp_screenshot_size');
	if (isset($mk['wplp_media_image'])) {
		$media = $mk['wplp_media_image'];
	} else {
		$media = '';
	}
	if (isset($mk['wplp_screenshot_url'])) {
		$screenshot_url = $mk['wplp_screenshot_url'];
	} else {
		$screenshot_url = '';
	}
	if (isset($mk['wplp_no_update'])) {
		$no_update = $mk['wplp_no_update'];
	} else {
		$no_update = '';
	}
	if (empty($media)) $media = 'false';

	echo '
		<div id="titlediv">
<div id="titlewrap">
	<input type="text" name="post_title" size="30" value="' . $post->post_title . '" placeholder="Link Url" class="ss" id="title" spellcheck="true" autocomplete="off">
</div>
	</div>
	<div class="wplp_error notice error" >
		<p></p>
    </div>
	<p class="description">Enter the Link Url in this field. The screenshot will generate automatically as soon as you are finished. If the screenshot is not generating properly try using the full url including the "http://" or "https://".</p>
		<img class="wplp_featured" src="' . $thumb . '" style="' . $display . ' width:300px; margin: 10px 0;" />
		<div class="wplp_loading" style="width: 300px; display:none;text-align: center; border: 1px solid #DDD; margin: 10px 0;">
		<img class="wplp_loading" src="' . $loading . '" style="display:none; width: 100px;" />
		<p class="wplp_loading" style="display: none;">Generating Screenshot...</p>
		</div>
		<br>
		<label for"wplp_screenshot_url"><b>Screenshot URL: &nbsp;&nbsp;<b></label><input id="wplp_screenshot_url" type="text" name="wplp_screenshot_url" value="' . $screenshot_url . '" style="width: 80%;"/>
		<p class="description">This field is useful for affiliate links. Your affiliate link can go in the "Link URL" field above, and the direct URL can go in the "Screenshot URL" field to retrieve the expected screenshot. Click "Generate New Screenshot" after entering the url below to retrieve the new screenshot.</p>
		<input id="wplp_media_image" type="hidden" name="wplp_media_image" value="' . $media . '" />
		<input id="wplp_featured_image" type="hidden" name="wplp_featured_image" value="' . $thumb_id . '" />
		<br>
		<p class="hide-if-no-js">
		<a class="set-featured-thumbnail setfeatured button" href="#" title="Choose Image">Choose Image</a>
		<a class="set-featured-screenshot generate button button-primary" href="#" title="Generate New Screenshot">Generate New Screenshot</a>
		<br>
		<br><label for="wplp_no_update"><input id="wplp_no_update" type="checkbox" name="wplp_no_update" value="no"';
	if ($no_update == 'no') {
		echo 'checked="checked"';
	} else echo 'data="not checked"';
	echo ' />Don\'t update this screenshot. Keep the current image.</label>';
}

function wplf_update_from_previous()
{
	if (! wp_verify_nonce($_POST['nonce'], 'ajax-nonce')) {
		die('Nonce verification failed.');
	}
	if (isset($_REQUEST['id'])) {
		$id = $_REQUEST['id'];
		$id = sanitize_text_field($id);
	} else die(json_encode(array('message' => 'ERROR', 'code' => 'no id')));
	global $wpdb;
	$table = $wpdb->prefix . 'wp_links_page_free_table';
	$links = $wpdb->get_results("SELECT * FROM $table WHERE id = $id ORDER BY weight");
	foreach ($links as $link) {

		if (!empty($link->display)) {
			$display = $link->display;
		} else {
			$display = $link->url;
		}

		$metadata = json_decode($metadata);
		if ($metadata->title == 'WPLPNotAllowed') {
			$metadata->title = '';
		}
		if ($link->no_update == 1) {
			$no_update = 'no';
		} else {
			$no_update = 'false';
		}
		$new_link = array(
			'post_title'    => sanitize_text_field($link->url),
			'post_content'  => wp_kses_post($link->description),
			'post_status'   => 'publish',
			'post_type'	  => 'wplp_link',
			'meta_input' => array(
				'wplp_display' => sanitize_text_field($display),
				'wplp_no_update' => $no_update,
				'wplp_screenshot_url' => $link->ssurl,
				'wplp_media_image' => 'false',
				'wplp_media_fav' => 'false',
			),
		);
		$new = wp_insert_post($new_link);

		if (!empty($link->ssurl)) {
			$url = $link->ssurl;
		} else {
			$url = $link->url;
		}

		if (isset($url)) {
			if (!(substr($url, 0, 4) == 'http')) {
				$url = 'https://' . $url;
			}
		} else {
			die();
		}



		$wplp_featured_image = "https://s0.wp.com/mshots/v1/" . $url . "?w=1280";


		// Add Featured Image to Post
		$image_url        = $wplp_featured_image; // Define the image URL here
		$image_name       = preg_replace("/[^a-zA-Z0-9]+/", "", $display);
		wplf_large_screenshot($image_url, $image_name, $new);
	}
}

add_action('wp_ajax_wplf_update_from_previous', 'wplf_update_from_previous');

function wplf_quick_link()
{
	$post_id = $_POST['post_ID'];
	// 处理 'wplp_no_update' 逻辑
	$no_update = isset($_POST['wplp_no_update']) ? 'no' : 'false';
	update_post_meta($post_id, 'wplp_no_update', $no_update);

	$result = array(
		'post_id' => $post_id,
		'status'  => 'no_action'
	);

	// 处理特色图片的上传和设置逻辑
	if (!empty($_POST['wplp_featured_image']) && !is_numeric($_POST['wplp_featured_image'])) {
		// 若未禁用自动更新或已有图片存在
		if ($no_update === 'no' || $_POST['wplp_media_image'] === 'true') {
			update_post_meta($post_id, 'wplp_no_update', 'no');
		} else {
			$image_url  = esc_url_raw($_POST['wplp_featured_image']);
			$image_name = sanitize_title($_POST['wplp_display']);

			$upload_result = wplf_large_screenshot_quick($image_url, $image_name, $post_id);

			if (is_wp_error($upload_result)) {
				wp_send_json_error(array(
					'message' => $upload_result->get_error_message(),
					'status'  => 500
				), 500);
			}

			// 合并返回数据
			$result = array_merge($result, $upload_result);
			$result['status'] = 'image_uploaded';
		}
	} elseif (!empty($_POST['wplp_featured_image']) && is_numeric($_POST['wplp_featured_image'])) {
		// 处理现有的特色图片
		$post_thumbnail_id = get_post_thumbnail_id($post_id);
		if (isset($mk['wplp_media_image']) && $mk['wplp_media_image'] !== $_POST['wplp_media_image']) {
			if ($_POST['wplp_media_image'] === 'true' && !empty($post_thumbnail_id) && $mk['wplp_media_image'] === 'false') {
				wp_delete_attachment($post_thumbnail_id, true);
			}
		}
		set_post_thumbnail($post_id, (int) $_POST['wplp_featured_image']);
		$result['status'] = 'thumbnail_set';
		$result['thumbnail_id'] = (int) $_POST['wplp_featured_image'];
	} else {
		// 如果特色图片为空，则移除
		delete_post_thumbnail($post_id);
		$result['status'] = 'thumbnail_deleted';
	}

	// 更新 'wplp_media_image' 元数据
	if (isset($_POST['wplp_media_image'])) {
		update_post_meta($post_id, 'wplp_media_image', sanitize_text_field($_POST['wplp_media_image']));
		$result['wplp_media_image'] = $_POST['wplp_media_image'];
	}

	// 更新 'wplp_media_fav' 元数据
	if (isset($_POST['wplp_media_fav'])) {
		update_post_meta($post_id, 'wplp_media_fav', sanitize_text_field($_POST['wplp_media_fav']));
		$result['wplp_media_fav'] = $_POST['wplp_media_fav'];
	}

	$result['message'] = 'Operation completed successfully.';

	wp_send_json_success($result);
}
add_action('wp_ajax_wplf_quick_link', "wplf_quick_link");
/**
 * Save the metabox data
 */

add_filter('wp_insert_post_data', 'wplf_filter_post_data', '99', 2);

function wplf_filter_post_data($data, $postarr)
{
	if (isset($postarr['action'])) {
		$action = $postarr['action'];
	} else {
		$action = '';
	}
	// Change post content on quick edit
	if ($postarr['post_type'] == 'wplp_link' && $action == 'inline-save') {
		if (isset($postarr['wplf_description'])) {
			$data['post_content'] = wp_kses_post($postarr['wplf_description']);
			$postarr['post_content'] = wp_kses_post($postarr['wplf_description']);
			$postarr['content'] = wp_kses_post($postarr['wplf_description']);
		}
	}
	return $data;
}

function wplf_display_save($post_id, $post)
{
	// 防止多次触发，如自动保存、修订或用户权限不足
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;
	if (!current_user_can('edit_post', $post_id)) return;

	// 检查文章类型，确保只处理特定类型
	$post_type = get_post_type($post_id);
	if ($post_type !== "wplp_link") return;

	// 过滤特定操作，防止重复调用
	if (isset($_POST['action'])) {
		if ($_POST['action'] == 'wplf_update_from_previous' || $_POST['action'] == 'wplf_import_list') {
			return;
		}
	}

	// 获取现有的文章元数据
	$mk = wplf_filter_metadata(get_post_meta($post_id));

	// 记录POST数据到日志以供调试
	error_log(print_r($_POST, true));

	// 更新元数据 - 处理 'wplp_display'
	if (!empty($_POST['wplp_display'])) {
		update_post_meta($post_id, 'wplp_display', sanitize_text_field($_POST['wplp_display']));
	} elseif (!isset($mk['wplp_display']) && !empty($_POST['post_title'])) {
		update_post_meta($post_id, 'wplp_display', sanitize_text_field($_POST['post_title']));
		$_POST['wplp_display'] = $_POST['post_title'];
	}

	// 更新元数据 - 处理 'wplp_screenshot_url'
	if (!empty($_POST['wplp_screenshot_url'])) {
		update_post_meta($post_id, 'wplp_screenshot_url', sanitize_text_field($_POST['wplp_screenshot_url']));
	}
}

// 将此函数挂载到 save_post 钩子上
add_action('save_post', 'wplf_display_save', 10, 2);


function wplf_delete_func($postid)
{
	$mk = wplf_filter_metadata(get_post_meta($postid));

	global $post_type;
	if ($post_type != 'wplp_link') return;

	$post_thumbnail_id = get_post_thumbnail_id($postid);

	if (!empty($post_thumbnail_id) && $mk['wplp_media_image'] == 'false') {
		wp_delete_attachment($post_thumbnail_id, true);
	}
}
add_action('before_delete_post', 'wplf_delete_func');

add_filter('gettext', 'wplf_text_filter', 20, 3);
/*
 * Change the text in the admin for my custom post type
 *
**/
function wplf_text_filter($translated_text, $untranslated_text, $domain)
{

	global $typenow;

	if (is_admin() && 'wplp_link' == $typenow) {

		//make the changes to the text
		switch ($untranslated_text) {

			case 'Enter title here':
				$translated_text = __('Enter Link Url', 'text_domain');
				break;
		}
	}
	return $translated_text;
}

function wplf_array_push_assoc($array, $key, $value)
{
	$array[$key] = $value;
	return $array;
}

function wplf_filter_metadata($array)
{
	$mk = array();
	foreach ($array as $k => $v) {
		if (is_array($v) && count($v) == 1) {
			$mk = wplf_array_push_assoc($mk, $k, $v[0]);
		} else {
			$mk = wplf_array_push_assoc($mk, $k, $v);
		}
	}
	return $mk;
}

function wplf_update_button($post_ID)
{
	return '<button id="' . $post_ID . '" class="update button button-primary button-large" style="display: none;">Update Screenshot</button>';
}


function wplf_shortcode_page()
{
?>
	<h2>Shortcodes</h2>
	<p class="description">Here you can generate shortcode based on the options you choose.</p>

	<div id="wplf-sb">
		<div id="tabs-1" aria-labelledby="ui-id-1">
			<h3>Display</h3>
			<hr style="border: 1px solid; width: 50%;" align="left">
			<div class="radio-i">
				<p>Which display would you like to use?</p>
				<label><input name="wplf-display" value="grid" type="radio"><i class="ti-size-xxl ti-layout-grid3-alt"></i><br><span>Grid</span></label>
				<label><input name="wplf-display" value="list" type="radio"><i class="ti-size-xxl ti-layout-list-thumb-alt"></i><br><span>List</span></label>
			</div>
		</div>
		<br><br>
		<div id="tabs-2">
			<h3>Display Settings</h3>
			<hr style="border: 1px solid; width: 50%;" align="left">
			<p class="description">Choose a display above to see the settings available for that display.</p>
			<div class="grid radio-no-i">
				<p>How many columns should your grid have?</p>
				<label><input type="radio" name="wplf-columns" value="2"><br><span>2 Columns</span></label>
				<label><input type="radio" name="wplf-columns" value="3"><br><span>3 Columns</span></label>
				<label><input type="radio" name="wplf-columns" value="4"><br><span>4 Columns</span></label>
				<label><input type="radio" name="wplf-columns" value="5"><br><span>5 Columns</span></label>
				<label><input type="radio" name="wplf-columns" value="6"><br><span>6 Columns</span></label>
				<br>
			</div>
		</div>
		<br><br>
		<div id="tabs-3">
			<h3>Link Ordering</h3>
			<hr style="border: 1px solid; width: 50%;" align="left">
			<div class="radio-no-i">
				<p>How do you want to sort your links?</p>
				<label><input type="radio" name="wplf-order" value="title"><br><span>By Title (Link Display)</span></label>
				<label><input type="radio" name="wplf-order" value="ID"><br><span>By Link ID</span></label>
				<label><input type="radio" name="wplf-order" value="date"><br><span>By Date</span></label>
				<label><input type="radio" name="wplf-order" value="rand"><br><span>Random</span></label>
				<br>
				<div>
					<p>Should they be descending or ascending?</p>
					<label><input type="radio" name="wplf-orderby" value="ASC"><br><span>Ascending</span></label>
					<label><input type="radio" name="wplf-orderby" value="DESC"><br><span>Descending</span></label>
					<br>
				</div>
				<br>

			</div>

			<br><br>
			<div id="tabs-4">
				<h3>Image</h3>
				<hr style="border: 1px solid; width: 50%;" align="left">
				<div class="radio-no-i">
					<div>
						<p>What size of image should this display use?</p>
						<?php
						$sizes = get_intermediate_image_sizes();

						foreach ($sizes as $size) {
							echo '<label><input type="radio" name="wplf-image-size" value="' . $size . '"><br><span>' . ucwords($size) . '</span></label>
		';
						}

						?>
						<label><input type="radio" name="wplf-image-size" value="full"><br><span>Original</span></label>
						<br>
					</div>
				</div>
				<div class="checks">
					<p>Should the image be styled?</p>
					<label><input type="checkbox" name="wplf-image-style" value="border"><br><span>Border</span></label>
					<label><input type="checkbox" name="wplf-image-style" value="shadow"><br><span>Shadow</span></label>
				</div>
			</div>
			<br><br>
			<div id="tabs-5">
				<h3>Title</h3>
				<hr style="border: 1px solid; width: 50%;" align="left">
				<div class="radio-no-i">
				</div>
				<div class="checks">
					<p>Should the title be styled?</p>
					<label><input type="checkbox" name="wplf-title-style" value="bold"><br><span>Bold</span></label>
					<label><input type="checkbox" name="wplf-title-style" value="italic"><br><span>Italic</span></label>
					<label><input type="checkbox" name="wplf-title-style" value="underline"><br><span>Underline</span></label>
					<br>
				</div>
				<div class="radio-no-i">
					<p>How should the title be aligned?</p>
					<label><input type="radio" name="wplf-title-align" value="left"><br><span>Left</span></label>
					<label><input type="radio" name="wplf-title-align" value="right"><br><span>Right</span></label>
					<label><input type="radio" name="wplf-title-align" value="center"><br><span>Center</span></label>
					<br>
				</div>
				<p>Do you want to change the font-size?</p>
				<label for="wplf-title-size">Font size: </label><input name="wplf-title-size" type="text"> px<br>
			</div>
			<br><br>
			<div id="tabs-6">
				<h3>Description</h3>
				<hr style="border: 1px solid; width: 50%;" align="left">
				<div class="radio-no-i">
					<p>What description should this display use?</p>
					<label><input type="radio" name="wplf-desc" value="content"><br><span>Link Description</span></label>
					<label><input type="radio" name="wplf-desc" value="none"><br><span>None</span></label><br>
					<br>
				</div>
				<div class="checks">
					<p>Should the description be styled?</p>
					<label><input type="checkbox" name="wplf-desc-style" value="bold"><br><span>Bold</span></label>
					<label><input type="checkbox" name="wplf-desc-style" value="italic"><br><span>Italic</span></label>
					<label><input type="checkbox" name="wplf-desc-style" value="underline"><br><span>Underline</span></label>
					<br>
				</div>
				<div class="radio-no-i">
					<p>How should the description be aligned?</p>
					<label><input type="radio" name="wplf-desc-align" value="left"><br><span>Left</span></label>
					<label><input type="radio" name="wplf-desc-align" value="right"><br><span>Right</span></label>
					<label><input type="radio" name="wplf-desc-align" value="center"><br><span>Center</span></label>
					<br>
				</div>
				<div class="radio-no-i">
					<p>Would you link the description to be Linked?</p>
					<label><input type="radio" name="wplf-description_link" value="no"><br><span>No</span></label>
					<label><input type="radio" name="wplf-description_link" value="yes"><br><span>Yes</span></label>
					<br>
				</div>
				<p>Do you want to change the font-size?</p>
				<label for="wplf-desc-size">Font size: </label><input name="wplf-desc-size" type="text"><br>
			</div>
		</div>
		<div class="wplf-shortcode">
			<p>Your Shortcode</p>
			<textarea id="final-shortcode">[wp_links_page]</textarea>
		</div>
		<div class="clear">
		</div>
	</div>
<?php
}

function wplf_subpage_options()
{

	if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
		$sr = get_option('wplp_screenshot_refresh');
		$timestamp = time();
		if ($sr == 'daily') {
			$rate = '+1 day';
		}
		if ($sr == 'threedays') {
			$rate = '+3 days';
		}
		if ($sr == 'weekly') {
			$rate = '+1 week';
		}
		if ($sr == 'biweekly') {
			$rate = '+2 weeks';
		}
		if ($sr == 'monthly') {
			$rate = '+1 month';
		}
		if ($sr == 'never') {
			wp_clear_scheduled_hook('wp_links_page_free_event');
		} else {
			$exists = wp_get_schedule('wp_links_page_free_event');
			if ($exists == false) {
				wp_schedule_event(time(), $sr, 'wp_links_page_free_event');
			} else {
				$next_event = strtotime($rate, $timestamp);
				$time = wp_next_scheduled('wp_links_page_free_event');
				wp_clear_scheduled_hook('wp_links_page_free_event');
				wp_schedule_event($next_event, $sr, 'wp_links_page_free_event');
			}
		}
	}
	echo '<div class="wrap wplf-settings">
		<h1>WP Links Page Settings</h1>';
	echo '<form method="post" action="options.php">';
	settings_fields('wp-links-page-option-group');
	do_settings_sections('wp-links-page-option-group');

	// $screenshot_size = esc_attr(get_option('wplp_screenshot_size'));
	$screenshot_refresh = esc_attr(get_option('wplp_screenshot_refresh'));
	echo '<table class="form-table"><tbody>';
	echo '<tr>
			<th scope="row" class="screenshot" ><label class="label" for="wplp_screenshot_refresh" >Screenshot Refresh Rate</label></th>
	        <td class="screenshot" >
			<label><input type="radio" name="wplp_screenshot_refresh" value="never" data-current="' . $screenshot_refresh . '" ';
	echo ($screenshot_refresh == 'never') ? 'checked' : '';
	echo ' >Never</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="daily" ';
	echo ($screenshot_refresh == 'daily') ? 'checked' : '';
	echo ' >Daily</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="threedays" ';
	echo ($screenshot_refresh == 'threedays') ? 'checked' : '';
	echo ' >Every Three Days</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="weekly" ';
	echo ($screenshot_refresh == 'weekly') ? 'checked' : '';
	echo ' >Weekly</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="biweekly" ';
	echo ($screenshot_refresh == 'biweekly') ? 'checked' : '';
	echo ' >Every Two Weeks</label><br/>
			<label><input type="radio" name="wplp_screenshot_refresh" value="monthly" ';
	echo ($screenshot_refresh == 'monthly') ? 'checked' : '';
	echo ' >Monthly</label><br/>';
	if ($screenshot_refresh == 'never') {
		$screenshot_refresh = 'Never';
	}
	if ($screenshot_refresh == 'daily') {
		$screenshot_refresh = 'Daily';
	}
	if ($screenshot_refresh == 'threedays') {
		$screenshot_refresh = 'Every Three Days';
	}
	if ($screenshot_refresh == 'weekly') {
		$screenshot_refresh = 'Weekly';
	}
	if ($screenshot_refresh == 'biweekly') {
		$screenshot_refresh = 'Every Two Weeks';
	}
	if ($screenshot_refresh == 'monthly') {
		$screenshot_refresh = 'Monthly';
	}
	echo '<p class="description">How often should WP Links Page get new screenshots for your links?<br/>The refresh rate is currently set to ' . $screenshot_refresh . '.</p></td></tr>';
	echo '</td></tr></tbody></table>';
	submit_button();
}

function wp_links_page_free_settings()
{ // whitelist options
	register_setting('wp-links-page-option-group', 'wplp_screenshot_refresh');
}

function wplf_large_screenshot($image_url, $image_name, $post_id)
{
	// Load necessary WordPress files
	// if (!function_exists('download_url')) {
	// 	require_once(ABSPATH . 'wp-admin/includes/file.php');
	// 	require_once(ABSPATH . 'wp-admin/includes/media.php');
	// 	require_once(ABSPATH . 'wp-admin/includes/image.php');
	// }

	// Set upload directory and generate a unique filename
	$upload_dir = WPLP_UPLOAD_DIR;
	$unique_file_name = wp_unique_filename($upload_dir, $image_name . '.jpg');
	$filename = basename($unique_file_name);
	$file_path = $upload_dir . '/' . $filename;

	// Attempt to download the image
	$tmp = download_url($image_url);

	// Check for download errors
	if (is_wp_error($tmp)) {
		return 'Error downloading image: ' . $tmp->get_error_message();
	}

	// Ensure file has a valid extension
	$image_extension = pathinfo($tmp, PATHINFO_EXTENSION);
	if (!$image_extension) {
		$new_tmp = $tmp . '.jpg';
		if (!rename($tmp, $new_tmp)) {
			@unlink($tmp);
			return 'Failed to rename the downloaded file.';
		}
		$tmp = $new_tmp;
	}

	// Move the file to final destination
	if (!rename($tmp, $file_path)) {
		@unlink($tmp);
		return 'Error moving file to upload directory.';
	}

	// Get file type and ensure it is an image
	$wp_filetype = wp_check_filetype($file_path, null);
	if (!$wp_filetype['type']) {
		@unlink($file_path);
		return 'Invalid file type.';
	}

	// Prepare attachment data
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title'     => sanitize_file_name($filename),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	// Insert attachment to WordPress
	$attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
	if (is_wp_error($attach_id)) {
		@unlink($file_path);
		return 'Error inserting attachment: ' . $attach_id->get_error_message();
	}

	// Generate attachment metadata
	$attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
	wp_update_attachment_metadata($attach_id, $attach_data);

	// Remove old featured image if it exists
	$existing_thumbnail_id = get_post_thumbnail_id($post_id);
	if ($existing_thumbnail_id) {
		$meta_data = get_post_meta($post_id, 'wplp_media_image', true);
		if ($meta_data !== 'true') {
			wp_delete_attachment($existing_thumbnail_id, true);
		}
	}

	// Set the new featured image for the post
	set_post_thumbnail($post_id, $attach_id);
	update_post_meta($post_id, 'wplp_media_image', 'false');

	return 'success';
}


/**
 * 下载缩略图
 */
function wplf_large_screenshot_quick($image_url, $image_name, $post_id)
{
	// Load necessary WordPress files
	// if (!function_exists('download_url')) {
	// 	require_once(ABSPATH . 'wp-admin/includes/file.php');
	// 	require_once(ABSPATH . 'wp-admin/includes/media.php');
	// 	require_once(ABSPATH . 'wp-admin/includes/image.php');
	// }

	// Set upload directory and generate a unique filename
	$upload_dir = WPLP_UPLOAD_DIR;
	$unique_file_name = wp_unique_filename($upload_dir, $image_name . '.jpg');
	$filename = basename($unique_file_name);
	$file_path = path_join(WPLP_UPLOAD_DIR, $filename);

	// Attempt to download the image
	$tmp = download_url($image_url);

	// Check for download errors
	if (is_wp_error($tmp)) {
		return new WP_Error('download_error', 'Error downloading image: ' . $tmp->get_error_message(), array('status' => 404));
	}

	// Ensure file has a valid extension
	$image_extension = pathinfo($tmp, PATHINFO_EXTENSION);
	if (!$image_extension) {
		$new_tmp = $tmp . '.jpg';
		if (!rename($tmp, $new_tmp)) {
			@unlink($tmp);
			return new WP_Error('rename_error', 'Failed to rename the downloaded file.', array('status' => 500));
		}
		$tmp = $new_tmp;
	}

	// Move the file to final destination
	if (!rename($tmp, $file_path)) {
		@unlink($tmp);
		return new WP_Error('move_error', 'Error moving file to upload directory.', array('status' => 500));
	}

	// Get file type and ensure it is an image
	$wp_filetype = wp_check_filetype($file_path, null);
	if (!$wp_filetype['type']) {
		@unlink($file_path);
		return new WP_Error('invalid_file_type', 'Invalid file type.', array('status' => 415));
	}

	// Prepare attachment data
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title'     => sanitize_file_name($filename),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	// Insert attachment to WordPress
	$attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
	if (is_wp_error($attach_id)) {
		@unlink($file_path);
		return new WP_Error('attachment_error', 'Error inserting attachment: ' . $attach_id->get_error_message(), array('status' => 500));
	}

	// Generate attachment metadata
	$attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
	wp_update_attachment_metadata($attach_id, $attach_data);

	// Remove old featured image if it exists
	$existing_thumbnail_id = get_post_thumbnail_id($post_id);
	if ($existing_thumbnail_id) {
		$meta_data = get_post_meta($post_id, 'wplp_media_image', true);
		if ($meta_data !== 'true') {
			wp_delete_attachment($existing_thumbnail_id, true);
		}
	}

	// Set the new featured image for the post
	set_post_thumbnail($post_id, $attach_id);
	update_post_meta($post_id, 'wplp_media_image', 'false');
	$attach_url = wp_get_attachment_url($attach_id);
	$rtn = array(
		"post_id" => $post_id,
		"attachment_id" => $attach_id,
		'attachment_url' => $attach_url
	);
	return $rtn;
}


/* Shortcode */

add_filter('the_content', 'wplf_remove_autop', 0);


function wplf_remove_autop($content)
{
	global $post;
	// Check for single page and image post type and remove
	if ($post->post_type == 'wplp_link')
		remove_filter('the_content', 'wpautop');

	return $content;
}

function wplf_shortcode($atts)
{

	if (get_option('wplp_grid') != false) {
		$dis = get_option('wplf_grid');
	} else {
		$dis = 'grid';
	}
	if (get_option('wplp_width') != false) {
		$col = get_option('wplf_width');
	} else {
		$col = '3';
	}

	if (get_option('wplpf_grid') != false) {
		$dis = get_option('wplff_grid');
	} else {
		$dis = 'grid';
	}
	if (get_option('wplpf_width') != false) {
		$col = get_option('wplff_width');
	} else {
		$col = '3';
	}

	// 分页设置
	$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
	$posts_per_page = 10; // 每页显示的链接数

	$vars = shortcode_atts(array(
		'ids' => '',
		'type' => '',
		'display' => 'grid',
		'cols' => '3',
		'order' => 'DESC',
		'orderby' => 'ID',
		'sort' => '',
		'img_size' => 'medium',
		'paged' => $paged,
		'img_style' => '',
		'title_style' => '',
		'desc' => '',
		'description' => '',
		'description_link' => 'no',
		'desc_style' => '',
	), $atts);

	// $default_num_posts = get_option('posts_per_page');
	$display = esc_attr($vars['display']);
	$type = esc_attr($vars['type']);
	if ($type != '' && $display == 'grid') {
		$display = $type;
	}
	$cols = esc_attr($vars['cols']);

	$order = esc_attr($vars['order']);
	$meta = '';
	$orderby = esc_attr($vars['orderby']);
	$sort = esc_attr($vars['sort']);
	if ($sort == 'random' && $orderby == 'ID') {
		$oderby = 'rand';
	}
	if ($orderby == 'title') {
		$orderby = 'meta_value';
		$meta = 'wplp_display';
	}
	$img_size = esc_attr($vars['img_size']);
	$img_style = esc_attr($vars['img_style']);
	$title_style = esc_attr($vars['title_style']);
	$desc = esc_attr($vars['desc']);
	$description = esc_attr($vars['description']);
	if ($description == 'yes' && $desc == '') {
		$desc = 'content';
	}
	$description_link = esc_attr($vars['description_link']);
	$desc_style = esc_attr($vars['desc_style']);
	$ids = esc_attr($vars['ids']);


	wp_enqueue_style('wplf-display-style');
	wp_enqueue_script('wplf-display-js');

	global $wpdb;
	$grid = '';
	$list = '';
	$gallery = '';
	$i = 0;

	$query_args = array(
		'post_type' => 'wplp_link',
		'order' => $order,
		'posts_per_page' => $posts_per_page,
		'paged' => $vars['paged'],
		'orderby' => $orderby,
		'meta_key' => 'wplp_display',
		'metakey' => $meta,
		'post_status' => 'publish'
	);

	if ($ids != '') {
		$idarr = explode(',', $ids);
		$query_args['post__in'] = $idarr;
	}
	remove_all_filters('posts_orderby');
	$custom_query = new WP_Query($query_args);
	//print('<pre>'.print_r($custom_query,true).'</pre>');

	while ($custom_query->have_posts()) : $custom_query->the_post();
		$post_id = get_the_ID();
		$mk = wplf_filter_metadata(get_post_meta($post_id));
		if (isset($mk['wplp_display'])) {
			$mdisp = $mk['wplp_display'];
		} else {
			$mdisp = '';
		}

		$url = the_title("", "", false);
		// Image
		$thumb = get_post_thumbnail_id($post_id);
		$img = wp_get_attachment_image($thumb, $img_size, false, array('style' => $img_style));


		// Title
		$title_display = $mdisp;


		// Description
		$description = '';
		if ($desc == 'content') {
			$description = apply_filters('the_content', get_the_content());
			$description = '<p class="wplf_desc" style="' . $desc_style . '">' . $description . '</p>';
		}




		if ($description_link == 'yes') {
			$description = $description . '</a>';
		} else {
			$description = '</a>' . $description;
		}



		if ($display == 'grid') {
			$gallery .= '<figure id="gallery-item-' . $i . '" class="gallery-item wplf-item">
				<div class="gallery-icon landscape">
				<a class="wplf_link" href="' . $url . '" target="_blank">
				' . $img . '
				<p class="wplf_display" style="' . $title_style . '" >' . $title_display . '</p>
				' . $description . '
				</div>
				</figure>';
		} elseif ($display == 'list') {
			$list .= '<div id="wplf_list-item-' . $i . '" class="list-item wplf-item">
				<a class="wplf_link" href="' . $url . '" target="_blank">
				<div class="list-img">' . $img . '</div>
				<p class="wplf_display" style="' . $title_style . '" >' . $title_display . '</p>
				' . $description . '
				</div>
				<hr>';
		}
		$i++;

	endwhile;

	// 分页导航
	$page_html = '';
	$total_pages = $custom_query->max_num_pages;
	if ($total_pages > 1) {
		$current_page = max(1, get_query_var('paged'));
		$page_html = paginate_links(array(
			'base' => get_pagenum_link(1) . '%_%',
			'format' => 'page/%#%',
			'current' => $current_page,
			'total' => $total_pages,
			'prev_text'    => __('« Prev'),
			'next_text'    => __('Next »'),
		));
	}

	if ($display == 'grid') {
		$output = '<div style="clear:both;"></div><div id="gallery-wplf" class="galleryid-wplf gallery-columns-' . $cols . ' wplf-display">' . $gallery . '</div><div style="clear:both;"></div>';
	} elseif ($display == 'list') {
		$output = '<div style="clear:both;"></div><div id="list-wplf" class="listid-wplf wplf-display">' . $list . '</div><div style="clear:both;">' . $page_html . '</div>';
	}
	wp_reset_query();

	return $output;
}
add_shortcode('wp_links_page', 'wplf_shortcode');


add_shortcode('wp_links_page_free', 'wplf_shortcode');
