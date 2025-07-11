/***
 **
 * Fabrik functions
 **
 ***/

function addLoader(container = 'body') {
    if (window.document.getElementById('em-dimmer') === null) {
        let loaderElement = window.document.createElement('div')
        loaderElement.id = 'em-dimmer'
        loaderElement.classList.add('em-page-loader')

        let containerElement = window.document.querySelector(container)
        if (containerElement) {
            containerElement.insertAdjacentElement('afterend', loaderElement)
        }
    }
}

function removeLoader() {
    const loader = document.getElementById('em-dimmer')
    if (loader !== null) {
        loader.remove()
    }
}

/**
 * Hide one or multiple elements
 *
 * Examples :
 * hideFabrikElt(fab.get('jos_emundus_1001_00___name'), clearElements = true);
 * hideFabrikElt([fab.get('jos_emundus_1001_00___name'), fab.get('jos_emundus_1001_00___city')], clearElements = true);
 *
 * @param elements
 * @param clearElements
 */
function hideFabrikElt(elements, clearElements = false) {
    if (!Array.isArray(elements)) elements = [elements]

    elements.forEach((element, index) => {
        if (element) {
            if (clearElements && element.plugin !== '') {
                element.clear()
            }
            element.hide()
        } else {
            console.log(`hideFabrikElt: Element at index ${index} is undefined`)
        }
    })

}

/**
 * Show one or multiple elements
 *
 * Examples :
 * showFabrikElt(fab.get('jos_emundus_1001_00___name'));
 * showFabrikElt([fab.get('jos_emundus_1001_00___name'), fab.get('jos_emundus_1001_00___city')]);
 *
 * @param elements
 */
function showFabrikElt(elements) {
    if (!Array.isArray(elements)) elements = [elements]

    elements.forEach((element, index) => {
        if (element) {
            element.show()
        } else {
            console.log(`showFabrikElt: Element at index ${index} is undefined`)
        }
    })

}


/**
 * Hide one or multiple groups by target an element of those groups
 *
 * Examples :
 * hideFabrikGroupByElt(fab.get('jos_emundus_1001_00___name'), clearElements = true);
 * hideFabrikGroupByElt([fab.get('jos_emundus_1001_00___name'), fab.get('jos_emundus_1001_00___city')], clearElements = true);
 *
 * @param elements
 * @param clearElements
 */
function hideFabrikGroupByElt(elements, clearElements = false) {
    if (!Array.isArray(elements)) elements = [elements]

    let form = null

    elements.forEach((element, index) => {
        if (element) {
            document.getElementById(`group${element.groupid}`).classList.add('fabrikHide')

            if (clearElements) {

                if (form === null) {
                    form = Fabrik.getBlock(element.form.block)
                }

                if (form) {
                    Object.values(form.elements).map((all_element) => {
                        if (all_element.groupid === element.groupid && all_element.plugin !== '') {
                            all_element.clear()
                        }
                    })
                }
            }
        } else {
            console.log(`hideFabrikGroupByElt: Element at index ${index} is undefined`)
        }
    })

}

/**
 * Show one or multiple groups by target an element of those groups
 *
 * Examples :
 * showFabrikGroupByElt(fab.get('jos_emundus_1001_00___name'));
 * showFabrikGroupByElt([fab.get('jos_emundus_1001_00___name'), fab.get('jos_emundus_1001_00___city')]);
 *
 * @param elements
 */
function showFabrikGroupByElt(elements) {
    if (!Array.isArray(elements)) elements = [elements]

    elements.forEach((element, index) => {
        if (element) {
            document.getElementById(`group${element.groupid}`).classList.remove('fabrikHide')
        } else {
            console.log(`showFabrikGroupByElt: Element at index ${index} is undefined`)
        }
    })
}

/**
 * Hide one or multiple groups with ID
 *
 * Examples :
 * hideFabrikGroup(748, clearElements = true);
 * hideFabrikGroup([748, 123, 564], clearElements = true);
 *
 * @param groups
 * @param clearElements
 */
