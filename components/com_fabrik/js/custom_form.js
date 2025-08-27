requirejs(['fab/fabrik'], function () {
  var removedFabrikFormSkeleton = false;
  var formDataChanged = false;
  var js_rules = [];
  var table_name = '';
  var form_loaded = false;
  var elt_to_not_clear = ['panel', 'calc'];

  var operators = {
    '=': function (a, b, plugin) {
      if (!Array.isArray(a)) {
        if (typeof a === 'string' && typeof b === 'string') {
          a = a.toLowerCase();
          b = b.toLowerCase();
        }
        return a == b;
      } else {
        return a.includes(b);
      }
    },
    '!=': function (a, b, plugin) {
      if (!Array.isArray(a)) {
        return a != b;
      } else {
        return !a.includes(b);
      }
    },
    '>' : function (a, b, plugin) {
        if (!Array.isArray(a)) {
            if (typeof a === 'string' && typeof b === 'string') {
            a = a.toLowerCase();
            b = b.toLowerCase();
            }

            // TODO: cast types to number ? check if values are numeric ?

            return a > b;
        } else {
            return false; // Not applicable for arrays
        }
    },
    '<' : function (a, b, plugin) {
      if (!Array.isArray(a)) {
        if (typeof a === 'string' && typeof b === 'string') {
          a = a.toLowerCase();
          b = b.toLowerCase();
        }
        return a < b;
      } else {
        return false; // Not applicable for arrays
      }
    },
    '>=': function (a, b, plugin) {
        if (!Array.isArray(a)) {
            if (typeof a === 'string' && typeof b === 'string') {
            a = a.toLowerCase();
            b = b.toLowerCase();
            }
            return a >= b;
        } else {
            return false; // Not applicable for arrays
        }
    },
    '<=': function (a, b, plugin) {
        if (!Array.isArray(a)) {
            if (typeof a === 'string' && typeof b === 'string') {
            a = a.toLowerCase();
            b = b.toLowerCase();
            }
            return a <= b;
        } else {
            return false; // Not applicable for arrays
        }
    }
    // ...
  };

  Fabrik.addEvent('fabrik.form.loaded', function (form) {
    /*setCookie('fabrik_form_session', 'true', 15);

            setInterval(() => {
                let active_form_session = getCookie('fabrik_form_session');
                if(!active_form_session) {
                    setTimeout(() => {
                        window.location.href = window.location.origin + '/';
                    }, 2000);
                }
            }, 10000);*/

    table_name = form.options.primaryKey.split('___')[0];

    manageRepeatGroup(form);

    var formBlock = document.getElementsByClassName('fabrikForm')[0];

    formBlock.addEventListener('input', function () {
      if (!formDataChanged) {
        formDataChanged = true;
      }
    });

    var links = [];
    var checklist_items = document.querySelectorAll('.mod_emundus_checklist a');
    var logo = document.querySelectorAll('#header-a a');
    var menu_items = document.querySelectorAll('#header-b a');
    var user_items = document.querySelectorAll('#userDropdown a');
    var flow_items = document.querySelectorAll('.mod_emundus_flow___intro a');
    var footer_items = document.querySelectorAll('#g-footer a');
    var back_button_form = document.querySelectorAll('.fabrikActions .goback-btn');

    links = [...checklist_items, ...menu_items, ...user_items, ...flow_items, ...logo, ...footer_items, ...back_button_form];

    for (var i = 0, len = links.length; i < len; i++) {
      links[i].onclick = (e) => {
        if (formDataChanged) {
          e.preventDefault();

          Swal.fire({
            title: Joomla.JText._('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_TITLE'),
            html: Joomla.JText._('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_TEXT'),
            reverseButtons: true,
            showCloseButton: false,
            showCancelButton: true,
            confirmButtonText: Joomla.JText._('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_CONFIRM'),
            cancelButtonText: Joomla.JText._('COM_EMUNDUS_FABRIK_WANT_EXIT_FORM_CANCEL'),
            customClass: {
              title: 'em-swal-title',
              cancelButton: 'em-swal-cancel-button',
              confirmButton: 'btn btn-primary save-btn sauvegarder button save_continue',
            },
          }).then((result) => {
            if (result.value) {
              clearFormSession(form.id);

              if (e.srcElement.classList.contains('goback-btn')) {
                window.history.back();
              }

              let href = window.location.origin + '/index.php';
              // If click event target is a direct link
              if (typeof e.target.href !== 'undefined') {
                href = e.target.href;
              }
              // If click event target is a child of a link
              else {
                e = e.target;
                let attempt = 0;
                do {
                  e = e.parentNode;
                } while (typeof e.href === 'undefined' && attempt++ < 5);

                if (typeof e.href !== 'undefined') {
                  href = e.href;
                }
              }

              window.location.href = href;
            }
          });
        } else {
          clearFormSession(form.id);
          if (e.srcElement.classList.contains('goback-btn')) {
            if (window.history.length > 1) {
              window.history.back();
            } else {
              window.close();
            }
          }
        }
      }
    }
  });

  Fabrik.addEvent('fabrik.form.group.duplicate.end', function (form, event) {
    manageRepeatGroup(form);

    setTimeout(() => {
      let last_index_added = form.addedGroups.length;
      form.elements.forEach(function (element) {
        if (element.getRepeatNum() == last_index_added) {
          manageRules(form, element, true);

          var $el = jQuery(element.element);
          $el.on(element.getChangeEvent(), function (e) {
            manageRules(form, element);
          });
        }
      });
    }, 200);
  });

  Fabrik.addEvent('fabrik.form.group.delete.end', function (form, event) {
    manageRepeatGroup(form);
  });

  Fabrik.addEvent('fabrik.form.elements.added', function (form, event) {
    let formHeight = document.getElementById(form.getBlock()).offsetHeight;
    if (!form_loaded) {
      setTimeout(() => {
        fetch('/index.php?option=com_emundus&controller=form&task=getjsconditions&form_id=' + form.id).then(response => response.json()).then(data => {
          if (data.status) {
            js_rules = data.data.conditions;

            form.elements.forEach(function (element) {
              manageRules(form, element, false);

              var $el = jQuery(element.element);
              $el.on(element.getChangeEvent(), function (e) {
                manageRules(form, element);
              });
            });
          }

          if (!removedFabrikFormSkeleton) {
            removeFabrikFormSkeleton();
          }

          form_loaded = true;
        });
      }, 500);

      form.elements.forEach(function (element) {
        if (element.plugin === 'fabrikdate') {
          var elementOffset = element.element.parentElement.parentElement.offsetTop;
          if (formHeight >= 500 && (formHeight - elementOffset < 400)) {
            element.element.getElementsByClassName('js-calendar')[0].style.bottom = 'var(--em-form-height)';
          }
        }
      });
    }
  });

  window.setInterval(function () {
    if (!removedFabrikFormSkeleton && Object.entries(Fabrik.blocks).length > 0) {
      removeFabrikFormSkeleton();
    }
  }, 5000);

  function removeFabrikFormSkeleton() {
    let header = document.querySelector('.page-header');
    if (header) {
      if (header.querySelector('h1')) {
        document.querySelector('.page-header h1').style.opacity = 1;
      }
      header.classList.remove('skeleton');
    }
    let intro = document.querySelector('.em-form-intro');
    if (intro) {
      let content = document.querySelector('.em-form-intro').children;
      if (content.length > 0) {
        for (const child of content) {
          child.style.opacity = 1;
        }
      }
      intro.classList.remove('skeleton');
    }
    let grouptitle = document.querySelectorAll('.fabrikGroup .legend');
    for (title of grouptitle) {
      title.style.opacity = 1;
    }
    grouptitle = document.querySelectorAll('.fabrikGroup h2, .fabrikGroup h3');
    for (title of grouptitle) {
      title.style.opacity = 1;
    }
    let groupintros = document.querySelectorAll('.groupintro');
    if (groupintros) {
      groupintros.forEach((groupintro) => {
        groupintro.style.opacity = 1;
      });
    }

    let elements = document.querySelectorAll('.fabrikGroup .row');
    let elements_fields = document.querySelectorAll('.fabrikElementContainer');
    for (field of elements_fields) {
      field.style.opacity = 1;
    }
    for (elt of elements) {
      elt.style.marginTop = '0';
      elt.classList.remove('skeleton');
    }

    removedFabrikFormSkeleton = true;
  }

  function manageRepeatGroup(form) {
    setTimeout(() => {
      // ID of the group that was duplicated (ex. group686)
      let repeat_groups = form.repeatGroupMarkers;
      repeat_groups.forEach(function (repeatGroupsMarked, group) {
        if (repeatGroupsMarked !== 0) {
          let minRepeat = Number(form.options.minRepeat[group]);
          let maxRepeat = Number(form.options.maxRepeat[group]);

          let deleteButtons = document.querySelectorAll('#group' + group + ' .fabrikGroupRepeater .deleteGroup');

          if (repeatGroupsMarked > 1) {
            deleteButtons.forEach(function (button) {
              button.style.display = 'flex';
            });

            // Hide delete button for first group
            deleteButtons[0].style.display = 'none';
          } else if (minRepeat > 0) {
            deleteButtons.forEach(function (button) {
              button.style.display = 'none';
            });
          }

          let addButtons = document.querySelectorAll('#group' + group + ' .fabrikGroupRepeater .addGroup');

          if (maxRepeat !== 0 && repeatGroupsMarked >= maxRepeat) {
            addButtons.forEach(function (button, index) {
              button.style.display = 'none';
            })
          } else {
            if (addButtons.length > 1) {
              addButtons.forEach(function (button, index) {
                if ((index + 1) < addButtons.length) {
                  button.style.display = 'none';
                } else {
                  button.style.display = 'flex';
                }
              })
            } else {
              addButtons.forEach(function (button, index) {
                button.style.display = 'flex';
              })
            }
          }
        }
      });
    }, 100)
  }

  function manageRules(form, element, clear = true) {
    let elt_name = element.origId ? element.origId.split('___')[1] : element.baseElementId.split('___')[1];

    let elt_rules = [];
    js_rules.forEach((js_rule) => {
      js_rule.conditions.forEach((condition) => {
        if (condition.field == elt_name) {
          elt_rules.push(js_rule);
        }
      });
    });

    if (elt_rules.length > 0) {
      elt_rules.forEach((rule) => {
        let condition_state = [];

        rule.conditions.forEach((condition) => {
          if (condition.group && !condition_state[condition.group]) {
            condition_state[condition.group] = {
              'type': condition.group_type,
              'states': []
            };
          }

          form.elements.forEach((elt) => {
            let name = elt.origId ? elt.origId.split('___')[1] : elt.baseElementId.split('___')[1];

            if (name == condition.field && elt.getRepeatNum() == element.getRepeatNum()) {
              if (operators[condition.state](elt.get('value'), condition.values, elt.plugin)) {
                if (condition.group) {
                  condition_state[condition.group].states.push(true);
                } else {
                  condition_state.push(true);
                }
              } else {
                if (condition.group) {
                  condition_state[condition.group].states.push(false);
                } else {
                  condition_state.push(false);
                }
              }
            }
          });
        });

        if (condition_state.length > 0 && check_condition(condition_state, rule.group)) {
          rule.actions.forEach((action) => {

            let fields = action.fields.split(',');

            if (action.action == 'define_repeat_group') {
              let params = JSON.parse(action.params);
              params[0].maxRepeat = Number(params[0].maxRepeat);
              params[0].minRepeat = Number(params[0].minRepeat);

              form.options.maxRepeat[action.fields] = params[0].maxRepeat;
              form.options.minRepeat[action.fields] = params[0].minRepeat;
              var repeat_counter = document.getElementById('fabrik_repeat_group_' + action.fields + '_counter');
              var repeat_rows = repeat_counter.value.toInt();

              if (params[0].maxRepeat > 0 && repeat_rows > params[0].maxRepeat) {
                var group = document.getElementById('group' + action.fields);
                // Delete groups
                for (i = repeat_rows; i > params[0].maxRepeat; i--) {
                  var del_btn = jQuery(document.querySelector('#group' + action.fields + ' .deleteGroup')).last()[0];
                  subGroup = jQuery(group.getElements('.fabrikSubGroup')).last()[0];
                  if (typeOf(del_btn) !== 'null') {
                    var del_e = new Event.Mock(del_btn, 'click');
                    form.deleteGroup(del_e, group, subGroup);
                  }
                }
              }

              // If min repeat is greater than 1, add groups of min repeat
              if (params[0].minRepeat > 1 && repeat_rows < params[0].minRepeat) {
                var add_btn = jQuery(document.querySelector('#group' + action.fields + ' .addGroup')).last()[0];

                if (typeOf(add_btn) !== 'null') {
                  for (i = 1; i < params[0].minRepeat; i++) {
                    var add_e = new Event.Mock(add_btn, 'click');
                    form.duplicateGroup(add_e);
                  }
                }
              }

              manageRepeatGroup(form);
            } else {
              form.elements.forEach((elt) => {
                let name = elt.origId ? elt.origId.split('___')[1] : elt.baseElementId.split('___')[1];
                let id = elt.baseElementId ? elt.baseElementId : elt.strElement;
                if (fields.includes(name) && ((element.options.inRepeatGroup && elt.getRepeatNum() == element.getRepeatNum()) || !element.options.inRepeatGroup)) {
                  if (['show', 'hide'].includes(action.action)) {
                    form.doElementFX('element_' + elt.strElement, action.action, elt);

                    if (action.action == 'hide') {
                      if (clear && !elt_to_not_clear.includes(elt.plugin)) {
                        elt.clear();
                      }
                    }

                    let event = new Event(elt.getChangeEvent());
                    elt.element.dispatchEvent(event);
                  } else if (['show_options', 'hide_options'].includes(action.action)) {
                    switch (action.action) {
                    case 'show_options':
                      addOption(elt, action.params);
                      if (clear) {
                        sortSelect(elt.element);
                      }
                      break;
                    case 'hide_options':
                      removeOption(elt, action.params);
                      break;
                    }

                    if (clear) {
                      let event = new Event(elt.getChangeEvent());
                      elt.element.dispatchEvent(event);
                    }
                  } else if (['set_optional', 'set_mandatory']) {
                    let required_icon = document.querySelector('label[for="' + id + '"] span.material-symbols-outlined');
                    if (required_icon) {
                      if (action.action == 'set_optional') {
                        required_icon.style.display = 'none';
                      } else {
                        required_icon.style.display = 'inline-block';
                      }
                    }
                  }
                }
              });
            }
          });
        } else {
          let opposite_action = 'hide';

          rule.actions.forEach((action) => {

            if (action.action == 'define_repeat_group') {
              form.options.maxRepeat[action.fields] = 0;
              form.options.minRepeat[action.fields] = 1;
              manageRepeatGroup(form);
            } else {

              switch (action.action) {
              case 'show':
                opposite_action = 'hide';
                break;
              case 'hide':
                opposite_action = 'show';
                break;
              case 'show_options':
                opposite_action = 'hide_options';
                break;
              case 'hide_options':
                opposite_action = 'show_options';
                break;
              case 'set_optional':
                opposite_action = 'set_mandatory';
                break;
              case 'set_mandatory':
                opposite_action = 'set_optional';
                break;
              }

              let fields = action.fields.split(',');

              form.elements.forEach((elt) => {
                let name = elt.origId ? elt.origId.split('___')[1] : elt.baseElementId.split('___')[1];
                let id = elt.baseElementId ? elt.baseElementId : elt.strElement;
                if (fields.includes(name) && ((element.options.inRepeatGroup && elt.getRepeatNum() == element.getRepeatNum()) || !element.options.inRepeatGroup)) {
                  if (['show', 'hide'].includes(opposite_action)) {
                    form.doElementFX('element_' + elt.strElement, opposite_action, elt);

                    if (opposite_action == 'hide') {
                      if (clear && !elt_to_not_clear.includes(elt.plugin)) {
                        elt.clear();
                      }
                    }

                    let event = new Event(elt.getChangeEvent());
                    elt.element.dispatchEvent(event);
                  } else if (['show_options', 'hide_options'].includes(action.action)) {
                    switch (opposite_action) {
                    case 'show_options':
                      addOption(elt, action.params);
                      if (clear) {
                        sortSelect(elt.element);
                      }
                      break;
                    case 'hide_options':
                      removeOption(elt, action.params);
                      break;
                    }

                    if (clear) {
                      let event = new Event(elt.getChangeEvent());
                      elt.element.dispatchEvent(event);
                    }
                  } else if (['set_optional', 'set_mandatory']) {
                    let required_icon = document.querySelector('label[for="' + id + '"] span.material-symbols-outlined');
                    if (required_icon) {
                      if (opposite_action == 'set_optional') {
                        required_icon.style.display = 'none';
                      } else {
                        required_icon.style.display = 'inline-block';
                      }
                    }
                  }
                }
              });
            }
          });
        }
      });
    }
  }

  function check_condition(condition_states, group) {
    let is_group = false;
    let grouped_conditions = [];

    for (var i = 0; i < condition_states.length; i++) {
      if (typeof condition_states[i] === 'object') {
        is_group = true;
        break;
      }
    }

    if (is_group) {
      for (var i = 0; i < condition_states.length; i++) {
        if (condition_states[i] !== undefined && typeof condition_states[i] === 'object') {
          if (condition_states[i].type === 'AND') {
            if (condition_states[i].states.every(v => v === true)) {
              grouped_conditions.push(true);
            } else {
              grouped_conditions.push(false);
            }
          } else {
            if (condition_states[i].states.some(v => v === true)) {
              grouped_conditions.push(true);
            } else {
              grouped_conditions.push(false);
            }
          }
        } else if (condition_states[i] !== undefined && typeof condition_states[i] === 'boolean') {
          grouped_conditions.push(condition_states[i]);
        }
      }
    } else {
      grouped_conditions = condition_states;
    }

    if (group === 'AND') {
      return grouped_conditions.every(v => v === true);
    } else {
      return grouped_conditions.some(v => v === true);
    }
  }

  function addOption(field, params) {
    params = JSON.parse(params);
    const options = field.element.options;

    params.forEach((p) => {
      // Check if option already exists
      var exists = false;
      [...options].map((o) => {
        if (o.value == p.primary_key) {
          exists = true;
        }
      });
      if (!exists) {
        var option = document.createElement("option");
        option.text = p.value;
        option.value = p.primary_key;
        field.element.add(option);
      }
    });
  }

  function removeOption(field, params) {
    params = JSON.parse(params);

    const options = field.element.options;
    const values = params.map((param) => {
      return param.primary_key.toString();
    });

    for (let i = options.length - 1; i >= 0; i--) {
      if (values.includes(options[i].value)) {
        field.element.remove(i);
      }
    }
  }

  function sortSelect(selElem) {
    var tmpAry = new Array();
    for (var i = 0; i < selElem.options.length; i++) {
      tmpAry[i] = new Array();
      tmpAry[i][0] = selElem.options[i].text;
      tmpAry[i][1] = selElem.options[i].value;
    }
    tmpAry.sort((a, b) => {
      // Sort by value
      return a[1].localeCompare(b[1]);
    });
    while (selElem.options.length > 0) {
      selElem.options[0] = null;
    }
    for (var i = 0; i < tmpAry.length; i++) {
      var op = new Option(tmpAry[i][0], tmpAry[i][1]);
      selElem.options[i] = op;
    }
    return;
  }

  function saveDatas(element, event) {
    let name = element.baseElementId;
    let value = element.get('value');

    let formData = new FormData();
    formData.append('element', name);
    formData.append('value', value);
    formData.append('form_id', element.form.id);

    fetch('/index.php?option=com_emundus&controller=application&task=saveformsession', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    }).then((response) => {
      return response.json();
    }).then((data) => {
      if (data.success) {
        setCookie('fabrik_form_session', 'true', 15);
      }
    }).catch((error) => {
      console.error('Error:', error);
    });
  }

  function clearFormSession(form_id) {
    let formData = new FormData();
    formData.append('form_id', form_id);

    fetch('/index.php?option=com_emundus&controller=application&task=clearformsession', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    }).then((response) => {
      return response.json();
    }).then((data) => {
      if (data.success) {
        document.cookie = "fabrik_form_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
      }
    }).catch((error) => {
      console.error('Error:', error);
    });
  }

  function setCookie(cname, cvalue, minutes) {
    const d = new Date();
    d.setTime(d.getTime() + (minutes * 60 * 1000));
    let expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  }

  function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for (let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }
});
