<?php

if (isset($_GET["simple-fields-action"]) && ($_GET["simple-fields-action"] == "select_file")) {
	header('HTTP/1.1 200 OK'); // wp seems to returns 404 otherwise
	?>
	<iframe
		src="<?php echo EASY_FIELDS_URL."simple_fields.php?wp_abspath=".rawurlencode(ABSPATH)."&simple-fields-action=select_file_inner" ?>"
		style="width: 100%; height: 100%;" hspace="0" frameborder="0"></iframe>
	<?php
	exit;
}

if (isset($_GET["simple-fields-action"]) && ($_GET["simple-fields-action"] == "select_file_inner")) {
	header('HTTP/1.1 200 OK'); // wp seems to returns 404 otherwise
	require("file_browser.php");
	exit;
}

add_action('save_post', 'simple_fields_save_postdata');
function simple_fields_save_postdata($post_id = null, $post = null) {

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) { return; }
	
	// @todo: check permissions, check wp_verify_nonce

	$simple_fields_selected_connector = $_POST["simple_fields_selected_connector"];
	update_post_meta($post_id, "_simple_fields_selected_connector", $simple_fields_selected_connector);

	$post_id = (int) $post_id;
	$fieldgroups = $_POST["simple_fields_fieldgroups"];
	if ($post_id && is_array($fieldgroups)) {

		// remove existing simple fields custom fields for this post
		if ( !$table = _get_meta_table("post") ) { return false; }

		global $wpdb;
		$wpdb->query("DELETE FROM $table WHERE post_id = $post_id AND meta_key LIKE '_simple_fields_fieldGroupID_%'");

		update_post_meta($post_id, "_simple_fields_been_saved", "1");

		foreach ($fieldgroups as $one_field_group_id => $one_field_group_fields) {
	
			foreach ($one_field_group_fields as $one_field_id => $one_field_values) {
			
				$num_in_set = 0;
				foreach ($one_field_values as $one_field_value) {
				
					$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group_id}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";
					$custom_field_value = $one_field_value;
					update_post_meta($post_id, $custom_field_key, $custom_field_value);

					$num_in_set++;
				
				}

			}
			
		}

	} // if array

}


/**
 * adds a fieldgroup through ajax = also fetch defaults
 */
function simple_fields_metabox_fieldgroup_add() {

	$simple_fields_new_fields_count = (int) $_POST["simple_fields_new_fields_count"];
	$post_id = (int) $_POST["post_id"];
	$field_group_id = (int) $_POST["field_group_id"];

	$num_in_set = "new{$simple_fields_new_fields_count}";
	simple_fields_meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, true);

	exit;
}
add_action('wp_ajax_simple_fields_metabox_fieldgroup_add', 'simple_fields_metabox_fieldgroup_add');


/**
 * print out fields for a meta box
 */
function simple_fields_meta_box_output($post_connector_field_id, $post_id) {

	// if not repeatable, just print it out
	// if repeatable: only print out the ones that have a value
	// and + add-button

	$field_groups = get_option("simple_fields_groups");
	$current_field_group = $field_groups[$post_connector_field_id];

	echo "<div class='simple-fields-meta-box-field-group-wrapper'>";
	echo "<input type='hidden' name='simple-fields-meta-box-field-group-id' value='$post_connector_field_id' />";

	if ($current_field_group["repeatable"]) {

		echo "
			<div class='simple-fields-metabox-field-add'>
				<a href='#'>+ Add</a>
			</div>
		";
		echo "<ul class='simple-fields-metabox-field-group-fields simple-fields-metabox-field-group-fields-repeatable'>";

		// check for prev. saved fieldgroups
		# _simple_fields_fieldGroupID_1_fieldID_added_numInSet_0
		// try until returns empty
		$num_added_field_groups = 0;
		while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$post_connector_field_id}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}

		// now add them. ooooh my, this is fancy stuff.
		$use_defaults = null;
		for ($num_in_set=0; $num_in_set<$num_added_field_groups; $num_in_set++) {
			simple_fields_meta_box_output_one_field_group($post_connector_field_id, $num_in_set, $post_id, $use_defaults);	
		}

		echo "</ul>";

	} else {
		
		// is this a new post, ie. should default values be used
		$been_saved = (bool) get_post_meta($post_id, "_simple_fields_been_saved", true);
		if ($been_saved) { $use_defaults = false; } else { $use_defaults = true; }
		
		echo "<ul>";
		simple_fields_meta_box_output_one_field_group($post_connector_field_id, 0, $post_id, $use_defaults);
		echo "</ul>";

	}
	
	echo "</div>";

}

