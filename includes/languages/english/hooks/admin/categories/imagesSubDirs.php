<?php
/*
  Copyright (c) 2020, Denzel

  This work is licensed under a 
  Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.

  You should have received a copy of the license along with this work.
  If not, see <http://creativecommons.org/licenses/by-nc-nd/4.0/>.
*/

const PRODUCTS_IMAGE_DIRECTORY_VERSION = "V1.12";
define('SECTION_HEADING_IMAGES_SUB_DIRS', '<i class="fas fa-images fa-fw mr-1"></i>Product Images');
const TEXT_PRODUCTS_IMAGE_DIRECTORY = 'Image Directory';
const TEXT_PRODUCTS_IMAGE_ROOT_DIRECTORY = 'Default Folder';
const TEXT_PRODUCTS_IMAGE_NEW_FOLDER = 'New Folder: ';
const TEXT_PRODUCTS_IMAGE_GET_MODEL = 'Get Model';
const TEXT_PRODUCTS_IMAGE_GET_CAT_ID = 'Get Categorie ID';
const TEXT_PRODUCTS_ADD_MULTI_LARGE_IMAGES = '<i class="fas fa-plus fa-fw mr-1"></i>Add multiple Images';
const TEXT_PRODUCTS_REMOVE_LARGE_IMAGES = '<i class="fas fa-minus fa-fw mr-1"></i>Remove all Gallery Images';
const TEXT_REMOVE_ORPHANED_DB_ENTRIES  = '<i class="fas fa-minus fa-fw mr-1"></i>Remove orphaned DB-Entries';
const TEXT_ERROR_FILE_NOT_FOUND = 'Error: File not found.';
const TEXT_IMAGES_SUB_DIRS_NOTES_BUTTON = '<button class="btn btn-sm btn-warning" type="button" data-toggle="collapse" data-target="#imgSubDirsInfo" aria-expanded="false" aria-controls="imgSubDirsInfo">Information</button>';
const TEXT_IMAGES_SUB_DIRS_NOTES = '<div class="collapse" id="imgSubDirsInfo"><div class="card bg-warning mb-3"><div class="card-header">Productimages in Subdirectories Informations:<small class="float-sm-right">' . PRODUCTS_IMAGE_DIRECTORY_VERSION . '</small></div><div class="card card-body">Keep in mind, that the whole insert/update product process was pulled out into this hook. If there were deeper core changes, it might be necessary to remove the hook and wait for an update. The images of your existing products wouldn\'t disappear after removing the hook. The categories.php file will fall back to core image handling.<br><br>If you fill out the "New Folder" field, a new folder will be created - if you choose a file to upload, or not.<br><br>If you fill out the "New Folder" field and choose a folder from the pulldown menu, the new folder will be created into the choosen folder.<br><br>The choosen images will be copied into the selected folder. Preset is the folder from Main Image.<br><br>The "Get Model" and "Get Category ID" buttons will fill the announced strings into the new folder field.<br><br>With the "Add multiple Large Images" field you are able to choose multiple imagefiles at once. After saving your product, you may sort them and add HTML Content if wanted.<br><br>After every Image-Action, you have to save your product to confirm the changes.</div></div></div>';
const SUCCESS_CREATED_DIRECTORY = 'Folder  %s created in %s.';
