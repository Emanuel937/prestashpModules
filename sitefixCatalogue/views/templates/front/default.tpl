{extends file="page.tpl"}
{block name="page_content_container"}

<head>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}sitefixCatalogue/views/assets/css/theme.css">
   <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}sitefixCatalogue/views/assets/css/angartheme.css">
   <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}sitefixCatalogue/views/assets/css/black.css">
   <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}sitefixCatalogue/views/assets/css/home_modyficators.css">
   <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}sitefixCatalogue/views/assets/css/rwd.css">
   <link rel="stylesheet" href="{$smarty.const._MODULE_DIR_}sitefixCatalogue/views/assets/css/custom.css">
   <style>
      .flexcard {
         display: flex;
         flex-wrap: wrap;
      }

      .catalogues {
         width: 22%;
         border: 0.50px solid lightgray;
         margin: 10px;
      }

      .catalogues img {
         width: 100%;
         height: 270px;
         object-fit:cover
      }

      .catalog-title {
         font-size: 12px;
         color: rgb(62, 62, 62);
         line-height: 10px;
         padding: 3px;
      }

      .download {
         border-left: 1px solid lightgray;
         color: rgb(162, 161, 161);
         padding: 5px;
      }

      .seebtn {
         font-size: 13px;
         width: 100px;
         color: black;
         border-left: 1px solid lightgray;
         border-right: 1px solid lightgray;
         text-align: center;
      }

      .title-brands {
         font-size: 14px;
	


      }

      i {
         color: rgb(162, 161, 161);
         font-size: 12px;
      }

      a {
         color: rgb(47, 47, 47);
      }

      .light-element {
         padding-top: 5px;
	 padding-bottom:5px;
Padding-left:4px
      }

      .border-item {
         border-top: 0.9px solid lightgray;
         border-right: 0.50px solid lightgray;
         border-left: 0.50px solid lightgray;
         border-bottom: 0.9px solid lightgray;
      }
.d-flex{
	display:flex;
	justify-content:space-between;
}
a:hover{
Border:none}

#_desktop_top_menu ul.top-menu li.home_icon i {
  display: inline-block;
  font: normal normal normal 14px/1 FontAwesome;
    font-size: 14px;
    line-height: 1;
  font-size: inherit;
  text-rendering: auto;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  transform: translate(0, 0);
  font-size: 22px;
  line-height: 14px;
  vertical-align: -2px;
  color: white;
  font-size: 13px;
}

.flex-style{
   display: flex;
   flex-wrap: wrap;
}

.flex-style > div{
   width: 19%;
   border: 1px solid lightgray;
   height: 130px;
   padding: 20px;
   margin-top: 10px;
   margin-bottom:10px;
   margin-left:10px
}

.flex-style > div img{

   width: 100%;
   height: 100%;
   object-fit: cover;
   max-height: 100px;
}

   </style>
</head>
      <div class="catalogue-container">
         {if $isbrands == false}
               <div class="flex-style" >
                  {foreach from=$brands item=item key=key name=name}
                     {foreach from=$selected_brand item=selected}
                        {if $selected.brandsID == $item.id} 
                           <div class="container">
                              <a href="{$controller_url|replace:'http://':'https://'}&brands={$selected.brandsID}" class="d-flex">
                                 <img src={$item.image}>
                              </a>
                           </div>
                        {/if}
                     {/foreach}
                  {/foreach}
               </div>
         {else}
      <div class="flexcard">
         {foreach from=$catalogs item=item key=key name=name}
            <div class="catalogues">
               <img src="{$item.cover_img}" >
               <div class="d-flex">
                  <h5 class="catalog-title small bold light-element">{$item.catalog_name}</h5>
                  <a href="{$item.file_name|replace:'http://':'https://'}" class="seebtn light-element" target="_blank">
                     {l s="visualiser"} <i class="fas fa-eye"></i>
                  </a>
                  <a href="{$item.file_name|replace:'http://':'https://'}" class="light-element" download>
                     <i class="fas fa-download"></i>
                  </a>
               </div>
            </div>
         {/foreach}
      </div>
   {/if}
   </div>
{/block}
