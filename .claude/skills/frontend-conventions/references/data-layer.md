# Data layer

Covers service files (API calls), Pinia stores, and cross-module communication.

## Service files

One service file per Joomla domain controller. If the action you need belongs to an existing controller, extend the existing file. Only create a new service file when introducing a brand new controller.

Instantiate `FetchClient` once at module level, not inside methods:

```javascript
import { FetchClient } from '@/services/fetchClient.js';

const client = new FetchClient('mycontroller'); // matches the Joomla controller

export default {
  async getItems(params) {
    return client.get('getitems', params);
  },
  async saveItem(data) {
    return client.post('save', data);
  },
  async deleteItem(id) {
    return client.delete('delete', { id });
  },
};
```

## Calling services from components

Import the service, never `FetchClient` directly. Components should be free of HTTP details.

```javascript
import myService from '@/services/myService.js';

export default {
  data() {
    return { items: [], loading: false };
  },
  methods: {
    async loadItems() {
      this.loading = true;
      const response = await myService.getItems({ limit: 10 });
      if (response.status) {
        this.items = response.data;
      } else {
        this.alertError('COM_EMUNDUS_LOAD_FAILED');
      }
      this.loading = false;
    },
  },
};
```

## Window events for inter-module communication

Several Vue apps can mount on the same page, one per DOM element. They share no Vue instance, so they communicate through `window` custom events.

Naming convention: `emundus::<verb><Noun>` (e.g., `emundus::itemSaved`, `emundus::workflowDeleted`).

Sender:

```javascript
window.dispatchEvent(new CustomEvent('emundus::itemSaved', {
  detail: { id: this.item.id },
}));
```

Receiver. Clean up in `beforeUnmount` to avoid memory leaks:

```javascript
mounted() {
  window.addEventListener('emundus::itemSaved', this.onItemSaved);
},
beforeUnmount() {
  window.removeEventListener('emundus::itemSaved', this.onItemSaved);
},
methods: {
  onItemSaved(event) {
    this.refresh(event.detail.id);
  },
},
```

The `beforeUnmount` cleanup is mandatory. Without it, the listener stays bound after the component unmounts and fires on stale data.

## Pinia stores

Files live in `src/stores/`. Use `defineStore('name', { state, getters, actions })`.

Project conventions:

Guard fetch actions with a `loaded` flag so the API is hit once:

```javascript
actions: {
  async fetchAll() {
    if (this.loaded) return;
    const response = await myService.getItems();
    if (response.status) {
      this.items = response.data;
      this.loaded = true;
    }
  },
},
```

Factory getters for keyed lookups:

```javascript
getters: {
  getItemById: (state) => (id) => state.items.find((item) => item.id === id),
},
```

Call `useMyStore()` inside lifecycle hooks (`mounted`, `created`), never at module level. Calling it at module level can run before Pinia is initialized.