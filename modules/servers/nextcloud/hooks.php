<?php
/**
 * WHMCS Nextcloud Provisioning Module - Hooks
 *
 * Syncs the client's email address to Nextcloud when their WHMCS profile is updated.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once __DIR__ . '/lib/NextcloudAPI.php';

use WHMCS\Module\Server\Nextcloud\NextcloudAPI;

/**
 * Syncs the client's email to all their active Nextcloud services when the
 * client profile is edited.
 */
function hook_nextcloud_clientedit(array $params)
{
    if (empty($params['userid'])) {
        return;
    }

    try {
        $services = \WHMCS\Service\Service::where('userid', $params['userid'])
            ->where('server', '>', 0)
            ->whereHas('product', function ($query) {
                $query->where('servertype', 'nextcloud');
            })
            ->where('domainstatus', 'Active')
            ->get();

        foreach ($services as $service) {
            try {
                $server = $service->serverModel;
                if (!$server) {
                    continue;
                }

                $ssl = !empty($server->secure);
                $api = new NextcloudAPI(
                    $server->hostname,
                    $server->username,
                    decrypt($server->password),
                    $ssl
                );

                $username = $service->username;
                if (empty($username)) {
                    continue;
                }

                $email = $params['email'] ?? '';
                if ($email !== '') {
                    $api->editUser($username, 'email', $email);
                }
            } catch (\Exception $e) {
                logModuleCall('nextcloud', 'hook_clientedit', [
                    'userid' => $params['userid'],
                    'service_id' => $service->id,
                ], $e->getMessage());
            }
        }
    } catch (\Exception $e) {
        logModuleCall('nextcloud', 'hook_clientedit', $params, $e->getMessage());
    }
}

add_hook('ClientEdit', 1, 'hook_nextcloud_clientedit');
