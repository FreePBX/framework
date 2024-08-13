<div role="tabpanel" class="tab-pane" id="systemupdatestab">
  <div class='container-fluid' style='padding-top: .75em'>
    <div class='panel panel-default panel-help'>
      <div class='panel-heading'>
        <?php
        echo "<p>"._("Currently, upgrading the operating system through the UI is not available. 
        Therefore, please proceed with the system upgrade using the Linux command line interface with the following commands: `apt update && apt upgrade`.")."</p>\n";
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
              if (isset($systemupdates) && is_array($systemupdates) && count($systemupdates) >0) {
                    foreach ($systemupdates as $package) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($package['service']) . "</td>";
                        echo "<td>" . htmlspecialchars($package['new_version']) . "</td>";
                        echo "<td>" . htmlspecialchars($package['old_version']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>"._("No upgradable packages found.")."</td></tr>";
                }
            ?>
        </tbody>
    </table>
  </div>
</div>


