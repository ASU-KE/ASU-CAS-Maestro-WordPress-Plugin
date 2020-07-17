<?php
$raw_roles = get_editable_roles();

//Get number of allowed users
$num_allowed = count($this->allowed_users)+1;

foreach($raw_roles as $role => $value) {
  $roles[] = $role;
  $roles_name[] = __($value['name']);
}
?>
<script type="text/javascript">
  var roles = <?php echo json_encode($roles)?>;
  var roles_name = <?php echo json_encode($roles_name)?>;
  var CurrentTextboxes = <?php echo $num_allowed?>;
</script>

<div>
  <h2><?php _e("Users Allowed to Register", 'CAS_Maestro'); ?></h2>
  <div class="main_content">
    <p><?php _e("Allow all authenticated users to be automatically registered in Wordpress with the 'subscriber' role.", 'CAS_Maestro'); ?></p>
    <p><input name="new_user" type="checkbox" id="new_user_inp" value="1" <?php checked('1', $this->settings['new_user']); ?> /><label for="new_user_inp"><?php _e("Register all users?", 'CAS_Maestro'); ?></label></p>
  </div>

  <div class="main_content">
    <p><?php _e("Whether you globally permit new user registrations or not, you can pre-approve specific user accounts and assign them a Wordpress role.", 'CAS_Maestro'); ?></p>
    <table id="autoAdd">
      <tbody>
        <?php
        $i=1;
        foreach($this->allowed_users as $username => $curr_role) {
        $roles = array();
        $select_options="<option></option>";
        $roles_name = array();
          foreach($raw_roles as $role => $value) {
            $roles[] = $role;
            $roles_name[] = __($value['name']);
            $selected ='';
            if($curr_role == $role)
              $selected = 'selected';
            $select_options .= "<option value='$role' $selected>" . __($value['name']) . "</option>\n";
          }
        ?>
          <tr>
          <td class="prefix">
            <input type="text" class="istid" id="txt<?php echo $i?>" name="username[<?php echo $i?>]" value="<?php echo $username?>" style="width: 150px;"></input>
          </td>
          <td>
            <select class="to_select_2" name="role[<?php echo $i?>]" style="width: 180px;">
              <?php echo $select_options?>
            </select>
          </td>
        </tr>
        <?php $i++; } ?>
        <tr>
          <td class="prefix"><input class="istid" type="text" id="txt<?php echo $i?>" name="username[<?php echo $i?>]" style="width: 150px;"></input></td>
          <td>
            <select class="to_select_2" name="role[<?php echo $i?>]" style="width: 180px;">
              <?php
              $select_options="<option></option>";
              foreach($raw_roles as $role => $value) {
                $roles[] = $role;
                $roles_name[] = __($value['name']);
                $selected ='';
                $select_options .= "<option value='$role'>".__($value['name'])."</option>\n";
              }
              ?>
              <?php echo $select_options; ?>
            </select>
          </td>
        </tr>
      </tbody>
    </table>
    <p class="grey_text"><?php _e("To add another user, just fill the last blank element.", 'CAS_Maestro'); ?></p>
  </div>
</div>
<hr />

