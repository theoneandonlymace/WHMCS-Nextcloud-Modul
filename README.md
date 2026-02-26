# WHMCS Nextcloud Provisioning Module

## Overview

This WHMCS provisioning module enables automated management of Nextcloud user accounts via the [OCS Provisioning API](https://docs.nextcloud.com/server/latest/admin_manual/configuration_user/user_provisioning_api.html).

### Features

- **User Creation** – Automatically creates a Nextcloud account when an order is placed
- **Group Assignment** – Automatically assigns users to a configurable Nextcloud group (created if it doesn't exist)
- **Quota Management** – Storage quota is set according to the ordered package
- **Package Changes** – Quota is automatically adjusted on upgrade/downgrade
- **Suspend/Unsuspend** – User is disabled on payment failure and re-enabled on payment
- **Termination** – Configurable: delete or disable the user
- **Password Change** – Supports password changes via WHMCS
- **Client Area** – Displays booked and used storage with a progress bar
- **Nextcloud Link** – Direct link to the Nextcloud instance in the client area
- **Email Sync** – Email address is synced to Nextcloud when the client profile is updated

## Installation

1. Copy the `modules/servers/nextcloud/` folder into your WHMCS installation directory:

```
whmcs/modules/servers/nextcloud/
├── lib/
│   └── NextcloudAPI.php
├── templates/
│   ├── overview.tpl
│   └── error.tpl
├── hooks.php
└── nextcloud.php
```

2. In WHMCS, go to **Setup → Products/Services → Servers** and create a new server:
   - **Type**: Nextcloud
   - **Hostname**: Your Nextcloud domain (e.g. `cloud.example.com`)
   - **Username**: Nextcloud admin username
   - **Password**: Nextcloud admin password
   - **Secure**: Check for HTTPS (recommended)
   - Click **Test Connection** to verify the connection

3. Create a product under **Setup → Products/Services → Products/Services**:
   - **Module Settings** tab:
     - **Module Name**: Nextcloud
     - **Server**: Select the server created above
     - **Quota**: Enter storage quota (e.g. `5 GB`, `50 GB`, `1 TB`)
     - **Group Name**: Nextcloud group name (optional)
     - **Termination**: Choose termination behavior
   - **Custom Fields** tab:
     - Create field: **Field Name** = `Nextcloud Username`, **Type** = Text, **Required** = Yes
     - Create field: **Field Name** = `Nextcloud Password`, **Type** = Password, **Required** = Yes

## Configuration Options

| Option | Description | Example |
|--------|-------------|---------|
| Quota | Storage space for the Nextcloud user | `5 GB`, `50 GB`, `1 TB` |
| Group Name | Nextcloud group for user assignment | `Premium-Users` |
| Termination | Behavior on service termination | `disable` or `delete` |

## Requirements

- WHMCS 7.0 or higher
- PHP 7.2 or higher with the cURL extension
- Nextcloud server with the OCS Provisioning API enabled
- A Nextcloud admin account for API access

## Nextcloud Requirements

The OCS Provisioning API must be reachable on the Nextcloud server. Make sure that:

- The Nextcloud server is accessible via HTTPS
- An admin user with full privileges exists
- The Provisioning API is not blocked by firewall rules

## License

MIT License
