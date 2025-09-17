<div role="tabpanel" class="tab-pane" id="systemupdatestab">
  <div class='container-fluid' style='padding-top: .75em'>
    <div class='panel panel-default panel-help'>
      <div class='panel-body' style='background-color: #FDFBF1; padding: 15px;'>
        <?php
        echo "<p style='color: #212529; margin: 0;'>"._("Currently, upgrading the operating system through the UI is not available. 
        Therefore, please proceed with the system upgrade using the Linux command line interface with the following commands: `apt update && apt upgrade`.")."</p>\n";
        ?>
      </div>
    </div>
    
    <?php
    // Add Debian configuration section if this is a Debian system
    $su = new \FreePBX\Builtin\SystemUpdates();
    $debian_status = $su->getDebianConfigStatus();
    
    // Red alert will be added dynamically via JavaScript when tab is clicked
    if ($debian_status) {
        echo "<div class='row' style='margin-top: 30px;' id='debian-config-section'>";
        echo "<div class='col-sm-12'>";
        echo "<div class='panel panel-info'>";
        echo "<div class='panel-heading'>";
        echo "<h4 class='panel-title'>Debian Repository Configuration (Debian " . htmlspecialchars($debian_status['version']) . ")</h4>";
        echo "</div>";
        echo "<div class='panel-body'>";
        
        if ($debian_status['is_trixie_blocked']) {
            echo "<div class='alert alert-success'>";
            echo "<strong>System is already configured!</strong><br>";
            echo "This system is already blocked from upgrading to Debian 13 (Trixie).";
            echo "</div>";
        } elseif ($debian_status['is_debian_12']) {
            echo "<p>Configure Debian repositories to use Bookworm (Debian 12) and block Trixie (Debian 13) upgrades.</p>";
            echo "<div class='row'>";
            echo "<div class='col-sm-6'>";
            echo "<button id='previewbookwormbutton' class='btn btn-info' onclick='preview_debian_bookworm_config()'>Preview Changes</button>";
            echo "</div>";
            echo "<div class='col-sm-6'>";
            echo "<button id='configurebookwormbutton' class='btn btn-warning' onclick='configure_debian_bookworm()'>Apply Configuration</button>";
            echo "</div>";
            echo "</div>";
            echo "<div id='bookworm-preview' style='margin-top: 15px; display: none;'></div>";
            echo "<div id='bookworm-results' style='margin-top: 15px; display: none;'></div>";
        } else {
            echo "<div class='alert alert-info'>";
            echo "<strong>Debian " . htmlspecialchars($debian_status['version']) . " detected</strong><br>";
            echo "This feature is designed for Debian 12 (Bookworm) systems to block upgrades to Debian 13 (Trixie).";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    ?>
    
    <?php
    // Add Debian held packages section if this is a Debian system
    if ($debian_status) {
        // Use cached data from $summary if available, otherwise get from cache
        if (isset($summary['heldpackages']) && is_array($summary['heldpackages'])) {
            $held_packages = $summary['heldpackages'];
        } else {
            // Fallback: try to get from cache (should not happen if page.modules.php is correct)
            $held_packages = $su->getHeldPackages();
        }
        echo "<div class='row' style='margin-top: 20px;'>";
        echo "<div class='col-sm-12'>";
        echo "<div class='panel panel-default'>";
        echo "<div class='panel-heading'>";
        echo "<h4 class='panel-title'>";
        echo "<a data-toggle='collapse' href='#held-packages-collapse' aria-expanded='false' aria-controls='held-packages-collapse'>";
        echo "Debian Held Packages (" . count($held_packages) . ")";
        echo " <span class='caret'></span>";
        echo "</a>";
        echo "</h4>";
        echo "</div>";
        echo "<div id='held-packages-collapse' class='panel-collapse collapse'>";
        echo "<div class='panel-body'>";
        
        if (empty($held_packages)) {
            echo "<div class='alert alert-info'>No packages are currently held (blocked from upgrades).</div>";
        } else {
            echo "<p>The following packages are held and will not be upgraded automatically:</p>";
            echo "<div class='table-responsive'>";
            echo "<table class='table table-striped table-condensed'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Package Name</th>";
            echo "<th>Version</th>";
            echo "<th>Description</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($held_packages as $package) {
                echo "<tr>";
                echo "<td>";
                if (isset($package['is_grouped']) && $package['is_grouped']) {
                    echo "<code>" . htmlspecialchars($package['name']) . "</code>";
                    echo " <button class='btn btn-xs btn-info' onclick='toggleNodejsPackages()' id='nodejs-toggle'>Show Details</button>";
                } else {
                    echo "<code>" . htmlspecialchars($package['name']) . "</code>";
                }
                echo "</td>";
                echo "<td>" . htmlspecialchars($package['version']) . "</td>";
                echo "<td>" . htmlspecialchars($package['description']) . "</td>";
                echo "</tr>";
                
                // Add expandable row for Node.js packages
                if (isset($package['is_grouped']) && $package['is_grouped']) {
                    echo "<tr id='nodejs-details' style='display: none;'>";
                    echo "<td colspan='3'>";
                    echo "<div class='panel panel-default' style='margin: 10px 0;'>";
                    echo "<div class='panel-body' style='padding: 10px;'>";
                    echo "<h6>Individual Node.js packages:</h6>";
                    echo "<div class='table-responsive'>";
                    echo "<table class='table table-condensed table-striped' style='margin: 0;'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>Package Name</th>";
                    echo "<th>Version</th>";
                    echo "<th>Description</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    
                    foreach ($package['packages'] as $sub_package) {
                        echo "<tr>";
                        echo "<td><code>" . htmlspecialchars($sub_package['name']) . "</code></td>";
                        echo "<td>" . htmlspecialchars($sub_package['version']) . "</td>";
                        echo "<td>" . htmlspecialchars($sub_package['description']) . "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody>";
                    echo "</table>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
            
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
    ?>
    
    <?php
    // Add Upgradable Packages section
    $upgradable_count = (isset($systemupdates) && is_array($systemupdates)) ? count($systemupdates) : 0;
    echo "<div class='row' style='margin-top: 20px;'>";
    echo "<div class='col-sm-12'>";
    echo "<div class='panel panel-default'>";
    echo "<div class='panel-heading'>";
    echo "<h4 class='panel-title'>";
    echo "<a data-toggle='collapse' href='#upgradable-packages-collapse' aria-expanded='false' aria-controls='upgradable-packages-collapse'>";
    echo "Upgradable Packages (" . $upgradable_count . ")";
    echo " <span class='caret'></span>";
    echo "</a>";
    echo "</h4>";
    echo "</div>";
    echo "<div id='upgradable-packages-collapse' class='panel-collapse collapse'>";
    echo "<div class='panel-body'>";
    
    if ($upgradable_count == 0) {
        echo "<div class='alert alert-info'>No upgradable packages found.</div>";
    } else {
        echo "<div class='table-responsive'>";
        echo "<table id='upgradable_packages_table' class='table table-condensed table-striped'";
        echo " data-cache='false'";
        echo " data-show-columns='true'";
        echo " data-show-toggle='true'";
        echo " data-pagination='true'";
        echo " data-search='true'";
        echo " data-toolbar='#toolbar-upgradable-packages'";
        echo " data-toggle='table'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th data-field='service'>" . _("Service Name") . "</th>";
        echo "<th data-field='new_version'>" . _("New Version") . "</th>";
        echo "<th data-field='old_version'>" . _("Current using Version") . "</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        
        foreach ($systemupdates as $package) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($package['service']) . "</td>";
            echo "<td>" . htmlspecialchars($package['new_version']) . "</td>";
            echo "<td>" . htmlspecialchars($package['old_version']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    ?>
  </div>
</div>


