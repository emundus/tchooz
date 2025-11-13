<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2025. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Form\Form;
use Falang\Component\Administrator\Table\FalangContentTable;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Router;
use Joomla\CMS\Table\Table;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Component\Router\RouterInterface;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\CMS\Menu\MenuFactoryInterface;
use Joomla\CMS\Router\SiteRouter;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Event\ConnectionEvent;
use Joomla\DI\Container;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;


/**
 *  Falang Custom Fields System Plugin
 */
class PlgSystemFalangCF extends CMSPlugin
{
    /**
     *  Auto load plugin's language file
     *
     *  @var  boolean
     */
    protected $autoloadLanguage = true;

    /**
     *  Application Object
     *
     *  @var  object
     */
    protected $app;

    /**
     *  Append publishing assignments XML to the
     *
     * @udpate 5.22 add the load custom fields in the translated form to falangcf
     *
     *  @param   Form   $form  The form to be altered.
     *  @param   mixed  $data  The associated data for the form.
     *
     *  @return  boolean
     */
    public function onContentPrepareForm(Form $form, $data)
    {
        // Run only on backend
        if (!$this->app->isClient('administrator') || !$form instanceof Form)
        {
            return;
        }

        $custom_fields = PluginHelper::isEnabled('system', 'fields');
        if ($custom_fields){
            $this->loadCustomFields($form, $data);
        }

        $context = $form->getName();

        if (!in_array($context, [
            'com_fields.field.com_users.user',
            'com_fields.field.com_content.article',
            'com_fields.field.com_contact.contact'
        ]))
        {
            return;
        }


        // Load "Falang Custom Fields Options" tab if the option is enabled in the plugin settings
        $form->loadFile(__DIR__ . '/form/options.xml', false);

        return true;
    }