/**
 * output the html for a field group in the meta box
 */
function simple_fields_meta_box_output_one_field_group($field_group_id, $num_in_set, $post_id, $use_defaults) {

	$post = get_post($post_id);
	
	$field_groups = get_option("simple_fields_groups");
	$current_field_group = $field_groups[$field_group_id];
	$repeatable = (bool) $current_field_group["repeatable"];
	?>
	<li class="simple-fields-metabox-field-group">
		<?php // must use this "added"-thingie do be able to track added field group that has no added values (like unchecked checkboxes, that we can't detect ?>
		<input type="hidden" name="simple_fields_fieldgroups[<?php echo $field_group_id ?>][added][<?php echo $num_in_set ?>]" value="1" />
		
		<div class="simple-fields-metabox-field-group-handle"></div>
		<?php
		// if repeatable: add remove-link
		if ($repeatable) {
			?><div class="hidden simple-fields-metabox-field-group-delete"><a href="#" title="Remove field group"></a></div><?php
		}
		?>
		<?php
				
		foreach ($current_field_group["fields"] as $field) {
			
			if ($field["deleted"]) { continue; }
			
			$field_id = $field["id"];
			$field_unique_id = "simple_fields_fieldgroups_{$field_group_id}_{$field_id}_{$num_in_set}";
			$field_name = "simple_fields_fieldgroups[$field_group_id][$field_id][$num_in_set]";

			$custom_field_key = "_simple_fields_fieldGroupID_{$field_group_id}_fieldID_{$field_id}_numInSet_{$num_in_set}";
			$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist

			?>
			<div class="simple-fields-metabox-field">
				<?php
				// different output depending on field type
				if ("checkbox" == $field["type"]) {
	
					if ($use_defaults) {
						$checked = $field["type_checkbox_options"]["checked_by_default"];
					} else {
						$checked = (bool) $saved_value;
					}
					
					if ($checked) {
						$str_checked = " checked='checked' ";
					} else {
						$str_checked = "";
					}
					echo "<input $str_checked id='$field_unique_id' type='checkbox' name='$field_name' value='1' />";
					echo "<label class='simple-fields-for-checkbox' for='$field_unique_id'> " . $field["name"] . "</label>";
	
				} elseif ("radiobuttons" == $field["type"]) {
	
					echo "<label>" . $field["name"] . "</label>";
					$radio_options = $field["type_radiobuttons_options"];
					$radio_checked_by_default_num = $radio_options["checked_by_default_num"];

					$loopNum = 0;
					foreach ($radio_options as $one_radio_option_key => $one_radio_option_val) {
						if ($one_radio_option_key == "checked_by_default_num") { continue; }
						if ($one_radio_option_val["deleted"]) { continue; }
						$radio_field_unique_id = $field_unique_id . "_radio_".$loopNum;
						
						$selected = "";
						if ($use_defaults) {
							if ($radio_checked_by_default_num == $one_radio_option_key) { $selected = " checked='checked' "; }
						} else {
							if ($saved_value == $one_radio_option_key) { $selected = " checked='checked' "; }
						}
												
						echo "<div class='simple-fields-metabox-field-radiobutton'>";
						echo "<input $selected name='$field_name' id='$radio_field_unique_id' type='radio' value='$one_radio_option_key' />";
						echo "<label for='$radio_field_unique_id' class='simple-fields-for-radiobutton'> ".$one_radio_option_val["value"]."</label>";
						echo "</div>";
						
						$loopNum++;
					}
	
				} elseif ("dropdown" == $field["type"]) {
					echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
					echo "<select id='$field_unique_id' name='$field_name'>";
					foreach ($field["type_dropdown_options"] as $one_option_internal_name => $one_option) {
						// $one_option_internal_name = dropdown_num_3
						if ($one_option["deleted"]) { continue; }
						$dropdown_value_esc = esc_html($one_option["value"]);
						$selected = "";
						if ($use_defaults == false && $saved_value == $one_option_internal_name) {
							$selected = " selected='selected' ";
						}
						echo "<option $selected value='$one_option_internal_name'>$dropdown_value_esc</option>";
					}
					echo "</select>";

				} elseif ("file" == $field["type"]) {

					$attachment_id = (int) $saved_value;
					$image_html = "";
					$image_name = "";
					if ($attachment_id) {
						$image_thumbnail = wp_get_attachment_image_src( $attachment_id, 'thumbnail', true );
						$image_thumbnail = $image_thumbnail[0];
						$image_html = "<img src='$image_thumbnail' alt='' />";
						$image_post = get_post($attachment_id);
						$image_name = esc_html($image_post->post_title);
					}
					echo "<div class='simple-fields-metabox-field-file'>";
						echo "<label>{$field["name"]}</label>";
						echo "<div class='simple-fields-metabox-field-file-col1'>";
							echo "<div class='simple-fields-metabox-field-file-selected-image'>$image_html</div>";
						echo "</div>";
						echo "<div class='simple-fields-metabox-field-file-col2'>";
							echo "<input type='hidden' class='text simple-fields-metabox-field-file-fileID' name='$field_name' id='$field_unique_id' value='$attachment_id' />";
							echo "<div class='simple-fields-metabox-field-file-selected-image-name'>$image_name</div>";
							echo "<a href='".EASY_FIELDS_URL."simple_fields.php?wp_abspath=".rawurlencode(ABSPATH)."&simple-fields-action=select_file' class='thickbox simple-fields-metabox-field-file-select'>Select file</a>";
							echo " | <a href='#' class='simple-fields-metabox-field-file-clear'>Clear</a>";
						echo "</div>";
					echo "</div>";

				} elseif ("image" == $field["type"]) {

					$text_value_esc = esc_html($saved_value);
					echo "<label>image</label>";
					echo "<input class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
					
				} elseif ("textarea" == $field["type"]) {
	
					$textarea_value_esc = esc_html($saved_value);
					$textarea_options = $field["type_textarea_options"];
					
					$textarea_class = "";
					$textarea_class_wrapper = "";
					if ($textarea_options["use_html_editor"]) {
						$textarea_class = "simple-fields-metabox-field-textarea-tinymce";
						$textarea_class_wrapper = "simple-fields-metabox-field-textarea-tinymce-wrapper";
					}

					echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
					echo "<div class='$textarea_class_wrapper'>";
					echo "<textarea class='$textarea_class' name='$field_name' id='$field_unique_id' cols='50' rows='5'>$textarea_value_esc</textarea>";
					echo "</div>";
	
				} elseif ("text" == $field["type"]) {
	
					$text_value_esc = esc_html($saved_value);
					echo "<label for='$field_unique_id'> " . $field["name"] . "</label>";
					echo "<input class='text' name='$field_name' id='$field_unique_id' value='$text_value_esc' />";
	
				}
				?>
				<div class="simple-fields-metabox-field-custom-field-key hidden highlight"><strong>Meta key:</strong> <?php echo $custom_field_key ?></div>
			</div><!-- // end simple-fields-metabox-field -->
			<?php
		} // foreach
		
		?>
	</li>
	<?php
}


