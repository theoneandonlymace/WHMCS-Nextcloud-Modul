<?php
/**
 * WHMCS Nextcloud Provisioning Module
 *
 * Manages Nextcloud user accounts via the OCS Provisioning API.
 * Supports creating/suspending/unsuspending/terminating users,
 * quota management, group assignment, and client area quota display.
 *
 * @see https://docs.nextcloud.com/server/latest/admin_manual/configuration_user/user_provisioning_api.html
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/NextcloudAPI.php';

use WHMCS\Module\Server\Nextcloud\NextcloudAPI;

function nextcloud_MetaData()
{
    return [
        'DisplayName' => 'Nextcloud',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
    ];
}

function nextcloud_ConfigOptions()
{
    return [
        'Quota' => [
            'Type' => 'text',
            'Size' => '10',
            'Default' => '5 GB',
            'Description' => 'Quota (z.B. "5 GB", "50 GB", "1 TB")',
        ],
        'Gruppenname' => [
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Nextcloud group to which the user is assigned (empty = no group)',
        ],
        'Termination' => [
            'Type' => 'dropdown',
            'Options' => [
                'disable' => 'Deactivate user (data remains)',
                'delete' => 'Delete user (data will be deleted)',
            ],
            'Default' => 'enable',
            'Description' => 'What to do if terminated',
        ],
    ];
}

/**
 * Builds an API client instance from WHMCS server parameters.
 * Defaults to HTTPS unless explicitly disabled (serversecure === 'off').
 */
function nextcloud_buildApi(array $params): NextcloudAPI
{
    $ssl = !(isset($params['serversecure']) && $params['serversecure'] === 'off');
    return new NextcloudAPI(
        $params['serverhostname'],
        $params['serverusername'],
        $params['serverpassword'],
        $ssl
    );
}

/**
 * Resolves the Nextcloud username from WHMCS service data.
 * Priority: stored service username > custom field "Nextcloud Username".
 */
function nextcloud_resolveUsername(array $params): string
{
    if (!empty($params['username'])) {
        return $params['username'];
    }

    if (!empty($params['customfields'])) {
        foreach ($params['customfields'] as $name => $value) {
            if (stripos($name, 'nextcloud') !== false && stripos($name, 'username') !== false && $value !== '') {
                return $value;
            }
        }
    }

    throw new \Exception('Nextcloud username not found. Please create a custom field named “Nextcloud Username” for the product.';
}

/**
 * Resolves the Nextcloud password from WHMCS service data.
 * Priority: custom field "Nextcloud Password" > stored service password.
 */
function nextcloud_resolvePassword(array $params): string
{
    if (!empty($params['customfields'])) {
        foreach ($params['customfields'] as $name => $value) {
            if (stripos($name, 'nextcloud') !== false && stripos($name, 'password') !== false && $value !== '') {
                return $value;
            }
        }
    }

    if (!empty($params['password'])) {
        return $params['password'];
    }

    throw new \Exception('No password has been set. Please create a custom field named “Nextcloud Password” for the product or enable the password field in the Details tab.');
}

/**
 * Returns the base URL of the Nextcloud instance derived from server settings.
 */
function nextcloud_getBaseUrl(array $params): string
{
    $ssl = !(isset($params['serversecure']) && $params['serversecure'] === 'off');
    $protocol = $ssl ? 'https' : 'http';
    return $protocol . '://' . $params['serverhostname'];
}

function nextcloud_TestConnection(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $api->testConnection();

        logModuleCall('nextcloud', __FUNCTION__, $params, 'Connection successful');

        return ['success' => true, 'error' => ''];
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function nextcloud_CreateAccount(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);
        $password = nextcloud_resolvePassword($params);
        $email = $params['clientsdetails']['email'] ?? '';
        $quota = $params['configoption1'] ?? '5 GB';
        $groupName = $params['configoption2'] ?? '';

        if ($groupName !== '') {
            $api->ensureGroupExists($groupName);
        }

        $groups = $groupName !== '' ? [$groupName] : [];
        $api->createUser($username, $password, $email, $groups, $quota);

        try {
            $params['model']->serviceProperties->save([
                'Username' => $username,
                'Password' => $password,
            ]);
        } catch (\Exception $e) {
            // serviceProperties may not be available in all contexts
        }

        logModuleCall('nextcloud', __FUNCTION__, $params, "User ‘{$username}’ has been created");
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }

    return 'success';
}

function nextcloud_SuspendAccount(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);

        $api->disableUser($username);

        logModuleCall('nextcloud', __FUNCTION__, $params, "User ‘{$username}’ has been deactivated");
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }

    return 'success';
}

