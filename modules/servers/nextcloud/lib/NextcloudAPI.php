<?php

namespace WHMCS\Module\Server\Nextcloud;

class NextcloudAPI
{
    private $baseUrl;
    private $adminUser;
    private $adminPassword;

    public function __construct(string $hostname, string $adminUser, string $adminPassword, bool $ssl = true)
    {
        $protocol = $ssl ? 'https' : 'http';
        $this->baseUrl = rtrim("{$protocol}://{$hostname}", '/');
        $this->adminUser = $adminUser;
        $this->adminPassword = $adminPassword;
    }

    /**
     * @throws \Exception on API or connection errors
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . '/ocs/v1.php' . $endpoint;

        $separator = (strpos($url, '?') === false) ? '?' : '&';
        if ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query(array_merge($data, ['format' => 'json']));
        } else {
            $url .= $separator . 'format=json';
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => "{$this->adminUser}:{$this->adminPassword}",
            CURLOPT_HTTPHEADER => [
                'OCS-APIRequest: true',
                'Accept: application/json',
            ],
        ]);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception("Verbindungsfehler: {$curlError}");
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Ungültige API-Antwort (HTTP {$httpCode}): " . substr($response, 0, 500));
        }

        return $decoded;
    }

    private function assertOcsSuccess(array $response, string $action): void
    {
        $statusCode = $response['ocs']['meta']['statuscode'] ?? -1;
        $message = $response['ocs']['meta']['message'] ?? 'Unbekannter Fehler';

        if ($statusCode !== 100 && $statusCode !== 200) {
            throw new \Exception("{$action} fehlgeschlagen (Code {$statusCode}): {$message}");
        }
    }

    public function testConnection(): bool
    {
        $response = $this->request('GET', '/cloud/users', ['limit' => 1]);
        $this->assertOcsSuccess($response, 'Verbindungstest');
        return true;
    }

    public function createUser(string $userid, string $password, string $email = '', array $groups = [], string $quota = ''): void
    {
        $data = [
            'userid' => $userid,
            'password' => $password,
        ];

        if ($email !== '') {
            $data['email'] = $email;
        }
        if (!empty($groups)) {
            $data['groups'] = $groups;
        }
        if ($quota !== '') {
            $data['quota'] = $quota;
        }

        $response = $this->request('POST', '/cloud/users', $data);
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' erstellen");
    }

    public function getUser(string $userid): array
    {
        $response = $this->request('GET', "/cloud/users/{$userid}");
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' abfragen");
        return $response['ocs']['data'] ?? [];
    }

    public function editUser(string $userid, string $key, string $value): void
    {
        $response = $this->request('PUT', "/cloud/users/{$userid}", [
            'key' => $key,
            'value' => $value,
        ]);
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' bearbeiten ({$key})");
    }

    public function enableUser(string $userid): void
    {
        $response = $this->request('PUT', "/cloud/users/{$userid}/enable");
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' aktivieren");
    }

    public function disableUser(string $userid): void
    {
        $response = $this->request('PUT', "/cloud/users/{$userid}/disable");
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' deaktivieren");
    }

    public function deleteUser(string $userid): void
    {
        $response = $this->request('DELETE', "/cloud/users/{$userid}");
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' löschen");
    }

    public function groupExists(string $groupid): bool
    {
        try {
            $response = $this->request('GET', '/cloud/groups', ['search' => $groupid]);
            $groups = $response['ocs']['data']['groups'] ?? [];
            return in_array($groupid, $groups, true);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createGroup(string $groupid): void
    {
        $response = $this->request('POST', '/cloud/groups', ['groupid' => $groupid]);
        $this->assertOcsSuccess($response, "Gruppe '{$groupid}' erstellen");
    }

    public function ensureGroupExists(string $groupid): void
    {
        if (!$this->groupExists($groupid)) {
            $this->createGroup($groupid);
        }
    }

    public function addUserToGroup(string $userid, string $groupid): void
    {
        $response = $this->request('POST', "/cloud/users/{$userid}/groups", [
            'groupid' => $groupid,
        ]);
        $this->assertOcsSuccess($response, "Benutzer '{$userid}' zu Gruppe '{$groupid}' hinzufügen");
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
