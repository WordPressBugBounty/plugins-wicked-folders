<form action="" method="post">
    <input type="hidden" name="action" value="wicked_folders_save_settings" />
    <?php wp_nonce_field( 'wicked_folders_save_settings', 'nonce' ); ?>
    <h2><?php _e( 'General', 'wicked-folders' ); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <?php _e( 'Enable folders for:', 'wicked-folders' ); ?>
            </th>
            <td>
                <?php foreach ( $post_types as $post_type ) : ?>
                    <?php
                        if ( ! $is_pro_active && in_array( $post_type->name, $pro_post_types ) ) continue;
                    ?>
                    <label>
                        <input type="checkbox" name="post_type[]" value="<?php echo esc_attr( $post_type->name ); ?>"<?php if ( in_array( $post_type->name, $enabled_posts_types ) ) echo ' checked="checked"'; ?>/>
                        <?php echo esc_html( $post_type->label ); ?>
                    </label>
                    <br />
                <?php endforeach; ?>
                <?php if ( ! $is_pro_active && Wicked_Folders::is_upsell_enabled() ) : ?>
                    <?php foreach ( $post_types as $post_type ) : ?>
                        <?php
                            if ( ! in_array( $post_type->name, $pro_post_types ) ) continue;
                        ?>
                        <label>
                            <input type="checkbox" name="post_type[]" value="<?php echo esc_attr( $post_type->name ); ?>" disabled="disabled" />
                            <?php echo esc_html( $post_type->label ); ?>
                        </label>
                        <br />
                    <?php endforeach; ?>
                    <label>
                        <input type="checkbox" name="post_type[]" value="wf_plugin" disabled="disabled" />
                        <?php _e( 'Plugins', 'wicked-folders' ); ?>
                    </label>
                    <br />
                    <label>
                        <input type="checkbox" name="post_type[]" value="wf_user" disabled="disabled" />
                        <?php _e( 'Users', 'wicked-folders' ); ?>
                    </label>
                    <br />
                    <?php if ( $is_gravity_forms_active ) : ?>
                        <label>
                            <input type="checkbox" name="post_type[]" value="wf_gf_entry" disabled="disabled" />
                            <?php _e( 'Gravity Forms Entries', 'wicked-folders' ); ?>
                        </label>
                        <br />
                        <label>
                            <input type="checkbox" name="post_type[]" value="wf_gf_form" disabled="disabled" />
                            <?php _e( 'Gravity Forms Forms', 'wicked-folders' ); ?>
                        </label>
                        <br />
                    <?php endif; ?>
                    <p><?php _e( '<a href="https://wickedplugins.com/plugins/wicked-folders/?utm_source=core_settings&utm_campaign=wicked_folders&utm_content=post_types" target="_blank">Upgrade to Wicked Folders Pro</a> to manage media, users, plugins, and more using folders.' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="show_item_counts" value="1"<?php if ( $show_item_counts ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Show number of items in each folder', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When checked (default), the number of items assigned to each folder is displayed next to the folder's name.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="show_unassigned_folder" value="1"<?php if ( $show_unassigned_folder ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Show unassigned items folder', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When checked (default), the 'Unassigned Items' folder will always be shown in the folder pane.  When left unchecked, the 'Unassigned Items' folder will appear as a child folder within 'Dynamic Folders'.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="show_folder_search" value="1"<?php if ( $show_folder_search ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Show folder search', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When checked (default), a search field is displayed above the folder tree that allows you to search folders by name.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="show_breadcrumbs" value="1"<?php if ( $show_breadcrumbs ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Show folder breadcrumbs', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( 'Displays a breadcrumb trail at the top of post lists.', 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="show_hierarchy_in_folder_column" value="1"<?php if ( $show_hierarchy_in_folder_column ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Show folder hierarchy in folder column', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When unchecked (default), folders will be displayed as a comma-separated list in the folder column that appears in post lists.  When checked, a breadcrumb path will be displayed showing the hierarchy of each folder the item is assigned to.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="include_children" value="1"<?php if ( $include_children ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Include items from child folders', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When unchecked (default) and a folder is selected, only items assigned to that folder will be displayed.  When checked, items in the selected folder *and* items in any of the folder's child folders will be displayed.  See additional/separate setting below if you'd like to enable this option for media files.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="enable_ajax_nav" value="1"<?php if ( $enable_ajax_nav ) echo ' checked="checked"'; ?>/>
                    <?php _e( "Don't reload page when navigating folders", 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When checked (default), navigating between folders will not cause the page to reload.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="enable_context_menus" value="1"<?php if ( $enable_context_menus ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Enable folder context menus', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "When checked (default), a button will be displayed after the folder name that displays a context menu with actions such as rename, edit, delete, etc.", 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>  
        <tr>
            <th scope="row">
                <?php _e( 'Colors:', 'wicked-folders' ); ?>
            </th>
            <td>
                <div id="colors" class="colors">
                    <ul>
                        <?php foreach ( $colors as $color ) : ?>
                            <li>
                                <div class="color" style="background-color: <?php echo esc_attr( $color ); ?>;">
                                    <span class="screen-reader-text"><?php echo esc_html( $color ); ?></span>
                                </div>
                                <button type="button">
                                    <span class="screen-reader-text">Delete color</span>
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                                <input type="hidden" name="colors[]" value="<?php echo esc_attr( $color ); ?>" />
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <input id="color-picker" type="text" data-coloris />
                    <button id="add-color" class="button" type="button">
                        <?php _e( 'Add Color', 'wicked-folders' ); ?>
                    </button>
                </div>
            </td>
        </tr>               
    </table>
    <h2><?php _e( 'Dynamic Folders', 'wicked-folders' ); ?></h2>
    <p><?php _e( 'Dynamic folders are generated on the fly based on your content.  They are useful for finding content based on things like date, author, etc.', 'wicked-folders' ); ?></p>
    <table class="form-table">
        <tr>
            <th scope="row">
                <?php _e( 'Enable dynamic folders for:', 'wicked-folders' ); ?>
            </th>
            <td>
                <?php foreach ( $post_types as $post_type ) : ?>
                    <?php
                        if ( ! $is_pro_active && in_array( $post_type->name, $pro_post_types ) ) continue;
                        if ( in_array( $post_type->name, array( 'wf_gf_form', 'wf_gf_entry', 'tablepress_table', 'wf_rcp_membership', 'wf_rcp_customer', 'wf_rcp_level', 'wf_afi_integration', 'wpforms', 'wf_plugin', 'wf_wc_product_review' ) ) ) continue;
                    ?>
                    <label>
                        <input type="checkbox" name="dynamic_folder_post_type[]" value="<?php echo esc_attr( $post_type->name ); ?>"<?php if ( in_array( $post_type->name, $dynamic_folders_enabled_posts_types ) ) echo ' checked="checked"'; ?><?php //if ( ! in_array( $post_type->name, $enabled_posts_types ) ) echo ' disabled="disabled"'; ?>/>
                        <?php echo esc_html( $post_type->label ); ?>
                    </label>
                    <br />
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>
                    <input type="checkbox" name="enable_lazy_dynamic_folders" value="1"<?php if ( $enable_lazy_dynamic_folders ) echo ' checked="checked"'; ?>/>
                    <?php _e( 'Lazy load dynamic folders', 'wicked-folders' ); ?>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( 'Improves performance by only loading dynamic folders when needed. When disabled, dynamic folders are loaded immediately.', 'wicked-folders' ); ?>"></span>
                </label>
            </td>
        </tr>  
    </table>
    <?php if ( $is_pro_active ) : ?>
        <h2><?php _e( 'Media', 'wicked-folders' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <?php //_e( 'Sync folder upload dropdown', 'wicked-folders' ); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sync_upload_folder_dropdown" value="1"<?php if ( $sync_upload_folder_dropdown ) echo ' checked="checked"'; ?>/>
                        <?php _e( 'Sync folder upload dropdown', 'wicked-folders' ); ?>
                        <span class="dashicons dashicons-editor-help" title="<?php _e( 'When checked, the dropdown that lets you to choose which folder to assign new uploads to will change as you browse folders and default to the currently selected folder. If left unchecked, the dropdown will default to no folder selected.', 'wicked-folders' ); ?>"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    &nbsp;
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="include_attachment_children" value="1"<?php if ( $include_attachment_children ) echo ' checked="checked"'; ?>/>
                        <?php _e( 'Include media from child folders', 'wicked-folders' ); ?>
                        <span class="dashicons dashicons-editor-help" title="<?php _e( "When unchecked (default) and a folder is selected, only media assigned to that folder will be displayed.  When checked, media in the selected folder *and* media in any of the folder's child folders will be displayed.", 'wicked-folders' ); ?>"></span>
                    </label>
                </td>
            </tr>
        </table>
    <?php endif; ?>

    <?php if ( $is_pro_active && ! is_multisite() ) : ?>
        <h2><?php _e( 'Wicked Folders Pro', 'wicked-folders' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wicked-folders-pro-license-key"><?php _e( 'License Key', 'wicked-folders' ); ?></label>
                </th>
                <td>
                    <?php if ( ! apply_filters( 'wicked_folders_mask_license_key', true ) ) : ?>
                        <p class="code"><?php echo esc_html( $license_key ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! $valid_license ) : ?>
                        <input type="text" id="wicked-folders-pro-license-key" class="regular-text" name="wicked_folders_pro_license_key" value="<?php echo esc_attr( $license_key ); ?>" />
                    <?php endif; ?>
                    <?php if ( $license_status ) : ?>
                        <div class="wicked-folders-license-status"><?php echo esc_html( $license_status ); ?></div>
                    <?php endif; ?>
                    <?php if ( $valid_license ) : ?>
                        <input name="deactivate_license" id="deactivate-license" class="button" value="<?php _e( 'Deactivate License', 'wicked-folders' ); ?>" type="submit" />
                    <?php else : ?>
                        <input name="activate_license" id="activate-license" class="button" value="<?php _e( 'Activate License', 'wicked-folders' ); ?>" type="submit" />
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    <?php endif; ?>
    <p class="submit">
        <input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes' ); ?>" type="submit" />
    </p>
</form>
<script>
    Coloris( {
        alpha:          false,
        closeButton:    true,
        closeLabel:     '<?php echo _e( 'Close', 'wicked-folders' ); ?>'
    } );

    ( function( $ ){
        $( function(){
            $( '#add-color' ).on( 'click', function( e ){
                var color = $( '#color-picker' ).val() || '#000000';
                var li = $( '<li>' );
                var colorDiv = $( '<div class="color">' )
                    .css( 'background-color', color )
                    .appendTo( li );

                $( '<span class="screen-reader-text">' )
                    .text( color )
                    .appendTo( colorDiv );

                var button = $( '<button type="button">' )
                    .appendTo( li );

                $( '<span class="screen-reader-text">Delete color</span><span class="dashicons dashicons-trash"></span>' )
                    .appendTo( button );

                $( '<input type="hidden" name="colors[]">' )
                    .val( color )
                    .appendTo( li );

                $( '#colors ul' ).append( li ); 

                $( '#color-picker' ).click();
            } );

            $( '#color-picker' ).on( 'input', function( e ){
                var color = $( this ).val();

                $( '#colors ul li:last .color' )
                    .css( 'background-color', color )
                    .find( '.screen-reader-text' )
                    .text( color );

                $( '#colors ul li:last input' ).val( color );
            } );

            $( '#colors' ).on( 'click', ' li button', function(){
                $( this ).closest( 'li' ).remove();
            } );
        } );
    } ( jQuery ) );
</script>