function hideFabrikGroup(groups, clearElements = false) {
    if (!Array.isArray(groups)) groups = [groups]

    groups.forEach((group, index) => {
        if (group) {
            let selector = document.getElementById(`group${group}`)
            if (selector) selector.classList.add('fabrikHide')

            if (clearElements) {
                let formDiv = document.querySelector(`.fabrikForm`).getAttribute('name')
                let form = Fabrik.getBlock(formDiv)

                Object.values(form.elements).map((element) => {
                    if (element.groupid == group) element.clear()
                })
            }
        } else {
            console.log(`hideFabrikGroup: Group at index ${index} is undefined`)
        }
    })
}

/**
 * Show one or multiple groups by ID
 *
 * Examples :
 * showFabrikGroup(748);
 * showFabrikGroup([748, 123, 456);
 *
 * @param groups
 */
function showFabrikGroup(groups) {
    if (!Array.isArray(groups)) groups = [groups]

    groups.forEach((group, index) => {
        if (group) {
            document.getElementById(`group${group}`).classList.remove('fabrikHide')
        } else {
            console.log(`showFabrikGroup: Group at index ${index} is undefined`)
        }
    })

}

/**
 * Disabled checkboxes when a limit has reached
 *
 * Examples :
 * defineCheckboxLimit(fab.get('jos_emundus_1001_00___name'));
 *
 * @param element
 * @param max
 */
function defineCheckboxLimit(element, max) {
    var allCheck = element.subElements

    if (element.get('value').length >= max) {
        Object.values(allCheck).forEach((option) => {
            if (!element.get('value').includes(option.value)) {
                option.disabled = true
            }
        })
    } else {
        Object.values(allCheck).forEach((option) => {
            option.disabled = false
        })
    }
}

/**
 * Uppercase the first letter of a value (working on keyup event)
 *
 * Examples :
 * firstLetterToUppercase(fab.get('jos_emundus_1001_00___name'));
 *
 * @param element
 */
function firstLetterToUppercase(element) {
    if (element.get('value').length > 1) {
        element.set(element.get('value')[0].toUpperCase() + element.get('value').substring(1))
    }
}

