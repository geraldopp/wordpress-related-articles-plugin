<?php
/*
Plugin Name: GPP Related Articles
Description: This plugin will create a new field for post tags called Tag Relevance, on which you will be able to set points from 1 to 100 depending on how generic the tag is. Then this metric is taken into account to determine how relevant is a tag to the topic in question, to make better and more accurate predictions in the suggestions panel.
Author: Geraldo Pena Perez
Version: 3.0.1
*/

if(!defined('ABSPATH')){
	exit;
}

function gppplugins_relatedarticles_tag_add_form_fields($taxonomy){
	?>
    <div class="form-field term-tagrelevance-wrap">
        <label for="term-tagrelevance">Tag relevance:</label>
        <input type="text" name="_tag_relevance" value="0" />
        <p>This is the field description where you can tell the user how the color is used in the theme.</p>
    </div>
	<?php 
}

function gppplugins_relatedarticles_tag_edit_form_fields($term){

    $rele = get_term_meta($term->term_id, '_tag_relevance', true);
    $rele = (!empty($rele))? $rele : 0;

	?>
    <tr class="form-field term-tagrelevance-wrap">
        <th scope="row"><label for="term-tagrelevance">Tag relevance:</label></th>
        <td>
            <input type="text" name="_tag_relevance" value="<?php echo $rele; ?>" />
            <p class="description">Points of the tag depending of how specific or generic it is. This value will determine the relevance.</p>
        </td>
    </tr>
    <?php
 }

function gppplugins_relatedarticles_save_termmeta_tag($term_id) {

     // Save term color if possible
    if(isset( $_POST['_tag_relevance']) && !empty( $_POST['_tag_relevance'])){
        update_term_meta($term_id, '_tag_relevance', intval($_POST['_tag_relevance']));
    }else{
        delete_term_meta($term_id, '_tag_relevance');
    }
}

function gppplugins_relatedarticles_gwp_quick_edit_category_field($column_name, $screen){
    if ($screen != 'edition' && $column_name != 'tag-relevance'){
        return false;
    }
    ?>
    <fieldset>
        <div id="gwp-first-appeared" class="inline-edit-col">
            <label>
                <span class="title">Relevance:</span>
                <span class="input-text-wrap"><input type="text" name="_tag_relevance" class="ptitle" placeholder="Insert here a number between 1 - 100" value=""></span>
            </label>
        </div>
    </fieldset>
    <?php
}

function gppplugins_relatedarticles_add_post_tag_columns($columns){
    $columns['tag-relevance'] = 'Tag Relevance';
    return $columns;
}

function gppplugins_relatedarticles_add_post_tag_column_content($string, $column_name, $term_id){
    $content = get_term_meta($term_id, '_tag_relevance', true);
    $content = (!empty($content))? $content : 0;
    return $content;
}

function gppplugins_relatedarticles_getrelatedarticles($the_content){
	$all_tags = array();
	$all_terms = array();
	$tag_points = array();
	$all_links = array();
	$all_links_points = array();
	$resultados_top = 4;
	$resultados_bottom = 6;

	if(is_single()){
		if(get_post_type() == 'post'){
			if($posttags = get_the_tags(get_the_ID())){
				for($i=0; $i<count($posttags); $i++){
					$tag_id = $posttags[$i]->term_id;
					$all_terms[] = $posttags[$i]->name;
					$all_tags[] = $tag_id;
					$temp_points = get_term_meta($tag_id, '_tag_relevance', true);
    				$temp_points = (!empty($temp_points))? $temp_points : 0;
					$tag_points[] = $temp_points;
				}

				// ***** Query arguments *****
				$args = array(
					'tag' => implode(',', $all_terms),
					'post__not_in' => array(get_the_ID()),
					'posts_per_page' => -1
				);
				$tagged_posts = new WP_Query($args);

				// ***** Check every post having at least 1 tag matching the original's collection and assigning points per relevance *****
				while($tagged_posts->have_posts()){
					$counter = 0;
					$tagged_posts->the_post();
					for($i=0; $i<count($all_tags); $i++){
						if(has_tag($all_tags[$i])){
							$counter = $counter + $tag_points[$i];
						}
					}

					$all_links[] = '<li><a href="'.get_permalink().'" title="'.get_the_title().'">'.get_the_title().'</a></li>';

					$all_links_points[] = $counter;
				}
				wp_reset_query();

				array_multisort($all_links_points, SORT_DESC, $all_links);
				
				$res_top = '';
				$res_top .= '<hr><h4>You can also be interested in these:</h4>';
				$res_top .= '<ul>';
				for($i=0; $i<$resultados_top; $i++){
					$res_top .= $all_links[$i];
				}
				$res_top .= '</ul><hr>';

				$res_bottom = '';
				$res_bottom .= '<hr><h4>More stories like this</h4>';
				$res_bottom .= '<ul>';
				for($i=0; $i<$resultados_bottom; $i++){
					$res_bottom .= $all_links[$i];
				}
				$res_bottom .= '</ul><hr>';

				return $res_top . $the_content . $res_bottom;
			}else{
				return $the_content;
			}
		}else{
			return $the_content;
		}
	}else{
		return $the_content;
	}
}

add_action('add_tag_form_fields', 'gppplugins_relatedarticles_tag_add_form_fields');
add_action('edit_tag_form_fields', 'gppplugins_relatedarticles_tag_edit_form_fields');
add_action('edit_term', 'gppplugins_relatedarticles_save_termmeta_tag');
add_action('saved_term', 'gppplugins_relatedarticles_save_termmeta_tag');
add_action('quick_edit_custom_box', 'gppplugins_relatedarticles_gwp_quick_edit_category_field', 10, 2);
add_filter('manage_edit-post_tag_columns', 'gppplugins_relatedarticles_add_post_tag_columns');
add_filter('manage_post_tag_custom_column', 'gppplugins_relatedarticles_add_post_tag_column_content', 10, 3);
add_filter('the_content', 'gppplugins_relatedarticles_getrelatedarticles');

