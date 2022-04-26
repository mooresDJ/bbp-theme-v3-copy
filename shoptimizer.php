<?php
/**
 * Inject Critical CSS to wp_head.
 */
function shoptimizer_criticalcss() {
	echo '<style>';
	if ( is_front_page() || is_home() ) {
		get_template_part( 'assets/css/criticalcss/home' );
	} elseif ( is_single() ) {
		get_template_part( 'assets/css/criticalcss/single-post' );
	} elseif ( is_page() ) {
		get_template_part( 'assets/css/criticalcss/single-post' );
	} elseif ( is_archive() ) {
		get_template_part( 'assets/css/criticalcss/blog-archive' );
	} elseif ( is_shop() || is_product_category() ) {
		get_template_part( 'assets/css/criticalcss/blog-archive' );
	} elseif ( is_product() ) {
		get_template_part( 'assets/css/criticalcss/single-product' );
	} else {
		get_template_part( 'assets/css/criticalcss/single-post' );
	}
	echo '</style>';
}

/**
 * Get the appropriate handle for css.
 */
function shoptimizer_get_css_handle() {

	// Safe Default.
	$css_handle = 'shoptimizer-main';

	$shoptimizer_general_speed_minify_main_css = '';
	$shoptimizer_general_speed_minify_main_css = shoptimizer_get_option( 'shoptimizer_general_speed_minify_main_css' );

	if ( 'yes' === $shoptimizer_general_speed_minify_main_css ) {
		$css_handle = 'shoptimizer-main-min';
	} else {
		$css_handle = 'shoptimizer-main';
	}

	return $css_handle;
}

/**
 * Replaces a stylesheet link tag with a preload tag.
 *
 * @param string $tag     The link tag as generated by WordPress.
 * @param string $handle  The handle by which the stylesheet is known to WordPress.
 * @param string $href    The URL to the stylesheet, including version number.
 * @param string $media   The media attribute of the stylesheet.
 * @return string The original tag wrapped in a noscript element, followed by the preload tag.
 */
function shoptimizer_filter_style_loader_tag( $tag, $handle, $href, $media ) {
	global $wp_styles;

	$shoptimizer_css_handle = shoptimizer_get_css_handle();

	if ( $shoptimizer_css_handle === $handle ) {

		$rel          = 'stylesheet';
		$noscript_tag = $tag;
		$tag          = sprintf(
			'<link rel="preload" as="style" onload="%s" id="%s-css" href="%s" type="text/css" media="%s" />',
			"this.onload=null;this.rel='" . esc_js( $rel ) . "'",
			esc_attr( $handle . '-preload' ),
			esc_url_raw( $href ),
			esc_attr( $media )
		);
		$tag         .= sprintf( '<noscript>%s</noscript>', $noscript_tag );
		$tag         .= '<script>!function(n){"use strict";n.loadCSS||(n.loadCSS=function(){});var o=loadCSS.relpreload={};if(o.support=function(){var e;try{e=n.document.createElement("link").relList.supports("preload")}catch(t){e=!1}return function(){return e}}(),o.bindMediaToggle=function(t){var e=t.media||"all";function a(){t.media=e}t.addEventListener?t.addEventListener("load",a):t.attachEvent&&t.attachEvent("onload",a),setTimeout(function(){t.rel="stylesheet",t.media="only x"}),setTimeout(a,3e3)},o.poly=function(){if(!o.support())for(var t=n.document.getElementsByTagName("link"),e=0;e<t.length;e++){var a=t[e];"preload"!==a.rel||"style"!==a.getAttribute("as")||a.getAttribute("data-loadcss")||(a.setAttribute("data-loadcss",!0),o.bindMediaToggle(a))}},!o.support()){o.poly();var t=n.setInterval(o.poly,500);n.addEventListener?n.addEventListener("load",function(){o.poly(),n.clearInterval(t)}):n.attachEvent&&n.attachEvent("onload",function(){o.poly(),n.clearInterval(t)})}"undefined"!=typeof exports?exports.loadCSS=loadCSS:n.loadCSS=loadCSS}("undefined"!=typeof global?global:this);</script>';
	}

	return $tag;
}

$shoptimizer_general_speed_critical_css = '';
$shoptimizer_general_speed_critical_css = shoptimizer_get_option( 'shoptimizer_general_speed_critical_css' );
if ( 'yes' === $shoptimizer_general_speed_critical_css ) {
	add_action( 'wp_head', 'shoptimizer_criticalcss', 5 );
	add_filter( 'style_loader_tag', 'shoptimizer_filter_style_loader_tag', 10, 4 );
}