function numberOfDaysBetweenDates(date1, date2 = null) {
    if (date2 === null) {
        date2 = new Date()
    }

    const diffTime = Math.abs(date2 - date1)
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

function submit(title = 'Dossier en cours d\'envoi...', timer = 3000) {
    let fabrikForm = document.querySelector('form.fabrikForm')
    if (fabrikForm) {
        fabrikForm.style.opacity = 0
    }
    let fabrikHeader = document.querySelector('.page-header')
    if (fabrikHeader) {
        fabrikHeader.style.opacity = 0
    }

    let emundusForm = document.querySelector('.emundus-form')
    if (emundusForm) {
        emundusForm.classList.add('skeleton')
    }

    Swal.fire({
        icon: 'success',
        position: 'center',
        title: title,
        showCancelButton: false,
        showConfirmButton: false,
        customClass: {
            title: 'em-swal-title'
        },
        timer: timer
    }).then(() => {
        if (fabrikForm) {
            fabrikForm.submit()
        }
    })
}

function purcentage(elements) {

    const value = elements.get('value')

    if (typeof value === 'number') {
        if (value < 0) {
            elements.set('')
        } else if (value > 100) {
            elements.set('100')
        } else {
            elements.set(value.toString())
        }
    } else if (typeof value === 'string') {
        const numericValue = parseFloat(value)

        if (!isNaN(numericValue)) {
            if (numericValue < 0) {
                elements.set('')
            } else if (numericValue > 100) {
                elements.set('100')
            } else {
                elements.set(numericValue.toString())
            }
        } else {
            elements.set('')
        }
    }
}

/**
 * Check if the user is older than the minAge or younger than the maxAge
 * @param element
 * @param minAge
 * @param maxAge
 * @returns {Date}
 */
function birthDateValidation(element, minAge = 0, maxAge = 0, minMessage = 'Vous devez être plus agé que %s ans', maxMessage = 'Vous devez être plus jeune que %s ans') {
    const errorElement = document.querySelector('.fb_el_' + element.baseElementId + ' .fabrikErrorMessage')
    if (errorElement) {
        errorElement.innerHTML = ''
    }

    let error = ''
    const value = element.get('value')

    let regex = /^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/
    const regexTest = regex.test(value)
    const userBirthDate = new Date(value.replace(regex, '$3-$2-$1'))
    let todayYear = (new Date()).getFullYear()

    if (!regexTest || isNaN(userBirthDate)) {
        error = 'Veuillez saisir une date de naissance valide'
    } else if (minAge !== 0) {
        let cutOffMin = new Date()
        cutOffMin.setFullYear(todayYear - minAge)

        if (userBirthDate > cutOffMin) {
            error = minMessage.replace('%s', minAge.toString())
        }
    } else if (maxAge !== 0) {
        let cutOffMax = new Date()
        cutOffMax.setFullYear(todayYear - maxAge)

        if (userBirthDate < cutOffMax) {
            error = maxMessage.replace('%s', maxAge.toString())
        }
    }

    if (error !== '') {
        if (errorElement) {
            errorElement.innerHTML = error
        }
    }

    return userBirthDate
}

function getAgeFromDate(date) {
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = date.match(regex);
    if (!match) {
        return 0;
    }

    const [_, dayStr, monthStr, yearStr] = match;
    const day = parseInt(dayStr, 10);
    const month = parseInt(monthStr, 10);
    const year = parseInt(yearStr, 10);

    const birthDate = new Date(year, month - 1, day);
    if (
        birthDate.getFullYear() !== year ||
        birthDate.getMonth() !== month - 1 ||
        birthDate.getDate() !== day
    ) {
        return 0;
    }

    const today = new Date();
    let age = today.getFullYear() - year;

    const currentMonth = today.getMonth();
    const currentDay = today.getDate();
    if (
        currentMonth < birthDate.getMonth() ||
        (currentMonth === birthDate.getMonth() && currentDay < birthDate.getDate())
    ) {
        age--;
    }

    return age;
}

/**
 * Function to display a modal with a message in form 102
 */
function submitNewFile() {
    let campaign = document.getElementById('jos_emundus_campaign_candidature___campaign_id')
    for (let i = 0; i < campaign.length; i++) {
        if (campaign.options[i].value == -1) {
            campaign.options[i].disabled = true
            campaign.options[i].style.backgroundColor = '#efefef'
            campaign.options[i].style.fontStyle = 'italic'
        }
    }

    var cid = document.querySelector('#jos_emundus_campaign_candidature___campaign_id option:checked').value
    if (cid != '' && cid != 0) {
        document.querySelector('#form_102').style.visibility = 'hidden'
        Swal.fire({
                title: Joomla.JText._('COM_EMUNDUS_FABRIK_NEW_FILE'),
                text: Joomla.JText._('COM_EMUNDUS_FABRIK_NEW_FILE_DESC'),
                icon: 'success',
                showConfirmButton: false
            }
        )
        document.querySelector('#form_102').submit()
    }
}

function checkPasswordSymbols(element) {
    var regex = /[#$\{\};<> ]/
    var password_value = element.get('value')

    if (password_value.match(regex) != null) {
        Swal.fire({
            icon: 'error',
            title: Joomla.Text._('COM_EMUNDUS_FABRIK_WRONG_PASSWORD_TITLE'),
            text: Joomla.Text._('COM_EMUNDUS_FABRIK_WRONG_PASSWORD_DESC'),
            reverseButtons: true,
            customClass: {
                title: 'em-swal-title',
                confirmButton: 'em-swal-confirm-button',
                actions: 'em-swal-single-action'
            }
        })

        element.set('')
    }
}

function cleanNumberInput(element, maxDecimals = 0, noGreaterThan = null, authorizeNegativeNumber = false) {
    var value = element.get('value')
    const input = document.getElementById(element.baseElementId)
    var nonDigitExceptCommaDot = ''

    if (authorizeNegativeNumber) {
        nonDigitExceptCommaDot = /[^-0-9.,]/
    } else {
        nonDigitExceptCommaDot = /[^0-9.,]/
    }

    const moreThanOneCommaDot = /[.,].*[.,]/

    const caretPosition = input.selectionStart
    const lastInsertedCharacter = value.charAt(caretPosition - 1)

    if (nonDigitExceptCommaDot.test(value)) {
        value = value.replace(nonDigitExceptCommaDot, '')
    }

    // If the last inserted character is a comma, replace it with a dot
    if (lastInsertedCharacter === ',') {
        value = value.substring(0, caretPosition - 1) + '.' + value.substring(caretPosition)
    }

    if (moreThanOneCommaDot.test(value)) {
        value = value.substring(0, caretPosition - 1) + value.substring(caretPosition)
    }

    const dotOrCommaIndex = value.indexOf('.') !== -1 ? value.indexOf('.') : value.indexOf(',')
    if (dotOrCommaIndex !== -1) {
        const digitsAfterDotOrComma = value.substring(dotOrCommaIndex + 1).length
        if (digitsAfterDotOrComma > maxDecimals) {
            value = value.substring(0, caretPosition - 1) + value.substring(caretPosition)
        }
    }
    if (maxDecimals === 0 && value.indexOf('.') !== -1) {
        value = value.replace('.', '')
    }

    if (noGreaterThan !== null && value > noGreaterThan) {
        value = ''
    }


    return value
}

function prefillBic(element, bic_element) {
    let table_name = element.form.options.primaryKey.split('___')[0]
    var fab = element.form.elements
    if (element.options.inRepeatGroup) {
        var bic = fab.get(table_name + '_' + element.groupid + '_repeat___' + bic_element + '_' + element.getRepeatNum())
    } else {
        var bic = fab.get(table_name + '___' + bic_element)
    }

    var value = element.get('value')

    if (bic && value != '') {
        value = value.replace(/\s/g, '')
        var bank_code = value.substring(4, 9)

        if (element.options.bicMapping[bank_code]) {
            bic.set(element.options.bicMapping[bank_code])
        }
    }
}

/**
 * Return the showed value in the form
 * <div> element from attachment for fileupload
 * @param element
 * @returns {string}
 */
function getElementShowedValue(element) {

    if (element) {
        const container = element.getContainer()

        switch (element.plugin) {
            case 'fabrikyesno':
            case 'fabrikradiobutton':
            case 'fabrikcheckbox':
            case 'databasejoin':
            case 'fabrikdropdown':
                const values = Array.isArray(element.get('value')) ? element.get('value') : [element.get('value')]
                let inputChecked = []
                values.forEach((value) => {
                    if (value !== null && value !== '') {
                        inputChecked.push(container.querySelector('[value=\"' + value + '\"]'))
                    }
                })

                let checked = []
                inputChecked.forEach((input) => {
                    if (input) {
                        if (input.localName === 'option') {
                            checked.push(input.innerText)
                        } else {
                            checked.push(input.nextSibling.textContent)
                        }
                    }
                })
                return checked.join(', ')


            case 'panel':
                const div_info = container.querySelector('.fabrikElementReadOnly').cloneNode(true)
                div_info.classList.remove('fabrikHide')
                return div_info

            case 'emundus_fileupload':
            case 'emundus_fileupload_new':
                const div_attachment = container.querySelector('.em-fileAttachment').cloneNode(true)
                for (const child of div_attachment.querySelectorAll('*')) {
                    if (child.classList.contains('em-deleteFile')) {
                        child.remove()
                    }
                }
                return div_attachment

            default:
                return element.get('value')
        }
    }
}


/**
 * Put elements in readonly mode
 * @param elements
 * @param flag
 * @returns {void}
 */
function readOnlyElt(elements, flag = true) {

    if (!Array.isArray(elements)) elements = [elements]

    elements.forEach((element, index) => {
        if (element) {
            const elementHTML = element.element
            const prefix = 'span '
            let span = document.getElementById(prefix + elementHTML.id)

            if (flag) { // on rajoute le span s'il n'existe pas
                if (span === null) {
                    span = document.createElement('span')
                    span.id = prefix + elementHTML.id

                    let parent = document.getElementById(elementHTML.id).parentNode

                    elementHTML.classList.add('fabrikHide')
                    parent.appendChild(span)
                }

                span.textContent = getElementShowedValue(element)

            } else { // on retire le span s'il existe
                if (span != null) {
                    elementHTML.classList.remove('fabrikHide')
                    span.remove()
                }
            }
        } else {
            console.log(`readOnlyElt: Element at index ${index} is undefined`)
        }
    })
}

/**
 + * Toggle readonly mode for one or multiple elements
 + * @param elements
 + * @param toogle {boolean} - true to enable readonly mode, false to disable
 + */
function toggleReadOnly(elements, toogle = true) {

    if (!Array.isArray(elements)) elements = [elements]

    elements.forEach((element) => {
        if (element) {

            // get all important html elements
            const main_div_element = document.querySelector('.fb_el_' + element.element.id)
            const main_div_fabrik_element = main_div_element.querySelector('.fabrikElement')
            const main_div_children = main_div_fabrik_element.children
            let span = document.getElementById('span_' + element.element.id)

            if (toogle) {


                // get the value to show for span
                const value = getElementShowedValue(element)
                if (value instanceof HTMLElement) {
                    if (span.children.length === 0) {
                        span.appendChild(value)
                    } else {
                        span.replaceChild(value, span.children[0])
                    }
                } else {
                    span.textContent = value
                }

            } else {

                // remove span element
                if (span !== null) {
                    span.remove()
                }

                // remove fabrikHide class to each children elements
                for (let i = 0; i < main_div_children.length; i++) {
                    main_div_children[i].classList.remove('fabrikHide')
                }
            }
        }
    })
}

function togglePasswordVisibility() {
    var passwordInput = document.querySelector('#jos_emundus_users___password');

    var spanShowPassword = document.createElement('span');
    spanShowPassword.classList.add('material-icons-outlined');
    spanShowPassword.classList.add('em-pointer');
    spanShowPassword.innerText = "visibility";
    spanShowPassword.style.position = "absolute";
    spanShowPassword.style.top = "10px";
    spanShowPassword.style.right = "10px";
    spanShowPassword.style.opacity = "0.3";

    passwordInput.parentNode.style.position = "relative";

    passwordInput.parentNode.insertBefore(spanShowPassword, passwordInput.nextSibling);

    spanShowPassword.addEventListener('click', function() {
        if (spanShowPassword.innerText === "visibility_off") {
            spanShowPassword.innerText = "visibility";
            passwordInput.type = "password";
        } else {
            spanShowPassword.innerText = "visibility_off";
            passwordInput.type = "text";
        }
    });
}

function beforeSubmitEvaluation(formId)
{
    const formSubmitButton = document.querySelector('#fabrikSubmit_'+formId);
    if (formSubmitButton) {
        formSubmitButton.type = 'button';
        formSubmitButton.onclick = null;

        formSubmitButton.addEventListener('click', function (event) {
            const form = document.querySelector('form[name=form_'+formId+']');
            event.stopImmediatePropagation();
            event.preventDefault();

            Swal.fire({
                title: Joomla.Text._('COM_EMUNDUS_ONBOARD_EVALUATION_LOCK_TITLE'),
                text: Joomla.Text._('COM_EMUNDUS_ONBOARD_EVALUATION_LOCK_TEXT'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_OK'),
                cancelButtonText: Joomla.Text._('COM_EMUNDUS_ONBOARD_CANCEL'),
                reverseButtons: true,
                customClass: {
                    title: 'em-swal-title',
                    confirmButton: 'em-swal-confirm-button',
                    cancelButton: 'em-swal-cancel-button',
                    actions: 'em-swal-double-action',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
                    setTimeout(() => {
                        form.submit();
                    },500);
                }
            });
        });
    } else {
        console.log('⚠️ Formulaire non trouvé !');
    }
}
function capitalizeCompoundName(str) {
    return str
        .toLowerCase()
        .replace(/(^|[\s-])(\w)/g, (match, sep, letter) => sep + letter.toUpperCase());
}