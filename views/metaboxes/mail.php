<?php $settings = $this->settings; ?>
<h2><?php _e("Sender", 'CAS_Maestro'); ?></h2>
<table width="700px" cellspacing="2" cellpadding="5" class="editform">
  <tr>
    <td colspan="2">
      <p><?php _e("All New User emails will be sent using the following sender details. Depending on configuration, this SIte Monitor account can also receive notification emails about new users and/or account activations.", 'CAS_Maestro'); ?></p>
    </td>
  </tr>
  <tr valign="center">
    <th width="150px" scope="row"><?php _e("Email address:", 'CAS_Maestro'); ?>*</th>
    <td>
      <input type="text" placeholder="" name="global_sender" id="email_suffix_inp" value="<?php echo $settings['global_sender']; ?>" size="35" />
    </td>
  </tr>
  <tr valign="center">
    <th width="150px" scope="row"><?php _e("Full name:", 'CAS_Maestro'); ?></th>
    <td>
      <input type="text" placeholder="" name="full_name" id="full_name_suffix_inp" value="<?php echo $settings['full_name']; ?>" size="35" />
    </td>
  </tr>
</table>

<h2><?php _e("Mails", 'CAS_Maestro'); ?></h2>
<p><?php _e("Please select when New User emails should be sent. It’s possible to send emails to the User and the Site Monitor account, depending on your configuration choices.", 'CAS_Maestro'); ?></p>
<div class="mail_tabs">
  <ul>
    <li id="welcome_mail_tab"><a href="#"><?php _e("Welcome", 'CAS_Maestro'); ?></a></li>
    <li id="wait_for_access_tab"><a href="#"><?php _e("Wait for Approval", 'CAS_Maestro'); ?></a></li>
  </ul>
</div>

<div class="message_container">
  <div id="welcome_mail">
    <p>
      <input name="welcome_send_user" type="checkbox" id="new_user_inp0" value="1" <?php checked('1', $this->settings['welcome_mail']['send_user']); ?> />
      <label for="new_user_inp0"><?php _e("Send to the User", 'CAS_Maestro'); ?></label>

      <input name="welcome_send_global" type="checkbox" id="new_user_inp1" value="1" <?php checked('1', $this->settings['welcome_mail']['send_global']); ?> />
      <label for="new_user_inp1"><?php _e("Send to Site Monitor", 'CAS_Maestro'); ?></label>
    </p>

    <h2><?php _e("Subject", 'CAS_Maestro'); ?></h2>
    <p>
      <input type="text" name="welcome_subject" id="email_suffix_inp" value="<?php echo $settings['welcome_mail']['subject']; ?>" size="35" placeholder="Type subject"/>
    </p>

    <h2><?php _e("Body", 'CAS_Maestro'); ?></h2>
    <div class="mail_body">
      <div>
        <p><?php _e("Message body sent to the User", 'CAS_Maestro'); ?></p>
        <textarea type="text" name="welcome_user_body" id="user_email_suffix_inp"><?php echo $this->settings['welcome_mail']['user_body']?></textarea>
      </div>
      <div>
        <p><?php _e("Message body sent to the Site Monitor", 'CAS_Maestro'); ?></p>
        <textarea type="text" name="welcome_global_body" id="global_email_suffix_inp"><?php echo $this->settings['welcome_mail']['global_body']?></textarea>
      </div>
    </div>
  </div>

  <div id="wait_for_access">
    <p>
      <input name="wait_send_user" type="checkbox" id="new_user_inp2" value="1" <?php checked('1', $this->settings['wait_mail']['send_user']); ?> />
      <label for="new_user_inp2"><?php _e("Send to the User", 'CAS_Maestro'); ?></label>

      <input name="wait_send_global" type="checkbox" id="new_user_inp3" value="1" <?php checked('1', $this->settings['wait_mail']['send_global']); ?> />
      <label for="new_user_inp3"><?php _e("Send to the Site Monitor account", 'CAS_Maestro'); ?></label>
    </p>

    <h2><?php _e("Subject", 'CAS_Maestro'); ?></h2>
    <p>
      <input type="text" name="wait_subject" id="email_suffix_inp" value="<?php echo $settings['wait_mail']['subject']; ?>" size="35" placeholder="Type subject"/>
    </p>

    <h2><?php _e("Body", 'CAS_Maestro'); ?></h2>
    <div class="mail_body">
      <div>
        <p><?php _e("Message body sent to the User", 'CAS_Maestro'); ?></p>
        <textarea type="text" name="wait_user_body" id="email_suffix_inp"><?php echo $this->settings['wait_mail']['user_body']?></textarea>
      </div>
      <div>
        <p><?php _e("Message body sent to the Site Monitor account", 'CAS_Maestro'); ?></p>
        <textarea type="text" name="wait_global_body" id="email_suffix_inp"><?php echo $this->settings['wait_mail']['global_body']?></textarea>
      </div>
    </div>
  </div>

  <p class="grey_text"><?php _e("The following tokens are available: %sitename% for the website name, %username% for the user’s name, and %realname% for the user’s full name.", 'CAS_Maestro'); ?></p>

  <div style="clear: both;"></div>
</div>

<div class="submit">
  <input type="submit" name="submit" class="button-primary" value="<?php _e('Update options') ?>" />
</div>
<div style="clear: both;"></div>
