<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<h4 class="input-group mb-3">
   <strong >Search:</strong> <input  style="margin-top:10px" type="search" placeholder="search ...." name="search">
</h4>
<table class="table table-striped table-bordered " >
    <thead class="thead-dark">
        <tr>
            <th scope="col">ID</th>
            <th scope="col">CATALOG NAME</th>
            <th scope="col">MARQUE</th>
            <th scope="col">DELETE</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$db_query item=item key=key name=name}
        <tr>
            <td>{$item.catalog_id}</td>
            <td>{$item.catalog_name}</td>
            {foreach from=$all_brands item=brands_item}
                {if $brands_item.id == $item.brand}
                    <td>{$brands_item.name}</td>
                {/if}
            {/foreach}
            <td>
                <button type="button" class="btn btn-danger" id="{$item.catalog_id}" data-url="{$item.file_name}">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    {/foreach}
</tbody>

</table>

