<?php
if(class_exists('WP_Users_List_Table')) {
	class MyPlugin_Users_List_Table extends WP_Users_List_Table {

		/**
		 * Generate HTML for a single row on the users.php admin panel.
		 *
		 * @param WP_User $user_object The current user object.
		 * @param string $style Deprecated. Not used.
		 * @param string $role Deprecated. Not used.
		 * @param int $numposts Optional. Post count to display for this user. Defaults
		 *                             to zero, as in, a new user has made zero posts.
		 * @return string Output for a single row.
		 * @since 4.4.0 The `$role` parameter was deprecated.
		 *
		 * @since 3.1.0
		 * @since 4.2.0 The `$style` parameter was deprecated.
		 */
		public function single_row($user_object, $style = '', $role = '', $numposts = 0) {

			if(!($user_object instanceof WP_User)) {
				$user_object = get_userdata((int)$user_object);
			}
			$user_object->filter = 'display';
			$email = $user_object->user_email;

			if($this->is_site_users) {
				$url = "site-users.php?id={$this->site_id}&amp;";
			}
			else {
				$url = 'users.php?';
			}

			$user_roles = $this->get_role_list($user_object);

			// Set up the hover actions for this user.
			$actions = array();
			$checkbox = '';
			$super_admin = '';

			if(is_multisite() && current_user_can('manage_network_users')) {
				if(in_array($user_object->user_login, get_super_admins(), true)) {
					$super_admin = ' &mdash; ' . __('Super Admin');
				}
			}

			// Check if the user for this row is editable.
			if(current_user_can('list_users')) {
				// Set up the user editing link.
				$edit_link = esc_url(
					add_query_arg(
						'wp_http_referer',
						urlencode(wp_unslash($_SERVER['REQUEST_URI'])),
						get_edit_user_link($user_object->ID)
					)
				);

				if(current_user_can('edit_user', $user_object->ID)) {
					// CUSTOMISED: Don't show for admins if the current user is a non-admin
					if(in_array('administrator', $user_object->roles) && !current_user_can('administrator')) {
						$edit = "<strong>{$user_object->user_login}{$super_admin}</strong><br />";
					}
					else {
						$edit = "<strong><a href=\"{$edit_link}\">{$user_object->user_login}</a>{$super_admin}</strong><br />";
					}
					$actions['edit'] = '<a href="' . $edit_link . '">' . __('Edit') . '</a>';
				}
				else {
					$edit = "<strong>{$user_object->user_login}{$super_admin}</strong><br />";
				}

				if(!is_multisite()
					&& get_current_user_id() !== $user_object->ID
					&& current_user_can('delete_user', $user_object->ID)
				) {
					$actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url("users.php?action=delete&amp;user=$user_object->ID", 'bulk-users') . "'>" . __('Delete') . '</a>';
				}

				if(is_multisite()
					&& current_user_can('remove_user', $user_object->ID)
				) {
					$actions['remove'] = "<a class='submitdelete' href='" . wp_nonce_url($url . "action=remove&amp;user=$user_object->ID", 'bulk-users') . "'>" . __('Remove') . '</a>';
				}

				// Add a link to the user's author archive, if not empty.
				$author_posts_url = get_author_posts_url($user_object->ID);
				if($author_posts_url) {
					$actions['view'] = sprintf(
						'<a href="%s" aria-label="%s">%s</a>',
						esc_url($author_posts_url),
						/* translators: %s: Author's display name. */
						esc_attr(sprintf(__('View posts by %s'), $user_object->display_name)),
						__('View')
					);
				}

				// Add a link to send the user a reset password link by email.
				if(get_current_user_id() !== $user_object->ID
					&& current_user_can('edit_user', $user_object->ID)
				) {
					$actions['resetpassword'] = "<a class='resetpassword' href='" . wp_nonce_url("users.php?action=resetpassword&amp;users=$user_object->ID", 'bulk-users') . "'>" . __('Send password reset') . '</a>';
				}

				/**
				 * Filters the action links displayed under each user in the Users list table.
				 *
				 * @param string[] $actions An array of action links to be displayed.
				 *                              Default 'Edit', 'Delete' for single site, and
				 *                              'Edit', 'Remove' for Multisite.
				 * @param WP_User $user_object WP_User object for the currently listed user.
				 * @since 2.8.0
				 *
				 */
				$actions = apply_filters('user_row_actions', $actions, $user_object);

				// Role classes.
				$role_classes = esc_attr(implode(' ', array_keys($user_roles)));

				// Set up the checkbox (because the user is editable, otherwise it's empty).
				$checkbox = sprintf(
					'<label class="screen-reader-text" for="user_%1$s">%2$s</label>' .
					'<input type="checkbox" name="users[]" id="user_%1$s" class="%3$s" value="%1$s" />',
					$user_object->ID,
					/* translators: Hidden accessibility text. %s: User login. */
					sprintf(__('Select %s'), $user_object->user_login),
					$role_classes
				);
				// CUSTOMISED: Don't show for admins if the current user is a non-admin
				if(in_array('administrator', $user_object->roles) && !current_user_can('administrator')) {
					$checkbox = '';
				}
			}
			else {
				$edit = "<strong>{$user_object->user_login}{$super_admin}</strong>";
			}

			$avatar = get_avatar($user_object->ID, 32);

			// Comma-separated list of user roles.
			$roles_list = implode(', ', $user_roles);

			$row = "<tr id='user-$user_object->ID'>";

			list($columns, $hidden, $sortable, $primary) = $this->get_column_info();

			foreach($columns as $column_name => $column_display_name) {
				$classes = "$column_name column-$column_name";
				if($primary === $column_name) {
					$classes .= ' has-row-actions column-primary';
				}
				if('posts' === $column_name) {
					$classes .= ' num'; // Special case for that column.
				}

				if(in_array($column_name, $hidden, true)) {
					$classes .= ' hidden';
				}

				$data = 'data-colname="' . esc_attr(wp_strip_all_tags($column_display_name)) . '"';

				$attributes = "class='$classes' $data";

				if('cb' === $column_name) {
					$row .= "<th scope='row' class='check-column'>$checkbox</th>";
				}
				else {
					$row .= "<td $attributes>";
					switch($column_name) {
						case 'username':
							$row .= "$avatar $edit";
							break;
						case 'name':
							if($user_object->first_name && $user_object->last_name) {
								$row .= sprintf(
								/* translators: 1: User's first name, 2: Last name. */
									_x('%1$s %2$s', 'Display name based on first name and last name'),
									$user_object->first_name,
									$user_object->last_name
								);
							}
							elseif($user_object->first_name) {
								$row .= $user_object->first_name;
							}
							elseif($user_object->last_name) {
								$row .= $user_object->last_name;
							}
							else {
								$row .= sprintf(
									'<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">%s</span>',
									/* translators: Hidden accessibility text. */
									_x('Unknown', 'name')
								);
							}
							break;
						case 'email':
							$row .= "<a href='" . esc_url("mailto:$email") . "'>$email</a>";
							break;
						case 'role':
							$row .= esc_html($roles_list);
							break;
						case 'posts':
							if($numposts > 0) {
								$row .= sprintf(
									'<a href="%s" class="edit"><span aria-hidden="true">%s</span><span class="screen-reader-text">%s</span></a>',
									"edit.php?author={$user_object->ID}",
									$numposts,
									sprintf(
									/* translators: Hidden accessibility text. %s: Number of posts. */
										_n('%s post by this author', '%s posts by this author', $numposts),
										number_format_i18n($numposts)
									)
								);
							}
							else {
								$row .= 0;
							}
							break;
						default:
							/**
							 * Filters the display output of custom columns in the Users list table.
							 *
							 * @param string $output Custom column output. Default empty.
							 * @param string $column_name Column name.
							 * @param int $user_id ID of the currently-listed user.
							 * @since 2.8.0
							 *
							 */
							$row .= apply_filters('manage_users_custom_column', '', $column_name, $user_object->ID);
					}

					if($primary === $column_name) {
						$row .= $this->row_actions($actions);
					}
					$row .= '</td>';
				}
			}
			$row .= '</tr>';

			return $row;
		}
	}
}
