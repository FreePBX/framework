<?php
  if (is_dir("/var/spool/asterisk/incron")) {
    if (file_exists("/var/spool/asterisk/incron/framework.list-system-updates")) {
      unlink("/var/spool/asterisk/incron/framework.list-system-updates");
    }
    touch("/var/spool/asterisk/incron/framework.list-system-updates");
    sleep(2);
  } else {
    dbug('Incron not configured, unable to manage system updates');
  }
?>
<div role="tabpanel" class="tab-pane" id="systemupdatestab">
  <div class='container-fluid' style='padding-top: .75em'>
    <div class='panel panel-danger'>
      <div class='panel-body'>
        <?php
        echo "<p>"._("Currently, upgrading the operating system through the UI is not available. 
        Therefore, please proceed with the system upgrade using the Linux command line interface with the following commands: `apt clean && apt upgrade`.")."</p>\n";
        ?>
      </div>
    </div>
    <h2>Upgradable Packages</h2>
    <table id="upgradable_packages_table" class="table table-condensed table-striped"
           data-cache="false"
           data-show-columns="true"
           data-show-toggle="true"
           data-pagination="true"
           data-search="true"
           data-toolbar="#toolbar-api-applications"
           data-toggle="table">
        <thead>
            <tr>
                <th data-field="service"><?php echo _("Service Name"); ?></th>
                <th data-field="new_version"><?php echo _("New Version"); ?></th>
                <th data-field="old_version"><?php echo _("Current using Version"); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
              $jsonFilePath = '/var/spool/asterisk/tmp/upgradable_packages.json';
              if (file_exists($jsonFilePath)) {
                $jsonContent = file_get_contents($jsonFilePath);
                $upgradablePackages = json_decode($jsonContent, true);
                if (is_array($upgradablePackages)) {
                    foreach ($upgradablePackages as $package) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($package['service']) . "</td>";
                        echo "<td>" . htmlspecialchars($package['new_version']) . "</td>";
                        echo "<td>" . htmlspecialchars($package['old_version']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>"._("No upgradable packages found or failed to read JSON file.")."</td></tr>";
                }
              }
            ?>
        </tbody>
    </table>
  </div>
</div>