/**
 * head of admin area: add css and stuff
 */
function simple_fields_admin_head() {

	// add css and scripts
	// @todo: now why do I not use enqueue script for these...??
	?>
	<script type="text/javascript" src="<?php echo EASY_FIELDS_URL ?>scripts.js"></script>
	<link rel="stylesheet" type="text/css" href="<?php echo EASY_FIELDS_URL ?>styles.css" />
	<?php
	
	// Add meta box to post 
	global $post;

	if ($post) {

		$post_type = $post->post_type;
		$arr_post_types = simple_fields_post_connector_attached_types();
		if (in_array($post_type, $arr_post_types)) {
			
			// general meta box to select fields for the post
			add_meta_box('simple-fields-post-edit-side-field-settings', 'Simple Fields', 'simple_fields_edit_post_side_field_settings', $post_type, 'side', 'low');
			
			$connector_to_use = simple_fields_get_selected_connector_for_post($post);
			
			// get connector
			$post_connectors = simple_fields_get_post_connectors();
			if (isset($post_connectors[$connector_to_use])) {
				$field_groups = get_option("simple_fields_groups");
				$selected_post_connector = $post_connectors[$connector_to_use];
				$selected_post_connector_field_groups = $selected_post_connector["field_groups"];
				foreach ($selected_post_connector_field_groups as $one_post_connector_field_group) {
					// add
					if (isset($field_groups[$one_post_connector_field_group["id"]])) {
						$field_group_to_add = $field_groups[$one_post_connector_field_group["id"]];

						$meta_box_id = "simple_fields_connector_" . $field_group_to_add["id"];
						$meta_box_title = $field_group_to_add["name"];
						$meta_box_context = $one_post_connector_field_group["context"];
						$meta_box_priority = $one_post_connector_field_group["priority"];
						$meta_box_callback = create_function ("", " simple_fields_meta_box_output({$one_post_connector_field_group["id"]}, $post->ID); ");
						add_meta_box( $meta_box_id, $meta_box_title, $meta_box_callback, $post_type, $meta_box_context, $meta_box_priority );
						
					}
					
				}
			}
			
		}
	}
	
}


