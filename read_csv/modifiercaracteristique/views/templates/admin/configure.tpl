<form method="post" action="">
    <label for="product_id">ID du produit</label>
    <input type="number" name="product_id" id="product_id" value="{$product_id}" required />

    <label for="attribute_id">Caractéristique</label>
    <select name="attribute_id" id="attribute_id" required>
        {foreach from=$attributes item=attribute}
            <option value="{$attribute.id_attribute}">{$attribute.name}</option>
        {/foreach}
    </select>

    <label for="new_value">Nouvelle valeur</label>
    <input type="text" name="new_value" id="new_value" required />

    <label for="action">Action</label>
    <select name="action" id="action" required>
        <option value="add">Ajouter</option>
        <option value="replace">Remplacer</option>
        <option value="delete">Supprimer</option>
    </select>

    <button type="submit" name="submit_{$module_name}">Sauvegarder</button>
</form>
