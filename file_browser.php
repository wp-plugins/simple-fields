<?php

require WP_CONTENT_DIR . "/../wp-admin/includes/media.php";
require WP_CONTENT_DIR . "/../wp-admin/includes/template.php";
require WP_CONTENT_DIR . "/../wp-admin/includes/post.php";

wp_enqueue_script("jquery");
wp_enqueue_script("jquery-ui-core");
wp_enqueue_script("jquery-ui-sortable");

wp_enqueue_style("colors-fresh");

/**
 */
function media_upload_library_form2($errors) {
	global $wpdb, $wp_query, $wp_locale, $type, $tab, $post_mime_types;

	$post_id = intval($_REQUEST['post_id']);

	$form_action_url = admin_url("media-upload.php?type=$type&tab=library&post_id=$post_id");
	$form_action_url = apply_filters('media_upload_form_url', $form_action_url, $type);

	$_GET['paged'] = isset( $_GET['paged'] ) ? intval($_GET['paged']) : 0;
	if ( $_GET['paged'] < 1 )
		$_GET['paged'] = 1;
	$start = ( $_GET['paged'] - 1 ) * 10;
	if ( $start < 1 )
		$start = 0;
	add_filter( 'post_limits', create_function( '$a', "return 'LIMIT $start, 10';" ) );


	list($post_mime_types, $avail_post_mime_types) = wp_edit_attachments_query($q);
	$args = array(
	    "xpaged" => 1,
	    "m" => 0,
	    "cat" => 0,
	    "post_type" => "attachment",
	    "post_status" => "inherit",
	    "is_paged" => 1,
	    "orderby" => "modified",
	    "order" => "DESC"
	   # "xposts_per_page" => 5,
	   # "xshowposts" => 5
	);
	$s = $_GET["s"];
	if ($s) {
		$args["s"] = $s;
	}
	if ($_GET["post_mime_type"]) {
		$args["post_mime_type"] = $_GET["post_mime_type"];
	}
	$query_attachments = new WP_Query($args);
	?>

	<form id="filter" action="" method="get">
	<input type="hidden" name="wp_abspath" value="<?php echo $_GET["wp_abspath"]; ?>" />
	<input type="hidden" name="simple-fields-action" value="<?php echo $_GET["simple-fields-action"]; ?>" />
	<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>" />
	<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />
	<input type="hidden" name="post_id" value="<?php echo (int) $post_id; ?>" />
	<input type="hidden" name="post_mime_type" value="<?php echo isset( $_GET['post_mime_type'] ) ? esc_attr( $_GET['post_mime_type'] ) : ''; ?>" />
	<p id="media-search" class="search-box">
		<label class="screen-reader-text" for="media-search-input"><?php _e('Search Media');?>:</label>
		<input type="text" id="media-search-input" name="s" value="<?php the_search_query(); ?>" />
		<input type="submit" value="<?php esc_attr_e( 'Search Media' ); ?>" class="button" />
	</p>
	
	<ul class="subsubsub">
	<?php

	$type_links = array();
	$_num_posts = (array) wp_count_attachments();
	$matches = wp_match_mime_types(array_keys($post_mime_types), array_keys($_num_posts));
	foreach ( $matches as $_type => $reals )
		foreach ( $reals as $real )
			if ( isset($num_posts[$_type]) )
				$num_posts[$_type] += $_num_posts[$real];
			else
				$num_posts[$_type] = $_num_posts[$real];
	// If available type specified by media button clicked, filter by that type
	if ( empty($_GET['post_mime_type']) && !empty($num_posts[$type]) ) {
		$_GET['post_mime_type'] = $type;
		list($post_mime_types, $avail_post_mime_types) = wp_edit_attachments_query();
	}
	if ( empty($_GET['post_mime_type']) || $_GET['post_mime_type'] == 'all' )
		$class = ' class="current"';
	else
		$class = '';
	$type_links[] = "<li><a href='" . esc_url(add_query_arg(array('post_mime_type'=>'all', 'paged'=>false, 'm'=>false))) . "'$class>".__('All Types')."</a>";
	foreach ( $post_mime_types as $mime_type => $label ) {
		$class = '';
	
		if ( !wp_match_mime_types($mime_type, $avail_post_mime_types) )
			continue;
	
		if ( isset($_GET['post_mime_type']) && wp_match_mime_types($mime_type, $_GET['post_mime_type']) )
			$class = ' class="current"';
	
		$type_links[] = "<li><a href='" . esc_url(add_query_arg(array('post_mime_type'=>$mime_type, 'paged'=>false))) . "'$class>" . sprintf(_n($label[2][0], $label[2][1], $num_posts[$mime_type]), "<span id='$mime_type-counter'>" . number_format_i18n( $num_posts[$mime_type] ) . '</span>') . '</a>';
	}
	echo implode(' | </li>', $type_links) . '</li>';
	unset($type_links);
	?>
	</ul>
	
	<div class="tablenav">
	
	<?php
	$page_links = paginate_links( array(
		'base' => add_query_arg( 'paged', '%#%' ),
		'format' => '',
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => ceil($query_attachments->found_posts / 10),
		'current' => $_GET['paged']
	));

	if ( $page_links )
		echo "<div class='tablenav-pages'>$page_links</div>";
	?>
	
	<div class="alignleft actions">
	<?php

	$arc_query = "SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts WHERE post_type = 'attachment' ORDER BY post_date DESC";
	$arc_result = $wpdb->get_results( $arc_query );
	
	$month_count = count($arc_result);
	
	if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) { ?>
	<select name='m'>
	<option<?php selected( @$_GET['m'], 0 ); ?> value='0'><?php _e('Show all dates'); ?></option>
	<?php
	foreach ($arc_result as $arc_row) {
		if ( $arc_row->yyear == 0 )
			continue;
		$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );
	
		if ( isset($_GET['m']) && ( $arc_row->yyear . $arc_row->mmonth == $_GET['m'] ) )
			$default = ' selected="selected"';
		else
			$default = '';
	
		echo "<option$default value='" . esc_attr( $arc_row->yyear . $arc_row->mmonth ) . "'>";
		echo esc_html( $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear" );
		echo "</option>\n";
	}
	?>
	</select>
	<?php } ?>
	
	<input type="submit" id="post-query-submit" value="<?php echo esc_attr( __( 'Filter &#187;' ) ); ?>" class="button-secondary" />
	
	</div>
	
	<br class="clear" />
	</div>
	</form>
	

	<div id="media-items">
		<?php

		$attachments = array();
		if ( $query_attachments->have_posts() ) {
			while ($query_attachments->have_posts()) {
				$query_attachments->the_post();
				$attachments[get_the_id()] = get_the_id();
			}
		}
	
		$attachments = (array) $attachments;
		if (sizeof($attachments)>0) {
			echo "<ul class='simple-fields-file-browser-list'>";
			foreach ( $attachments as $id => $attachment ) {
				if ( $attachment->post_status == 'trash' ) { continue; }
				echo get_media_item2($id);
			}
			echo "</ul>";
		}
	?>
	</div>
	<?php
} // end function




