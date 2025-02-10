---
outline: deep
---

# Events

We use events to run some code when a specific action is triggered. This is useful when you want to run some code when a
specific action is triggered. For example, you can use events to send an email when an application file is set to a
specific status.

## Declare new event

To declare a new event you can use the following code:

```php
PluginHelper::importPlugin('emundus');
$dispatcher = Factory::getApplication()->getDispatcher();

// Declare the event
$onAfterAttachmentUpload = new GenericEvent(
    'onCallEventHandler',
     ['onAfterAttachmentUpload',
        // Datas to pass to the event 
        ['fnum' => $fnum, 'attachment_id' => (int)$attachments, 'file' => $paths]
     ]
);

// Dispatch the event
$dispatcher->dispatch('onCallEventHandler', $onAfterAttachmentUpload);
```

Then add it to the `jos_emundus_plugin_events` table in the database to make it available in the `custom_event_handler` plugin

## Listen to an event

Each plugin located in emundus folder can listen to an event. To listen to an event you can use the following code:

```php
public function onAfterAttachmentUpload($args)
{
    $fnum = $args['fnum'];
    
    // Do something with the datas
    // ...
}
```

## Use custom_event_handler plugin

In Joomla! plugins you can find a plugin called `custom_event_handler`. This plugin is used to listen to events and run
some code when an event is triggered. You can use this plugin to listen to events and run some code when an event is
triggered. In plugin choose the event to listen to and write the code to run when the event is triggered.

For example, you can use this plugin to send an email when an application file is set to a specific status. All `$args` are stored in the `$data` variable.
```php
$fnum = $data['fnum'];

if(!empty($fnum)) {
    require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
    $m_emails = new EmundusModelEmails();
    
    $m_emails->sendEmail($fnum, 'status_change');
}
```

## List of events

Here is a list of events that are available in the Tchooz platform:

