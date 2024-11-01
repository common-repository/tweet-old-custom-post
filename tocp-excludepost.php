<?php
require_once( 'tweet-old-cpost.php' );
require_once( 'tocp-core.php' );
require_once( 'Include/tocp-oauth.php' );
require_once( 'xml.php' );

if ( ! function_exists( "mysql_real_escape_string" ) ) {
	function mysql_real_escape_string( $str ) {
		return mysql_escape_string( $str );
	}
}
function tocp_exclude() {
	if ( current_user_can( 'manage_options' ) ) {
		$message          = null;
		$message_updated  = __( "Tweet Old Custom Post Options Updated.", 'TweetOldCustomPost' );
		$response         = null;
		$records_per_page = 20;
		$omit_cat         = get_option( 'tocp_opt_omit_cats' );
		$omitCustCats     = get_option( 'tocp_opt_omit_cust_cats' );
		$tocp_opt_post_type     = get_option( 'tocp_opt_post_type' );
		$update_text      = "Exclude Selected";
		$search_term      = "";
		$ex_filter        = "all";
		$cat_filter       = 0;
		global $wpdb;
		if ( ( ! isset( $_GET["paged"] ) ) && ( ! isset( $_POST["delids"] ) ) ) {
			$exposts = get_option( 'tocp_opt_excluded_post' );
		} else {
			$exposts = $_POST["delids"];
		}
		$exposts = preg_replace( '/,,+/', ',', $exposts );
		if ( substr( $exposts, 0, 1 ) == "," ) {
			$exposts = substr( $exposts, 1, strlen( $exposts ) );
		}
		if ( substr( $exposts, - 1, 1 ) == "," ) {
			$exposts = substr( $exposts, 0, strlen( $exposts ) - 1 );
		}
		$excluded_posts = explode( ",", $exposts );
		if ( ! isset( $_GET['paged'] ) ) {
			$_GET['paged'] = 1;
		}
		if ( isset( $_POST["excludeall"] ) ) {
			if ( substr( $_POST["delids"], 0, - 1 ) == "" ) {
				print( '
			<div id="message" style="margin-top:30px" class="updated fade">
				<p>' . __( 'No post selected please select a post to be excluded.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
			update_option( 'tocp_opt_excluded_post', $exposts );
			} else {
				update_option( 'tocp_opt_excluded_post', $exposts );
				print( '
			<div id="message" style="margin-top:30px" class="updated fade">
				<p>' . __( 'Posts excluded successfully.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
			}
		}
	/*	print( '
			<div id="help" style="overflow: hidden;padding-left:15px; font-size: 14px; margin-top: 25px; background-color: rgb(239, 239, 239);">
				<p>' . __( 'This page shows all the post from your <a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=TweetOldCustomPost">selected categories</a>.<br /> All the post excluded will not be tweeted even if the category is selected.', 'TweetOldCustomPost' ) . '</p>
			</div>' ); */
		print( '
			<div id="help" style="overflow: hidden;padding-left:15px; font-size: 14px; margin-top: 25px;margin-bottom:25px; background-color: rgb(239, 239, 239);">
				<p>' . __( 'You have selected following POST IDs to be excluded from tweeting: <span id="excludeList" style="font-weight:bold;font-style:italic;"></span>.<br /> <strong>Note:</strong> If you have made any change and dint hit "Exclude Selected" button changes will not be saved. Please hit "Exclude Selected" button after making any changes.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
		if($tocp_opt_post_type == 'both'){
			$post_types_included = "post_type = 'post' or post_type = 'page'";
		}elseif($tocp_opt_post_type == 'post'){
			$post_types_included = "post_type = 'post'";
		}else{
			$post_types_included = "post_type = 'page'";
		}
		$sql = "SELECT p.ID,p.post_title,p.post_date,u.user_nicename,p.guid,p.post_type FROM $wpdb->posts p join  $wpdb->users u on p.post_author=u.ID WHERE (" . $post_types_included . " )
                  AND post_status = 'publish'";
                  $omit_cat = trim($omit_cat);
                  $omitCustCats = trim($omitCustCats);
		if ( isset( $_POST["setFilter"] ) ) {
			if ( $_POST["cat"] != 0 ) {
				$cat_filter = $_POST["cat"];
				$cat_filter = mysql_real_escape_string( $cat_filter );
				$sql        = $sql . " and p.ID IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id=" . $cat_filter . ")";

			} elseif ( $omit_cat != '' && $omitCustCats != '' ) {
				//$sql        = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omit_cat . ',' . $omitCustCats . "))";
				$sql        = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omit_cat . ',' . $omitCustCats . "))";
				$cat_filter = 0;
			} elseif ( $omit_cat != '' ) {
			//	$sql        = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omit_cat . "))";
				$sql        = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omit_cat . "))";
				$cat_filter = 0;
			} elseif($omitCustCats != '') {
				//$sql        = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omitCustCats . "))";
				$sql        = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omitCustCats . "))";
				$cat_filter = 0;
			}
			if ( $_POST["selFilter"] == "excluded" ) {
				$sql         = $sql . " and p.ID IN (" . $exposts . ")";
				$update_text = "Update";
				$ex_filter   = "excluded";
			}

		} else {
			$omit_cat = trim($omit_cat);
			$omitCustCats  = trim($omitCustCats);
			if ( $omit_cat != '' && $omitCustCats != '' ) {
				//$sql = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omit_cat . ',' . $omitCustCats . "))";
				$sql = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' AND tt.term_id IN (" . $omit_cat . ',' . $omitCustCats . "))";
			} elseif ( $omit_cat != '' ) {
				//$sql = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' ";
				$sql = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' ";
				$omit_cat = trim($omit_cat);
				if(!empty($omit_cat)){
					$sql.= " AND tt.term_id IN (" . $omit_cat . "))";
				}else{
					$sql.= ")";
				}
			} elseif(!empty($omitCustCats )) {
				$omitCustCats  = trim($omitCustCats);
				//$sql = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' ";
				$sql = $sql . " and p.ID NOT IN ( SELECT tr.object_id FROM " . $wpdb->prefix . "term_relationships AS tr INNER JOIN " . $wpdb->prefix . "term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = 'category' ";
				if(!empty($omitCustCats )){
					$sql.= " AND tt.term_id IN (" . $omitCustCats . "))";
				}else{
					$sql.= ")";
				}
			}

		}
		if ( isset( $_POST["s"] ) ) {
			if ( trim( $_POST["s"] ) != "" ) {
				$_s          = $_POST["s"];
				$_s          = mysql_real_escape_string( $_s );
				$sql         = $sql . " and post_title like '%" . trim( $_s ) . "%'";
				$search_term = trim( $_s );
			}
		}

		$sql   = $sql . " order by post_date desc";

		$posts = $wpdb->get_results( $sql );
		$from       = $_GET["paged"] * $records_per_page - $records_per_page;
		$to         = min( $_GET['paged'] * $records_per_page, count( $posts ) );
		$post_count = count( $posts );
		$ex = 0;
		$excludeList = array();
		for ( $j = 0; $j < $post_count; $j ++ ) {
			if ( in_array( $posts[ $j ]->ID, $excluded_posts ) ) {
				$excludeList[ $ex ] = $posts[ $j ]->ID;
				$ex                 = $ex + 1;
			}
		}
		if ( count( $excludeList ) > 0 ) {
			$exposts = implode( ",", $excludeList );
		}
		print( '<form id="tocp_TweetOldCustomPost" name="tocp_TweetOldCustomPost" action="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=ExcludePosts" method="post"><input type="hidden" name="delids" id="delids" value="' . $exposts . '" /><input type="submit" id="pageit" name="pageit" style="display:none" value="" /> ' );
		print( '<div class="tablenav"><div class="alignleft actions">' );
		print( '<input type="submit" class="button-secondary" name="excludeall" value="' . __( $update_text, 'TweetOldCustomPost' ) . '" />' );
		print( '<select name="selFilter" id="selFilter" style="width:100px"><option value="all" ' . tocp_opt_optionselected( "all", $ex_filter ) . '> All </option><option value="excluded" ' . tocp_opt_optionselected( "excluded", $ex_filter ) . '> Excluded </option></select>' );
		if ( $omit_cat != '' && $omitCustCats != '' ) {
//			echo "<br/>option1<br/>";
			$dropdown_options = array(
				'show_option_all' => __( 'Selected Categories' ),
				'exclude'         => $omit_cat . ',' . $omitCustCats,
				'selected'        => $cat_filter
			);
		} elseif ( $omit_cat != '' ) {
	//		echo "<br/>option2<br/>";
	//		echo "<br/>omit cat $omit_cat<br/>";
//			echo "<br/>cat_filter: $cat_filter<br/>";
			$dropdown_options = array(
				'show_option_all' => __( 'Selected Categories' ),
				'exclude'         => $omit_cat,
				'selected'        => $cat_filter
			);
		} else {
//			echo "<br/>option3<br/>";
			$dropdown_options = array(
				'show_option_all' => __( 'Selected Categories' ),
				'exclude'         => $omitCustCats,
				'selected'        => $cat_filter
			);
		}

	//	var_dump($dropdown_options);

	//	echo "<br/>wp_dropdown_categories<br/>";
		wp_dropdown_categories( $dropdown_options );
	//	echo "<br/>";
	//	wp_dropdown_categories( 'show_count=1&hierarchical=1' );

		print( '<input type="submit" class="button-secondary" name="setFilter" value="' . __( 'Filter', 'TweetOldCustomPost' ) . '" />' );
		print( '<p class="search-box" style="margin:0px">
	<input type="text" id="post-search-input" name="s" value="' . $search_term . '" />
	<input type="submit" value="Search Posts" name="search" class="button" />
</p>' );
		print( '</div>' );
		if ( count( $posts ) > 0 ) {
			$page_links = paginate_links( array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => __( '&laquo;' ),
				'next_text' => __( '&raquo;' ),
				'total'     => ceil( count( $posts ) / $records_per_page ),
				'current'   => $_GET['paged']
			) );
			if ( $page_links ) {
				print( '<div class="tablenav-pages">' );
				$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
					number_format_i18n( ( $_GET['paged'] - 1 ) * $records_per_page + 1 ),
					number_format_i18n( min( $_GET['paged'] * $records_per_page, count( $posts ) ) ),
					number_format_i18n( count( $posts ) ),
					$page_links
				);
				echo $page_links_text;
				print( '</div>' );
			}
			print( '</div>' );//tablenav div
			print( '	<div class="wrap">
				<table class="widefat fixed">
					<thead>
					<tr>
						<th class="manage-column column-cb check-column"><input name="headchkbx" onchange="javascript:checkedAll();" type="checkbox" value="checkall"/></th>
						<th>No.</th>
						<th>Id</th>
						<th>Post Title</th>
						<th>Author</th>
						<th>Post Date</th>
                                                <th>Categories</th>
                                                <th>Post Type</th>
					</tr>
					</thead>
					<tbody>
		' );
			for ( $i = $from; $i < $to; $i ++ ) {
				$categories = get_the_category( $posts[ $i ]->ID );
				if ( ! empty( $categories ) && count($categories ) > 0 ) {
					$out = array();
					foreach ( $categories as $c ) {
						$out[] = "<a href='edit.php?post_type=" . $posts[ $i ]->post_type . "&amp;category_name={$c->slug}'> " . esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'category', 'display' ) ) . "</a>";
					}
					$cats = join( ', ', $out );
				} else {
					$cats = 'Uncategorized';
				}
				if ( in_array( $posts[ $i ]->ID, $excluded_posts ) ) {
					$checked = "Checked";
					$bgcolor = "#FFCC99";
				} else {
					$checked = "";
					$bgcolor = "#FFF";
				}
				print( '

				<tr style="background-color:' . $bgcolor . ';">
					<th class="check-column">
						<input type="checkbox" name="chkbx" id="del' . $posts[ $i ]->ID . '" onchange="javascript:managedelid(this,\'' . $posts[ $i ]->ID . '\');" value="' . $posts[ $i ]->ID . '" ' . $checked . '/>
					</th>
					<td>
						' . ( $i + 1 ) . '
					</td>
					<td>
						' . $posts[ $i ]->ID . '
					</td>
					<td>
						<a href=' . $posts[ $i ]->guid . ' target="_blank">' . $posts[ $i ]->post_title . '</a>
					</td>
					<td>
                                            ' . $posts[ $i ]->user_nicename . '
                                        </td>
                                        <td>
                                            ' . $posts[ $i ]->post_date . '
                                        </td>
                                        <td>
                                            ' . $cats . '
                                        </td>
                                        <td>
                                            ' . $posts[ $i ]->post_type . '
                                        </td>
				</tr>

			' );
			}
			print( '
				</tbody>
				</table>
			</div>
		' );
			print( '<div class="tablenav"><div class="alignleft actions"><input type="submit" class="button-secondary" name="excludeall" value="' . __( $update_text, 'TweetOldCustomPost' ) . '" /></div>' );
			if ( $page_links ) {
				print( '<div class="tablenav-pages">' );
				$page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
					number_format_i18n( ( $_GET['paged'] - 1 ) * $records_per_page + 1 ),
					number_format_i18n( min( $_GET['paged'] * $records_per_page, count( $posts ) ) ),
					number_format_i18n( count( $posts ) ),
					$page_links
				);
				echo $page_links_text;
				print( '</div>' );
			}
			print( '</div>' );
			print( '
			<script language="javascript">


jQuery(function() {
  jQuery(".page-numbers").click(function(e){
         jQuery("#tocp_TweetOldCustomPost").attr("action",jQuery(this).attr("href"));
         e.preventDefault();
         jQuery("#pageit").click();
    });// page number click end
 });//jquery document.ready end

                                function setExcludeList(exlist)
                                {
                                    jQuery("#excludeList").html("\"" + exlist + "\"");
                                }


                                function managedelid(ctrl,id)
				{

					var delids = document.getElementById("delids").value;
					if(ctrl.checked)
					{
						delids=addId(delids,id);
					}
					else
					{
						delids=removeId(delids,id);
					}
					document.getElementById("delids").value=delids;
                                        setExcludeList(delids);
				}

function removeId(list, value) {
  list = list.split(",");
if(list.indexOf(value) != -1)
  list.splice(list.indexOf(value), 1);
  return list.join(",");
}


function addId(list,value)
{
list = list.split(",");
if(list.indexOf(value) == -1)
    list.push(value);
return list.join(",");
}

				function checkedAll() {
					var ischecked=document.tocp_TweetOldCustomPost.headchkbx.checked;
					var delids="";
                                        for (var i = 0; i < document.tocp_TweetOldCustomPost.chkbx.length; i++) {
        				document.tocp_TweetOldCustomPost.chkbx[i].checked = ischecked;
        				if(ischecked)
        					delids=delids+document.tocp_TweetOldCustomPost.chkbx[i].value+",";
                                         }
                                         document.getElementById("delids").value=delids;
                                }

                        setExcludeList("' . $exposts . '");

			</script>
		' );
		} else {
			print( '</div>' );//tablenav div
			print( '
			<div id="message" style="margin-top:30px" class="updated fade">
				<p>' . __( 'No Posts found. Review your search or filter criteria/term.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
		}
		print( '</form>' );
	} else {
		print( '
			<div id="message" class="updated fade">
				<p>' . __( 'You do not have enough permission to set the option. Please contact your admin.', 'TweetOldCustomPost' ) . '</p>
			</div>' );
	}
}

?>