<div class="panel" id="fenixtrace-panel">
    <div class="panel-heading">
        <i class="icon-link"></i> FenixTrace Blockchain
    </div>
    <div class="panel-body">
        {if $fenixtrace_sync}
            <div class="form-group">
                <label class="control-label col-lg-3">Status:</label>
                <div class="col-lg-9">
                    {if $fenixtrace_sync.state == 'synced'}
                        <span class="label label-success">Synced</span>
                    {elseif $fenixtrace_sync.state == 'queued'}
                        <span class="label label-warning">Queued</span>
                    {elseif $fenixtrace_sync.state == 'error'}
                        <span class="label label-danger">Error</span>
                    {else}
                        <span class="label label-default">Draft</span>
                    {/if}
                </div>
            </div>

            {if $fenixtrace_sync.tx_hash}
            <div class="form-group">
                <label class="control-label col-lg-3">TX Hash:</label>
                <div class="col-lg-9">
                    <code style="word-break:break-all;font-size:11px;">{$fenixtrace_sync.tx_hash|escape:'html':'UTF-8'}</code>
                </div>
            </div>
            {/if}

            {if $fenixtrace_sync.notarization_tx_hash}
            <div class="form-group">
                <label class="control-label col-lg-3">Notarization:</label>
                <div class="col-lg-9">
                    <code style="word-break:break-all;font-size:11px;">{$fenixtrace_sync.notarization_tx_hash|escape:'html':'UTF-8'}</code>
                </div>
            </div>
            {/if}

            {if $fenixtrace_sync.last_sync_at}
            <div class="form-group">
                <label class="control-label col-lg-3">Last Sync:</label>
                <div class="col-lg-9">{$fenixtrace_sync.last_sync_at|escape:'html':'UTF-8'}</div>
            </div>
            {/if}

            {if $fenixtrace_sync.state == 'error' && $fenixtrace_sync.last_error}
            <div class="alert alert-danger" style="margin-top:10px;">
                <strong>Error:</strong> {$fenixtrace_sync.last_error|escape:'html':'UTF-8'}
            </div>
            {/if}
        {else}
            <p class="text-muted">This product has not been synced to FenixTrace yet.</p>
        {/if}

        <div style="margin-top:15px;">
            <a href="{$fenixtrace_sync_url|escape:'html':'UTF-8'}" class="btn btn-primary">
                <i class="icon-upload"></i>
                {if $fenixtrace_sync && $fenixtrace_sync.state == 'error'}
                    Retry FenixTrace
                {else}
                    Send to FenixTrace
                {/if}
            </a>
        </div>
    </div>
</div>
