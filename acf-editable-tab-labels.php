<?php

	/**
	 * Plugin Name:  Advanced Custom Fields - Editable Tab Labels
	 * Plugin URI:   https://github.com/orcadesign/acf-editable-tab-labels
	 * Description:  Enable editability of tab field labels in Advanced Custom Fields
	 * Version:      0.0.1
	 * Author:       Orca Design
	 * Author URI:   https://github.com/orcadesign/
	 */
	
	if(!defined('ABSPATH'))
	{
		exit;
	}
	
	add_action('wp_ajax_acf_rename_tab', function()
	{
		global $wpdb;

		if($wpdb->query("UPDATE {$wpdb->posts} SET post_title='" . esc_sql($_REQUEST['name']) . "' WHERE post_type='acf-field' AND post_name='" . esc_sql($_REQUEST['field']) . "'"))
		{
			echo json_encode(array("status" => "OK"));
		}
		else
		{
			echo json_encode(array("status" => "ERROR"));
		}

		wp_die();
	});

	add_action('admin_init', function()
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui');
		wp_enqueue_script('jquery-ui-dialog');

		wp_enqueue_style('wp-jquery-ui-dialog');
	});

	add_action('admin_head', function()
	{
		global $_wp_admin_css_colors;

		$color_scheme = get_user_option('admin_color'); ?>

		<div id="rename-tab-dialog" class="hidden" style="max-width: 800px;">
			<p>Please enter the tab's new name:</p>
			<input type="text" name="acf-tab-field-rename" class="widefat" placeholder="Tab Title..." />
			<p>
				<button type="button" class="button" onclick="return $('#rename-tab-dialog').dialog('close'), !1">Cancel</button>
				<button type="button" class="button button-primary" style="float: right;" onclick="return renameacftab.call(this), $('#rename-tab-dialog').dialog('close'), !1">Rename Tab</button>
			</p>
		</div>
		<style type="text/css">
			.acf-tab-button > .edit-tab-name
			{
				display: inline-block;
				margin: 0 0 0 .5em;
				vertical-align: middle;
				font-size: 1.2em;
				color: <?=$_wp_admin_css_colors[$color_scheme]->icon_colors['base']?>;
				transform: translateY(.1em);
			}
			.acf-tab-button > .edit-tab-name:hover
			{
				color: <?=$_wp_admin_css_colors[$color_scheme]->icon_colors['focus']?>;
			}
		</style>
		<script type="text/javascript">
			var currenttab = null;

			function renameacftab()
			{
				if(currenttab)
				{
					var field   = currenttab.data('key'),
						newname = String($('#rename-tab-dialog input[name="acf-tab-field-rename"]').val()).trim();

					if(field && newname)
					{
						currenttab.fadeTo(333, .5);

						$.ajax(
						{
							url:      '<?=admin_url('admin-ajax.php')?>',
							data:
							{
								action: 'acf_rename_tab',
								field:  currenttab.data('key'),
								name:   newname
							},
							dataType: 'json',
							type:     'post',
							success:  function(data)
							{
								currenttab.fadeTo(1, 333);

								if(data.status && data.status == 'OK')
								{
									currenttab
										.find('.edit-tab-label')
										.html(newname);
								}
								else
								{
									alert('Something went wrong. Please try again.');
								}

								currenttab = null;
							}
						});
					}
				}
			}

			jQuery(function()
			{
				$('#rename-tab-dialog').dialog(
				{
					title:         'Rename Tab',
					dialogClass:   'wp-dialog',
					autoOpen:      false,
					draggable:     false,
					width:         300,
					modal:         true,
					resizable:     false,
					closeOnEscape: true,
					position:
					{
						my: "center",
						at: "center",
						of: window
					},
					open:          function()
					{
						if(currenttab)
						{
							$('#rename-tab-dialog input[name="acf-tab-field-rename"]').val(currenttab.find('.edit-tab-label').text().trim());
						}

						$('.ui-widget-overlay').bind('click', function()
						{
							$('#rename-tab-dialog').dialog('close');
						})
					},
					create:        function()
					{
						$('.ui-dialog-titlebar-close').addClass('ui-button');
					}
				});

				$('.acf-tab-button').each(function()
				{
					var tab = $(this);

					tab.contents().wrap('<span class="edit-tab-label">');

					$('<span class="dashicons dashicons-edit edit-tab-name" title="Rename Tab"></span>')
						.appendTo(tab)
						.on('click', function(e)
						{
							currenttab = tab;

							e.preventDefault();

							$('#rename-tab-dialog').dialog('open');

							return false;
						});
				});
			});
		</script><?
	}, 5);

	function acf_get_tabs($post_id = NULL, $format = true)
	{
		$tabs = array(array());

		if(!$post_id)
		{
			global $post;
		}
		else
		{
			$post = get_post($post_id);
		}

		if($post)
		{
			$groups = acf_get_field_groups(array
			(
				"post_id" => $post->ID
			));

			if(!empty($groups))
			{
				$curgroup = 0;

				foreach($groups as $group)
				{
					$fields = acf_get_fields($group['key']);

					if(!empty($fields))
					{
						if($format)
						{
							$curtab = NULL;

							foreach($fields as &$field)
							{
								if($field['type'] == 'tab')
								{
									if(isset($field['endpoint']) and $field['endpoint'])
									{
										$curtab = NULL;
									}
									else
									{
										$curtab = &$field;
									}
								}
								else if($curtab)
								{
									if(!isset($curtab['sub_fields']))
									{
										$curtab['sub_fields'] = array();
									}

									$curtab['sub_fields'][] = $field;
								}
							}

							foreach($fields as $field)
							{
								if($field['type'] == 'tab')
								{
									if(!isset($tabs[$curgroup]))
									{
										$tabs[$curgroup] = array();
									}

									$tabs[$curgroup][] = $field;

									if(isset($field['endpoint']) and $field['endpoint'])
									{
										$curgroup ++;
									}
								}
							}
						}
						else
						{
							foreach($fields as $field)
							{
								if($field['type'] == 'tab')
								{
									if(!isset($tabs[$curgroup]))
									{
										$tabs[$curgroup] = array();
									}

									$tabs[$curgroup][] = $field['label'];

									if(isset($field['endpoint']) and $field['endpoint'])
									{
										$curgroup ++;
									}
								}
							}
						}
					}

					$curgroup ++;
				}
			}

			return array_filter($tabs);
		}

		return array();
	}
