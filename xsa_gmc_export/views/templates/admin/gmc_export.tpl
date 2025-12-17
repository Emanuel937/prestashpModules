<div class="panel">
    <h3>{$module->l('Export Products to Google Merchant Center')}</h3>

    <div class="form-group">
        <label class="control-label" for="category_id">{$module->l('Select Category')}</label>
        <select id="category_id" class="form-control" data-ajax-url="{$ajax_url}">
            {foreach from=$categories item=cat}
                <option value="{$cat.id_category}">{$cat.name}</option>
            {/foreach}
        </select>
    </div>

    <div class="form-group">
        <label class="control-label" for="batch_size">{$module->l('Batch Size')}</label>
        <input type="number" id="batch_size" class="form-control" value="50" min="1" step="1">
    </div>

    <button class="btn btn-primary" type="button" id="start">
        {$module->l('Start Export')}
    </button>

    <p id="progress" style="margin-top:10px;"></p>
    <p id="report" style="margin-top:10px;color:red;"></p>

   <a id="feed_btn"
    href=""
    target="_blank"
    class="btn btn-success"
    style="display:none;margin-top:10px;">
        {$module->l('View / Download Feed')}
    </a>
    {if $link}
        <p> <a href="{$link}"> {$module->l("view flux")}</a> </p>
    {/if}

</div>
