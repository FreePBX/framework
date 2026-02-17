<?php
// vim: set ai ts=4 sw=4 ft=php:
/**
 * GPG Key Checker for FreePBX APT Repository
 *
 * Checks the expiry status of the FreePBX repository GPG key at
 * /etc/apt/trusted.gpg.d/freepbx.gpg and provides update capability.
 *
 * License for all code of this FreePBX module can be found in the license file inside the module directory
 * Copyright 2024 Sangoma Technology Corp.
 */

namespace FreePBX\Builtin;

#[\AllowDynamicProperties]
class GpgKeyChecker {

	/** GPG key path for Debian/APT systems */
	const GPG_KEY_PATH = '/etc/apt/trusted.gpg.d/freepbx.gpg';

	/** URL to download the GPG key */
	const GPG_KEY_URL = 'http://deb.freepbx.org/gpg/aptly-pubkey.asc';

	/** Warn when key expires within this many days */
	const EXPIRY_WARNING_DAYS = 30;

	/**
	 * Check if the FreePBX GPG key exists and get its expiry status.
	 * Uses incron pattern like SystemUpdates.
	 *
	 * @return array {
	 *   @type bool   $applicable     True if this system uses the APT GPG key
	 *   @type bool   $needs_update   True if key expires in less than EXPIRY_WARNING_DAYS
	 *   @type int    $expires_in_days Days until expiry (negative if expired)
	 *   @type string $expiry_date    Expiry date Y-m-d or empty
	 *   @type string $key_id         Short key ID if available
	 * }
	 */
	public static function checkFreepbxGpgExpiry() {
		$result = [
			'applicable' => false,
			'needs_update' => false,
			'expires_in_days' => 0,
			'expiry_date' => '',
			'key_id' => '',
		];

		if (!file_exists(self::GPG_KEY_PATH)) {
			return $result;
		}

		$result['applicable'] = true;

		// Use incron pattern (like SystemUpdates.php line 147-150)
		if (is_dir("/var/spool/asterisk/incron")) {
			$hookFile = "/var/spool/asterisk/incron/framework.check-gpg-expiry";
			
			if (file_exists($hookFile)) {
				unlink($hookFile);
			}
			touch($hookFile);
			
			// Wait briefly for incron to process
			usleep(200000); // 0.2 seconds
		}

		// Read cached result from hook
		$cacheFile = '/var/spool/asterisk/tmp/gpg_expiry.json';
		
		if (file_exists($cacheFile)) {
			$cached = json_decode(file_get_contents($cacheFile), true);
			if (is_array($cached)) {
				return array_merge($result, $cached);
			}
		}

		// Fallback: If incron not configured or cache not available, return needs_update
		$result['needs_update'] = true;
		return $result;
	}

	/**
	 * Update the FreePBX GPG key by downloading from the official URL.
	 * Uses incron pattern like SystemUpdates (line 147-150).
	 *
	 * @return array {
	 *   @type bool   $success Whether the update succeeded
	 *   @type string $message Human-readable message
	 * }
	 */
	public static function updateFreepbxGpgKey() {
		if (!file_exists('/etc/apt/trusted.gpg.d')) {
			return [
				'success' => false,
				'message' => _('APT trusted key directory does not exist. This system may not use APT.'),
			];
		}

		if (!is_dir("/var/spool/asterisk/incron")) {
			return [
				'success' => false,
				'message' => _('Incron not configured, unable to update GPG key.'),
			];
		}

		// Use incron pattern (like SystemUpdates.php line 147-150)
		$hookFile = "/var/spool/asterisk/incron/framework.update-gpg-key";
		
		if (file_exists($hookFile)) {
			unlink($hookFile);
		}
		touch($hookFile);

		// Wait briefly for incron to pick up and process
		usleep(500000); // 0.5 seconds
		
		// Check result file written by hook
		$resultFile = '/var/spool/asterisk/tmp/gpg_update_result.json';
		$maxWait = 5; // Wait up to 5 seconds
		$waited = 0;
		
		while ($waited < $maxWait) {
			if (file_exists($resultFile)) {
				$result = json_decode(file_get_contents($resultFile), true);
				if (is_array($result)) {
					// Clean up result file
					@unlink($resultFile);
					
					// Clear expiry cache and trigger fresh check if update was successful
					if (!empty($result['success'])) {
						$cacheFile = '/var/spool/asterisk/tmp/gpg_expiry.json';
						if (file_exists($cacheFile)) {
							@unlink($cacheFile);
						}
						
						// Trigger fresh check of new key
						if (is_dir("/var/spool/asterisk/incron")) {
							$checkHook = "/var/spool/asterisk/incron/framework.check-gpg-expiry";
							if (file_exists($checkHook)) {
								@unlink($checkHook);
							}
							touch($checkHook);
							// Give incron time to process
							usleep(500000); // 0.5 seconds
						}
					}
					
					return $result;
				}
			}
			usleep(500000); // 0.5 seconds
			$waited += 0.5;
		}

		// Timeout - assume success if no error file
		return [
			'success' => true,
			'message' => _('GPG key update initiated. Please refresh to verify.'),
		];
	}

