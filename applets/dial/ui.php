<div class="vbx-applet">

  <h2>PagerDuty On-Call Schedule Dialer</h2>

  <p>Dial the currently on-call user from a PagerDuty On-Call schedule.</p>

  <h3>Account Hostname</h3>
  <fieldset class="vbx-applet-fieldset vbx-full-pane">
    <label>
      <p>Enter the PagerDuty account hostname.</p>
      <p>(If the fully-qualified domain name for the PagerDuty account is "example.pagerduty.com", then enter "example".)</p>
      <input class="text" type="text" name="pd-hostname" maxlength="63" value="<?= AppletInstance::getValue('pd-hostname') ?>" />
    </label>
  </fieldset>

  <h3>API Access Key</h3>
  <fieldset class="vbx-applet-fieldset vbx-full-pane">
    <label>
      <p>Enter the PagerDuty API Access Key (20 characters).</p>
      <input class="text" type="text" name="pd-api-key" maxlenth="20" value="<?= AppletInstance::getValue('pd-api-key') ?>" />
    </label>
  </fieldset>

  <h3>On-Call Schedule</h3>
  <fieldset class="vbx-applet-fieldset vbx-full-pane">
    <label>
      <p>Enter the unique identifier for the PagerDuty On-Call Schedule (7 characters).</p>
      <input class="text" type="text" name="pd-schedule" maxlength="7" value="<?= AppletInstance::getValue('pd-schedule') ?>" />
    </label>
  </fieldset>

</div><!-- .vbx-applet -->
