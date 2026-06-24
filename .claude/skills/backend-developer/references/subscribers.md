# Subscribers — event registration and dispatch

Subscribers let `com_emundus` react to events fired anywhere in the system without coupling the firing code to the reaction. They replace the older "drop a plugin in `plugins/emundus/`" pattern for behaviour that *belongs to the component itself*.

## When to use a Subscriber vs a Plugin

| Use a **Subscriber** (in `classes/Subscribers/`) when… | Use a **Plugin** (in `plugins/emundus/`) when… |
|---|---|
| The behaviour is part of `com_emundus`'s own responsibilities (reference generation, audit logging, default-status assignment). | The behaviour is a **per-tenant customisation** that should be enable-able from Joomla's plugin manager. |
| You want it to ship with the component and run unconditionally for everyone. | You want a tenant-specific hook (e.g. "Excelia: sync this campaign to Aurion"). |
| You want it autoloadable via the `Tchooz\` namespace and testable like any other service. | You need lifecycle controls (publish/unpublish, params from Joomla UI). |

Both end up listening to the same dispatcher; the difference is *governance and packaging*.

## Anatomy of a Subscriber

```php
namespace Tchooz\Subscribers;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;

class GenerateReferenceSubscriber extends EmundusSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterCampaignCandidature' => 'generateShortReference',
            'onAfterStatusChange'        => 'generateReference',
        ];
    }

    public function generateShortReference(GenericEvent $event): void
    {
        try {
            $data = $event->getArguments();
            if (empty($data['fnum'])) {
                return;
            }
            // delegate the actual work to a service — the subscriber is glue
            (new InternalReferenceService(new DateProvider(), new ApplicationFileRepository()))
                ->generateShortReference(/* … */);
        } catch (\Exception $e) {
            Log::add('Error while generating reference: ' . $e->getMessage(), Log::ERROR);
        }
    }

    public function generateReference(GenericEvent $event): void
    {
        // same shape — one method per subscribed event
    }
}
```

Rules:
- One method per subscribed event. Never branch one big method on `$event->getName()`.
- The method body is glue: read `$event->getArguments()`, validate, delegate to a service. Business logic does **not** live here.
- Catch broadly *only at the boundary* — subscribers run inside Joomla's dispatcher, so an uncaught exception can break unrelated handlers downstream. Log it, return.
- Extend `EmundusSubscriber` so you get the `$this->db` and the logger registered for free.

## The base class

```php
class EmundusSubscriber implements SubscriberInterface
{
    protected DatabaseDriver $db;

    public function __construct(string $name)
    {
        $this->db = Factory::getContainer()->get('DatabaseDriver');
        Log::addLogger(['text_file' => "com_emundus.subscriber{$name}.php"], Log::ALL, array("com_emundus.subscriber{$name}"));
    }

    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
```

The `$name` constructor argument is the subscriber's log channel suffix. Pass a short, stable identifier (`'generate_reference'`).

## Registration

Subscribers must be registered with the dispatcher to fire. The registration point is `Tchooz\Providers\EmundusSubscriberProvider::register()`:

```php
class EmundusSubscriberProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $subject = $container->get(DispatcherInterface::class);
        $subject->addSubscriber(new GenerateReferenceSubscriber('generate_reference'));
    }
}
```

**Add your new subscriber here.** A subscriber that isn't registered is dead code — nothing will route events to it.

If the subscriber should only run when a component parameter is set (e.g. a feature flag in `com_emundus`'s configuration), branch on `ComponentHelper::getParams('com_emundus')->get('my_flag', 0)` before calling `addSubscriber`. There's a TODO in the provider noting this is the intended evolution.

## Dispatching events from your own code

When you want *other* code to be able to react to something you do, dispatch an event using `TraitDispatcher` (in `classes/Traits/`):

```php
use Tchooz\Traits\TraitDispatcher;

class MyService
{
    use TraitDispatcher;

    public function doIt(string $fnum): void
    {
        /* … */
        $this->dispatchJoomlaEvent('onAfterMyDomainAction', [
            'fnum'    => $fnum,
            'context' => $someContextEntity,
        ]);
    }
}
```

`dispatchJoomlaEvent(string $event, array $arguments, bool $dispatch_event_handler = true, $plugin_folder = 'emundus', bool $dispatch_default_event = true)`:
- Loads the `emundus` plugin group (legacy plugins listening to the event).
- Loads `actionlog` (audit logging).
- Dispatches `onCallEventHandler` (a meta-event the `custom_event_handler` plugin uses to fan out to user-configured handlers).
- Dispatches the real event name.

Naming: events are camelCase starting with `on`, e.g. `onAfterStatusChange`, `onAfterCampaignCandidature`, `onBeforeFileExport`. Past tense is conventional for "after"; future tense for "before".

The arguments array is the contract for everyone listening — adding a key is safe; renaming or removing one breaks subscribers and plugins silently. Document the shape near the dispatch site.

## Common mistakes

1. **Forgetting to register the subscriber.** No errors, just silence. Always check `EmundusSubscriberProvider::register()` after adding a subscriber.
2. **Business logic inline in the subscriber.** Makes the logic untestable in isolation. Delegate to a service.
3. **Throwing from the handler.** Crashes the dispatch chain for unrelated handlers downstream. Catch, log, return.
4. **Subscribing to a non-existent event.** No errors; the handler never runs. Verify the event name by searching for the dispatcher call site (`grep -rn "dispatchJoomlaEvent.*'onAfter" components/`).
5. **Coupling the subscriber to a specific fire-site.** Subscribers should work for *any* dispatcher of the event — don't assume the fire-site is one specific service.