function get_media_item2( $attachment_id, $args = null ) {

	global $redir_tab;

	if ( ( $attachment_id = intval( $attachment_id ) ) && $thumb_url = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true ) )
		$thumb_url = $thumb_url[0];
	else
		$thumb_url = false;

	$post = get_post( $attachment_id );

	$default_args = array( 'errors' => null, 'send' => post_type_supports(get_post_type($post->post_parent), 'editor'), 'delete' => true, 'show_title' => true );
	$args = wp_parse_args( $args, $default_args );
	extract( $args, EXTR_SKIP );

	$filename = basename( $post->guid );
	$title = esc_attr( $post->post_title );

	if ( $_tags = get_the_tags( $attachment_id ) ) {
		foreach ( $_tags as $tag )
			$tags[] = $tag->name;
		$tags = esc_attr( join( ', ', $tags ) );
	}

	$post_mime_types = get_post_mime_types();
	$keys = array_keys( wp_match_mime_types( array_keys( $post_mime_types ), $post->post_mime_type ) );
	$type = array_shift( $keys );

	$form_fields = get_attachment_fields_to_edit( $post, $errors );

	$display_title = ( !empty( $title ) ) ? $title : $filename; // $title shouldn't ever be empty, but just in case

	$media_dims = '';
	$meta = wp_get_attachment_metadata( $post->ID );
	if ( is_array( $meta ) && array_key_exists( 'width', $meta ) && array_key_exists( 'height', $meta ) )
		$media_dims .= "<span id='media-dims-$post->ID'>{$meta['width']}&nbsp;&times;&nbsp;{$meta['height']}</span> ";
	$media_dims = apply_filters( 'media_meta', $media_dims, $post );

	$attachment_url = get_permalink( $attachment_id );
	
	$str_media_dims = "";
	if ( !empty( $media_dims ) ) {
		$str_media_dims = " | <strong>" . __('Dimensions:') . "</strong> $media_dims";
	}

	$item = "
		<li>
			<div class='thumbnail'><a href='$attachment_url' target='_blank'><img src='$thumb_url' alt='' /></a></div>
			<div class='simple-fields-file-browser-list-file-info'>
				<h3><input class='simple-fields-file-browser-file-select' type='submit' class='button-secondary' value='Select' /> $display_title</h3>
				<p><strong>" . __('File name:') . "</strong> $filename</p>
				<div class='hiddenx simple-fields-file-browser-list-file-info-details'>
					<p><strong>" . __('File type:') . "</strong> $post->post_mime_type | <strong>" . __('Upload date:') . "</strong> " . mysql2date( get_option('date_format'), $post->post_date ). "
					$str_media_dims
					</p>
				</div>
			</div>
			<div class='clear'></div>
			<input type='hidden' name='simple-fields-file-browser-list-file-id' value='$attachment_id' />
		</li>";

	return $item;
} // func


$errors = "";
wp_iframe( 'media_upload_library_form2', $errors );

?>