import moment from 'moment';
import userService from '../services/user.js';
import attachmentService from '../services/attachment.js';
import mimeTypes from '../data/mimeTypes';
import {useGlobalStore} from '../stores/global.js';
import {useAttachmentStore} from '../stores/attachment.js';
import {useUserStore} from '../stores/user.js';

import { toast } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

var mixin = {
  data: function () {
    return {
      timer: null,
    };
  },
  methods: {
    /**
         * Format date
         * @param date
         * @param format
         * @param utc
         * @param local, if true, the date will not be converted using utc offset
         * @returns {string}
         */
    formattedDate: function (date = '', format = 'LLLL', utc = null) {
      let formattedDate = '';

      if (date !== null) {
        if (utc === null) {
          const globalStore = useGlobalStore();

          utc = globalStore.offset;
        }

        if (date !== '') {
          let year = date.substring(0, 4);
          let month = date.substring(5, 7);
          let day = date.substring(8, 10);
          let hour = date.substring(11, 13);
          let minute = date.substring(14, 16);
          let second = date.substring(17, 19);
          const stringDate = year + '-' + month + '-' + day + 'T' + hour + ':' + minute + ':' + second + '+00:00';

          formattedDate = utc != null && typeof utc != 'undefined' ? moment(stringDate).utcOffset(utc).format(format) : moment(stringDate).format(format);
        } else {
          formattedDate = utc != null && typeof utc != 'undefined' ? moment().utcOffset(utc).format(format) :  moment().format(format);
        }
      }

      return formattedDate;
    },
    strippedHtml: function (html) {
      if (html === null || html === undefined) {
        return '';
      }

      return html.replace(/<(?:.|\n)*?>/gm, '');
    },
    getUserNameById: function (id) {
      let completeName = '';
      id = parseInt(id);

      if (id > 0) {
        const userStore = useUserStore();
        const user = userStore.getUserById(id);
        if (user) {
          completeName = user.firstname + ' ' + user.lastname;
        } else {
          userService.getUserNameById(id).then(data => {
            if (data.status && data.user.user_id == id) {
              completeName = data.user.firstname + ' ' + data.user.lastname;
              userStore.setUsers([data.user]);
            }
          });
        }
      }

      return completeName;
    },
    async getAttachmentCategories() {
      const response = await attachmentService.getAttachmentCategories();

      if (response.status === true) {
        // translate categories values
        Object.entries(response.categories).forEach(([key, value]) => {
          response.categories[key] = this.translate(value);
        });

        // remove empty categories
        delete response.categories[''];

        const attachmentStore = useAttachmentStore();
        attachmentStore.setCategories(response.categories);

        return response.categories;
      } else {
        return {};
      }
    },
    async asyncForEach(array, callback) {
      for (let index = 0; index < array.length; index++) {
        await callback(array[index], index, array);
      }
    },
    getMimeTypeFromExtension(extension) {
      if (Object.prototype.hasOwnProperty.call(mimeTypes.mimeTypes, extension)) {
        return mimeTypes.mimeTypes[extension];
      }
      return false;
    },
    checkMaxMinlength(event, maxlength, minlength = null) {
      if (event.target.textContent.length >= maxlength && event.keyCode != 8) {
        event.preventDefault();
      }
      if (minlength !== null) {
        if (event.target.textContent.length <= minlength && event.keyCode == 8) {
          event.preventDefault();
        }
      }
    },
    differencesBetweenObjetcs(obj1, obj2, propsToCompare = null) {
      let differences = [];

      if (propsToCompare === null) {
        const props1 = Object.getOwnPropertyNames(obj1);
        const props2 = Object.getOwnPropertyNames(obj2);

        propsToCompare = Array.from(new Set(props1.concat(props2)));
      }

      propsToCompare = propsToCompare.filter((prop) => {
        return prop !== '__ob__';
      });

      propsToCompare.forEach((prop) => {
        if (typeof obj1[prop] === "undefined" || typeof obj2[prop] === "undefined") {
          differences.push(prop);
        } else if (obj1[prop] != obj2[prop]) {
          if (typeof obj1[prop] != 'object' ||
                        (typeof obj1[prop] == 'object' && JSON.stringify(obj1[prop]) !== JSON.stringify(obj2[prop]))) {
            differences.push(prop);
          }
        }
      });

      return differences;
    },
    tipToast(text = "", type = 'info', position = 'bottom-left', duration = 10000, delay = 0, allowHtml = true) {
      switch (position) {
      case 'bottom-left':
        position = toast.POSITION.BOTTOM_LEFT;
        break;
      case 'bottom-right':
        position = toast.POSITION.BOTTOM_RIGHT;
        break;
      case 'top-left':
        position = toast.POSITION.TOP_LEFT;
        break;
      case 'top-right':
        position = toast.POSITION.TOP_RIGHT;
        break;
      default:
        position = toast.POSITION.BOTTOM_LEFT;
        break;
      }

      let options = {
        position: position,
        autoClose: duration,
        delay: delay,
        dangerouslyHTMLString: allowHtml
      };

      switch (type) {
      case 'info':
        toast.info(text,options);
        break;
      case 'error':
        toast.error(text,options);
        break;
      case 'success':
        toast.success(text,options);
        break;
      }

    },

    /**
     * Highlight the search term in the elements by wrapping it in a span with a background color
     * @param searchTerm
     * @param elementsToSearchIn
     */
    highlight(searchTerm, elementsToSearchIn = []) {
      if (elementsToSearchIn.length > 0) {
        let elements = [];
        elementsToSearchIn.forEach((elementClass) => {
          elements = elements.concat(Array.from(document.querySelectorAll(elementClass)));
        });

        elements.forEach((element) => {
          const text = element.innerText;
          let regex = new RegExp(`(${searchTerm})`, "gi");
          // Check if the element's text contains the search term
          if (searchTerm && text.match(regex)) {
            // Split the text into parts (matched and unmatched)
            const parts = text.split(regex);
            // Create a new HTML structure with the matched term highlighted
            const highlightedText = parts
                .map((part) =>
                    part.match(regex)
                        ? `<span style="background-color: var(--em-yellow-1);">${part}</span>`
                        : part
                )
                .join("");
            // Replace the original text with the highlighted version
            element.innerHTML = highlightedText;
          } else {
            element.innerHTML = text;
          }
        });
      }
    }
  }
};

export default mixin;
