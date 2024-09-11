
export default {
  async getAttachmentProgress(fnum) {
    if (fnum !== '') {
      return fetch('index.php?option=com_emundus&controller=files&task=getattachmentprogress&fnum=' + fnum).then(response => {
        if (response.ok) {
          return response.json();
        } else {
          return {
            status: false,
            msg: 'response not ok'
          };
        }
      }).then(data => {
        return data;
      }).catch(error => {
        return {
          status: false,
          msg: error.message
        };
      });
    } else {
      return {
        status: false,
        msg: 'fnum is empty'
      };
    }
  },

  async getAttachmentsByFnum(fnum) {
    return fetch('index.php?option=com_emundus&controller=application&task=getattachmentsbyfnum&fnum=' + fnum).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      if (data.status) {
        // add show attribute to true to all attchments in response data

        if (typeof data.attachments === 'string') {
          data.attachments = JSON.parse(data.attachments);
        }

        if (typeof data.attachments === 'object') {
          // cast object to array of objects
          data.attachments = Object.values(data.attachments);
        }

        data.attachments.forEach(attachment => {
          if (attachment.is_validated === null) {
            attachment.is_validated = -2;
          }

          if (attachment.upload_description === null || typeof attachment.upload_description !== 'string') {
            attachment.upload_description = '';
          }

          attachment.show = true;
        });

        return data;
      }
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async getAttachmentCategories() {
    return fetch('index.php?option=com_emundus&controller=files&task=getattachmentcategories').then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      return data;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async deleteAttachments(fnum, student_id, attachment_ids) {
    const formData = new FormData();
    formData.append('ids', JSON.stringify(attachment_ids));
    formData.append('fnum', fnum);
    formData.append('student_id', student_id);

    return fetch(`index.php?option=com_emundus&controller=application&task=deleteattachement`, {
      method: 'POST',
      body: formData
    }).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      return data;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },
  async updateAttachment(formData) {
    return fetch('index.php?option=com_emundus&controller=application&task=updateattachment', {
      method: 'POST',
      body: formData
    }).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      return data;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async getPreview(user, filename, upload_id) {
    return fetch('index.php?option=com_emundus&controller=application&task=getattachmentpreview&user=' + user + '&filename=' + filename + '&upload_id=' + upload_id).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      return data;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },
  exportAttachments(student, fnum, attachment_ids) {
    const formData = new FormData();
    formData.append('attachments_only', true);
    formData.append('student_id', student);
    formData.append('fnum', fnum);
    attachment_ids.forEach(id => {
      formData.append('ids[]', id);
    });

    return fetch('index.php?option=com_emundus&controller=application&task=exportpdf', {
      method: 'POST',
      body: formData
    }).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(json => {
      return json;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async getProfileAttachments() {
    return fetch('index.php?option=com_emundus&controller=users&task=getprofileattachments').then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      return data;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async getProfileAttachmentsAllowed() {
    return fetch('index.php?option=com_emundus&controller=users&task=getprofileattachmentsallowed').then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(data => {
      return data;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async deleteProfileAttachment(id, filename) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('filename', filename);

    return fetch('index.php?option=com_emundus&controller=users&task=deleteprofileattachment', {
      method: 'POST',
      body: formData
    }).then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(json => {
      return json;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async isExpiresDateDisplayed() {
    return fetch('index.php?option=com_emundus&controller=settings&task=isexpiresdatedisplayed').then(response => {
      if (response.ok) {
        return response.json();
      } else {
        return {
          status: false,
          msg: 'response not ok'
        };
      }
    }).then(json => {
      return json;
    }).catch(error => {
      return {
        status: false,
        msg: error.message
      };
    });
  },
};