/**
 * get selected post connector for a post
 */
function simple_fields_get_selected_connector_for_post($post) {
	/*
	om sparad connector finns för denna artikel, använd den
	om inte sparad connector, använd default
	om sparad eller default = inherit, leta upp connector för parent post

	$post->ID
	$post->post_type
	*/
	#d($post);
	$post_type = $post->post_type;
	$connector_to_use = null;
	if (!$post->ID) {
		// no id (new post), use default for post type
		$connector_to_use = simple_fields_get_default_connector_for_post_type($post_type);
	} elseif ($post->ID) {
		// get saved connector for post
		$connector_to_use = get_post_meta($post->ID, "_simple_fields_selected_connector", true);
		#var_dump($connector_to_use);
		if ($connector_to_use == "") {
			// no previous post connector saved, use default for post type
			$connector_to_use = simple_fields_get_default_connector_for_post_type($post_type);
		}
	}
	
	// $connector_to_use is now a id or __none__ or __inherit__

	// if __inherit__, get connector from post_parent
	if ("__inherit__" == $connector_to_use && $post->post_parent > 0) {
		$parent_post_id = $post->post_parent;
		$parent_post = get_post($parent_post_id);
		simple_fields_get_selected_connector_for_post($parent_post);
	} elseif ("__inherit__" == $connector_to_use && 0 == $post->post_parent) {
		// already at the top, so inherit should mean... __none__..? right?
		// hm.. no.. then the wrong value is selected in the drop down.. hm...
		#$connector_to_use = "__none__";
	}
	
	// if selected connector is deleted, then return none
	$post_connectors = simple_fields_get_post_connectors();
	if ($post_connectors[$connector_to_use]["deleted"]) {
		$connector_to_use = "__none__";
	}
	
	return $connector_to_use;

}

function simple_fields_admin_init() {

	wp_enqueue_script("jquery");
	wp_enqueue_script("jquery-ui-core");
	wp_enqueue_script("jquery-ui-sortable");
	wp_enqueue_script("jquery-ui-effects-core", "http://jquery-ui.googlecode.com/svn/tags/1.8.1/ui/jquery.effects.core.js");
	wp_enqueue_script("jquery-ui-effects-highlight", "http://jquery-ui.googlecode.com/svn/tags/1.8.1/ui/jquery.effects.highlight.js");
	wp_enqueue_script("thickbox");
	wp_enqueue_style("thickbox");

}


/**
 * get default connector for a post type
 * if no connector has been set, __none__ is returned
 * @param string $post_type
 * @return mixed int connector id or string __none__ or __inherit__
 */
function simple_fields_get_default_connector_for_post_type($post_type) {
	$post_type_defaults = (array) get_option("simple_fields_post_type_defaults");
	$selected_post_type_default = (isset($post_type_defaults[$post_type]) ? $post_type_defaults[$post_type] : "__none__");
	return $selected_post_type_default;
}


