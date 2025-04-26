<style>
    .card {
        display: flex;
        flex-wrap: wrap; /* Ajout pour gérer le wrapping des éléments */
    }
    .card > div {
        width: 45%;
        padding: 10px; /* Ajout de padding pour l'espace entre les éléments */
        box-sizing: border-box; /* Pour inclure le padding dans la largeur */
        margin:10px ;
        margin-right: 10px;
        
       }
    .img_tuto {
        width: 100%; /* Ajustement pour remplir la largeur de son parent */
        max-width: 400px; /* Limitation de la largeur maximale */
        height: auto; /* Pour conserver les proportions de l'image */
        object-fit: contain;
    }
    h2 {
        font-size: 18px; /* Correction de la taille de police */
        margin-top: 0; /* Suppression de la marge supérieure par défaut */
    }
    h3{
        margin-top:20px
    }
    p {
        margin-bottom: 10px; /* Ajout d'un espace entre les paragraphes */
    }
</style>
<div class="alert alert-warning">
    <p>{l s="Before all, it is important to understand that some web hosting providers don't allow you to upload files that are too large. In this case, if your catalog is too heavy, it may not work on your server. Make sure that your web hosting provider allows you to upload files of sufficient size"}</div>
<h1 class="h3">How to use sitefixCatalog module</h1>
<hr>
<section class="card">
    <div>
        <h3>1. Select brands</h3>
        <p>On the select brands tab, you must <strong>select the brands</strong> that you want to add a catalog for. You can select all at once by clicking "select all brands yes".</p>
        <img class="img_tuto" src="{$img}1.png" alt="Image description">
    </div>
    <div>
    <h3>3. Delete catalog</h3>
    <p>If you want to delete an existing catalog, you just need to go to the "Delete catalog" section and click the delete button.</p>
    <img class="img_tuto" src="{$img}3.png" alt="Image description">
</div>
    <div>
        <h3>2. Adding catalog</h3>
        <p>Once you have selected the brands that you want to work with, it's time to add a catalog to your brands.</p>
        <p><strong>Catalog name:</strong> On the add catalog tab, you must enter the name of the catalog. It can be anything; it's just a name.</p>
        <p><strong>File:</strong> This is the PDF file of your catalog. It must be a PDF file.</p>
        <p><strong>Catalog cover:</strong> This is the preview image of your catalog. You can use any image, but its extension must be either PNG or JPEG.</p>
        <p><strong>The brands for the catalog:</strong> These are the brands that correspond to your catalog.</p>
        <img class="img_tuto" src="{$img}2.png" alt="Image description">
    </div>

    <div>
        <h3>4. View your catalog</h3>
        <p>After adding your catalog, if you want to view it, you can go to the provided link _____. If you want to add it to your header menu, create a customer menu item and add this link.</p>
        <img class="img_tuto" src="{$img}4.png" alt="Image description">
        <p>After adding your catalog, if you want to view it, you can go to the provided link _____. If you want to add it to your header menu, create a customer menu item and add this link.</p>
        <img class="img_tuto" src="{$img}5.png" alt="Image description">
    </div>
</section>
