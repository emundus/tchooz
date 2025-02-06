import { _ as _export_sfc, a3 as $3ed269f2f0fb224b$export$2e2bcd8739ae039, o as openBlock, c as createElementBlock, a4 as renderSlot, D as createTextVNode, e as createCommentVNode, d as normalizeClass } from "./app_emundus.js";
const awsEndpoint = {
  getSignedURL(file, config) {
    let payload = {
      filePath: file.name,
      contentType: file.type
    };
    return new Promise((resolve, reject) => {
      var fd = new FormData();
      let request = new XMLHttpRequest(), signingURL = typeof config.signingURL === "function" ? config.signingURL(file) : config.signingURL;
      request.open("POST", signingURL);
      request.onload = function() {
        if (request.status == 200) {
          resolve(JSON.parse(request.response));
        } else {
          reject(request.statusText);
        }
      };
      request.onerror = function(err) {
        console.error("Network Error : Could not send request to AWS (Maybe CORS errors)");
        reject(err);
      };
      if (config.withCredentials === true) {
        request.withCredentials = true;
      }
      Object.entries(config.headers || {}).forEach(([name, value]) => {
        request.setRequestHeader(name, value);
      });
      payload = Object.assign(payload, config.params || {});
      Object.entries(payload).forEach(([name, value]) => {
        fd.append(name, value);
      });
      request.send(fd);
    });
  },
  sendFile(file, config, is_sending_s3) {
    var handler = is_sending_s3 ? this.setResponseHandler : this.sendS3Handler;
    return this.getSignedURL(file, config).then((response) => {
      return handler(response, file);
    }).catch((error) => {
      return error;
    });
  },
  setResponseHandler(response, file) {
    file.s3Signature = response.signature;
    file.s3Url = response.postEndpoint;
  },
  sendS3Handler(response, file) {
    let fd = new FormData(), signature = response.signature;
    Object.keys(signature).forEach(function(key) {
      fd.append(key, signature[key]);
    });
    fd.append("file", file);
    return new Promise((resolve, reject) => {
      let request = new XMLHttpRequest();
      request.open("POST", response.postEndpoint);
      request.onload = function() {
        if (request.status == 201) {
          var s3Error = new window.DOMParser().parseFromString(request.response, "text/xml");
          var successMsg = s3Error.firstChild.children[0].innerHTML;
          resolve({
            "success": true,
            "message": successMsg
          });
        } else {
          var s3Error = new window.DOMParser().parseFromString(request.response, "text/xml");
          var errMsg = s3Error.firstChild.children[0].innerHTML;
          reject({
            "success": false,
            "message": errMsg + ". Request is marked as resolved when returns as status 201"
          });
        }
      };
      request.onerror = function() {
        var s3Error = new window.DOMParser().parseFromString(request.response, "text/xml");
        var errMsg = s3Error.firstChild.children[1].innerHTML;
        reject({
          "success": false,
          "message": errMsg
        });
      };
      request.send(fd);
    });
  }
};
const _sfc_main = {
  props: {
    id: {
      type: String,
      required: true,
      default: "dropzone"
    },
    options: {
      type: Object,
      required: true
    },
    includeStyling: {
      type: Boolean,
      default: true,
      required: false
    },
    awss3: {
      type: Object,
      required: false,
      default: null
    },
    destroyDropzone: {
      type: Boolean,
      default: true,
      required: false
    },
    duplicateCheck: {
      type: Boolean,
      default: false,
      required: false
    },
    useCustomSlot: {
      type: Boolean,
      default: false,
      required: false
    }
  },
  emits: [
    "vdropzone-thumbnail",
    "vdropzone-duplicate-file",
    "vdropzone-file-added",
    "vdropzone-files-added",
    "vdropzone-removed-file",
    "vdropzone-success",
    "vdropzone-error",
    "vdropzone-s3-upload-success",
    "vdropzone-s3-upload-error",
    "vdropzone-success-multiple",
    "vdropzone-error-multiple",
    "vdropzone-sending",
    "vdropzone-sending-multiple",
    "vdropzone-complete",
    "vdropzone-complete-multiple",
    "vdropzone-canceled",
    "vdropzone-canceled-multiple",
    "vdropzone-max-files-reached",
    "vdropzone-max-files-exceeded",
    "vdropzone-processing",
    "vdropzone-processing-multiple",
    "vdropzone-upload-progress",
    "vdropzone-total-upload-progress",
    "vdropzone-reset",
    "vdropzone-queue-complete",
    "vdropzone-drop",
    "vdropzone-drag-start",
    "vdropzone-drag-end",
    "vdropzone-drag-enter",
    "vdropzone-drag-over",
    "vdropzone-drag-leave",
    "vdropzone-mounted",
    "vdropzone-file-added-manually"
  ],
  data() {
    return {
      aws: null,
      isS3: false,
      isS3OverridesServerPropagation: false,
      wasQueueAutoProcess: true,
      files: [],
      dropzoneSettings: {
        thumbnailWidth: 200,
        thumbnailHeight: 200
      }
    };
  },
  watch: {
    options: {
      handler() {
        this.updateSettings();
      },
      deep: true
    },
    awss3: {
      handler() {
        this.updateAWSSettings();
      },
      deep: true
    }
  },
  beforeMount() {
    this.updateSettings();
    this.updateAWSSettings();
  },
  mounted() {
    if (this.$isServer && this.hasBeenMounted) {
      return;
    }
    this.hasBeenMounted = true;
    this.dropzone = new $3ed269f2f0fb224b$export$2e2bcd8739ae039(
      this.$refs.dropzoneElement,
      this.dropzoneSettings
    );
    this.dropzone.on("thumbnail", (file, dataUrl) => {
      this.$emit("vdropzone-thumbnail", file, dataUrl);
    });
    this.dropzone.on("addedfile", (file) => {
      if (this.duplicateCheck && this.dropzone.getQueuedFiles().length) {
        this.getQueuedFiles().forEach((existingFile) => {
          if (existingFile.name === file.name && existingFile.size === file.size && existingFile.lastModifiedDate.toString() === file.lastModifiedDate.toString() && existingFile.dataUrl === file.dataUrl) {
            this.removeFile(file);
            this.$emit("vdropzone-duplicate-file", file);
          }
        });
      }
      this.$emit("vdropzone-file-added", file);
      if (this.isS3 && this.wasQueueAutoProcess && !file.manuallyAdded) {
        this.getSignedAndUploadToS3(file);
      }
    });
    this.dropzone.on("addedfiles", (files) => {
      this.$emit("vdropzone-files-added", files);
    });
    this.dropzone.on("removedfile", (file) => {
      this.$emit("vdropzone-removed-file", file);
      if (file.manuallyAdded && this.dropzone.options.maxFiles !== null)
        this.dropzone.options.maxFiles++;
    });
    this.dropzone.on("success", (file, response) => {
      this.$emit("vdropzone-success", file, response);
      if (this.isS3) {
        if (this.isS3OverridesServerPropagation) {
          let xmlResponse = new window.DOMParser().parseFromString(
            response,
            "text/xml"
          );
          let s3ObjectLocation = xmlResponse.firstChild.children[0].innerHTML;
          this.$emit("vdropzone-s3-upload-success", s3ObjectLocation);
        }
        if (this.wasQueueAutoProcess) {
          this.setOption("autoProcessQueue", false);
        }
      }
    });
    this.dropzone.on("successmultiple", (file, response) => {
      this.$emit("vdropzone-success-multiple", file, response);
    });
    this.dropzone.on("error", (file, message, xhr) => {
      this.$emit("vdropzone-error", file, message, xhr);
      if (this.isS3) this.$emit("vdropzone-s3-upload-error");
    });
    this.dropzone.on("errormultiple", (files, message, xhr) => {
      this.$emit("vdropzone-error-multiple", files, message, xhr);
    });
    this.dropzone.on("sending", (file, xhr, formData) => {
      if (this.isS3) {
        if (this.isS3OverridesServerPropagation) {
          let signature = file.s3Signature;
          Object.keys(signature).forEach(function(key) {
            formData.append(key, signature[key]);
          });
        } else {
          formData.append("s3ObjectLocation", file.s3ObjectLocation);
        }
      }
      this.$emit("vdropzone-sending", file, xhr, formData);
    });
    this.dropzone.on("sendingmultiple", (file, xhr, formData) => {
      this.$emit("vdropzone-sending-multiple", file, xhr, formData);
    });
    this.dropzone.on("complete", (file) => {
      this.$emit("vdropzone-complete", file);
    });
    this.dropzone.on("completemultiple", (files) => {
      this.$emit("vdropzone-complete-multiple", files);
    });
    this.dropzone.on("canceled", (file) => {
      this.$emit("vdropzone-canceled", file);
    });
    this.dropzone.on("canceledmultiple", (files) => {
      this.$emit("vdropzone-canceled-multiple", files);
    });
    this.dropzone.on("maxfilesreached", (files) => {
      this.$emit("vdropzone-max-files-reached", files);
    });
    this.dropzone.on("maxfilesexceeded", (file) => {
      this.$emit("vdropzone-max-files-exceeded", file);
    });
    this.dropzone.on("processing", (file) => {
      this.$emit("vdropzone-processing", file);
    });
    this.dropzone.on("processingmultiple", (files) => {
      this.$emit("vdropzone-processing-multiple", files);
    });
    this.dropzone.on("uploadprogress", (file, progress, bytesSent) => {
      this.$emit("vdropzone-upload-progress", file, progress, bytesSent);
    });
    this.dropzone.on("totaluploadprogress", (totaluploadprogress, totalBytes, totalBytesSent) => {
      this.$emit(
        "vdropzone-total-upload-progress",
        totaluploadprogress,
        totalBytes,
        totalBytesSent
      );
    });
    this.dropzone.on("reset", () => {
      this.$emit("vdropzone-reset");
    });
    this.dropzone.on("queuecomplete", () => {
      this.$emit("vdropzone-queue-complete");
    });
    this.dropzone.on("drop", (event) => {
      this.$emit("vdropzone-drop", event);
    });
    this.dropzone.on("dragstart", (event) => {
      this.$emit("vdropzone-drag-start", event);
    });
    this.dropzone.on("dragend", (event) => {
      this.$emit("vdropzone-drag-end", event);
    });
    this.dropzone.on("dragenter", (event) => {
      this.$emit("vdropzone-drag-enter", event);
    });
    this.dropzone.on("dragover", (event) => {
      this.$emit("vdropzone-drag-over", event);
    });
    this.dropzone.on("dragleave", (event) => {
      this.$emit("vdropzone-drag-leave", event);
    });
    this.$emit("vdropzone-mounted");
  },
  beforeUnmount() {
    if (this.destroyDropzone) {
      this.dropzone.destroy();
    }
  },
  methods: {
    updateAWSSettings() {
      if (this.awss3 !== null) {
        this.aws = { ...this.awss3 };
        this.dropzoneSettings["autoProcessQueue"] = false;
        this.isS3 = true;
        this.isS3OverridesServerPropagation = this.aws.sendFileToServer === false;
        if (this.options.autoProcessQueue !== void 0) {
          this.wasQueueAutoProcess = this.options.autoProcessQueue;
        }
        if (this.isS3OverridesServerPropagation) {
          this.dropzoneSettings["url"] = (files) => files[0].s3Url;
        }
      }
    },
    updateSettings() {
      this.dropzoneSettings = Object.assign(this.dropzoneSettings, this.options);
    },
    manuallyAddFile: function(file, fileUrl) {
      file.manuallyAdded = true;
      this.dropzone.emit("addedfile", file);
      let containsImageFileType = false;
      if (fileUrl.indexOf(".svg") > -1 || fileUrl.indexOf(".png") > -1 || fileUrl.indexOf(".jpg") > -1 || fileUrl.indexOf(".jpeg") > -1 || fileUrl.indexOf(".gif") > -1 || fileUrl.indexOf(".webp") > -1)
        containsImageFileType = true;
      if (this.dropzone.options.createImageThumbnails && containsImageFileType && file.size <= this.dropzone.options.maxThumbnailFilesize * 1024 * 1024) {
        fileUrl && this.dropzone.emit("thumbnail", file, fileUrl);
        let thumbnails = file.previewElement.querySelectorAll(
          "[data-dz-thumbnail]"
        );
        for (let i = 0; i < thumbnails.length; i++) {
          thumbnails[i].style.width = this.dropzoneSettings.thumbnailWidth + "px";
          thumbnails[i].style.height = this.dropzoneSettings.thumbnailHeight + "px";
          thumbnails[i].style["object-fit"] = "contain";
        }
      }
      this.dropzone.emit("complete", file);
      if (this.dropzone.options.maxFiles) this.dropzone.options.maxFiles--;
      this.dropzone.files.push(file);
      this.$emit("vdropzone-file-added-manually", file);
    },
    setOption: function(option, value) {
      this.dropzone.options[option] = value;
    },
    removeAllFiles: function(bool) {
      this.dropzone.removeAllFiles(bool);
    },
    processQueue: function() {
      let dropzoneEle = this.dropzone;
      if (this.isS3 && !this.wasQueueAutoProcess) {
        this.getQueuedFiles().forEach((file) => {
          this.getSignedAndUploadToS3(file);
        });
      } else {
        this.dropzone.processQueue();
      }
      this.dropzone.on("success", function() {
        dropzoneEle.options.autoProcessQueue = true;
      });
      this.dropzone.on("queuecomplete", function() {
        dropzoneEle.options.autoProcessQueue = false;
      });
    },
    init: function() {
      return this.dropzone.init();
    },
    destroy: function() {
      return this.dropzone.destroy();
    },
    updateTotalUploadProgress: function() {
      return this.dropzone.updateTotalUploadProgress();
    },
    getFallbackForm: function() {
      return this.dropzone.getFallbackForm();
    },
    getExistingFallback: function() {
      return this.dropzone.getExistingFallback();
    },
    setupEventListeners: function() {
      return this.dropzone.setupEventListeners();
    },
    removeEventListeners: function() {
      return this.dropzone.removeEventListeners();
    },
    disable: function() {
      return this.dropzone.disable();
    },
    enable: function() {
      return this.dropzone.enable();
    },
    filesize: function(size) {
      return this.dropzone.filesize(size);
    },
    accept: function(file, done) {
      return this.dropzone.accept(file, done);
    },
    addFile: function(file) {
      return this.dropzone.addFile(file);
    },
    removeFile: function(file) {
      this.dropzone.removeFile(file);
    },
    getAcceptedFiles: function() {
      return this.dropzone.getAcceptedFiles();
    },
    getRejectedFiles: function() {
      return this.dropzone.getRejectedFiles();
    },
    getFilesWithStatus: function() {
      return this.dropzone.getFilesWithStatus();
    },
    getQueuedFiles: function() {
      return this.dropzone.getQueuedFiles();
    },
    getUploadingFiles: function() {
      return this.dropzone.getUploadingFiles();
    },
    getAddedFiles: function() {
      return this.dropzone.getAddedFiles();
    },
    getActiveFiles: function() {
      return this.dropzone.getActiveFiles();
    },
    getSignedAndUploadToS3(file) {
      let promise = awsEndpoint.sendFile(
        file,
        this.aws,
        this.isS3OverridesServerPropagation
      );
      if (!this.isS3OverridesServerPropagation) {
        promise.then((response) => {
          if (response.success) {
            file.s3ObjectLocation = response.message;
            setTimeout(() => this.dropzone.processFile(file));
            this.$emit("vdropzone-s3-upload-success", response.message);
          } else {
            if ("undefined" !== typeof response.message) {
              this.$emit("vdropzone-s3-upload-error", response.message);
            } else {
              this.$emit(
                "vdropzone-s3-upload-error",
                "Network Error : Could not send request to AWS. (Maybe CORS error)"
              );
            }
          }
        });
      } else {
        promise.then(() => {
          setTimeout(() => this.dropzone.processFile(file));
        });
      }
      promise.catch((error) => {
        alert(error);
      });
    },
    setAWSSigningURL(location) {
      if (this.isS3 && this.aws) {
        this.aws.signingURL = location;
      }
    }
  }
};
const _hoisted_1 = ["id"];
const _hoisted_2 = {
  key: 0,
  class: "dz-message"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    id: $props.id,
    ref: "dropzoneElement",
    class: normalizeClass({ "vue-dropzone dropzone": $props.includeStyling })
  }, [
    $props.useCustomSlot ? (openBlock(), createElementBlock("div", _hoisted_2, [
      renderSlot(_ctx.$slots, "default", {}, () => [
        _cache[0] || (_cache[0] = createTextVNode("Drop files here to upload"))
      ])
    ])) : createCommentVNode("", true)
  ], 10, _hoisted_1);
}
const vueDropzone = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  vueDropzone as v
};