/**
 * meta box in sidebar in post edit screen
 * let user select post connector to use for current post
 */
function simple_fields_edit_post_side_field_settings() {
	
	global $post;
	
	$arr_connectors = simple_fields_get_post_connectors_for_post_type($post->post_type);
	$connector_default = simple_fields_get_default_connector_for_post_type($post->post_type);
	$connector_selected = simple_fields_get_selected_connector_for_post($post);
	
	?>
	<div class="inside">
		<div>
			<select name="simple_fields_selected_connector" id="simple-fields-post-edit-side-field-settings-select-connector">
				<option <?php echo ($connector_selected == "__none__") ? " selected='selected' " : "" ?> value="__none__">None</option>
				<option <?php echo ($connector_selected == "__inherit__") ? " selected='selected' " : "" ?> value="__inherit__">Inherit from parent</option>
				<?php foreach ($arr_connectors as $one_connector) : ?>
					<?php if ($one_connector["deleted"]) { continue; } ?>
					<option <?php echo ($connector_selected == $one_connector["id"]) ? " selected='selected' " : "" ?> value="<?php echo $one_connector["id"] ?>"><?php echo $one_connector["name"] ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div id="simple-fields-post-edit-side-field-settings-select-connector-please-save" class="hidden">
			<p>Save post to switch to selected fields.</p>
		</div>
		<div>
			<p><a href="#" id="simple-fields-post-edit-side-field-settings-show-keys">Show custom field keys</a></p>
		</div>
	</div>
	<?php
}

if (!function_exists("bonny_d")) {
	function bonny_d($s) {
		echo "<pre>"; print_r($s); echo "</pre>";
	}
}



/**
 * get all values or just the from a field in a field group
 * @param $post_id
 * @param $field_name_or_id name as string or field group id and field id as array. 
 * 		  for example array(3,2) to fetch field 2 from field group 3
 * @param $single bool return a single (the first) value or all values (as array)
 * @return string or array
 */
function simple_fields_get_post_value($post_id, $field_name_or_id, $single = true) {

	$fetch_by_id = true;
	if (is_array($field_name_or_id) && sizeof($field_name_or_id) == 2) {
		$field_group_id = $field_name_or_id[0];
		$field_id = $field_name_or_id[1];
		$fetch_by_id = false;
	}
	$connector = simple_fields_get_all_fields_and_values_for_post($post_id);
	$return_val = null;
	foreach ($connector["field_groups"] as $one_field_group) {
		$is_found = false;
		foreach ($one_field_group["fields"] as $one_field) {
			if ($fetch_by_id && $one_field["name"] == $field_name_or_id) {
				// we got our field, get the value(s)
				$is_found = true;
			} else if (($one_field_group["id"] == $field_group_id) && ($one_field["id"] == $field_id)) {
				$is_found = true;
			}

			$saved_values = $one_field["saved_values"];

			if ($one_field["type"] == "radiobuttons" || $one_field["type"] == "dropdown") {
				if ($one_field["type"] == "radiobuttons") {
					$get_value_key = "type_radiobuttons_options";
				} else if ($one_field["type"] == "dropdown") {
					$get_value_key = "type_dropdown_options";
				}
				// if radiobutton or dropdown, get value from type_dropdown_options[<saved value>][value]
				// for each saved value, get value from type_dropdown_options[<saved value>]
				for ($saved_i = 0; $saved_i < sizeof($saved_values); $saved_i++) {
					$saved_values[$saved_i] = $one_field[$get_value_key][$saved_values[$saved_i]]["value"];
				}
			}
			
			if ($is_found && $single) {
				$return_val = $saved_values[0];
			} else if ($is_found) {
				$return_val = $saved_values;
			}

			if ($is_found) {
				return $return_val;
			}


		}
	}
	return; // oh no! nothing found. bummer.
}

/**
 * get all values from a field group
 * @param int $post_id
 * @param name or ir $field_group_name_or_id
 * @param bool use_name return array with names or id as key
 * @param int $return_format 1|2
 * @return array
 */
