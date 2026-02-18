<?php
// Get brand name
$brand = \FreePBX::Config()->get('DASHBOARD_FREEPBX_BRAND');

// Check if sysadmin module is available
$hasSysadmin = false;
try {
	$modinfo = \FreePBX::Modules()->getInfo('sysadmin');
	if (!empty($modinfo['sysadmin']) && 
		isset($modinfo['sysadmin']['status']) && 
		$modinfo['sysadmin']['status'] != 0) {
		$hasSysadmin = true;
	}
} catch (\Exception $e) {
	$hasSysadmin = false;
}
?>
<div role="tabpanel" class="tab-pane" id="systemupdatestab">
  <div class='container-fluid' style='padding-top: .75em'>
    
    <!-- 1. Warning Section -->
    <div class='alert alert-warning' style='margin-bottom: 20px; padding: 10px 15px;'>
      <strong><?php echo _("System updates via the web interface are not currently available."); ?></strong> <?php echo _("Use"); ?> <code>apt update</code> <?php echo _("and"); ?> <code>apt upgrade</code> <?php echo _("on the command line."); ?>
    </div>
    
    
    <!-- Sysadmin Module Notice -->
    <div id="sysadmin-notice" class="alert alert-info" style="margin-bottom: 20px; <?php echo $hasSysadmin ? 'display: none;' : ''; ?>">
      <h4 style='margin-top: 0;'><i class="fa fa-info-circle"></i> <?php echo _("Sysadmin Module Notice"); ?></h4>
      <p><?php echo _("System update functionality is only supported with Sysadmin module."); ?></p>
    </div>
    
    <!-- Debian 13 Upgrade Risk Warning -->
    <div id="debian13-warning" class="alert alert-danger" style="margin-bottom: 20px; display: none;">
      <h4 style='margin-top: 0;'><i class="fa fa-exclamation-circle"></i> <strong><?php echo _("WARNING: Debian 13 Upgrade Risk Detected"); ?></strong></h4>
      <p><strong><?php echo sprintf(_("%s 17 is supported only on Debian 12."), $brand); ?></strong></p>
      <p><?php echo sprintf(_("Your repository configuration may allow an upgrade to Debian 13, which is not supported by %s."), $brand); ?></p>
      <p><?php echo _("To prevent this, ensure your repository files use a specific Debian codename (such as 'bookworm') instead of 'stable'."); ?></p>
      <p><strong><?php echo _("Affected files:"); ?></strong></p>
      <ul id="debian13-risk-files-list"></ul>
      <p><em><?php echo _("Please update your repository configuration to use 'bookworm' instead of 'stable' to prevent unsupported upgrades."); ?></em></p>
      <div id="debian13-sysadmin-help" style="display: none; margin-top: 15px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
        <p><strong><?php echo _("To automatically prevent Debian 13 upgrades:"); ?></strong></p>
        <p><?php echo _("Please execute"); ?> <code>fwconsole sa disable-deb-update-v13</code> <?php echo _("to run sysadmin hook"); ?></p>
      </div>
    </div>
    
    <div id="systemupdates-loading" style="text-align: center; padding: 20px; <?php echo $hasSysadmin ? '' : 'display: none;'; ?>">
      <i class="fa fa-spinner fa-spin"></i> <?php echo _("Loading system updates data..."); ?>
    </div>
    
    <div id="systemupdates-content" style="display: none;">
      
      <!-- 2. Repository Files Section -->
      <div class="panel panel-info" style="margin-top: 20px;">
        <div class="panel-heading" style="cursor: pointer;" data-toggle="collapse" data-target="#repositories-collapse">
          <h3 class="panel-title">
            <i class="fa fa-chevron-down" id="repositories-chevron"></i>
            <?php echo _("Repository Configuration Files"); ?>
            <span id="repositories-count" class="badge" style="margin-left: 10px;">0</span>
          </h3>
        </div>
        <div id="repositories-collapse" class="panel-collapse collapse">
          <div class="panel-body">
            <div id="repositories-list">
              <p><?php echo _("No repository files found."); ?></p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- 3. System OS Updates Summary Section -->
      <div class="panel panel-primary" style="margin-top: 20px;">
        <div class="panel-heading">
          <h3 class="panel-title">
            <i class="fa fa-info-circle"></i> <?php echo _("System OS Updates Summary"); ?>
            <button id="refresh-cache-btn" class="btn btn-sm btn-default pull-right" style="margin-top: -5px;" title="<?php echo _("Refresh cache to get latest data"); ?>">
              <i class="fa fa-refresh"></i> <span><?php echo _("Refresh Cache"); ?></span>
            </button>
          </h3>
        </div>
        <div class="panel-body">
          <div class="row">
            <div class="col-md-4">
              <div class="well text-center">
                <h4><?php echo _("Upgradable Packages"); ?></h4>
                <h2 id="summary-upgradable-count" style="margin: 0; color: #337ab7;">0</h2>
              </div>
            </div>
            <div class="col-md-4">
              <div class="well text-center">
                <h4><?php echo _("Security Updates"); ?></h4>
                <h2 id="summary-security-count" style="margin: 0; color: #d9534f;">0</h2>
              </div>
            </div>
            <div class="col-md-4">
              <div class="well text-center">
                <h4><?php echo _("Held Packages"); ?></h4>
                <h2 id="summary-held-count" style="margin: 0; color: #f0ad4e;">0</h2>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Security Upgradable Packages Section -->
      <div class="panel panel-danger" style="margin-top: 20px;">
        <div class="panel-heading" style="cursor: pointer;" data-toggle="collapse" data-target="#security-packages-collapse">
          <h3 class="panel-title">
            <i class="fa fa-shield"></i> <i class="fa fa-chevron-down" id="security-chevron"></i>
            <?php echo _("Security Upgradable Packages"); ?>
            <span id="security-count" class="badge" style="margin-left: 10px;">0</span>
          </h3>
        </div>
        <div id="security-packages-collapse" class="panel-collapse collapse">
          <div class="panel-body">
            <div id="security-packages-list">
              <p><?php echo _("No security upgradable packages found."); ?></p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Upgradable Packages Section -->
      <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading" style="cursor: pointer;" data-toggle="collapse" data-target="#upgradable-packages-collapse">
          <h3 class="panel-title">
            <i class="fa fa-chevron-down" id="upgradable-chevron"></i>
            <?php echo _("Upgradable Packages"); ?>
            <span id="upgradable-count" class="badge" style="margin-left: 10px;">0</span>
          </h3>
        </div>
        <div id="upgradable-packages-collapse" class="panel-collapse collapse">
          <div class="panel-body">
            <div id="upgradable-packages-list">
              <p><?php echo _("No upgradable packages found."); ?></p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Held Packages Section -->
      <div class="panel panel-default" style="margin-top: 20px;">
        <div class="panel-heading" style="cursor: pointer;" data-toggle="collapse" data-target="#held-packages-collapse">
          <h3 class="panel-title">
            <i class="fa fa-chevron-down" id="held-chevron"></i>
            <?php echo _("Held Packages"); ?>
            <span id="held-count" class="badge" style="margin-left: 10px;">0</span>
          </h3>
        </div>
        <div id="held-packages-collapse" class="panel-collapse collapse">
          <div class="panel-body">
            <div class="alert alert-info" style="margin-bottom: 15px;">
              <p style="margin-bottom: 10px;"><strong><?php echo _("Why are packages held?"); ?></strong></p>
              <p style="margin-bottom: 10px;"><?php echo _("The freepbx17 and sangoma-pbx17 Debian packages are bootstrap-only components. Their installation process is non-idempotent, and reinstalling them will overwrite critical configuration and may render the system unusable. For this reason, these packages are held to prevent accidental reinstallation."); ?></p>
              <p style="margin-bottom: 0;"><?php echo sprintf(_("Additionally, nodejs* packages are held to prevent upgrades to newer Node.js versions that may break %s components that depend on a specific Node.js runtime."), $brand); ?></p>
            </div>
            <div id="held-packages-list">
              <p><?php echo _("No held packages found."); ?></p>
            </div>
          </div>
        </div>
      </div>
      
      <div id="systemupdates-message" class="alert alert-info" style="margin-top: 20px; display: none;"></div>
    </div>
  </div>
