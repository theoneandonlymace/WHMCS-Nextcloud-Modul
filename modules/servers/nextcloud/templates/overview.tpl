<div class="row">
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fas fa-cloud"></i> Nextcloud Speicher
                </h3>
            </div>
            <div class="panel-body">
                <div class="text-center" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 5px;">
                        {$ncQuotaUsed} von {$ncQuotaBooked} verwendet
                    </h4>
                    <span class="text-muted">({$ncQuotaPercent}% belegt)</span>
                </div>

                <div class="progress" style="height: 25px; margin-bottom: 20px;">
                    {if $ncQuotaPercent < 80}
                        {assign var="barClass" value="progress-bar-success"}
                    {elseif $ncQuotaPercent < 95}
                        {assign var="barClass" value="progress-bar-warning"}
                    {else}
                        {assign var="barClass" value="progress-bar-danger"}
                    {/if}
                    <div class="progress-bar {$barClass}" role="progressbar"
                         aria-valuenow="{$ncQuotaPercent}" aria-valuemin="0" aria-valuemax="100"
                         style="width: {$ncQuotaPercent}%; min-width: 2em; line-height: 25px; font-size: 13px;">
                        {$ncQuotaPercent}%
                    </div>
                </div>

                <div class="row text-center">
                    <div class="col-xs-4">
                        <strong>{$ncQuotaBooked}</strong><br>
                        <small class="text-muted">Gebucht</small>
                    </div>
                    <div class="col-xs-4">
                        <strong>{$ncQuotaUsed}</strong><br>
                        <small class="text-muted">Verwendet</small>
                    </div>
                    <div class="col-xs-4">
                        <strong>{$ncQuotaFree}</strong><br>
                        <small class="text-muted">Verfügbar</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="fas fa-external-link-alt"></i> Schnellzugriff
                </h3>
            </div>
            <div class="panel-body text-center">
                <a href="{$ncUrl}" target="_blank" class="btn btn-primary btn-lg btn-block" style="margin-bottom: 15px;">
                    <i class="fas fa-cloud"></i> Nextcloud öffnen
                </a>
                <p class="text-muted" style="font-size: 12px;">
                    Sie werden zur Nextcloud-Anmeldeseite weitergeleitet.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fas fa-info-circle"></i> Kontoinformationen
        </h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-5">
                <strong>Benutzername</strong>
            </div>
            <div class="col-sm-7">
                {$ncUsername}
            </div>
        </div>
        <hr style="margin: 8px 0;">

        {if $ncEmail}
        <div class="row">
            <div class="col-sm-5">
                <strong>E-Mail</strong>
            </div>
            <div class="col-sm-7">
                {$ncEmail}
            </div>
        </div>
        <hr style="margin: 8px 0;">
        {/if}

        <div class="row">
            <div class="col-sm-5">
                <strong>Paket</strong>
            </div>
            <div class="col-sm-7">
                {$groupname} &mdash; {$ncQuotaBooked} Speicherplatz
            </div>
        </div>
        <hr style="margin: 8px 0;">

        <div class="row">
            <div class="col-sm-5">
                <strong>Status</strong>
            </div>
            <div class="col-sm-7">
                {if $ncEnabled}
                    <span class="label label-success">Aktiv</span>
                {else}
                    <span class="label label-danger">Deaktiviert</span>
                {/if}
            </div>
        </div>
        <hr style="margin: 8px 0;">

        <div class="row">
            <div class="col-sm-5">
                <strong>Nextcloud URL</strong>
            </div>
            <div class="col-sm-7">
                <a href="{$ncUrl}" target="_blank">{$ncUrl}</a>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">
            <i class="fas fa-file-invoice-dollar"></i> Abrechnungsinformationen
        </h3>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-5">
                <strong>{$LANG.orderpaymentmethod}</strong>
            </div>
            <div class="col-sm-7">
                {$paymentmethod}
            </div>
        </div>
        <hr style="margin: 8px 0;">

        <div class="row">
            <div class="col-sm-5">
                <strong>{$LANG.recurringamount}</strong>
            </div>
            <div class="col-sm-7">
                {$recurringamount}
            </div>
        </div>
        <hr style="margin: 8px 0;">

        <div class="row">
            <div class="col-sm-5">
                <strong>{$LANG.clientareahostingnextduedate}</strong>
            </div>
            <div class="col-sm-7">
                {$nextduedate}
            </div>
        </div>
        <hr style="margin: 8px 0;">

        <div class="row">
            <div class="col-sm-5">
                <strong>{$LANG.orderbillingcycle}</strong>
            </div>
            <div class="col-sm-7">
                {$billingcycle}
            </div>
        </div>
        <hr style="margin: 8px 0;">

        <div class="row">
            <div class="col-sm-5">
                <strong>{$LANG.clientareastatus}</strong>
            </div>
            <div class="col-sm-7">
                {$status}
            </div>
        </div>

        {if $suspendreason}
        <hr style="margin: 8px 0;">
        <div class="row">
            <div class="col-sm-5">
                <strong>{$LANG.suspendreason}</strong>
            </div>
            <div class="col-sm-7">
                {$suspendreason}
            </div>
        </div>
        {/if}
    </div>
</div>

<div class="row">
    {if $packagesupgrade}
    <div class="col-sm-6">
        <a href="upgrade.php?type=package&amp;id={$id}" class="btn btn-success btn-block">
            <i class="fas fa-arrow-up"></i> {$LANG.upgrade}
        </a>
    </div>
    {/if}
    <div class="col-sm-6">
        <a href="clientarea.php?action=cancel&amp;id={$id}" class="btn btn-danger btn-block{if $pendingcancellation} disabled{/if}">
            {if $pendingcancellation}
                {$LANG.cancellationrequested}
            {else}
                <i class="fas fa-times"></i> {$LANG.cancel}
            {/if}
        </a>
    </div>
</div>
