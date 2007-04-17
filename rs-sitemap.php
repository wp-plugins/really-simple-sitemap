<?php
/*
Plugin Name: Really Simple Sitemap
Plugin URI: http://www.internetwealthmaster.com/wordpress-really-simple-sitemap/
Description: Adds a really simple sitemap to your Wordpress blog. Add <!--rs sitemap--> to any page or post and the site map will be added there. Use Options->RS Sitemap to set options.
Author: Dominic Foster
Version: 1.2
Author URI: http://www.internetwealthmaster.com/
*/

/*
Updates:
1.2 : Only perform database queries if <!--rs sitemap--> exists in post/page text. Previously hit the db regardless.
*/

/*
Really Simple Sitemap is a Wordpress Plugin that will create a list of posts and pages from your Wordpress Blog.
Copyright (C) 2007 Dominic Foster

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

//To replace the <!--rs sitemap--> with the actual sitemap
function rs_sitemap($text) {
	global $wpdb, $table_prefix;

	//Only perform plugin functionality if post/page text has <!--rs sitemap-->
	if (preg_match("|<!--rs sitemap-->|", $text)) {

		//Get option values
		$orderby = get_option('rs_sitemap_order');
		$showhidden = get_option('rs_sitemap_hidden');;
		$showpages = get_option('rs_sitemap_pages');

		//do the order by
		switch ($orderby) {
			case 'date_descending':
				$sqlorder = "ORDER BY post_date DESC";
				break;
			case 'date_ascending':
				$sqlorder = "ORDER BY post_date";
				break;
			case 'alpha_descending':
				$sqlorder = "ORDER BY post_title";
				break;
			case 'alpha_ascending':
				$sqlorder = "ORDER BY post_title DESC";
				break;
		}

		//show private
		if ($showhidden == 'on') {
			$sqlwhere = "WHERE post_type='post' ";
		} else {
			$sqlwhere = "WHERE post_type='post' AND post_status='publish' ";
		}

		$sql = "SELECT * FROM " . $table_prefix . "posts " . $sqlwhere . $sqlorder;

		$allposts = $wpdb->get_results($sql);

		foreach($allposts as $ap) {
			$perma = get_permalink($ap->ID);
			$posts .= '<a href=' . $perma . '>' . $ap->post_title . '</a><br/>';
		}

		//Do we want the pages too?
		if ($showpages != 'pages_none') {
			$sqlpages = "SELECT * FROM " . $table_prefix . "posts where post_type='page' ";

			if ($showhidden != 'on') {
				$sqlpages .= "AND post_status='publish' ";
			}

			$allpages = $wpdb->get_results($sqlpages);

			foreach($allpages as $ap) {
				$perma = get_permalink($ap->ID);
				$pages .= '<a href=' . $perma . '>' . $ap->post_title . '</a><br/>';
			}

			if ($showpages == 'pages_before') {
				$posts = $pages . '<br/>' . $posts;
			} else {
				$posts = $posts . '<br/>' . $pages;
			}
		}

		$text = preg_replace("|<!--rs sitemap-->|", $posts, $text);

	}

	return $text;

} //end rs_sitemap()


//admin menu
function rs_sitemap_admin() {
	if (function_exists('add_options_page')) {
		add_options_page('rs-sitemap', 'RS Sitemap', 1, basename(__FILE__), 'rs_sitemap_admin_panel');
  }
}

function rs_sitemap_admin_panel() {

	//Add options if first time running
	add_option('rs_sitemap_order', 'date_descending', 'Really Simple Sitemap Plugin');
	add_option('rs_sitemap_hidden', 'false', 'Really Simple Sitemap Plugin');
	add_option('rs_sitemap_pages', 'pages_none', 'Really Simple Sitemap Plugin');

	//get posted options
	$orderby = $_POST['orderby'];

	if (isset($_POST['info_update'])) {
		//update settings
		$orderby = $_POST['orderby'];
		$showhidden = $_POST['showhidden'];
		$showpages = $_POST['showpages'];

		update_option('rs_sitemap_order', $orderby);
		update_option('rs_sitemap_hidden', $showhidden);
		update_option('rs_sitemap_pages', $showpages);
	} else {
		//load settings from database
		$orderby = get_option('rs_sitemap_order');
		$showhidden	= get_option('rs_sitemap_hidden');
		$showpages = get_option('rs_sitemap_pages');
	}

	?>

	<div class=wrap>
		<form method="post">

			<h2>Really Simple Sitemap Options</h2>

			<fieldset name="set1">
				<h3>Order Sitemap Pages By:</h3>

				<p>
					<label>
						<input name="orderby" type="radio" value="date_descending" <?php checked('date_descending', $orderby); ?> class="tog"/>
						Date descending (Default)<br />
						<span> &raquo; Most recent to first posted.</span>
					</label>
				</p>

				<p>
					<label>
						<input name="orderby" type="radio" value="date_ascending" <?php checked('date_ascending', $orderby); ?> class="tog"/>
						Date Ascending<br />
						<span> &raquo; First posted to most recent.</span>
					</label>
				</p>

				<p>
					<label>
						<input name="orderby" type="radio" value="alpha_descending" <?php checked('alpha_descending', $orderby); ?> class="tog"/>
						Alphabetical descending<br />
						<span> &raquo; A to Z.</span>
					</label>
				</p>

				<p>
					<label>
						<input name="orderby" type="radio" value="alpha_ascending" <?php checked('alpha_ascending', $orderby); ?> class="tog"/>
						Alphabetical ascending<br />
						<span> &raquo; Z to A.</span>
					</label>
				</p>
			</fieldset>

			<fieldset name="set2">
				<h3>Hidden Posts:</h3>

				<p>
					<label>
						Show hidden posts &amp; pages:
						<input name="showhidden" type="checkbox" <?php checked('on', $showhidden); ?> class="tog"/>
					</label>
				</p>
			</fieldset>

			<fieldset name="set3">
				<h3>Pages:</h3>

				<p>
					<label>
						<input name="showpages" type="radio" value="pages_none" <?php checked('pages_none', $showpages); ?> class="tog"/>
						Don't show pages<br />
					</label>
				</p>

				<p>
					<label>
						<input name="showpages" type="radio" value="pages_before" <?php checked('pages_before', $showpages); ?> class="tog"/>
						Show pages before posts<br />
					</label>
				</p>

				<p>
					<label>
						<input name="showpages" type="radio" value="pages_after" <?php checked('pages_after', $showpages); ?> class="tog"/>
						Show pages after posts<br />
					</label>
				</p>
			</fieldset>


			<div class="submit">

				<input type="submit" name="info_update" value="Update Options" />

			</div>

		</form>
	</div><?php
}


//hooks
add_filter('the_content', 'rs_sitemap', 2);
add_action('admin_menu', 'rs_sitemap_admin');

?>