<div>
  <h2><?php _e("User Account Auto-fill", 'CAS_Maestro'); ?></h2>
  <?php if ( current_user_can( 'manage_options' ) ) : ?>
  <div>
    <div>
      <p><?php _e("If you wish to auto-fill a new user's name and/or email address after authentication, please select the mechanism by which the user account info will be set. At minimum, the user email address must be set in order for Welcome and Wait for Approval emails to be sent.", 'CAS_Maestro'); ?></p>
      <?php
        if ( $this->settings['email_registration'] < 1 || $this->settings['email_registration'] > 5 ) {
          $this->settings['email_registration'] = 1;
        }
      ?>
      <input id="no-autocomplete-userinfo" type="radio" name="email_registration" value="1" <?php echo ($this->settings['email_registration'] == '1') ? 'checked' : ''; ?>>
      <label for="no-autocomplete-userinfo"><?php _e("Don't auto-fill account info (e.g. email address)", 'CAS_Maestro'); ?></label><br />

      <input id="email-suffix" type="radio" name="email_registration" value="2" <?php echo ($this->settings['email_registration'] == '2') ? 'checked' : ''; ?>>
      <label for="email-suffix"><?php _e("Set email: username@", 'CAS_Maestro'); ?></label>

      <input type="text" name="email_suffix" id="email_suffix_inp" value="<?php echo $this->settings['email_suffix']; ?>" placeholder="<?php echo parse_url(site_url(),PHP_URL_HOST)?>" size="15" /><br />
      <input id="ldap-email" type="radio" name="email_registration" value="3" <?php echo ($this->settings['email_registration'] == '3') ? 'checked' : ''; ?>>
      <label for="ldap-email"><?php _e("Name and email from LDAP server connection", 'CAS_Maestro'); ?></label><br />

      <input id="cas-email" type="radio" name="email_registration" value="4" <?php echo ($this->settings['email_registration'] == '4') ? 'checked' : ''; ?>>
      <label for="cas-email"><?php _e("Name and email from CAS attributes", 'CAS_Maestro'); ?></label><br />

      <input id="asu-directory" type="radio" name="email_registration" value="5" <?php echo ($this->settings['email_registration'] == '5') ? 'checked' : ''; ?>>
      <label for="asu-directory"><?php _e("Name and email from ASU Directory", 'CAS_Maestro'); ?></label><br />
    </div>

    <div id="ldap_container">
      <p><?php _e("You must complete this configuration with your LDAP server info. For anonymous server access, which might be insufficient for successful sign-ins on your network, leave the fields “RDN User” and “Password” blank.", 'CAS_Maestro'); ?></p>
      <table width="700px" cellspacing="2" cellpadding="5" class="editform">
        <tbody>
          <tr valign="center">
            <th width="150px" scope="row">Protocol version</th>
            <td>
              <select name="ldap_protocol" id="ldap_proto" style="width: 75px">
                <option value="3" <?php echo ($this->settings['ldap_protocol'] == '3')?'selected':''; ?>>3</option>
                <option value="2" <?php echo ($this->settings['ldap_protocol'] == '2')?'selected':''; ?>>2</option>
                <option value="1" <?php echo ($this->settings['ldap_protocol'] == '1')?'selected':''; ?>>1</option>
              </select>
            </td>
          </tr>
          <tr valign="center">
            <th width="150px" scope="row"><?php _e("Server hostname", 'CAS_Maestro'); ?>* <br /><span><?php echo sprintf(__("(with %s or %s)", 'CAS_Maestro'), 'ldap://', 'ldaps://'); ?></span></th>
            <td><input type="text" <?php check_empty($this->settings['ldap_server'])?>  name="ldap_server" id="ldap_server" value="<?php echo $this->settings['ldap_server']; ?>" size="35" /></td>
          </tr>
          <tr valign="center">
            <th width="150px" scope="row"> <?php _e("Username <abbr title='(Relative Distinguished Name)'>RDN</abbr>", 'CAS_Maestro');?></th>
            <td><input type="text" name="ldap_username_rdn" id="ldap_user" value="<?php echo $this->settings['ldap_username_rdn']; ?>" size="35" /></td>
          </tr>
          <tr valign="center">
            <th width="150px" scope="row"><?php _e("Password", 'CAS_Maestro'); ?></th>
            <td><input type="text" name="ldap_password" id="ldap_pass" value="<?php echo $this->settings['ldap_password']; ?>" size="35" /></td>
          </tr>
          <tr valign="center">
            <th width="150px" scope="row"><?php _e("Base DN", 'CAS_Maestro'); ?>*</th>
            <td><input type="text" <?php check_empty($this->settings['ldap_basedn'])?> name="ldap_basedn" id="ldap_bdn" value="<?php echo $this->settings['ldap_basedn']; ?>" size="35" /></td>
          </tr>
        </tbody>
      </table>
      <div class='availability_result' id='ldap_availability_result'></div>
    </div>

    <div id="cas_container">
      <h2><?php _e('CAS attributes settings','CAS_Maestro'); ?></h2>
      <p><?php _e("Define what attributes you want to copy from the CAS server.", 'CAS_Maestro'); ?></p>
      <table width="700px" cellspacing="2" cellpadding="5" class="editform">
          <tbody>
              <tr>
                  <th width="150px" scope="row"><label for="cas_first_name_inp"><?php _e('First name', 'CAS_Maestro'); ?></label></th>
                  <td>
                      <input type="text" name="cas_first_name" id="cas_first_name_inp" value="<?php echo $this->settings['cas_first_name']; ?>" size="25" />
                  </td>
              </tr>
              <tr>
                  <th width="150px" scope="row"><label for="cas_last_name_inp"><?php _e('Last name', 'CAS_Maestro'); ?></label></th>
                  <td>
                      <input type="text" name="cas_last_name" id="cas_last_name_inp" value="<?php echo $this->settings['cas_last_name']; ?>" size="25" />
                  </td>
              </tr>
              <tr>
                  <th width="150px" scope="row"><label for="cas_user_email_inp"><?php _e('User email', 'CAS_Maestro'); ?></label></th>
                  <td>
                      <input type="text" name="cas_user_email" id="cas_user_email_inp" value="<?php echo $this->settings['cas_user_email']; ?>" size="25" />
                  </td>
              </tr>
          </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="submit">
    <input type="submit" name="submit" class="button-primary" value="<?php _e('Update options') ?>" />
</div>
<div style="clear: both;"></div>
