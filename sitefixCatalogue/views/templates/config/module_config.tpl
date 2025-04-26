{include file="./tabCatalog.tpl" assign="tableCatalog"}
{include file="./tuto.tpl" assign="tuto"}
<html>
  <body>
    <div class="container translate-content">
    <div id="google_translate_element"></div>
      <div class="alert alert-info">
        {$active_tab_index}
        This module allows you to add several catalogs to your homepage and customer page. It was developed by <strong>EMANUEL ABIZIMI</strong>
      <span>{$img}</span>
     
        </div>
      <div class="row">
        <div class="col-md-3">
          <ul class="nav nav-pills nav-stacked border">
            <li class="{if $active_tab_index==false}active{/if}">
              <a href="#default" data-toggle="tab">{l s="Tutorial"}</a>
            </li>
            <li class="{if $active_tab_index==1}active{/if}">
                <a href="#tab1" data-toggle="tab">{l s="select Brands"}</a>
            </li>
            <li class="{if $active_tab_index==2}active{/if}">
              <a href="#tab2" data-toggle="tab">{l s="Add Catalog"}</a>
            </li>
            <li class="{if $active_tab_index==3}active{/if}">
              <a href="#tab3" data-toggle="tab">{l s="Delete Catalog"}</a>
            </li>
          </ul>
        </div>
        <div class="col-md-9">
          <div class="tab-content bg-white">
            <div class="tab-pane {if $active_tab_index==false}active{/if}" id="default">
              {$tuto}
            </div>
            <div class="tab-pane {if $active_tab_index==1}active{/if}" id="tab1">
              {$tabs}
            </div>
            <div class="tab-pane {if $active_tab_index==2}active{/if}" id="tab2">
              {$catalog}
            </div>
            <div class="tab-pane  {if $active_tab_index==3}active{/if}" id="tab3">
              {$tableCatalog}
            </div>
          </div>
        </div>
      </div>
    </div>

    <p style="display:none" id="json_data">{$selected_values}</p>
    <input type="hidden" name="url_controller" value={$controller_url}>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        var options = document.querySelectorAll("select[name='SITEFIX_SELECTED_BRANDS[]'] option");
        var jsonSelectOption = document.querySelector("p[id='json_data']").innerHTML;
        jsonSelectOption = JSON.parse(jsonSelectOption);
        var sitefixRadio = document.querySelectorAll("input[name='SITEFIX_RADIO']");
      
        // change the paramenter of configuration page of module 
        var changeParam = ()=>{
            let currentUrl = window.location.href;
            let newActiveTabValue = 3;
            let url = new URL(currentUrl);
            url.searchParams.set('active_tab', newActiveTabValue);
            window.location.href = url.toString();
        }

        // Add the change event listener
        sitefixRadio.forEach(function(element) {
          element.addEventListener('change', function(e) {
            for (var index = 0; index < options.length; index++) {
              if (e.target.value == 1) {
                options[index].selected = true;
              } else {
                options[index].selected = false;
              }
            }
          });
        });

        jsonSelectOption.forEach((element, index) => {
          for (var i = 0; i < options.length; i++) {
            if (element["id"] == options[i].value) {
              options[i].selected = true;
            }
          }
        });

        // GET ALL ID OF CATALOGUE
        // Sélection du champ de recherche par attribut name
        var search = document.querySelector("input[name='search']");
        if (search) {
          // Ajout d'un écouteur d'événement pour l'événement 'keyup'
          search.addEventListener('keyup', function(event) {
            var value = event.target.value.toUpperCase(); // Valeur saisie dans le champ de recherche, en majuscules
            // Sélection de toutes les lignes (<tr>) du corps du tableau (<tbody>)
            var rows = document.querySelectorAll("tbody tr");
            // Parcourir toutes les lignes du tableau
            rows.forEach(function(row) {
              var rowText = row.textContent.toUpperCase(); // Contenu de la ligne en majuscules
              // Vérifier si la valeur de recherche est présente dans le contenu de la ligne
              if (rowText.includes(value)) {
                row.style.display = "table-row";
              } else {
                row.style.display = "none";
              }
            });
          });
        }

       
        // DELETE THE DATA FROM TABLE
        var deleteBtn = document.querySelectorAll("td button");
        deleteBtn.forEach(function(element) {
          element.addEventListener('click', function(e) {
            // Si l'élément cliqué n'est pas un bouton, chercher le bouton parent
            let targetButton = e.target.closest('button');
            if (targetButton) {
              let index = targetButton.id;
              let url = document.querySelector("input[name='url_controller']")
              let file_path = targetButton.getAttribute("data-url");
              url = url.value;
              fetch(url + "&ID=" + index + "&FILE_URL=" + file_path  , { method: "GET" })
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error('ERROR');
                        }else{
                          alert("hello");
                          changeParam();
                        }
                        return response.json();
                    })
                    .then((data) => {
                      changeParam();
                    })
                    .catch((error) => {
                        console.log(error);
                    });
            }
          });
        });
  });
    </script>
  </body>
</html>

  </body>
</html>