function nextcloud_UnsuspendAccount(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);

        $api->enableUser($username);

        logModuleCall('nextcloud', __FUNCTION__, $params, "User ‘{$username}’ has been activated");
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }

    return 'success';
}

function nextcloud_TerminateAccount(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);
        $action = $params['configoption3'] ?? 'disable';

        if ($action === 'delete') {
            $api->deleteUser($username);
            logModuleCall('nextcloud', __FUNCTION__, $params, "User ‘{$username}’ deleted");
        } else {
            $api->disableUser($username);
            logModuleCall('nextcloud', __FUNCTION__, $params, "User ‘{$username}’ has been deactivated (Terminate)");
        }
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }

    return 'success';
}

function nextcloud_ChangePackage(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);
        $newQuota = $params['configoption1'] ?? '';

        if ($newQuota === '') {
            throw new \Exception('No quota is configured in the new package.');
        }

        $api->editUser($username, 'quota', $newQuota);

        $newGroup = $params['configoption2'] ?? '';
        if ($newGroup !== '') {
            $api->ensureGroupExists($newGroup);
            $api->addUserToGroup($username, $newGroup);
        }

        logModuleCall('nextcloud', __FUNCTION__, $params, "Quota for ‘{$username}’ changed to '{$newQuota}'");
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }

    return 'success';
}

function nextcloud_ChangePassword(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);
        $newPassword = $params['password'];

        if (empty($newPassword)) {
            throw new \Exception('No new password was entered.');
        }

        $api->editUser($username, 'password', $newPassword);

        logModuleCall('nextcloud', __FUNCTION__, $params, "Password for ‘{$username}’ has been changed");
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return $e->getMessage();
    }

    return 'success';
}

function nextcloud_AdminServicesTabFields(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);
        $user = $api->getUser($username);

        $quota = $user['quota'] ?? [];
        $used = $quota['used'] ?? 0;
        $total = $quota['total'] ?? 0;
        $relative = $quota['relative'] ?? 0;
        $free = $quota['free'] ?? 0;

        return [
            'Nextcloud User' => $username,
            'Email' => $user['email'] ?? '-',
            'Enabled' => ($user['enabled'] ?? false) ? 'Ja' : 'Nein',
            'Quota booked' => nextcloud_formatBytes($total),
            'Quota used' => nextcloud_formatBytes($used) . " ({$relative}%)",
            'Free quota' => nextcloud_formatBytes($free),
            'Groups' => implode(', ', $user['groups'] ?? []),
            'Nextcloud URL' => nextcloud_getBaseUrl($params),
        ];
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return ['Fehler' => $e->getMessage()];
    }
}

function nextcloud_ClientArea(array $params)
{
    try {
        $api = nextcloud_buildApi($params);
        $username = nextcloud_resolveUsername($params);
        $user = $api->getUser($username);

        $quota = $user['quota'] ?? [];
        $usedBytes = $quota['used'] ?? 0;
        $totalBytes = $quota['total'] ?? 0;
        $freeBytes = $quota['free'] ?? 0;
        $relative = $quota['relative'] ?? 0;

        $nextcloudUrl = nextcloud_getBaseUrl($params);
        $bookedQuota = $params['configoption1'] ?? '-';

        return [
            'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
            'templateVariables' => [
                'ncUsername' => $username,
                'ncEmail' => $user['email'] ?? '',
                'ncEnabled' => $user['enabled'] ?? false,
                'ncGroups' => $user['groups'] ?? [],
                'ncQuotaUsed' => nextcloud_formatBytes($usedBytes),
                'ncQuotaUsedBytes' => $usedBytes,
                'ncQuotaTotal' => nextcloud_formatBytes($totalBytes),
                'ncQuotaTotalBytes' => $totalBytes,
                'ncQuotaFree' => nextcloud_formatBytes($freeBytes),
                'ncQuotaPercent' => round($relative, 1),
                'ncQuotaBooked' => $bookedQuota,
                'ncUrl' => $nextcloudUrl,
            ],
        ];
    } catch (\Exception $e) {
        logModuleCall('nextcloud', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());

        return [
            'tabOverviewReplacementTemplate' => 'templates/error.tpl',
            'templateVariables' => [
                'errorMessage' => $e->getMessage(),
            ],
        ];
    }
}

/**
 * Formats a byte value into a human-readable string.
 */
function nextcloud_formatBytes(int $bytes, int $precision = 2): string
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $exp = floor(log($bytes, 1024));
    $exp = min($exp, count($units) - 1);

    return round($bytes / pow(1024, $exp), $precision) . ' ' . $units[$exp];
}
