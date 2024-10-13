<?php
/**
 * Plugin Name: Post Table of Content
 * Description: Plugin make auto insert toc to post content. !Required add css to theme. Also, you can use: <code>&lt;!--insert-toc--&gt;</code> or <code>[toc]</code>
 * Version: 1.0.1
 * Text Domain: toc
 * Domain Path: /languages
 * Author: Aleksey Tikhomirov
 *
 * Requires at least: 4.6
 * Tested up to: 6.4
 * Requires PHP: 8.0+
 *
 * Bootstrap 5 required (css)
 * How to use: Plugin make auto insert toc to post content after plugin has been activated. !Required add css to theme.
 */

defined( 'ABSPATH' ) or die( 'Nothing here!' );

add_action( 'plugins_loaded', function () {
    load_plugin_textdomain('toc', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

add_filter('the_content', function ($content){
    return '<!--insert-toc-->' . $content;
},11);

// declare a function and pass the $content as an argument
function insert_table_of_contents($content) {

    if(!is_singular('post')){
        return $content;
    }

    // used to determine the location of the
    // table of contents when $fixed_location is set to false
    $html_comment = '<!--insert-toc-->';
    // checks if $html_comment exists in $content
    $comment_found = str_contains($content, $html_comment);

    // set to true to insert the table of contents in a fixed location
    // set to false to replace $html_comment with $table_of_contents
    $fixed_location = true;

    // return the $content if
    // $comment_found and $fixed_location are false
    if (!$fixed_location && !$comment_found) {
        return $content;
    }

    // exclude the table of contents from all pages
    // other exclusion options include:
    // in_category($id)
    // has_term($term_name)
    // is_single($array)
    // is_author($id)
    if (is_page()) {
        return $content;
    }

    // regex to match all HTML heading elements 2-6
    $regex = "~(<h([2-6]))(.*?>(.*)</h[2-6]>)~";

    // preg_match_all() searches the $content using $regex patterns and
    // returns the results to $heading_results[]
    //
    // $heading_results[0][] contains all matches in full
    // $heading_results[1][] contains '<h2-6'
    // $heading_results[2][] contains '2-6'
    // $heading_results[3][] contains '>heading title</h2-6>
    // $heading_results[4][] contains the title text
    preg_match_all($regex, $content, $heading_results);

    // return $content if less than 3 heading exist in the $content
    $num_match = count($heading_results[0]);
    if($num_match < 3) {
        return $content;
    }

    // declare local variable
    $link_list = "";
    // loop through $heading_results
    for ($i = 0; $i < $num_match; ++ $i) {

        $id = sanitize_title($heading_results[4][$i]);

        // find original heading elements that don't have anchors
        $old_heading = $heading_results[0][$i];

        // rebuild heading elements to have anchors
        $new_heading = $heading_results[1][$i] . " id='$id' " . $heading_results[3][$i];

        // search the $content for $old_heading and replace with $new_heading
        $content = str_replace($old_heading, $new_heading, $content);

        $modified = $heading_results[2][$i] - 1;
        if( !in_array(2, $heading_results[2]) ){
            $modified = $heading_results[2][$i] - 2;
            if( !in_array(3, $heading_results[2]) ){
                $modified = $heading_results[2][$i] - 3;
            }
        }

        // generate links for each heading element
        // each link points to an anchor
        $link_list .= "<li class='level-" . $modified . "'><a href='#$id' >" . strip_tags($heading_results[4][$i]) . "</li></a>";
    }

    // opening nav tag
    $start_nav = '<nav class="accordion accordion-flush table-of-contents" id="toc"><div class="accordion-item">';
    // closing nav tag
    $end_nav = '</div></nav>';
    // title
    $title = '<div class="accordion-header" id="toc-heading"><button type="button" data-bs-toggle="collapse" data-bs-target="#toc-content" class="accordion-button">'.
        esc_html__('Table of Content', 'toc').'</button></div>';

    // wrap links in '<ul>' element
    $link_list = '<div id="toc-content" class="accordion-content bg-light accordion-collapse collapse show" data-bs-parent="#toc" aria-labelledby="toc-heading"><ol class="accordion-body">' .
        $link_list . '</ol></div>';

    // piece together the table of contents
    $table_of_contents = $start_nav . $title . $link_list . $end_nav;

    // if $fixed_location is true and
    // $comment_found is false
    // insert the table of contents at a fixed location
    if($fixed_location && !$comment_found) {
        // location of first paragraph
        $first_paragraph = strpos($content, '</p>', 0) + 4;
        // location of second paragraph
        $second_paragraph = strpos($content, '</p>', $first_paragraph);
        // insert $table_of_contents after $second_paragraph
        return substr_replace($content, $table_of_contents, $second_paragraph + 4 , 0);
    }
    // if $fixed_location is false and
    // $comment_found is true
    else {
        // replace $html_comment with the $table_of_contents
        return str_replace($html_comment, $table_of_contents, $content);
    }
}
// pass the function to the content add_filter hook
add_filter('the_content', 'insert_table_of_contents', 100);

add_shortcode('toc', fn($atts) => '<!--insert-toc-->');