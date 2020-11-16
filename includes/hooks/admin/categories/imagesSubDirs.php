<?php
/*
  Productimages in Subdirectories V1.12 Copyright (c) 2020, @Denzel
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

  1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

  2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

  3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class hook_admin_categories_imagesSubDirs {

  function listen_preAction() {
    global $current_category_id, $cPath, $messageStack, $OSCOM_Hooks;
    
    $this->load_lang();

    $images_dir = "images/"; // default catalog images folder;
    $root_images_dir = DIR_FS_CATALOG_IMAGES;
      
    $action = (isset($_GET['action']) ? $_GET['action'] : '');
    
    if ($action=="insert_product" || $action=="update_product") {

        if (isset($_GET['pID'])) $products_id = tep_db_prepare_input($_GET['pID']);
        $products_date_available = tep_db_prepare_input($_POST['products_date_available']);

        $products_date_available = (date('Y-m-d') < $products_date_available) ? $products_date_available : 'null';

        $sql_data_array = array('products_quantity' => (int)tep_db_prepare_input($_POST['products_quantity']),
                                'products_model' => tep_db_prepare_input($_POST['products_model']),
                                'products_price' => tep_db_prepare_input($_POST['products_price']),
                                'products_date_available' => $products_date_available,
                                'products_weight' => (float)tep_db_prepare_input($_POST['products_weight']),
                                'products_status' => tep_db_prepare_input($_POST['products_status']),
                                'products_tax_class_id' => tep_db_prepare_input($_POST['products_tax_class_id']),
                                'manufacturers_id' => (int)tep_db_prepare_input($_POST['manufacturers_id']));
        $sql_data_array['products_gtin'] = (tep_not_null($_POST['products_gtin'])) ? str_pad(tep_db_prepare_input($_POST['products_gtin']), 14, '0', STR_PAD_LEFT) : 'null';
        
          $new_dir = preg_replace('/[^a-zA-Z0-9_.-]/i', '_',$_POST['new_directory']);
          $dir = (tep_not_null($new_dir) ? $_POST['directory'].'/'.$new_dir : $_POST['directory']);
          $dir = ($dir ? $dir .'/' : '');

        if ($dir && !is_dir($root_images_dir . $dir)) {
          if (mkdir($root_images_dir . $dir)) $messageStack->add_session(sprintf(SUCCESS_CREATED_DIRECTORY, $new_dir, $images_dir.$_POST['directory']), 'success');
        }

        $mimetype = array('image/jpeg', 'image/gif', 'image/png');
        if ($_FILES['products_image']['error'] != 4) {
          if (in_array(mime_content_type($_FILES['products_image']['tmp_name']), $mimetype)) {
            $products_image = new upload('products_image');
            $products_image->set_destination($root_images_dir . $dir);
            if ($products_image->parse() && $products_image->save()) {
              $sql_data_array['products_image'] = $dir . tep_db_prepare_input($products_image->filename);
              $messageStack->add_session($products_image->filename.'&nbsp;'.SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');
            }
          } else {
            $messageStack->add_session($_FILES['products_image']['name'].'&nbsp;'. ERROR_FILETYPE_NOT_ALLOWED, 'warning');
          }
        }

        if ($action == 'insert_product') {
          $insert_sql_data = array('products_date_added' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

          tep_db_perform('products', $sql_data_array);
          $products_id = tep_db_insert_id();

          tep_db_query("insert into products_to_categories (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$current_category_id . "')");
        } elseif ($action == 'update_product') {
          $update_sql_data = array('products_last_modified' => 'now()');

          $sql_data_array = array_merge($sql_data_array, $update_sql_data);

          tep_db_perform('products', $sql_data_array, 'update', "products_id = '" . (int)$products_id . "'");
        }

        $languages = tep_get_languages();
        for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
          $language_id = $languages[$i]['id'];

          $sql_data_array = array('products_name' => tep_db_prepare_input($_POST['products_name'][$language_id]),
                                  'products_description' => tep_db_prepare_input($_POST['products_description'][$language_id]),
                                  'products_url' => tep_db_prepare_input($_POST['products_url'][$language_id]));
          $sql_data_array['products_seo_description'] = tep_db_prepare_input($_POST['products_seo_description'][$language_id]);
          $sql_data_array['products_seo_keywords'] = tep_db_prepare_input($_POST['products_seo_keywords'][$language_id]);
          $sql_data_array['products_seo_title'] = tep_db_prepare_input($_POST['products_seo_title'][$language_id]);

          if ($action == 'insert_product') {
            $insert_sql_data = array('products_id' => $products_id,
                                     'language_id' => $language_id);

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            tep_db_perform('products_description', $sql_data_array);
          } elseif ($action == 'update_product') {
            tep_db_perform('products_description', $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and language_id = '" . (int)$language_id . "'");
          }
        }

        $pi_sort_order = 0;
        $piArray = array(0);
        
        foreach ($_FILES as $key => $value) {
// Update existing large product images
          if (preg_match('/^products_image_large_([0-9]+)$/', $key, $matches)) {
                $pi_sort_order++;

                $sql_data_array = array('htmlcontent' => tep_db_prepare_input($_POST['products_image_htmlcontent_' . $matches[1]]),
                                        'sort_order' => $pi_sort_order);

            if ($_FILES['products_image_large_'.$matches[1]]['error'] != 4) {
              $t="";
              $t = new upload($key);
              if (in_array(mime_content_type($_FILES['products_image_large_'.$matches[1]]['tmp_name']), $mimetype)) {
                $t->set_destination($root_images_dir . $dir);
                if ($t->parse() && $t->save()) {
                  $sql_data_array['image'] = $dir . tep_db_prepare_input($t->filename);
                  $messageStack->add_session($t->filename.'&nbsp;'.SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');
                }
              } else {
                $messageStack->add_session($_FILES['products_image_large_'.$matches[1]]['name'].'&nbsp;'. ERROR_FILETYPE_NOT_ALLOWED, 'warning');
              }
            }
            tep_db_perform('products_images', $sql_data_array, 'update', "products_id = '" . (int)$products_id . "' and id = '" . (int)$matches[1] . "'");
            $piArray[] = (int)$matches[1];
          } elseif (preg_match('/^products_image_large_new_([0-9]+)$/', $key, $matches)) {
// Insert new large product images
                $sql_data_array = array('products_id' => (int)$products_id,
                                        'htmlcontent' => tep_db_prepare_input($_POST['products_image_htmlcontent_new_' . $matches[1]]));

            if ($_FILES['products_image_large_new_'.$matches[1]]['error'] != 4) {
              $t="";
              $t = new upload($key);
              if (in_array(mime_content_type($_FILES['products_image_large_new_'.$matches[1]]['tmp_name']), $mimetype)) {
                $t->set_destination($root_images_dir . $dir);
                if ($t->parse() && $t->save()) {
                  $pi_sort_order++;

                  $sql_data_array['image'] = $dir . tep_db_prepare_input($t->filename);
                  $sql_data_array['sort_order'] = $pi_sort_order;

                  tep_db_perform('products_images', $sql_data_array);

                  $piArray[] = tep_db_insert_id();
                  $messageStack->add_session($t->filename.'&nbsp;'.SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');
                }
              } else {
                $messageStack->add_session($_FILES['products_image_large_new_'.$matches[1]]['name'].'&nbsp;'. ERROR_FILETYPE_NOT_ALLOWED, 'warning');
              }
            }
          }
          if ($key == 'products_multiple_images_new') {
// Insert multiple large product images
                if ($_FILES['products_multiple_images_new']['error'][0] != 4) {
                    $sql_data_array = array('products_id' => (int)$products_id);
                    $num_files = count($_FILES['products_multiple_images_new']['tmp_name']);
                    for($i=0; $i < $num_files;$i++) {
                        if (in_array(mime_content_type($_FILES['products_multiple_images_new']['tmp_name'][$i]), $mimetype)) {
                            if(!is_uploaded_file($_FILES['products_multiple_images_new']['tmp_name'][$i])) {
                                $messageStack->add_session(WARNING_NO_FILE_UPLOADED, 'warning');
                            } else {
                                if (move_uploaded_file($_FILES['products_multiple_images_new']['tmp_name'][$i], DIR_FS_CATALOG_IMAGES . '/' . $dir . $_FILES['products_multiple_images_new']['name'][$i])) {
                                  $sql_data_array['image'] = $dir . $_FILES['products_multiple_images_new']['name'][$i];
                                  $sql_data_array['sort_order'] = $i;
                                  tep_db_perform('products_images', $sql_data_array);
                                  $piArray[] = tep_db_insert_id();
                                  $messageStack->add_session($_FILES['products_multiple_images_new']['name'][$i].'&nbsp;'.SUCCESS_FILE_SAVED_SUCCESSFULLY, 'success');
                                } else {
                                  $messageStack->add_session($_FILES['products_multiple_images_new']['name'][$i].'&nbsp;'.WARNING_NO_FILE_UPLOADED, 'warning');
                                }
                            }
                        } else {
                            $messageStack->add_session($_FILES['products_multiple_images_new']['name'][$i].'&nbsp;'. ERROR_FILETYPE_NOT_ALLOWED, 'warning');
                        }
                    }
                }
            }
        }

        $product_images_query = tep_db_query("select image from products_images where products_id = '" . (int)$products_id . "' and id not in (" . implode(',', $piArray) . ")");
        if (tep_db_num_rows($product_images_query)) {
          while ($product_images = tep_db_fetch_array($product_images_query)) {
            $duplicate_image_query = tep_db_query("select count(*) as total from products_images where image = '" . tep_db_input($product_images['image']) . "'");
            $duplicate_image = tep_db_fetch_array($duplicate_image_query);

            if ($duplicate_image['total'] < 2) {
              if (file_exists($root_images_dir . $product_images['image'])) {
                @unlink($root_images_dir . $product_images['image']);
              }
            }
          }

          tep_db_query("delete from products_images where products_id = '" . (int)$products_id . "' and id not in (" . implode(',', $piArray) . ")");
        }
        if (!isset($GLOBALS['products_id'])) {
          $GLOBALS['products_id'] = $products_id;
        }
        $OSCOM_Hooks->call('categories', 'productActionSave');

        tep_redirect(tep_href_link('categories.php', 'cPath=' . $cPath . '&pID=' . $products_id));
    }
  }

  function listen_productTab() {
    global $pInfo, $current_category_id, $base_url;
    
    $tab_title = addslashes(SECTION_HEADING_IMAGES_SUB_DIRS);
    $tab_link  = '#section_imagesSubDirs_content';
    
    $textMainImage = TEXT_PRODUCTS_MAIN_IMAGE;
    $textImageDirectory = TEXT_PRODUCTS_IMAGE_DIRECTORY;
    $imgLargeImage = TEXT_PRODUCTS_OTHER_IMAGES;
    $imgLargeImageHTML = TEXT_PRODUCTS_LARGE_IMAGE_HTML_CONTENT;
    $addLargeImage = TEXT_PRODUCTS_ADD_LARGE_IMAGE;
    $removeLargeImages = TEXT_PRODUCTS_REMOVE_LARGE_IMAGES;
    $addMultiLargeImages = TEXT_PRODUCTS_ADD_MULTI_LARGE_IMAGES;
    $imagesSubDirsNotes = TEXT_IMAGES_SUB_DIRS_NOTES;
    $imagesSubDirsNotesButton = TEXT_IMAGES_SUB_DIRS_NOTES_BUTTON;
    $imgLargeImages = '';
    $orphimg = '';
    $delOrphImgButton = '';

    $root_images_dir = DIR_FS_CATALOG_IMAGES; // default catalog images folder;
    $exclude_folders = array("apps"); // folders to exclude from adding new images
    
    $dir_array = array();
    if ($handle = @opendir($root_images_dir)) { 
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                if (is_dir($root_images_dir.$file) && !in_array($file,$exclude_folders)) $dir_array[] = preg_replace("/\/\//si", "/", $file);
            }
        }
        closedir($handle);
        sort($dir_array);
    } 
    $drop_array[0] = array('id' => '', 'text' => TEXT_PRODUCTS_IMAGE_ROOT_DIRECTORY);
    foreach($dir_array as $img_dir) {
        $drop_array[] = array('id' => $img_dir, 'text' => $img_dir);
    }
    if (substr_count($pInfo->products_image,'/') > 1) {
      $drop_array[] = array('id' => substr($pInfo->products_image,0,strrpos($pInfo->products_image,'/')), 'text' => substr($pInfo->products_image,0,strrpos($pInfo->products_image,'/')));
    }
    $dirPulldown = '<div class="mb-2">' . tep_draw_pull_down_menu('directory', $drop_array, substr($pInfo->products_image,0,strrpos($pInfo->products_image,'/')), 'class="custom-select"') . '</div><div class="mb-2 input-group">' . tep_draw_input_field('new_directory', '', 'id="new_dir" class="form-control" placeholder="' . TEXT_PRODUCTS_IMAGE_NEW_FOLDER . '"') . '<div class="input-group-append btn-group"><button class="btn btn-secondary" type="button" onclick="new_directory.value=products_model.value">' . TEXT_PRODUCTS_IMAGE_GET_MODEL . '</button><button class="btn btn-secondary" type="button" onclick="new_directory.value=\'cat_' . $current_category_id . '\'">' . TEXT_PRODUCTS_IMAGE_GET_CAT_ID . '</button></div></div>';

    $mainImageField = '<div class="row mb-2"><div class="col-auto">' . tep_info_image($pInfo->products_image, $pInfo->products_name, SMALL_IMAGE_WIDTH, SMALL_IMAGE_HEIGHT) . '</div><div class="col"><div class="custom-file mb-2">' . tep_draw_input_field('products_image', '', 'id="products_image"', 'file', null, (!tep_not_null($pInfo->products_image) ? 'required aria-required="true" ' : null) . 'class="form-control-input custom-file-input"') . '<label class="custom-file-label" for="products_image">' . $pInfo->products_image . '</label></div>' . (tep_not_null($pInfo->products_image) ? '<a href="../images/' . $pInfo->products_image . '" target="_blank">' . $pInfo->products_image . '</a>' : '') . '</div></div>';                

    $pi_counter = 0;
    foreach ($pInfo->products_larger_images as $pi) {
      $pi_counter++;
      if (file_exists(DIR_FS_CATALOG_IMAGES . $pi['image'])) {
        $pi_size = getimagesize(DIR_FS_CATALOG_IMAGES . $pi['image']);
        $imgLargeImages .= '<div class="row mr-0 mb-2" id="piId' . $pi_counter . '"><div class="col-1 text-right"><span class="font-weight-bold h5">' . $pi_counter . '. </span><i class="fas fa-arrows-alt-v mr-2"></i><a href="#" class="piDel" data-pi-id="' . $pi_counter . '"><i class="fas fa-trash text-danger"></i></a></div><div class="col-2">' . tep_image(HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'images/' . $pi['image'], $pInfo->products_name, "", "", "", TRUE, "rounded") . '</div><div class="col-9"><div class="custom-file mb-2">' . tep_draw_input_field('products_image_large_' . $pi['id'], '', 'id="pImg' . $pi_counter . '"', 'file', null, 'class="custom-file-input"') . '<label class="custom-file-label text-truncate" for="pImg' . $pi_counter . '">' . $pi['image'] . '</label></div>' . tep_draw_textarea_field('products_image_htmlcontent_' . $pi['id'], 'soft', '70', '3', $pi['htmlcontent']) . '<small class="form-text text-muted">' . TEXT_PRODUCTS_LARGE_IMAGE_HTML_CONTENT . '</small></div></div>';
      } else {
        $orphimg = true;
        $imgLargeImages .= '<div class="row mr-0 mb-2" id="piIdOrph' . $pi_counter . '"><div class="col-1 text-right"><span class="font-weight-bold h5">' . $pi_counter . '. </span><i class="fas fa-arrows-alt-v mr-2"></i><a href="#" class="piDel" data-pi-id="' . $pi_counter . '"><i class="fas fa-trash text-danger"></i></a></div><div class="col"><div class="custom-file mb-2">' . tep_draw_input_field('products_image_large_' . $pi['id'], '', 'id="pImg' . $pi_counter . '"', 'file', null, 'class="custom-file-input is-invalid"') . '<div class="invalid-feedback">' . TEXT_ERROR_FILE_NOT_FOUND . '</div><label class="custom-file-label text-danger text-truncate" for="pImg' . $pi_counter . '">' . $pi['image'] . '</label></div></div><div class="col">' . tep_draw_textarea_field('products_image_htmlcontent_' . $pi['id'], 'soft', '70', '3', $pi['htmlcontent']) . '<small class="form-text text-muted">' . TEXT_PRODUCTS_LARGE_IMAGE_HTML_CONTENT . '</small></div></div>';
      }
    }
    
    if ($orphimg) {
      $delOrphImgButton = '<br><button type="button" class="btn btn-warning btn-sm text-white mt-2" onclick="remOrphPiForm();return false;">' . TEXT_REMOVE_ORPHANED_DB_ENTRIES . '</button>';
    }
    
    $output = <<<EOD
<div class="tab-pane fade" id="section_imagesSubDirs_content" role="tabpanel">

    <div class="mb-3">
        <div class="form-group row">
            <label class="col-form-label col-sm-3 text-left text-sm-right">{$textImageDirectory}</label>
            <div class="col-sm-9">
                {$dirPulldown}
            </div>
        </div>

        <hr>
    
        <div class="form-group row">
            <label for="pImg" class="col-form-label col-sm-3 text-left text-sm-right">{$textMainImage}</label>
            <div class="col-sm-9">
                {$mainImageField}
            </div>
        </div>

        <hr>

        <div class="form-group row">
            <div class="col-sm-3 text-left text-sm-right">
                {$imgLargeImage}
                <br><a class="btn btn-info btn-sm text-white mt-2" role="button" href="#" id="add_image" onclick="addNewPiForm();return false;">{$addLargeImage}</a>
                <br><div id="products_multiple_images_div" class="file btn btn-primary btn-sm text-white mt-2">
                    {$addMultiLargeImages}
                    <input type="file" name="products_multiple_images_new[]" id="products_multiple_images" multiple/>
                </div>
                {$delOrphImgButton}
                <br><button type="button" class="btn btn-danger btn-sm text-white mt-2" onclick="remPiForm();return false;">{$removeLargeImages}</button>
            </div>

            <div class="col-sm-9" id="piList">
                {$imgLargeImages}
            </div>
        </div>
  
  
        <div class="form-group row">
            <div class="col-sm-3 text-left text-sm-right">
                {$imagesSubDirsNotesButton}
            </div>

            <div class="col-sm-9">
                {$imagesSubDirsNotes}
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    #piList { list-style-type: none; margin: 0; padding: 0; }
    #piList li { margin: 5px 0; padding: 2px; }
    #products_multiple_images { position: absolute; opacity: 0; right: 0; top: 0; }
    #products_multiple_images_div { position: relative; overflow: hidden; }
</style>
<script>
    var piSize = {$pi_counter};
    function addNewPiForm() {
        piSize++;
        $('#piList').append('<div class="row mr-0 mb-2" id="piId' + piSize + '"><div class="col-1 text-right"><i class="fas fa-arrows-alt-v mr-2"></i><a href="#" class="piDel" data-pi-id="' + piSize + '"><i class="fas fa-trash text-danger"></i></a></div><div class="col"><div class="custom-file mb-2"><input type="file" class="custom-file-input form-control-input" id="pImg' + piSize + '" name="products_image_large_new_' + piSize + '"><label class="custom-file-label text-truncate" for="pImg' + piSize + '">&nbsp;</label></div></div><div class="col"><textarea name="products_image_htmlcontent_new_' + piSize + '" wrap="soft" class="form-control" cols="70" rows="3"></textarea><small class="form-text text-muted">{$imgLargeImageHTML}</small></div></div>');
        bindChange();
    }
    function remPiForm() {
        $('div[id^="piId"').effect('blind').remove();
    }
    function remOrphPiForm() {
        $('div[id^="piIdOrph"').effect('blind').remove();
    }
    function bindChange() {
        $('input[type="file"]').change(function(e){
            var fileName = e.target.files[0].name;
            $(this).parent().find($('.custom-file-label')).html(fileName);
        });
    $('a.piDel').click(function(e){
        var p = $(this).data('pi-id');
        $('#piId' + p).effect('blind').remove();
        e.preventDefault();
    });
    }
    $(function() { 
        $('#productTabs ul.nav.nav-tabs').append('<li class="nav-item"><a class="nav-link" data-toggle="tab" href="{$tab_link}" role="tab">{$tab_title}</a></li>'); 
        $('#section_images_content').remove(); 
        $('a[href$="#section_images_content"]').parent().remove(); 
        bindChange();
        $('#piList').sortable({
            containment: 'parent'
        });
        $('#products_multiple_images').on("change", function() {
            let filenames = [];
            let files = document.getElementById("products_multiple_images").files;
            if (files.length > 1) {
                filenames.push("Total Files (" + files.length + ")");
            } else {
                for (let i in files) {
                    if (files.hasOwnProperty(i)) {
                        filenames.push(files[i].name);
                    }
                }
            }
        $(this).next(".custom-file-label").html(filenames.join(","));
        });
    });
</script>
EOD;

    return $output;
    }
  
 function load_lang() {
    global $language;

    require(DIR_FS_CATALOG . 'includes/languages/' . $language . '/hooks/admin/categories/imagesSubDirs.php');
    }

}