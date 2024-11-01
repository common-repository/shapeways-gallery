<?php
/*
Plugin Name: Shapeways Gallery Widget
Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
Description: Displays a dynamic gallery of your Shapeways designs.
Version: 1.0.5
Author: Bart Veldhuizen
Author URI: http://www.v-int.nl
License: GPLv2
*/

/* activation and deactivation of the plugin */
register_activation_hook( __FILE__, 'Shapeways_widget_activate');
register_deactivation_hook( __FILE__, 'Shapeways_widget_deactivate');

/* widget registration */
register_sidebar_widget('Shapeways Gallery', 'Shapeways_display_widget');
register_widget_control('Shapeways Gallery', 'Shapeways_control_widget');

/* content parser registration */
add_filter('the_content', 'Shapeways_parse_content');
	
/**
 * Prepare the database, if necessary
 */
function Shapeways_widget_activate(){
	$data = array(
		'title'			=> 'Shapeways Gallery',
		'cols'			=> '1',
		'rows'			=> '3',
		'style'			=> 'light',
		'shop'			=> '',
		'shopsection'	=> '',
		'fav'			=> ''
	);

	if ( ! get_option('Shapeways_widget')){
		add_option('Shapeways_widget' , $data);
	} else {
		update_option('Shapeways_widget' , $data);
	}
}

/**
 * Cleanup after removal
 */
function Shapeways_widget_deactivate(){
	delete_option('Shapeways_widget');
}

/**
 * Helper function: display a numeric drop-down
 */
function numSelect( $name, $currentValue  ) {
	?>
		<select name="<?=$name?>">
	<?
	for($i=1;$i<=7;$i++){
		?>
		<option value="<?=$i?>" <? if($i==$currentValue){echo "selected";} ?> ><?=$i?></option>
		<?
	}
	?></select><?
} 

/**
 * Display the admin form and store the posted results
 */
function Shapeways_control_widget(){
	$data = get_option('Shapeways_widget');
	?>

	<p><label>Title:<br/><input name="Shapeways_title" class="widefat" type="text" value="<?php echo $data['title']; ?>" /></label></p>

	<p><label>Columns:<br/><?=numSelect( "Shapeways_cols", $data['cols']);?></label></p>
	<p><label>Rows:<br/><?=numSelect( "Shapeways_rows", $data['rows']);?></label></p>
	
	<p>
		<label>Style:<br/>
			<select name="Shapeways_style">
				<option value="light" <? if( $data['style']=='light' ){echo "selected";}?> >Light</option>
				<option value="dark" <? if( $data['style']=='dark' ){echo "selected";}?> >Dark</option>
			</select>
		</label>
	</p>

	<p><label>Shop: <br/><input name="Shapeways_shop" class="widefat" type="text" value="<?php echo $data['shop']; ?>" /></label></p>
	<p><label>Shop section: <br/><input name="Shapeways_shopsection" class="widefat" type="text" value="<?php echo $data['shopsection']; ?>" /></label></p>
	<p><label>Favorite (username): <br/><input name="Shapeways_fav" class="widefat" type="text" value="<?php echo $data['fav']; ?>" /></label></p>

	
	<?php
	if (isset($_POST['Shapeways_title'])){
		$data['title'] = attribute_escape($_POST['Shapeways_title']);
		$data['cols'] = attribute_escape($_POST['Shapeways_cols']);
		$data['rows'] = attribute_escape($_POST['Shapeways_rows']);
		$data['style'] = attribute_escape($_POST['Shapeways_style']);
		$data['shop'] = attribute_escape($_POST['Shapeways_shop']);
		$data['shopsection'] = attribute_escape($_POST['Shapeways_shopsection']);
		$data['fav'] = attribute_escape($_POST['Shapeways_fav']);

		
		update_option('Shapeways_widget', $data);
	}
}

/**
 * DIsplay the widget
 */
function Shapeways_display_widget($args){
	$data = get_option('Shapeways_widget');
	
	echo $args['before_widget'];
	echo $args['before_title'] . $data['title'] . $args['after_title'];
					
	echo Shapeways_build_iframe( $data );
					
	echo $args['after_widget'];
}

/**
 * Construct the URL and insert the iframe
 */
function Shapeways_build_iframe( $data ) {

	$cols = $data['cols'];
	$rows = $data['rows'];
	$shop = $data['shop'];
	$shopsection = $data['shopsection'];
	$fav = $data['fav'];
	$style = $data['style'];

	/* start constructing the URL */
	$url = "http://www.shapeways.com/widget/include.php?";

	/* shop and shop section */
	if( $shop != '' ) {
		$url .= "shop=$shop";
		
		if( $shopsection != '' ) {
			$url .= "&shopcat=$shopsection";
		}
	}

	/* user favorite */
	if( $fav != '' ) {
		$url .= "fav=$fav";
	}
	
	/* default style = 'light' */
	if( $style == 'dark' ) {
		$url .= '&style=dark';
	} else {
		$url .= '&style=light';
	}
	
	$url .= "&rows=$rows&cols=$cols";

	/* default values: 3 columns, 1 row */
	if( $cols == 0 ) $cols = 3;
	if( $rows == 0 ) $rows = 1;

	/* calculate iframe width + height */	
	$width = 157 * $cols + 11;
	$height = 146 * $rows + 36;
	
	/* error checks */
	if( ($shop != '') && ($fav != '') ) {
		return( '<span style="font-weight:bold;color:red;">Shapeways widget: You can\'t use both \'shop\' and \'fav\' at the same time.</span>');		
	}
	
	if( ($shop == '') && ($fav == '') ) {
		return( '<span style="font-weight:bold;color:red;">Shapeways widget: No \'shop\' or \'fav\' value configured!</span>');
	}
		
	return( "<iframe frameborder=\"0\" style=\" border:1px solid #c0c0c0; height:".$height."px; width:".$width."px;\" src=\"$url\"></iframe>" );
}

/* in-post parser */
function Shapeways_parse_replace($matches)
{	
	$args = explode(' ', $matches[1]);
    $count = count($temp);
  
	$data = array();
	foreach( $args as $strValue ) {
		$a = explode( '=', $strValue );
		$strKey = $a[0];
		$strValue = $a[1];
		$data[$strKey] = $strValue;
	}
	return( Shapeways_build_iframe( $data ) );
}

function Shapeways_parse_content($text)
{
	return preg_replace_callback("@(?:<p>\s*)?\[shapeways\s*(.*?)\](?:\s*</p>)?@", 'Shapeways_parse_replace', $text);
}