	/**
	 * Check and optionally update the FreePBX repository GPG key.
	 */
	public static function checkAndUpdate($autoUpdate = false, $outputCallback = null) {
		$result = [
			'checked' => true,
			'applicable' => false,
			'needed_update' => false,
			'updated' => false,
			'success' => false,
			'message' => '',
		];

		// Helper to output messages
		$output = function($message, $type = 'info') use ($outputCallback) {
			if (is_callable($outputCallback)) {
				$outputCallback($message, $type);
			}
		};

		// Check GPG key status
		$gpgStatus = self::checkFreepbxGpgExpiry();
		$result['applicable'] = $gpgStatus['applicable'];

		if (!$gpgStatus['applicable']) {
			$output(_('Not applicable (non-Debian system)'), 'info');
			$result['message'] = _('Not applicable (non-Debian system)');
			return $result;
		}

		if (!$gpgStatus['needs_update']) {
			$message = sprintf(_('Valid (expires: %s)'), $gpgStatus['expiry_date']);
			$output($message, 'success');
			$result['message'] = $message;
			
			// Delete any lingering dashboard notification since key is valid
			if (class_exists('\FreePBX')) {
				try {
					\FreePBX::create()->Notifications->delete('framework', 'GPG_KEY_EXPIRY');
				} catch (\Exception $e) {
					// Silently ignore if notification system not available
				}
			}
			
			return $result;
		}

		// Key needs update
		$result['needed_update'] = true;
		$daysText = $gpgStatus['expires_in_days'] >= 0 
			? sprintf(_('expires in %s days'), $gpgStatus['expires_in_days'])
			: sprintf(_('expired %s days ago'), abs($gpgStatus['expires_in_days']));
		
		$statusMessage = sprintf(_('%s (%s)'), $daysText, $gpgStatus['expiry_date']);
		$output($statusMessage, 'warning');

		// Auto-update if requested
		if ($autoUpdate) {
			$output(_('Updating FreePBX repository GPG key...'), 'info');
			$result['updated'] = true;

			$updateResult = self::updateFreepbxGpgKey();
			$result['success'] = $updateResult['success'];

			if ($updateResult['success']) {
				$output($updateResult['message'], 'success');
				
				// Verify the update
				sleep(1);
				$newStatus = self::checkFreepbxGpgExpiry();
				if (isset($newStatus['expiry_date']) && !empty($newStatus['expiry_date'])) {
					$verifyMessage = sprintf(_('New expiry: %s'), $newStatus['expiry_date']);
					$output($verifyMessage, 'success');
					$result['message'] = $updateResult['message'] . ' - ' . $verifyMessage;
					
					// Delete dashboard notification if key is now valid (>= 30 days)
					if (!$newStatus['needs_update'] && class_exists('\FreePBX')) {
						try {
							\FreePBX::create()->Notifications->delete('framework', 'GPG_KEY_EXPIRY');
						} catch (\Exception $e) {
							// Silently ignore if notification system not available
						}
					}
				} else {
					$result['message'] = $updateResult['message'];
				}
			} else {
				$output(sprintf(_('Failed: %s'), $updateResult['message']), 'error');
				$result['message'] = $updateResult['message'];
			}
		} else {
			$result['message'] = $statusMessage;
		}
		return $result;
	}
}