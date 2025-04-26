{* Interface du formulaire *}

<style>
    #progress-container {
    margin: 20px 0;
}
.progress-bar {
    background-color: #28a745; /* Vert pour indiquer le succès */
    height: 20px;
}

</style>


 <div class="alert alert-danger" id="alert" style="display:none"> </div>


<div class="panel">
            <h3><i class="icon-cogs"></i> {l s='Master Modifier Settings'} </h3>

    <div id="progress-container" style="display: none;">
        <p>
            <strong>Traitement en cours...</strong>
        </p>
            <div class="progress">
                <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
    </div>


    <form id="upload_form" method="post" enctype="multipart/form-data" class="form-horizontal">
        
        {* Fichier CSV *}
        <div class="form-group row">
            <label class="col-sm-5 col-form-label">{l s='Upload CSV File'}</label>
            <div class="col-sm-7">
                <input type="file" name="csv_file" class="form-control">
            </div>
        </div>

        {* Index Produit *}
        <div class="form-group row">
            <label class="col-sm-5 col-form-label">{l s='Product index'}</label>
            <div class="col-sm-7">
                <input type="text" name="product_index" class="form-control" placeholder="Enter product index">
            </div>
        </div>

        {* Valeur de la colonne *}
        <div class="form-group row">
            <label class="col-sm-5 col-form-label">{l s='Column value'}</label>
            <div class="col-sm-7">
                <input type="text" name="column_value" class="form-control" placeholder="Enter column value">
            </div>
        </div>

        {* Sélection des caractéristiques *}
        <div class="form-group row">
            <label class="col-sm-5 col-form-label">{l s='Select the features'}</label>
            <div class="col-sm-7">
                <select name="features" class="form-control">
                    {foreach from=$features item=feature}
                        <option value={$feature.id_feature}>{$feature.name}</option>
                    
                    {/foreach}
                </select>
            </div>
        </div>

        {* Bouton d'envoi *}
        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary">
                <i class="icon-upload"></i> {l s='Upload'}
            </button>
        </div>

    </form>
</div>

<script>
   document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#upload_form");
    const buttonSubmit = form.querySelector("button[type='submit']");
    const featureControllerUrl = "{$featureControllerURl}"; // Lien vers le contrôleur 

    const progressBar = document.getElementById("progress-bar");
    const progressContainer = document.getElementById("progress-container");

    buttonSubmit.addEventListener("click", function (e) {
        e.preventDefault();
        handleFileUpload();
    });

    function handleFileUpload() {
        const errorMessage  = document.querySelector("#alert");
        errorMessage.style.display = 'none';


        let formData = new FormData(form);

        // Envoyer le fichier CSV en AJAX
        fetch(featureControllerUrl, {
            method: "POST",
            body: formData
        })
        .then(response => response.json()) // On attend une réponse JSON
        .then(data => {
          
        if (data.error) {
                // ⚠️ Affichage de l'erreur et arrêt du processus
              
                errorMessage.innerHTML   = data.message;
                errorMessage.style.display = 'block';

                throw new Error(data.message);
            }else{

                alert("ruinig")
                startProgressListener();
            }

          

        })
        .catch(error => {
            console.error("Erreur lors de l'envoi du fichier :", error);
            alert("Erreur d'envoi du fichier.");
        });
    }

    function startProgressListener() {
        progressContainer.style.display = "block";
        progressBar.style.width = "0%";
        progressBar.setAttribute("aria-valuenow", "0");

        let eventSource = new EventSource(featureControllerUrl + "&sse=1"); // Écouter le SSE

        eventSource.onmessage = function(event) {
            try {
                let data = JSON.parse(event.data);
                let progress = data.progress;

                console.log("Progression :", progress);
                progressBar.style.width = progress + "%";
                progressBar.setAttribute("aria-valuenow", progress);

                if (progress >= 100) {
                    eventSource.close();
                    setTimeout(() => {
                        alert("Mise à jour terminée !");
                        progressContainer.style.display = "none";
                    }, 500);
                }
            } catch (error) {
                console.error("Erreur SSE :", error.message);
            }
        };

        eventSource.onerror = function(event) {
            console.error("Erreur SSE", event);
            eventSource.close();
        };
    }
});

</script>