    /*
    * use to set the value of the custom fields to the falang translation form
    * actually can't work perfectly because fields_values table don't have id key
    *
    * @since 4.0.5 load publish/unpublish custom field translation
    * @update 5.3 fix bug with extra plugin showtime, create model need to be done for content in Admin mode and not Site
    * @update 5.4 the context for category custom fields translation was not set correctly fix it
    * @update 5.22 move load custom fields in the falangcf plugin
    *
    */
    private function loadCustomFields($form, $data){

        $input = Factory::getApplication()->input;
        $option = $input->get('option');
        $task = $input->get('task');
        $catid = $input->get('catid');
        $reference_id = $input->get('reference_id');
        $cid = $input->get('cid');
        $language_id = $input->get('language_id'); //from quickump it's this one
        $select_language_id = $input->get('select_language_id');//from falang list it's selectec langauge

        if ($option == 'com_falang' && ($task == 'translate.edit' || ($task == 'translate.apply') )) {

            $context = $form->getName();

            // When a category is edited, the context is not set correctly => fix it
            if (strpos($context, 'com_categories.category') === 0) {
                $context = 'com_content.categories';
            }

            $parts = FieldsHelper::extract($context, $form);

            if (!$parts) {
                return true;
            }

            if (empty($reference_id)){
                $reference_id = current($cid);
            }

            //load category from original item to set to the translation
            //necessary to load the related custom fields even if a translation for them don't exist.
            if ( !empty($reference_id) && $catid == 'content'){
                $mvcFactory = Factory::getApplication()->bootComponent('com_content')->getMVCFactory();
                $articleModel = $mvcFactory->createModel('Article', 'Administrator', ['ignore_request' => true]);
                $contentParams = ComponentHelper::getParams('com_content');
                $articleModel->setState('params', $contentParams);
                $item =  $articleModel->getItem($reference_id);
                $data->catid = $item->catid;
            }
            if ( !empty($reference_id) && $catid == 'categories'){
                $categoryModel = Factory::getApplication()->bootComponent('com_categories')
                    ->getMVCFactory()->createModel('Category', 'Administrator', ['ignore_request' => true]);
                $item =  $categoryModel->getItem($reference_id);
            }

            // Getting the fields
            $fields = FieldsHelper::getFields($parts[0] . '.' . $parts[1], $data);

            //prepare the fields
            FieldsHelper::prepareForm($parts[0] . '.' . $parts[1], $form, $data);

            $fManager = FalangManager::getInstance();
            $content_element = $fManager->getContentElement($catid);

            if (empty($content_element)){
                return;
            }


            if (empty($language_id)){
                $language_id = $select_language_id;
            }
            //load com_fields values (json format) published or unpublish value
            $translations =  $fManager->getRawFieldTranslations($content_element->getTableName(),'com_fields',$reference_id,$language_id,false);


            if (empty($translations)) {
                $params = ComponentHelper::getParams('com_falang');
                $copy_cusom_fields = $params->get('copy_custom_fields',false);


                if ($copy_cusom_fields == false){
                    return true;
                }

                $original = $fManager->getRawFieldOrigninal($reference_id);

                //load orinal customfield to translation
                foreach ($fields as $field)
                {
                    if (isset($original[$field->id])){
                        $value  = $original[$field->id];
                        $form->setValue($field->name, 'com_fields', $value);
                    }

                }

            }

            $json_value = json_decode($translations);
            foreach ($fields as $field)
            {
                if (isset($json_value->{$field->name})) {
                    $form->setValue($field->name, 'com_fields', $json_value->{$field->name});
                }
            }

        }

        return true;
    }
    /**
     * Save custom fields translation
     * Throw by Falang (Backend)
     *
     * @since 4.0.5 add cateogry custom fields support
     * @update 4.12 save original text (null not accepted)
     * @update 5.0 original value is empty
     * @update 5.4 add showtime support use original gallery (the gallery can't be removed or changed)
     * @update 5.17 save acfupload field as array
     * @update 5.20 add calendar field support with user_utc support (seem the only one now 5.3)
     * @update 5.21 fix empty value set in the calendar field.
     * @update 5.22 move in the falancf plugin
     *              change the acfupload saving form no more as array (why before ? subform ?)
     *
     * @param $post
     * @return bool|void
     * @throws Exception
     *
     *
     */
    public function onAfterTranslationSave($post){
        $supported_context = array('content','categories');
        //system fields plugins need to be published.
        $fields_plugin = PluginHelper::getPlugin('system', 'fields');
        if (empty($fields_plugin)){return true;}

        $app = Factory::getApplication();
        $input = $app->input;

        $catid = $input->get('catid');
        $language_id = $input->get('select_language_id');
        $reference_id = $input->get('reference_id');
        $formData = new Registry($input->get('jform', '', 'array'));
        $context = $catid;

        //content and category supported
        if (!in_array($context,$supported_context)){return;}

        //TODO not set article here
        //load the content item to have the custom field associated with the categories of this item.
        if ( !empty($reference_id) && $catid == 'content'){
            $model =  new Joomla\Component\Content\Administrator\Model\ArticleModel;
            $contentParams = ComponentHelper::getParams('com_content');
            $model->setState('params', $contentParams);
            $item = $model->getItem($reference_id);
            $fields = FieldsHelper::getFields('com_'.$context. '.' . 'article', $item);
        }
        if ( !empty($reference_id) && $catid == 'categories'){
            Factory::getApplication()->input->set('extension', 'com_content');//not necessary by default com_content is used
            $model =  new Joomla\Component\Categories\Administrator\Model\CategoryModel;
            $contentParams = ComponentHelper::getParams('com_content');
            $model->setState('params', $contentParams);//TODO check
            $item = $model->getItem($reference_id);
            $fields = FieldsHelper::getFields('com_content'. '.' . 'categories', $item);//load com_content
        }

        if (!$fields) {
            return true;
        }

        // Get the translated fields data
        $fieldsData = !empty($formData) ? (array)$formData['com_fields'] : array();

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $user = Factory::getApplication()->getIdentity();

        $values = array();
        // Loop over the fields
        foreach ($fields as $field) {
            //acf upload file are saved as array of json
            if ($field->type == 'acfupload') {
                $value = key_exists($field->name, $fieldsData) ? $fieldsData[$field->name] : null;
                //$values[$field->name] = array($value);//remove from 5.22
                $values[$field->name] = $value;
            }
            else if ($field->type == 'calendar') {
                //date need to be stored in utc (user utc) from the calendar field filter
                $offset = $app->getIdentity()->getParam('timezone', $app->get('offset'));
                $value = key_exists($field->name, $fieldsData) ? $fieldsData[$field->name] : "";
                //value not set don't have to be save (like in the original calendar field)
                if (!empty($value)) {
                    $values[$field->name] = Factory::getDate($value, $offset)->toSql();
                }
            }
            else if ($field->type == 'showtime'){
                $values[$field->name] = $field->value;
            } else {
                // Determine the value if it is available from the data
                $value = key_exists($field->name, $fieldsData) ? $fieldsData[$field->name] : null;
                $values[$field->name] = $value;
            }
        }


        //save $values array in json format
        if (!empty($values)){
            //get previous com_fields falang translation
            //get previous value if exit to make update or insert

            $query = $db->getQuery(true);
            $query->select($query->qn('id'))
                ->from($query->qn('#__falang_content'))
                ->where($db->qn('language_id') . ' = ' . $db->q($language_id))
                ->where($db->qn('reference_id') . ' = ' . $reference_id)
                ->where($db->qn('reference_field') . ' = ' . $db->q('com_fields'))
                ->where($db->qn('reference_table') . ' = ' . $db->q($context));

            $db->setQuery($query);
            $falangId = $db->loadResult();


            $jsonValues = json_encode($values);
            $fieldContent = new FalangContentTable($db);

            if (isset($falangId)){$fieldContent->id = $falangId;}
            $fieldContent->reference_id = $reference_id ;
            $fieldContent->language_id = $language_id;
            $fieldContent->reference_table= $context;
            $fieldContent->reference_field= 'com_fields';
            $fieldContent->value = $jsonValues;
            // the original value don't exist for custom_fields.
            $fieldContent->original_value = '';
            $fieldContent->original_text = "";

            $fieldContent->modified =  Factory::getDate()->toSql();

            $fieldContent->modified_by = $user->id;
            $fieldContent->published= true;

            $fieldContent->store();

        }

        return true;
    }