</div>

<script>
// Load system updates data when tab is clicked
$(document).ready(function() {
  var userClickedTab = false;
  
  // Track when user actually clicks the tab (not just shown on page load)
  $('a[href="#systemupdatestab"]').on('click', function(e) {
    userClickedTab = true;
  });
  
  // Only load when systemupdatestab is shown AND user clicked it
  $('a[href="#systemupdatestab"]').on('shown.bs.tab', function(e) {
    // Check if the tab was clicked via a link from summary tab(not just shown on page load)
    var clickedViaLink = $(this).data('user-clicked') === true;
    // Only load if user actually clicked the tab
    if (userClickedTab || clickedViaLink) {
      <?php if ($hasSysadmin): ?>
      // Sysadmin is available - load data via AJAX
      loadSystemUpdatesData();
      <?php else: ?>
      // Sysadmin not available - just show the notice, hide loading
      $('#systemupdates-loading').hide();
      $('#sysadmin-notice').show();
      <?php endif; ?>
      userClickedTab = false; // Reset for next time
    }
  });
  
  // Handle collapse chevron rotation for all sections
  $('#repositories-collapse').on('show.bs.collapse', function() {
    $('#repositories-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
  });
  $('#repositories-collapse').on('hide.bs.collapse', function() {
    $('#repositories-chevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
  });
  
  $('#security-packages-collapse').on('show.bs.collapse', function() {
    $('#security-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
  });
  $('#security-packages-collapse').on('hide.bs.collapse', function() {
    $('#security-chevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
  });
  
  $('#upgradable-packages-collapse').on('show.bs.collapse', function() {
    $('#upgradable-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
  });
  $('#upgradable-packages-collapse').on('hide.bs.collapse', function() {
    $('#upgradable-chevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
  });
  
  $('#held-packages-collapse').on('show.bs.collapse', function() {
    $('#held-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
  });
  $('#held-packages-collapse').on('hide.bs.collapse', function() {
    $('#held-chevron').removeClass('fa-chevron-up').addClass('fa-chevron-down');
  });
  
  // Handle refresh cache button
  $('#refresh-cache-btn').on('click', function() {
    refreshSystemUpdatesCache();
  });
  
});

function refreshSystemUpdatesCache() {
  var $btn = $('#refresh-cache-btn');
  var $icon = $btn.find('i');
  var originalHtml = $btn.html();
  
  // Disable button and show loading state
  $btn.prop('disabled', true);
  $icon.removeClass('fa-refresh').addClass('fa-spinner fa-spin');
  $btn.find('span').text('<?php echo _("Refreshing..."); ?>');
  
  $.ajax({
    url: window.ajaxurl,
    data: { module: "framework", command: "sysupdate", action: "refreshsystemupdatescache" },
    success: function(response) {
      if (response.status === true) {
        // Cache refreshed, now reload the data
        loadSystemUpdatesData();
        // Show success message briefly
        $('#systemupdates-message').text(response.message || _("Cache refreshed successfully.")).removeClass('alert-danger alert-warning').addClass('alert-success').show();
        setTimeout(function() {
          $('#systemupdates-message').fadeOut();
        }, 3000);
      } else {
        $('#systemupdates-message').text(response.message || _("Failed to refresh cache.")).removeClass('alert-success alert-info').addClass('alert-danger').show();
      }
    },
    error: function(xhr, status, error) {
      $('#systemupdates-message').text(_("Error refreshing cache.")).removeClass('alert-success alert-info').addClass('alert-danger').show();
    },
    complete: function() {
      // Re-enable button and restore original state
      $btn.prop('disabled', false);
      $icon.removeClass('fa-spinner fa-spin').addClass('fa-refresh');
      $btn.find('span').text('<?php echo _("Refresh Cache"); ?>');
    }
  });
}

function loadSystemUpdatesData() {
  $.ajax({
    url: window.ajaxurl,
    data: { module: "framework", command: "sysupdate", action: "getsystemupdatesdata", checkAndRefresh: true },
    success: function(response) {
      $('#systemupdates-loading').hide();
      $('#systemupdates-content').show();
      
      if (response.status === true) {
        // Show sysadmin notice if module is not present
        if (response.has_sysadmin === false) {
          $('#sysadmin-notice').show();
          // Hide all content sections when sysadmin is not installed
          $('#systemupdates-content').hide();
          // Hide any error messages
          $('#systemupdates-message').hide();
          return;
        } else {
          $('#sysadmin-notice').hide();
          $('#systemupdates-content').show();
        }
        
        // Check for Debian 13 risk and show warning
        if (response.debian13_risk === true) {
          $('#debian13-warning').show();
          var riskFilesList = $('#debian13-risk-files-list');
          riskFilesList.empty();
          if (response.debian13_risk_files && response.debian13_risk_files.length > 0) {
            response.debian13_risk_files.forEach(function(file) {
              riskFilesList.append('<li><code>' + escapeHtml(file) + '</code></li>');
            });
          }
          // Show sysadmin help text only if sysadmin module is available
          if (response.has_sysadmin === true) {
            $('#debian13-sysadmin-help').show();
          } else {
            $('#debian13-sysadmin-help').hide();
          }
        } else {
          $('#debian13-warning').hide();
          $('#debian13-sysadmin-help').hide();
        }
        
        // Update summary counts
        var upgradableCount = (response.upgradable || []).length;
        var securityCount = (response.security || []).length;
        var heldCount = (response.held || []).length;
        
        $('#summary-upgradable-count').text(upgradableCount);
        $('#summary-security-count').text(securityCount);
        $('#summary-held-count').text(heldCount);
        
        // Display all sections
        displayRepositories(response.repositories || {});
        displaySecurityPackages(response.security || []);
        displayUpgradablePackages(response.upgradable || []);
        displayHeldPackages(response.held || []);
        
        // Hide message if data loaded successfully
        $('#systemupdates-message').hide();
      } else {
        // Only show error if sysadmin is installed (otherwise we already handled it above)
        if (response.has_sysadmin !== false) {
          $('#systemupdates-message').text(response.message || _("Unable to load system updates data.")).removeClass('alert-info').addClass('alert-warning').show();
        }
      }
    },
    error: function(xhr, status, error) {
      $('#systemupdates-loading').hide();
      $('#systemupdates-content').show();
      $('#systemupdates-message').text(_("Error loading system updates data.")).removeClass('alert-info').addClass('alert-danger').show();
    }
  });
}

function displayRepositories(repositories) {
  var count = Object.keys(repositories).length;
  $('#repositories-count').text(count);
  
  if (count === 0) {
    $('#repositories-list').html('<p><?php echo _("No repository files found."); ?></p>');
    return;
  }
  
  var html = '';
  for (var filepath in repositories) {
    if (repositories.hasOwnProperty(filepath)) {
      html += '<div style="margin-bottom: 20px;">';
      html += '<h5><strong>' + escapeHtml(filepath) + '</strong></h5>';
      html += '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 4px; max-height: 300px; overflow-y: auto;">' + escapeHtml(repositories[filepath]) + '</pre>';
      html += '</div>';
    }
  }
  
  $('#repositories-list').html(html);
}

function displaySecurityPackages(packages) {
  var count = packages.length;
  $('#security-count').text(count);
  
  if (count === 0) {
    $('#security-packages-list').html('<p><?php echo _("No security upgradable packages found."); ?></p>');
    return;
  }
  
  var html = '<table class="table table-condensed table-striped table-bordered">';
  html += '<thead><tr><th><?php echo _("Package Name"); ?></th><th><?php echo _("New Version"); ?></th><th><?php echo _("Current Version"); ?></th><th><?php echo _("Repository"); ?></th></tr></thead>';
  html += '<tbody>';
  
  for (var i = 0; i < packages.length; i++) {
    var pkg = packages[i];
    html += '<tr>';
    html += '<td><strong>' + escapeHtml(pkg.name || '') + '</strong></td>';
    html += '<td><span class="label label-danger">' + escapeHtml(pkg.new_version || '') + '</span></td>';
    html += '<td>' + escapeHtml(pkg.old_version || '') + '</td>';
    html += '<td><code>' + escapeHtml(pkg.repository || '') + '</code></td>';
    html += '</tr>';
  }
  
  html += '</tbody></table>';
  $('#security-packages-list').html(html);
}

function displayUpgradablePackages(packages) {
  var count = packages.length;
  $('#upgradable-count').text(count);
  
  if (count === 0) {
    $('#upgradable-packages-list').html('<p><?php echo _("No upgradable packages found."); ?></p>');
    return;
  }
  
  var html = '<table class="table table-condensed table-striped table-bordered">';
  html += '<thead><tr><th><?php echo _("Package Name"); ?></th><th><?php echo _("New Version"); ?></th><th><?php echo _("Current Version"); ?></th></tr></thead>';
  html += '<tbody>';
  
  for (var i = 0; i < packages.length; i++) {
    var pkg = packages[i];
    html += '<tr>';
    html += '<td>' + escapeHtml(pkg.name || '') + '</td>';
    html += '<td>' + escapeHtml(pkg.new_version || '') + '</td>';
    html += '<td>' + escapeHtml(pkg.old_version || '') + '</td>';
    html += '</tr>';
  }
  
  html += '</tbody></table>';
  $('#upgradable-packages-list').html(html);
}

function displayHeldPackages(packages) {
  var count = packages.length;
  $('#held-count').text(count);
  
  if (count === 0) {
    $('#held-packages-list').html('<p><?php echo _("No held packages found."); ?></p>');
    return;
  }
  
  // Group packages by similar patterns
  var regularPackages = [];
  var similarGroups = {};
  
  for (var i = 0; i < packages.length; i++) {
    var pkg = packages[i];
    var pkgName = pkg.name || '';
    
    // Pattern 1: node-* packages -> group as "nodejs*"
    if (pkgName.indexOf('node-') === 0) {
      var groupKey = 'nodejs*';
      if (!similarGroups[groupKey]) {
        similarGroups[groupKey] = [];
      }
      similarGroups[groupKey].push(pkg);
    }
    // Pattern 2: lib*-* packages (e.g., libfoo1, libfoo-dev)
    else if (pkgName.indexOf('lib') === 0) {
      // Extract base name (remove version numbers and common suffixes)
      var baseName = pkgName.replace(/^lib/, '').replace(/[-_]\d+.*$/, '').replace(/-\w+$/, '');
      if (baseName.length >= 2) {
        var groupKey = 'lib' + baseName + '*';
        if (!similarGroups[groupKey]) {
          similarGroups[groupKey] = [];
        }
        similarGroups[groupKey].push(pkg);
      } else {
        regularPackages.push(pkg);
      }
    }
    // Pattern 3: Packages with same prefix (e.g., python3-*, php7-*)
    else {
      var parts = pkgName.split('-');
      if (parts.length >= 2 && parts[0].length >= 3) {
        var prefix = parts[0];
        var groupKey = prefix + '*';
        if (!similarGroups[groupKey]) {
          similarGroups[groupKey] = [];
        }
        similarGroups[groupKey].push(pkg);
      } else {
        regularPackages.push(pkg);
      }
    }
  }
  
  // Filter similar groups to only show those with 2+ packages
  var groupsWithMultiple = {};
  for (var groupKey in similarGroups) {
    if (similarGroups[groupKey].length > 1) {
      groupsWithMultiple[groupKey] = similarGroups[groupKey];
    } else {
      // If only one package in group, move it back to regular
      regularPackages.push(similarGroups[groupKey][0]);
    }
  }
  
  // Build table with regular packages and grouped similar packages
  var html = '<table class="table table-condensed table-striped table-bordered">';
  html += '<thead><tr><th><?php echo _("Package Name"); ?></th><th><?php echo _("Version"); ?></th></tr></thead>';
  html += '<tbody>';
  
  // Display regular packages first
  for (var i = 0; i < regularPackages.length; i++) {
    var pkg = regularPackages[i];
    html += '<tr>';
    html += '<td>' + escapeHtml(pkg.name || '') + '</td>';
    html += '<td>' + escapeHtml(pkg.version || '') + '</td>';
    html += '</tr>';
  }
  
  // Display similar package groups as expandable rows
  for (var groupKey in groupsWithMultiple) {
    var groupId = 'similar-group-' + escapeHtml(groupKey).replace(/[^a-zA-Z0-9*]/g, '-');
    var groupPackages = groupsWithMultiple[groupKey];
    
    // Main row with group name (clickable to expand)
    html += '<tr class="info expandable-group-row" style="cursor: pointer;" data-group-id="' + groupId + '">';
    html += '<td>';
    html += '<i class="fa fa-chevron-right similar-chevron-' + groupId + '" style="margin-right: 5px;"></i>';
    html += '<strong>' + escapeHtml(groupKey) + '</strong>';
    html += ' <span class="badge">' + groupPackages.length + '</span>';
    html += '</td>';
    html += '<td><?php echo _("Click to expand"); ?></td>';
    html += '</tr>';
    
    // Collapsible row with individual packages
    html += '<tr class="similar-packages-detail" id="' + groupId + '" style="display: none;">';
    html += '<td colspan="2" style="padding: 0; border-top: none;">';
    html += '<div style="padding: 10px; background-color: #f9f9f9;">';
    html += '<table class="table table-condensed table-bordered" style="margin-bottom: 0;">';
    html += '<thead><tr><th style="width: 70%;"><?php echo _("Package Name"); ?></th><th><?php echo _("Version"); ?></th></tr></thead>';
    html += '<tbody>';
    
    for (var j = 0; j < groupPackages.length; j++) {
      var pkg = groupPackages[j];
      html += '<tr>';
      html += '<td style="padding-left: 30px;">' + escapeHtml(pkg.name || '') + '</td>';
      html += '<td>' + escapeHtml(pkg.version || '') + '</td>';
      html += '</tr>';
    }
    
    html += '</tbody></table>';
    html += '</div>';
    html += '</td>';
    html += '</tr>';
  }
  
  html += '</tbody></table>';
  $('#held-packages-list').html(html);
  
  // Add click handlers for expandable rows - use direct binding after HTML is inserted
  // Use delegated event handler that works with dynamically added content
  $('#held-packages-list').off('click', '.expandable-group-row');
  $('#held-packages-list').on('click', '.expandable-group-row', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var $row = $(this);
    var groupId = $row.data('group-id');
    
    // Use attribute selector to avoid issues with special characters in ID
    var $detailRow = $('#held-packages-list [id="' + groupId.replace(/"/g, '\\"') + '"]');
    
    // For chevron, we need to escape special characters for class selector
    // The chevron class is: similar-chevron-{groupId}
    // Find it within the clicked row to avoid selector issues
    var $chevron = $row.find('i[class*="similar-chevron-"]');
    
    if ($detailRow.length === 0) {
      console.error('Detail row not found for group:', groupId);
      return false;
    }
    
    if ($detailRow.is(':visible')) {
      $detailRow.slideUp(200);
      $chevron.removeClass('fa-chevron-down').addClass('fa-chevron-right');
    } else {
      $detailRow.slideDown(200);
      $chevron.removeClass('fa-chevron-right').addClass('fa-chevron-down');
    }
    
    return false;
  });
}

function escapeHtml(text) {
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
}
</script>
