<h2><?php _e('Server settings','CAS_Maestro'); ?></h2>
<fieldset class="options">
  <p><?php _e("The plugin will only be active after the CAS server settings are completed. After the configuration is saved, please verify if the connection is valid. This is strongly advised because if you enter incorrect data, your access to WordPress might be denied.", 'CAS_Maestro'); ?></p>
  <p class="grey_text"><?php _e("If you don’t know all the server connection details, or you are unsure about any of them, you should contact the person responsible for your CAS server.", 'CAS_Maestro'); ?></p>

  <table width="700px" cellspacing="2" cellpadding="5" class="editform">
    <tbody>
      <tr>
        <th width="150px" scope="row"><label for="cas_version_inp"><?php _e('CAS version', 'CAS_Maestro'); ?></label></th>
        <td>
          <select name="cas_version" id="cas_version_inp" style="width: 100px;">
            <option value="2.0" <?php echo ($this->settings['cas_version'] == '2.0')?'selected':''; ?>>2.0</option>
            <option value="1.0" <?php echo ($this->settings['cas_version'] == '1.0')?'selected':''; ?>>1.0</option>
          </select>
        </td>
      </tr>
      <tr>
        <th width="150px" scope="row">
          <label for="server_hostname_inp"><?php _e('Server hostname', 'CAS_Maestro'); ?>*</label>
        </th>
        <td>
          <input <?php check_empty($this->settings['server_hostname'])?> type="text" name="server_hostname" id="server_hostname_inp" value="<?php echo $this->settings['server_hostname']; ?>" size="25" />
        </td>
      </tr>
      <tr>
        <th width="150px" scope="row">
          <label for="server_port_inp"><?php _e('Server port', 'CAS_Maestro'); ?>*</label>
        </th>
        <td>
          <input <?php check_empty($this->settings['server_port'])?> type="text" name="server_port" id="server_port_inp" value="<?php echo $this->settings['server_port']; ?>" size="25" />
        </td>
      </tr>
      <tr>
        <th width="150px" scope="row">
          <label for="server_path_inp"><?php _e('Server path', 'CAS_Maestro'); ?></label>
        </th>
        <td>
          <input type="text" name="server_path" id="server_path_inp" value="<?php echo $this->settings['server_path']; ?>" size="25" />
        </td>
      </tr>
      <tr>
        <th width="150px" scope="row">
          <label for="redirect_url_inp"><?php _e('Redirect URL', 'CAS_Maestro'); ?></label>
        </th>
        <td>
          <input type="text" name="redirect_url" id="redirect_url_inp" value="<?php echo $this->settings['redirect_url']; ?>" size="25" />
          <div class="grey_text">
            <p>Specify the url to redirect to after logging out.<br /> Leave blank to disable redirection.</p>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
  <div class='availability_result' id="username_availability_result"></div>

  <h2><?php _e('Advanced options', 'CAS_Maestro'); ?></h2>
  <table width="700px" cellspacing="2" cellpadding="5" class="editform">
    <tbody>
      <tr>
        <th width="150px" scope="row">
          <label for="phpcas_path_inp"><?php _e('Path to phpCAS', 'CAS_Maestro'); ?></label>
        </th>
        <td>
          <input type="text" name="phpcas_path" id="phpcas_path_inp" value="<?php echo $this->settings['phpcas_path']; ?>" size="25" />
          <div class="grey_text">
            <p>Specify the path to the phpCAS library.<br />Use "phpCAS/" for the version of the library shipped with the module.<br />Leave blank to load phpCAS from your PHP include path.</p>
          </div>
        </td>
      </tr>
      <tr>
        <th width="150px" scope="row">
          <label for="debug_path_inp"><?php _e('Path to log file', 'CAS_Maestro'); ?></label>
        </th>
        <td>
          <input type="text" name="debug_path" id="debug_path_inp" value="<?php echo $this->settings['debug_path']; ?>" size="25" />
          <div class="grey_text">
            <p>A file system path and filename where the CAS debug log will be written.<br />Leave blank to disable logging.<br />Debugging should not be enabled on production systems.<p>
          </div>
        </td>
      </tr>
    </tbody>
  </table>

  <h2><?php _e('Menu localization', 'CAS_Maestro'); ?></h2>
  <p>
    <label for="admin_menu_side" class="label-radio">
      <input type="radio" name="admin_menu" id="admin_menu_side" value="sidebar" <?php echo (($this->settings['cas_menu_location'] === 'sidebar') ? ' checked="checked"' : ''); ?>>
      <span><?php _e('Sidebar','CAS_Maestro'); ?></span>
    </label>
    <label for="admin_menu_settings" class="label-radio">
      <input type="radio" name="admin_menu" id="admin_menu_settings" value="settings"<?php echo (($this->settings['cas_menu_location']  === 'settings') ? ' checked="checked"' : ''); ?>>
      <span><?php _e('Settings menu','CAS_Maestro'); ?></span>
    </label>
  </p>
</fieldset>
<div class="submit">
  <input type="submit" name="submit" class="button-primary" value="<?php _e('Update options') ?>" />
</div>
<div style="clear: both;"></div>