    /**
     * We need to prepare custom fields per plugin because #__fields_values doesn't have a primary key
     *
     * $since 4.0.5 load only published by param's
     *              fix subform custom field translation
     * @since 4.0.7 fix for categories custom fields
     * @update 5.5 add mediajce custom field translation support
     * @update 5.22 move in the falancf plugin
     * @update 5.23 fix mediajce and legacy system
     *
     * @param $context
     * @param $item
     * @param $field
     */
    public function onCustomFieldsBeforePrepareField($context, $item, $field) {

        // We only work in frontend
        if (!Factory::getApplication()->isClient('site')) {
            return;
        }

        //only for transalted language
        $current_lang = Factory::getApplication()->getLanguage()->getTag();
        $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
        //nothing to do for default langauge
        if ($current_lang == $default_lang ){
            return;
        }

        list($component, $view) = explode('.', $context, 2);

        if (strpos($component, "com_")=== 0)	{
            $component_name = substr($component, 4);
        } else {
            $component_name = $component;
        }

        //fix for com_content.categories
        if ($view == 'categories'){
            $component_name = 'categories';
        }

        $fManager = FalangManager::getInstance();

        $content_element = $fManager::getInstance()->getContentElement($component_name);

        if (empty($content_element)){
            return;
        }

        $languageTag  = Factory::getLanguage()->getTag();
        $id_lang = $fManager->getLanguageID($languageTag);

        $translations = FalangManager::getInstance()->getRawFieldTranslations($content_element->getTableName(),'com_fields',$item->{$content_element->getReferenceId()},$id_lang,true);//load only published

        if (empty($translations)) {
            return;
        }

        //get mediajce lecacymode
        $legacy = false;
        $field_mediajce = PluginHelper::getPlugin('fields', 'mediajce');
        if ($field_mediajce){//legacymedia
            $mediaJceParams = new Registry($field_mediajce->params);
            $legacy = $mediaJceParams->get('legacymedia',false);
        }

        //supposed to be array
        $json_value = json_decode($translations,true);

        if (isset($json_value[$field->name])) {

            $field->valueUntranslated    = $field->value;
            $field->rawvalueUntranslated = $field->rawvalue;

            //is_array($json_value[$field->name]) don't work checkfield and probably
            switch ($field->type) {
                //mediajce by default is NO , it's mean it's an image with description but yootheme have problem to deal with it
                //if it's legacy it's just a string without description
                //Falang translation have to be resave in case of change of lecacy
                //it's seem the legacy system change the media_src don't exist anymore
                case 'mediajce' :
                    if ($legacy) {
                        $field->value                = $json_value[$field->name];
                        $field->rawvalue             = $json_value[$field->name];
                    } else {
                        $field->value                = json_encode($json_value[$field->name]);
                        if (isset($json_value[$field->name]['media_src'])){
                            $field->rawvalue             = $json_value[$field->name]['media_src'];//fix yootheme dynamic field translated with Falnag ???
                        } else {
                            $field->rawvalue             = $json_value[$field->name];
                        }
                    }
                    break;
                case 'repeatable':
                case 'subform' :
                case 'media' :
                    $field->value                = json_encode($json_value[$field->name]);
                    $field->rawvalue             = json_encode($json_value[$field->name]);
                    break;
                default :
                    $field->value                = $json_value[$field->name];
                    $field->rawvalue             = $json_value[$field->name];
                    break;
            }

        }

    }
}