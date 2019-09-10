<?php
if (! class_exists('WP_List_Table')) {
	require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * 管理ページに表示するテーブルのクラス
 */
class scsz_My_List_Table extends WP_List_Table
{

	
	// カラムの設定
	function get_columns()
	{

		$columns = array(
			'title' => __('Title', 'sc-simple-zazzle'),
			'short_code' => __('Short code', 'sc-simple-zazzle'),
			'feed_type' => __('Type', 'sc-simple-zazzle'),
			'feed_name' => __('Store name', 'sc-simple-zazzle').' / '.__('Correction ID', 'sc-simple-zazzle'),
			'feed_default_flg' => __('Custom HTML', 'sc-simple-zazzle') //HTMLカスタム
		);
		return $columns;
	}

	// テーブルに表示するデータの設定
	function prepare_items()
	{

		// sc_simple_zazzle_tableからデータを取得
		global $wpdb;
		$scsz_table_name = $wpdb->prefix . "sc_simple_zazzle_table";
		$scsz_all_feed_settings = $wpdb->get_results("SELECT * FROM " . $scsz_table_name);

		// 種別の日本語表記
		$scsz_feed_type_mapping = array(
			'store' => __('Store', 'sc-simple-zazzle'),
			'collections' => __('Collections', 'sc-simple-zazzle'),
			'market' => __('Market place', 'sc-simple-zazzle')
		);
		foreach ($scsz_all_feed_settings as &$scsz_obj_to_arr) {
			$scsz_obj_to_arr = json_decode(json_encode($scsz_obj_to_arr), true);
			$scsz_obj_to_arr['short_code'] = '[simple_zazzle id=' . $scsz_obj_to_arr['scid'] . ']';
			$scsz_obj_to_arr['feed_type'] = $scsz_feed_type_mapping[$scsz_obj_to_arr['feed_type']];
			$scsz_obj_to_arr['feed_default_flg'] = $scsz_obj_to_arr['feed_default_flg'] ? 'OFF' : 'ON';
		}

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array(
			$columns,
			$hidden,
			$sortable
		);
		$this->items = $scsz_all_feed_settings;
	}

	// デフォルトのカラム表示
	function column_default($item, $column_name)
	{
		switch ($column_name) {
			case 'title':
			case 'short_code':
			case 'feed_type':
			case 'feed_name':
			case 'feed_default_flg':
				return $item[$column_name];
			default:
				return print_r($item, true);
		}
	}

	// タイトル列のカラム表示
	function column_title($item)
	{
		$itemScid = $item['scid'];
		$scsz_view_colmun = $item['title'].'<form method="POST" name="duplication_scid_'.$itemScid.'" action="?page=simple-zazzle">'
			.'<input type="hidden" name="action" value="duplication" />'
			.'<input type="hidden" name="scid" value="'.$itemScid.'" />'
			.'</form>'
			.'<form method="POST" name="delete_scid_'.$itemScid.'" action="?page=simple-zazzle">'
			.'<input type="hidden" name="action" value="delete" />'
			.'<input type="hidden" name="scid" value="'.$itemScid.'" />'
			.'</form>';
		$actions = array(
			'edit' => sprintf('<a href="?page=simple-zazzle-edit&scid=%s">'.__('Edit').'</a>', $itemScid),
			'duplication' => sprintf(
			'<a href="javascript:duplication_scid_%s.submit()">'.__('Duplication', 'sc-simple-zazzle').'</a>', $itemScid),
			'delete' => sprintf(
			'<a href="javascript:delete_scid_%s.submit()">'.__('Delete').'</a>', $itemScid)
		);
		return sprintf('%1$s %2$s', $scsz_view_colmun, $this->row_actions($actions));
	}

	// ショートコード列のカラム表示
	function column_short_code($item)
	{
		$actions = array(
			'edit' => sprintf('<a class="text-copy" data-short-code=%s>'.__('Copy').'</a>', "'" . $item['short_code'] . "'")
		);
		return sprintf('%1$s %2$s', $item['short_code'], $this->row_actions($actions));
	}
}

function scsz_display_plugin_admin_page()
{

	// POSTデータがあれば設定を更新
	if (isset($_POST['affiliate_update'])) {
		$scsz_affiliate_agree = isset($_POST['scsz_affiliate_agree']) ? 1 : 0;
		update_option('scsz_affiliate_agree', $scsz_affiliate_agree);
		echo '<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong>'.__('The settings was saved.', 'sc-simple-zazzle').'</strong></p></div>'; // 設定を保存しました。
	}
	$scsz_agree_flg = get_option('scsz_affiliate_agree');

	if (isset($_POST['action']) && isset($_POST['scid'])) {
		if( $_POST['action'] == 'delete' ){
			scsz_delete_shortcode($_POST['scid']);
		}else if( $_POST['action'] == 'duplication' ){
			scsz_duplication_shortcode($_POST['scid']);
		}
	}

	$myListTable = new scsz_My_List_Table();
	echo '<div class="wrap aioseop_options_wrapper"><h1 class="wp-heading-inline">SC Simple Zazzle</h1><a class="page-title-action" href="?page=simple-zazzle-edit">'.__('Add New', 'sc-simple-zazzle').'</a>';
	echo '<div id="scsz-main-table" class="main-table"><h2>'.__('List of settings', 'sc-simple-zazzle').'</h2>';
	$myListTable->prepare_items();
	$myListTable->display();
	?>
<input type="hidden" id="outputCode">
<!-- ショートコードをクリップボードにコピーするためのボックス -->
<div class="postbox">
	<h2>
	<span><?php _e('About This Plugin.', 'sc-simple-zazzle'); ?></span>
	</h2>
	<div class="inside">
	<p><?php _e('Plugin author: ', 'sc-simple-zazzle'); ?><a href="https://sayoko-ct.com/">sayoko</a><br>
		<a href="https://sayoko-ct.com/sc-simple-zazzle/" target="_blank"><?php _e('This plugin page.', 'sc-simple-zazzle'); ?></a></p>
		<h3><?php _e('Report bugs', 'sc-simple-zazzle'); ?></h3>
		<p><?php _e('Please send bug reports and feature improvements suggestions on GitHub.', 'sc-simple-zazzle'); ?>&emsp;<a href="https://github.com/sayocode/simpleZazzle/issues/new" target="_blank">GitHub</a></p>
	</div>
</div>
</div>
</div>
<form method="post" action="">
	<table class="form-table">
		<tr id="agreeAffiliate">
			<td colspan="2"><label><?php _e('Use an affiliate', 'sc-simple-zazzle'); ?>&emsp; <input
						name="scsz_affiliate_agree" type="checkbox"
						id="scsz_affiliate_agree" value="1"
						<?php checked( 1, $scsz_agree_flg); ?> />
			</label></td>
		</tr>
	</table>
	<input type="hidden" name="affiliate_update" value="1" />
		<?php submit_button(); ?>
	</form>
	<script type="text/javascript">
		const copyMsg = "<?php echo __('Copied to clipboard.', 'sc-simple-zazzle'); ?>";
	</script>
<?php
}