function simple_fields_get_post_group_values($post_id, $field_group_name_or_id, $use_name = true, $return_format = 1) {

	$fetch_by_id = true;
	if (is_int($field_group_name_or_id)) {
		$fetch_by_id = true;
	}
	$connector = simple_fields_get_all_fields_and_values_for_post($post_id);

	foreach ($connector["field_groups"] as $one_field_group) {

		$is_found = false;
		if ($fetch_by_id && $one_field_group["id"] == $field_group_name_or_id) {
			$is_found = true;
		} else if ($field_group_name_or_id == $one_field_group["name"]) {
			$is_found = true;
		}

		if ($is_found) {
			$arr_return = array();
			foreach ($one_field_group["fields"] as $one_field) {
			
				$saved_values = $one_field["saved_values"];

				if ($one_field["type"] == "radiobuttons" || $one_field["type"] == "dropdown") {
					if ($one_field["type"] == "radiobuttons") {
						$get_value_key = "type_radiobuttons_options";
					} else if ($one_field["type"] == "dropdown") {
						$get_value_key = "type_dropdown_options";
					}
					// if radiobutton or dropdown, get value from type_dropdown_options[<saved value>][value]
					// for each saved value, get value from type_dropdown_options[<saved value>]
					for ($saved_i = 0; $saved_i < sizeof($saved_values); $saved_i++) {
						$saved_values[$saved_i] = $one_field[$get_value_key][$saved_values[$saved_i]]["value"];
					}
				}

				if ($use_name) {
					$arr_return[$one_field["name"]] = $saved_values;
				} else {
					$arr_return[$one_field["id"]] = $saved_values;
				}
			}
			
			$set_count = sizeof($one_field["saved_values"]);
			
			$arr_return2 = array();
			for ($i=0; $i<$set_count; $i++) {
				$arr_return2[$i] = array();
				foreach ($arr_return as $key => $val) {
					$arr_return2[$i][$key] = $val[$i];
				}
			}
			if ($return_format == 1) {
				return $arr_return;
			} elseif ($return_format == 2) {
				return $arr_return2;
			}
		}
	}
	

}

/**
 * fetch all information about the field group that a post has
 * returns connector structure, field groups, fields, and values
 * well.. everything! it's really funky.
 * return @array a really fat one!
 */
function simple_fields_get_all_fields_and_values_for_post($post_id) {
	$post = get_post($post_id);
	$connector_to_use = simple_fields_get_selected_connector_for_post($post);
	$existing_post_connectors = simple_fields_get_post_connectors();
	$field_groups = get_option("simple_fields_groups");
	$selected_post_connector = $existing_post_connectors[$connector_to_use];
	foreach ($selected_post_connector["field_groups"] as $one_field_group) { // one_field_group = name, deleted, context, priority, id
	
		// now get all fields for that fieldgroup and join them together
		$selected_post_connector["field_groups"][$one_field_group["id"]] = array_merge($selected_post_connector["field_groups"][$one_field_group["id"]], $field_groups[$one_field_group["id"]]);
	
		// loop through all fields within this field group
		// now find out how many times this field group has been added
		// can be zero, 1 och several (if field group is repeatable)
	
		$num_added_field_groups = 0;
		while (get_post_meta($post_id, "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_added_numInSet_{$num_added_field_groups}", true)) {
			$num_added_field_groups++;
		}
		
		// now fetch the stored values, one field at a time
		for ($num_in_set = 0; $num_in_set < $num_added_field_groups; $num_in_set++) {
			// fetch value for each field
			foreach ($selected_post_connector["field_groups"][$one_field_group["id"]]["fields"] as $one_field_id => $one_field_value) {

				$custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group["id"]}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";	
				$saved_value = get_post_meta($post_id, $custom_field_key, true); // empty string if does not exist

				$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["saved_values"][$num_in_set] = $saved_value;
				$selected_post_connector["field_groups"][$one_field_group["id"]]["fields"][$one_field_id]["meta_keys"][$num_in_set] = $custom_field_key;

			}
		}
		
	}
	return $selected_post_connector;
}
# $custom_field_key = "_simple_fields_fieldGroupID_{$one_field_group_id}_fieldID_{$one_field_id}_numInSet_{$num_in_set}";