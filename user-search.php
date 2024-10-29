<?php

class acf_field_usersearch extends acf_field{
	
	function __construct(){
		$this->name = 'usersearch';
		$this->label = __("Search") . " " . __("User",'acf');
		$this->category = __("Relational",'acf');
		$this->defaults = array(
			'role' 			=> 'all',
			'field_type' 	=> 'select',
			'allow_null' 	=> 0,
		);
		
		parent::__construct();
	}
	
	function format_value_for_api( $value, $post_id, $field ){
		if( !$value || $value == 'null' ){
			return false;
		}
		
		$is_array = true;
		
		if( !is_array($value) ){
			$is_array = false;
			$value = array( $value );
		}
		
		foreach( $value as $k => $v ){
			$user_data = get_userdata( $v );
			
			if( !is_object($user_data) ){
				unset( $value[$k] );
				continue;
			}

			$value[ $k ] = array();
			$value[ $k ]['ID'] = $v;
			$value[ $k ]['user_firstname'] = $user_data->user_firstname;
			$value[ $k ]['user_lastname'] = $user_data->user_lastname;
			$value[ $k ]['nickname'] = $user_data->nickname;
			$value[ $k ]['user_nicename'] = $user_data->user_nicename;
			$value[ $k ]['display_name'] = $user_data->display_name;
			$value[ $k ]['user_email'] = $user_data->user_email;
			$value[ $k ]['user_url'] = $user_data->user_url;
			$value[ $k ]['user_registered'] = $user_data->user_registered;
			$value[ $k ]['user_description'] = $user_data->user_description;
			$value[ $k ]['user_avatar'] = get_avatar( $v );
		}
		
		if( !$is_array && isset($value[0]) ){
			$value = $value[0];
		}

		return $value;
	}

	function input_admin_head(){
		if( ! function_exists( 'get_editable_roles' ) ){
			require_once( ABSPATH . '/wp-admin/includes/user.php' ); 
		}
	}
	
	function create_field( $field ){
		if( ! function_exists( 'get_editable_roles' ) ){
			require_once( ABSPATH . '/wp-admin/includes/user.php' ); 
		}
		
   		$options = array(
			'post_id' => get_the_ID(),
		);
		
		$args = array();
		$editable_roles = get_editable_roles();
		
		if( !empty($field['role']) ){
			if( ! in_array('all', $field['role']) ){
				$role__in = array();
				foreach( $editable_roles as $role => $role_info ){
					if( !in_array($role, $field['role']) ){
						unset( $editable_roles[ $role ] );
					}else{
						$role__in[] = $role;
					}
				}
				$args['role__in'] = $role__in;
			}
		}
		
		$args = apply_filters('acf/fields/user/query', $args, $field, $options['post_id']);
		$args = apply_filters('acf/fields/user/query/name=' . $field['_name'], $args, $field, $options['post_id'] );
		$args = apply_filters('acf/fields/user/query/key=' . $field['key'], $args, $field, $options['post_id'] );

		$users = get_users( $args );

		if( !empty($users) && !empty($editable_roles) ){
			$field['choices'] = array();
			
			foreach( $editable_roles as $role => $role_info ){
				$this_users = array();
				
				$keys = array_keys($users);
				foreach( $keys as $key ){
					if( in_array($role, $users[ $key ]->roles) ){
						$this_users[] = $users[ $key ];
						unset( $users[ $key ] );
					}
				}
				
				if( empty($this_users) ){
					continue;
				}
				
				$label = translate_user_role( $role_info['name'] );
				
				$field['choices'][ $label ] = array();
				
				foreach( $this_users as $user ){
					$field['choices'][ $label ][ $user->ID ] = ucfirst( $user->display_name ) . " (".$user->first_name." ".$user->last_name." - ".$user->user_email.")";
				}
			}
		}		
		
		$field['type'] = 'select';
		$field['class'] = "select2search";
		if( $field['field_type'] == 'multi_select' ){
			$field['multiple'] = 1;
			$field['class'] .= ' multiple';
		}
		
		do_action('acf/create_field', $field);
	}

	function input_admin_enqueue_scripts(){
		wp_enqueue_script( 'acf-input-select2', plugins_url( '/js/select2.min.js', __FILE__ ), array('acf-input'), 1, 1 );
		wp_enqueue_script( 'acf-input-select2-init', plugins_url( '/js/field.js', __FILE__ ), array('acf-input-select2'), 1, 1 );
		wp_localize_script( 'acf-input-select2-init', 'langs', array(
			'placeholder' => __('Search Users').'...'
		));
		wp_enqueue_style( 'acf-input-select2', plugins_url( '/css/select2.min.css', __FILE__ ), array('acf-input'), 1 );
	}
	
	function create_options( $field ){
		$key = $field['name']; ?>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e( "Filter by role", 'acf' ); ?></label>
			</td>
			<td><?php
				$choices = array('all' => __('All', 'acf'));
				$editable_roles = get_editable_roles();

				foreach( $editable_roles as $role => $details ){
					$choices[$role] = translate_user_role( $details['name'] );
				}

				do_action('acf/create_field', array(
					'type' => 'select',
					'name' => 'fields[' . $key . '][role]',
					'value'	=> $field['role'],
					'choices' => $choices,
					'multiple' => '1',
				)); ?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Field Type",'acf'); ?></label>
			</td>
			<td>
				<?php	
				do_action('acf/create_field', array(
					'type'	=>	'select',
					'name'	=>	'fields[' . $key . '][field_type]',
					'value'	=>	$field['field_type'],
					'choices' => array(
						__("Multiple Values",'acf') => array(
							'multi_select' => __('Multi Select', 'acf')
						),
						__("Single Value",'acf') => array(
							'select' => __('Select', 'acf')
						)
					)
				));
				?>
			</td>
		</tr>
		<tr class="field_option field_option_<?php echo $this->name; ?>">
			<td class="label">
				<label><?php _e("Allow Null?",'acf'); ?></label>
			</td>
			<td><?php
				do_action('acf/create_field', array(
					'type'	=>	'radio',
					'name'	=>	'fields['.$key.'][allow_null]',
					'value'	=>	$field['allow_null'],
					'choices'	=>	array(
						1	=>	__("Yes",'acf'),
						0	=>	__("No",'acf'),
					),
					'layout'	=>	'horizontal',
				)); ?>
			</td>
		</tr><?php
	}
	
	function update_value( $value, $post_id, $field ){
		if( is_array($value) && isset($value['ID']) ){
			$value = $value['ID'];	
		}
		
		if( is_object($value) && isset($value->ID) ){
			$value = $value->ID;
		}
		
		return $value;
	}	
}

new acf_field_usersearch();