| Name                                      | Category   | Note                                                                   | Datas                                   |
|-------------------------------------------|------------|------------------------------------------------------------------------|-----------------------------------------|
| **onBeforeStatusChange**                  | Status(es) | Before the status was updated                                          | `$fnum` / `$state` / `$old_state`       |
| **onAfterStatusChange**                   | Status(es) | Triggered when status has been changed                                 | `$fnum` / `$state` / `$old_state`       |
| **onBeforePublishChange**                 | File       | Before update of application file state (Published, Archived, Trashed) | `$fnum` / `$published`                  |
| **onAfterPublishChange**                  | File       | After update of application file state (Published, Archived, Trashed)  | `$fnum` / `$published`                  |
| **onBeforeDeleteFile**                    | File       | Before deletion of an application file by the applicant                | `$fnum`                                 |
| **onAfterDeleteFile**                     | File       | After deletion of an application file by the applicant                 | `$fnum`                                 |
| **onBeforeSaveEmundusUser**               | User       | After Joomla! user creation but before creation of an eMundus user     | `$user_id` / `$params`                  |
| **onAfterSaveEmundusUser**                | User       | After insertion of the emundus user                                    | `$user_id` / `$params`                  |
| **onBeforeAddUserToGroup**                | User       |                                                                        | `$user_id` / `$group`                   |
| **onAfterAddUserToGroup**                 | User       |                                                                        | `$user_id` / `$group`                   |
| **onBeforeAddUserProfile**                | User       |                                                                        | `$user_id` / `$profile`                 |
| **onAfterAddUserProfile**                 | User       |                                                                        | `$user_id` / `$profile`                 |
| **onBeforeCampaignCandidature**           | File       |                                                                        | `$user_id` / `$connected` / `$campaign` |
| **onAfterCampaignCandidature**            | File       |                                                                        | `$user_id` / `$connected` / `$campaign` |
| **onBeforeCampaignCreate**                | Campaign   |                                                                        | `$campaign`                             |
| **onAfterCampaignCreate**                 | Campaign   |                                                                        | `$campaign`                             |
| **onBeforeCampaignUnpublish**             | Campaign   |                                                                        | `$campaign`                             |
| **onAfterCampaignUnpublish**              | Campaign   |                                                                        | `$campaign`                             |
| **onBeforeCampaignDelete**                | Campaign   |                                                                        | `$campaign`                             |
| **onAfterCampaignDelete**                 | Campaign   |                                                                        | `$campaign`                             |
| **onBeforeProgramCreate**                 | Program    |                                                                        | `$data`                                 |
| **onAfterProgramCreate**                  | Program    |                                                                        | `$data`                                 |
| **onBeforeProgramUpdate**                 | Program    |                                                                        | `$id` / `$data`                         |
| **onAfterProgramUpdate**                  | Program    |                                                                        | `$id` / `$data`                         |
| **onBeforeProgramDelete**                 | Program    |                                                                        | `$data`                                 |
| **onAfterProgramDelete**                  | Program    |                                                                        | `$data`                                 |
| **onBeforeLoad**                          | Form       |                                                                        | `$formModel`                            |
| **onLoad**                                | Form       |                                                                        | `$formModel`                            |
| **onJSReady**                             | Form       |                                                                        |                                         |
| **onJSOpts**                              | Form       |                                                                        |                                         |
| **onCanEditGroup**                        | Form       |                                                                        | `$formModel` / `$groupModel`            |
| **onBeforeProcess**                       | Form       |                                                                        | `$formModel`                            |
| **onBeforeStore**                         | Form       |                                                                        | `$formModel`                            |
| **onBeforeCalculations**                  | Form       |                                                                        |                                         |
| **onAfterProcess**                        | Form       |                                                                        | `$formModel`                            |
| **onError**                               | Form       |                                                                        |                                         |
| **getTopContent**                         | Form       |                                                                        |                                         |
| **getBottomContent**                      | Form       |                                                                        |                                         |
| **getEndContent**                         | Form       |                                                                        |                                         |
| **onDeleteRowsForm**                      | Form       |                                                                        |                                         |
| **onAfterDeleteRowsForm**                 | Form       |                                                                        |                                         |
| **onSavePage**                            | Form       |                                                                        |                                         |
| **onElementCanUse**                       | Form       |                                                                        | `$formModel` / `$elementModel`          |
| **onElementCanView**                      | Form       |                                                                        | `$formModel` / `$elementModel`          |
| **onBeforeEmailUpdate**                   | Email      |                                                                        |                                         |
| **onAfterEmailUpdate**                    | Email      |                                                                        |                                         |
| **onBeforeEmailCreate**                   | Email      |                                                                        |                                         |
| **onAfterEmailCreate**                    | Email      |                                                                        |                                         |
| **onBeforeEmailDelete**                   | Email      |                                                                        |                                         |
| **onAfterEmailDelete**                    | Email      |                                                                        |                                         |
| **onBeforeTagAdd**                        | Tag        |                                                                        |                                         |
| **onAfterTagAdd**                         | Tag        |                                                                        |                                         |
| **onBeforePdfExport**                     | Export     |                                                                        |                                         |
| **onAfterPdfExport**                      | Export     |                                                                        |                                         |
| **onBeforeExcelExport**                   | Export     |                                                                        |                                         |
| **onAfterExcelExport**                    | Export     |                                                                        |                                         |
| **onBeforeZipExport**                     | Export     |                                                                        |                                         |
| **onAfterZipExport**                      | Export     |                                                                        |                                         |
| **onBeforeLetterExport**                  | Export     |                                                                        |                                         |
| **onAfterLetterExport**                   | Export     |                                                                        |                                         |
| **onBeforeEmailSend**                     | Email      |                                                                        |                                         |
| **onAfterEmailSend**                      | Email      |                                                                        |                                         |
| **onBeforeCommentUpdate**                 | Comment    |                                                                        |                                         |
| **onAfterCommentUpdate**                  | Comment    |                                                                        |                                         |
| **onBeforeDocumentCreate**                | Document   |                                                                        |                                         |
| **onAfterDocumentCreate**                 | Document   |                                                                        |                                         |
| **onBeforeDocumentDelete**                | Document   |                                                                        |                                         |
| **onAfterDocumentDelete**                 | Document   |                                                                        |                                         |
| **onCreateNewFile**                       | File       |                                                                        |                                         |
| **onExportFiles**                         | Export     |                                                                        |                                         |
| **onBeforeGroupCreate**                   | Group      |                                                                        |                                         |
| **onAfterGroupCreate**                    | Group      |                                                                        |                                         |
| **onBeforeGroupUpdate**                   | Group      |                                                                        |                                         |
| **onAfterGroupUpdate**                    | Group      |                                                                        |                                         |
| **onBeforeGroupDelete**                   | Group      |                                                                        |                                         |
| **onAfterGroupDelete**                    | Group      |                                                                        |                                         |
| **onBeforeCampaignPublish**               | Camapign   |                                                                        |                                         |
| **onAfterCampaignPublish**                | Camapign   |                                                                        |                                         |
| **onBeforeCampaignUpdate**                | Camapign   |                                                                        |                                         |
| **onAfterCampaignUpdate**                 | Camapign   |                                                                        |                                         |
| **onBeforeTagRemove**                     | Tag        |                                                                        |                                         |
| **onAfterTagRemove**                      | Tag        |                                                                        |                                         |
| **onBeforeImportCSV**                     | Import     |                                                                        |                                         |
| **onAfterImportCSV**                      | Import     |                                                                        |                                         |
| **onAfterCopyApplication**                | Campaign   |                                                                        |                                         |
| **onBeforeCommentAdd**                    | Comment    |                                                                        |                                         |
| **onAfterCommentAdd**                     | Comment    |                                                                        |                                         |
| **onUserLogin**                           | User       |                                                                        |                                         |
| **onAfterAttachmentUpload**               | Attachment |                                                                        |                                         |
| **onAfterProfileAttachmentUpload**        | Attachment |                                                                        |                                         |
| **onAfterProfileAttachmentDelete**        | Attachment |                                                                        |                                         |
| **onBeforeMultipleStatusChange**          | Status(es) |                                                                        |                                         |
| **onHikashopBeforeOrderCreate**           | Hikashop   |                                                                        |                                         |
| **onHikashopAfterOrderCreate**            | Hikashop   |                                                                        |                                         |
| **onHikashopBeforeOrderUpdate**           | Hikashop   |                                                                        |                                         |
| **onHikashopAfterOrderUpdate**            | Hikashop   |                                                                        |                                         |
| **onHikashopAfterOrderConfirm**           | Hikashop   |                                                                        |                                         |
| **onHikashopAfterOrderDelete**            | Hikashop   |                                                                        |                                         |
| **onHikashopCheckoutWorkflowLoad**        | Hikashop   |                                                                        |                                         |
| **onHikashopBeforeProductListingLoad**    | Hikashop   |                                                                        |                                         |
| **onAfterGenerateLetters**                | Files      |                                                                        |                                         |
| **onRenderEvaluation**                    | Evaluation |                                                                        |                                         |
| **onBeforeSubmitEvaluation**              | Evaluation |                                                                        | `$formModel`                            |
| **onAfterSubmitEvaluation**               | Evaluation |                                                                        | `$formModel`                            |
| **onHikashopAfterCheckoutStep**           | Hikashop   |                                                                        |                                         |
| **onHikashopAfterCartProductsLoad**       | Hikashop   |                                                                        |                                         |
| **onBeforeRenderApplications**            | Applicant  |                                                                        |                                         |
| **onWebhookCallbackProcess**              | Webhook    |                                                                        |                                         |
| **onAfterMoveApplication**                | Campaign   |                                                                        |                                         |
| **onBeforeEmundusRedirectToHikashopCart** | Hikashop   |                                                                        |                                         |
| **onBeforeApplicantEnterApplication**     | Files      |                                                                        |                                         |
| **onAccessDenied**                        | Access     |                                                                        |                                         |

