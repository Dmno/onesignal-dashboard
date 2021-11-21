$(document).ready(function () {
    let urlStatus = false;
    let dateStatus = true;
    let allowEditing = true;
    let notificationSendingRegex = /[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1]) (2[0-3]|[01][0-9]):[0-5][0-9]/;
    let duplicationUrl = "";

    $('.show-loading-block').click(function () {
        $('#loading-spinner').css('margin-top','18px');
        $('#loading').fadeIn('fast');
        $('#loading-text').html('Loading..');
    });

    function previewSwitcher(windowsNotification, androidNotification, notificationType)
    {
        if (windowsNotification.is(':visible')) {
            windowsNotification.hide();
            notificationType.html('Web, Android');
            androidNotification.css('display', 'inline-block');
        } else {
            androidNotification.hide();
            notificationType.html('Web, Windows');
            windowsNotification.show();
        }
    }

    $('#switch-preview').on("click",function () {
        let windowsNotification = $('#windows-notification');
        let androidNotification = $('#android-notification');
        let notificationType = $('#notification-type');

        previewSwitcher(windowsNotification, androidNotification, notificationType);
    });

    $('#confirm').prop("checked", false);

    let ns_name = $('.notification_name');
    let ns_country = $('.notification_country');
    let ns_title = $('.notification_title');
    let ns_message = $('.notification_message');
    let ns_icon = $('.notification_icon');
    let ns_image = $('.notification_image');
    let ns_url = $('.notification_url');
    let ns_delivery = $('.notification_delivery');
    let ns_date = $('.notification_schedule');
    let ns_optimisation = $('.notification_optimisation');
    let ns_store = $('.notification_store');

    $('#to-schedule').on("click",function () {
        $('#send-time').html('Right away');
        $('#notification-part').slideUp('fast');
        $('#schedule-part').slideDown('fast');
        $('#schedule-block').slideDown('fast');
    });

    $('#back').on("click",function () {
        $('#schedule-part').slideUp('fast');
        $('#notification-part').slideDown('fast');
    });

    function resetDuplicationPageUrl() {
        let newUrl =  duplicationUrl.substr(0, duplicationUrl.lastIndexOf("duplicate/"));
        if (!newUrl) {
            newUrl =  duplicationUrl.substr(0, duplicationUrl.lastIndexOf("create/"));
        }
        history.pushState({}, null, newUrl);
    }

    window.onload = function() {
        let formType = $('#form-type').html();
        getSettingsTimezone();
        if (formType === "copy" || formType === "campaign" || formType === "new") {
            urlStatus = true;
            duplicationUrl = window.location.href;
            resetDuplicationPageUrl();
        }
        AllowEditingNotification();

        if ($('.notification_title').val() || $('.notification_message').val()) {
            $('#to-schedule').show();
            $('.notification-title').html(ns_title.val());
            $('.notification-message').html(ns_message.val());
            if (ns_image.val()) {
                let imageName = ns_image.val();
                let imageFolder = "images/";
                if (!imageName.includes(imageFolder)) {
                    imageName = imageFolder + imageName;
                }
                $('.notification-image').attr('src', imageName);
            }

            if (ns_icon.val()) {
                let iconName = ns_icon.val();
                let iconFolder = "icons/";
                if (!iconName.includes(iconFolder)) {
                    iconName = iconFolder + iconName;
                }
                $('.notification-icon').attr('src', iconName);
            }

            if (ns_delivery.val() !== "immediately") {
                $('.date-block').show();
            }

            if (ns_store.is(':checked')) {
                $('.icon2').show();
                $('.title3').show();
                $('#save').html('Save');
            }

            $('.grid-item-2').show();
        }
    };

    // GET SETTINGS TIMEZONE
    function getSettingsTimezone(){
        $.ajax({
            type: "POST",
            url: "/get-settings-timezone",
            dataType: "json",
            success: function (response) {
                localStorage.setItem('timezone', response);
            }
        });
    }

    ns_delivery.change(function () {
        if (ns_delivery.val() !== "immediately") {
            $('#send-time').html('Starting at ' + generateCurrentDate() + ' ' + localStorage.getItem('timezone'));
            if ($(".invalid-feedback").closest(".date-block").length === 0) {
                ns_date.val(generateCurrentDate());
                $('.date-block').slideDown('fast');
            } else {
                $('.date-block').slideDown('fast');
            }
        } else {
            $('#send-time').html('Right away');
            if ($(".invalid-feedback").closest(".date-block").length === 0) {
                $('.date-block').slideUp('fast');
                ns_date.val("");
            } else {
                $('.date-block').slideUp('fast');
            }
        }
    });

    $('.notification_name, .notification_country, .notification_title, .notification_message, .notification_url').on("change input paste keyup", function() {
        if (ns_name.val() && ns_country.val() && ns_title.val() && ns_message.val() && ns_url.val() && urlStatus === true) {
            $('#to-schedule').slideDown('slow')
        } else {
            $('#to-schedule').slideUp("slow");
        }
    });

    $('.notification_title, .notification_message, .notification__image, .notification_icon').on("change input paste keyup", function() {
        $('.grid-item-2').slideDown('slow');
    });

    ns_title.on("keyup",function () {
        let notification_title = $('.notification-title');
        notification_title.html(ns_title.val());
        let titleError = $('.title-error');
        if(ns_title.val().length > 50) {
            titleError.slideDown('fast');
            notification_title.html(notification_title.html().substr(0,50)+"...");
        } else {
            titleError.slideUp('fast');
        }
    });

    ns_message.on("keyup",function () {
        let notification_message = $('.notification-message');
        notification_message.html(ns_message.val());
        let messageError = $('.message-error');
        if(ns_message.val().length > 150) {
            messageError.slideDown('fast');
            notification_message.html(notification_message.html().substr(0,150)+"...");
        } else {
            messageError.slideUp('fast');
        }
    });

    function showNotificationDateError(errorMessage)
    {
        $('#date-error-message').html(errorMessage);
        $('#date-error').slideDown('slow');
        ns_date.css('border','3px solid red');
        $('#save').prop("disabled", "true");
        dateStatus = false;
    }

    function generatePlus30DaysDate() {
        let dateNow = new Date();
        dateNow.setDate(dateNow.getDate() + 30);
        return dateNow.toISOString().slice(0,10);
    }

    ns_date.on("keyup",function () {
        $('#send-time').html('Starting at ' + ns_date.val() + ' ' + localStorage.getItem('timezone'));
        let dateRegexResult = "true";
        let currentDate = generateCurrentDate();
        let dateError = $('#date-error');
        let dateValue = ns_date.val();
        let plusThirtyDayDate = generatePlus30DaysDate();

        dateRegexResult = dateValue.match(notificationSendingRegex);

        if (!dateRegexResult && dateValue < currentDate) {
            showNotificationDateError("Bad format, can't set a past date");
        } else if (dateRegexResult && dateValue < currentDate) {
            showNotificationDateError("You can't set a past date");
        } else if (!dateRegexResult && dateValue > currentDate) {
            showNotificationDateError("Bad format!");
        } else if (dateValue > plusThirtyDayDate) {
            showNotificationDateError("Can't set a schedule above 30 days");
        }else {
            ns_date.css('border','1px solid #ced4da');
            dateError.slideUp('fast');
            dateStatus = true;
        }
    });

    function generateCurrentDate() {
        let date = new Date();
        let currentDate = date.toISOString().slice(0,10);
        let currentTime = ('0' + date.getHours()).slice(-2) + ':' + ('0' + date.getMinutes()).slice(-2);
        return currentDate + " " + currentTime;
    }

    if ($(".invalid-feedback").closest(".date-block").length === 0 && !ns_date.val()) {
        ns_date.val(generateCurrentDate());
    }

    $('#reset-date').on("click",function () {
        ns_date.css('border','1px solid #ced4da');
        $('#date-error').slideUp('fast');
        ns_date.val(generateCurrentDate());
        $('#send-time').html('Starting at ' + ns_date.val() + ' ' + localStorage.getItem('timezone'));
    });

    function setProperties(type) {
        ns_name.prop("readonly", type);
        ns_country.prop("readonly", type);
        ns_title.prop("readonly", type);
        ns_message.prop("readonly", type);
        ns_image.prop("readonly", type);
        ns_icon.prop("readonly", type);
        ns_delivery.find(':not(:selected)').prop('disabled',type);
        ns_date.prop("readonly", type);
        ns_optimisation.prop("readonly", type);
        ns_url.prop("readonly", type);

        let storeCheck = $('#store-check');

        if (type === true) {
            storeCheck.slideUp('fast');
        } else {
            storeCheck.slideDown('fast');
        }

        let saveButtonType;
        saveButtonType = type === false;

        $('#save').prop("disabled", saveButtonType)
    }

    let loadingMessage;
    ns_store.on("click",function () {
        let icon = $('.icon2');
        let title = $('.title3');
        let submit = $('#save');
        if($(this).is(':checked')) {
            icon.slideDown('fast');
            title.slideDown('fast');
            submit.html('Save');
            loadingMessage = "Saving";
        } else {
            icon.slideUp('fast');
            title.slideUp('fast');
            submit.html('Send');
            loadingMessage = "Sending";
        }
    });

    function AllowEditingNotification() {
        allowEditing = true;
        setProperties(false);
        $('#reset-date').fadeIn("fast");

        if (ns_icon.val()) {
            ns_icon.css('width','92%');
            $("#clear-icon-input").fadeIn('fast');
        }

        if (ns_image.val()) {
            ns_image.css('width','92%');
            $("#clear-image-input").fadeIn('fast');
        }
    }

    $('#confirm').on("click",function(){
        if($(this).is(':checked')){
            allowEditing = false;
            if (ns_delivery.val() === "immediately") {
                ns_date.val("");
            }
            if (dateStatus) {
                setProperties(true);
                $('#reset-date').fadeOut("fast");
                $("#clear-icon-input, #clear-image-input").fadeOut('fast');
                ns_icon.css('width', '100%');
                ns_image.css('width', '100%');

                let formType = $('#form-type').html();
                if (formType === "copy" || formType === "campaign" || formType === "new") {
                    history.pushState({}, null, duplicationUrl);
                }
            } else {
                ns_date.css('background-color','#f5c6cb');
                setTimeout(function () {
                    ns_date.css('background-color','white');
                }, 300);

                $('#confirm').prop('checked', false);
                resetDuplicationPageUrl();
                AllowEditingNotification();
            }
        } else {
            resetDuplicationPageUrl();
            AllowEditingNotification();
        }
    });

    $('#save').on("click",function () {
        $('#loading-text').html(loadingMessage);
        $('#loading-spinner').css('margin-top','18px');
        $('#loading').fadeIn('fast');
    });

    $(".preview-notification").click(function () {
        let id = this.id;
        $('.preview-block' + id).slideToggle('fast');
        $('.notification-preview' + id).slideToggle('fast')
    });

    $('.switch-notification-preview').click(function () {
        let id = this.id;

        let windowsNotification = $('.win' + id);
        let androidNotification = $('.android' + id);
        let notificationType = $('#notification-type' + id);

        previewSwitcher(windowsNotification, androidNotification, notificationType);
    });

    function updatePopupInformation(id){
        $("#upload-type").val(id);
        $("#upload-pop-title").html("Uploading " + id + "s");
    }

    $('.uploadNotificationImage').on("click",function () {
        let typeId = this.id;
        let type = (typeId === "image") ? updatePopupInformation(typeId) : (typeId === "icon") ? updatePopupInformation(typeId) : "";
        $('#upload-image-popup').fadeIn('fast');
    });

    let uploadFileField = $("#upload-file-field");

    $(".close-upload-popup").on("click",function(){
        $('#upload-image-popup').fadeOut('fast');

        if (document.location.pathname === '/control-panel') {
            location.reload();
        } else {
            setTimeout(function () {
                uploadFileField.val('');
            }, 200);
        }
    });

    uploadFileField.on("change",function(){
        let buttonId = '#saveUpload';
        let uploadError = $('#upload-error');

        if (parseInt(uploadFileField.get(0).files.length)>25){
            showUploadMessage(uploadError, 'red', 'Maximum upload amount is 25 files');
            $(buttonId).prop('disabled',true)
        } else {
            uploadError.slideUp('fast');
            $('.upload-pop-inside').css('height','330px');
            $(buttonId).prop('disabled',false)
        }
    });

    $('#saveUpload').on('click',function () {
        $('#saveUpload').prop('disabled',true);
        $('#upload-file-field').prop("readonly", true);
        $("#file-upload-text").html('<i class="fa fa-spinner fa-pulse" style="font-size: 30px"></i>');
        $(".image-selection-image").remove();
        let uploadData = $("#upload_image_form")[0];
        let link = "/save-image";

        ajaxPostImages(uploadData, link)
    });

    function ajaxPostImages(uploadData, link) {
        $.ajax({
            type: "POST",
            url: link,
            data: new FormData(uploadData),
            contentType:false,
            processData:false,
            dataType: "json",
            success: function(response) {
                let link = "/get-images";
                ajaxGetImages(link);

                let uploadError = $('#upload-error');
                let message = (response.uploads === 1) ? '1 image has been uploaded' : response.uploads + ' images have been uploaded';

                showUploadMessage(uploadError, 'green', message);
                setTimeout(function() {
                    clearUploadForms();
                }, 2000);
            }
        });
    }

    let selectedImageType = "";
    let imageTypeSelector = $('.image-type-selector');

    imageTypeSelector.on("click",function () {
        if (allowEditing === true) {
            imageTypeSelector.prop("readonly", true);
            let fieldId = this.id;
            let id = fieldId.substr(fieldId.length - 5);

            if (id === "image") {
                $('#image-wrapper').show();
                selectedImageType = "image";
            } else {
                $('#icon-wrapper').show();
                selectedImageType = "icon";
            }
            $('#image-select-pop').fadeIn('fast');
        }
    });

    function closeImageSelectionPopup() {
        $('#image-select-pop').fadeOut('fast');
        $('#image-wrapper, #icon-wrapper').hide();
        imageTypeSelector.prop("readonly", false);
    }

    $('.close-image-selection-popup').click(function () {
        closeImageSelectionPopup();
    });

    function setImageValues(formFieldId, previewClass, imageTitle, previewTitle) {
        $(formFieldId).val(imageTitle);
        $(previewClass).attr('src', previewTitle);
    }

    $('.clear-input').on("click", function () {
        let id = this.id;

        if (id === "clear-image-input") {
            $("#clear-image-input").fadeOut('fast');
            ns_image.val("");
            $('.notification-image').attr('src', '/big_image.jpg');
            setTimeout(function () {
                ns_image.css('width','100%');
            }, 500);
        } else {
            $("#clear-icon-input").fadeOut('fast');
            ns_icon.val("");
            $('.notification-icon').attr('src', '/icon_image.jpg');
            setTimeout(function () {
                ns_icon.css('width','100%');
            }, 500);
        }
    });

    function setUtilityModalValues(clear, inputField, picturePreview, imageTitle, imageLocation) {
        let currentDomain = $('#current-main-domain').html();

        clear.fadeIn('fast');
        inputField.val(imageTitle);
        picturePreview.attr('src', currentDomain + imageLocation).show();
    }

    $(document).on('click','.image-style-class',function () {
        let imageTitle = this.id;
        let pageLocation = $('#page-location').html();

        if (pageLocation) {
            if (selectedImageType === "image") {
                setUtilityModalValues($("#utility-clear-image-input"), $("#utility-notification-image"), $('#utility-notification-image-preview'),imageTitle, '/images/'+imageTitle);
            } else {
                setUtilityModalValues($("#utility-clear-icon-input"), $("#utility-notification-icon"), $('#utility-notification-icon-preview'), imageTitle, '/icons/'+imageTitle);
            }
        } else {
            if (selectedImageType === "image") {
                ns_image.css('width','92%');
                $("#clear-image-input").fadeIn('fast');
                setImageValues('.notification_image', '.notification-image', imageTitle, 'images/' + imageTitle);
            } else {
                ns_icon.css('width', '92%');
                $("#clear-icon-input").fadeIn('fast');
                setImageValues('.notification_icon', '.notification-icon', imageTitle, 'icons/' + imageTitle);
            }
            $('.grid-item-2').slideDown('slow');
        }
        closeImageSelectionPopup();
    });

    function validURL(str) {
        let pattern = /^(https?:\/\/(?:www\.|(?!www))[^\s\.]+\.[^\s]{2,}|www\.[^\s]+\.[^\s]{2,})/;
        return pattern.test(str);
    }

    ns_url.on("change input paste keyup", function () {
        if (validURL(ns_url.val()) === false) {
            urlStatus = false;
            $('.url-error').slideDown('fast');
        } else {
            urlStatus = true;
            $('.url-error').slideUp('fast');
        }
    });

    let images_html = '';
    let icons_html = '';

    function ajaxGetImages(link) {
        $.ajax({
            url: link,
            dataType: 'json',
            success: function(response) {
                $.each(response, function(i, array) {
                    $.each(array.data, function(i, item) {
                        if (array.source === "images") {
                            images_html +=
                                '<div class="image-selection-image">' +
                                '<img src="/images/' + item.title + '" id = "' + item.title + '" alt="" class="image-style-class">' +
                                '</div>'
                        } else {
                            icons_html +=
                                '<div class="image-selection-image">' +
                                '<img src="/icons/' + item.title + '" id = "' + item.title + '" alt="" class="image-style-class">' +
                                '</div>'
                        }
                    });
                });
                $("#image-wrapper").append(images_html);
                $("#icon-wrapper").append(icons_html);
                images_html = '';
                icons_html = '';
            }
        });
    }

    function showUploadMessage(uploadError, color, message) {
        uploadError.css('color', color);
        uploadError.html(message);
        $('.upload-pop-inside').animate({height : '355px'}, 200);
        uploadError.slideDown('fast');
    }

    function clearUploadForms() {
        $('#file-upload-text').html('Choose files..');
        $('#saveUpload').prop('disabled',true);
        $('#upload-file-field').prop("readonly", false);
        $('#upload_image_form').val('');
        $('.upload-pop-inside').animate({height : '330px'}, 200);
        $('#upload-error').slideUp('fast');
    }

   $('#clear-upload').click(function () {
       clearUploadForms();
   });

    // RESEND MODAL

    let formDelivery = $('#form_delivery');
    let formDate = $('#form_date');

    // Fetch currently selected notification data and add it to a modal
    function ajaxGetNotificatioAndSchedule(link, notificationId) {
        $.ajax({
            type: "POST",
            url: link,
            data: {'id' : notificationId},
            dataType: 'json',
            success: function (response) {
                let name = response.notification.name;
                let delivery = response.schedule.delivery;
                let date = response.schedule.date;

                $('#resend-notification-title').html('Resend '+ name);
                $('.resend_form_id').val(notificationId);
                if (delivery === "immediately") {
                    formDelivery.val("immediately");
                    $('.date-block').hide();
                    formDate.val("");
                } else {
                    let currentDate = generateCurrentDate();
                    formDelivery.val("particular time");
                    $('.date-block').show();
                    formDate.val(date);
                    if (date < currentDate) {
                        showErrorAndDisableResend("You can't set a past date");
                    }
                }

                $('#resendOverlay').fadeIn('fast');
                $("#resendModal").slideDown('fast');
                $('#loading').fadeOut('fast');
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    }

    // Show the resend modal on button click
    $('.display-resend-modal').click(function () {
        $('#loading-spinner').css('margin-top','18px');
        $('#loading').fadeIn('fast');
        ajaxGetNotificatioAndSchedule("get-notification-and-schedule", this.id);
    });

    // Hide the modal on x click or on background click
    $('#hide-modal').on("click", function () {
        $("#resendModal").slideUp('fast');
        $('#resendOverlay').fadeOut('fast');
        $('.date-block').slideUp('fast');
        formDate.val("");
    });

    // Enables the resend button
    function enableResend() {
        formDate.css('border','1px solid #ced4da');
        $('#date-error').slideUp('fast');
        $('.resend-submit').prop("disabled", false);
    }

    // Displays an error and disabled resend button
    function showErrorAndDisableResend(errorMessage) {
        $('#resend-date-error-message').html(errorMessage)
        $('#date-error').slideDown('slow');
        formDate.css('border','3px solid red');
        $('.resend-submit').prop("disabled", true);
    }

    // Reset the date field on the resend form
    $('.resend-reset-date').click(function () {
        enableResend();
        formDate.val(generateCurrentDate());
    });

    // Run a check on date field change
    formDate.keyup(function () {
        let formDateValue = this.value;
        let regexResult = "true";
        let currentDate = generateCurrentDate();
        let plusThirtyDayDate = generatePlus30DaysDate();

        regexResult = formDateValue.match(notificationSendingRegex);

        setTimeout(function () {
            if (!regexResult && formDateValue < currentDate) {
                showErrorAndDisableResend("Bad format, can't set a past date");
            } else if (regexResult && formDateValue < currentDate) {
                showErrorAndDisableResend("You can't set a past date");
            } else if (!regexResult && formDateValue > currentDate) {
                showErrorAndDisableResend("Bad format!");
            } else if (formDateValue > plusThirtyDayDate) {
                showErrorAndDisableResend("Can't set a schedule above 30 days");
            } else {
                enableResend();
            }
        }, 100);
    });

    // On delivery field change run validation
    formDelivery.on("change",function () {
        let formDeliveryValue = this.value;
        if (formDeliveryValue !== "immediately") {
            if ($(".invalid-feedback").closest(".date-block").length === 0) {
                formDate.val(generateCurrentDate());
                $('.date-block').slideDown('fast');
            } else {
                $('.date-block').slideDown('fast');
            }
        } else {
            $('.date-block').slideUp('fast');
            formDate.val("");
            enableResend();
        }
    });

    $(".resend-submit").on("click",function () {
        $("#resendModal").slideUp('fast');
        $('#resendOverlay').fadeOut('fast');
        $('#loading').fadeIn('fast');
        let resendData = $("#resend-form")[0];

        $.ajax({
            type: "POST",
            url: "resend-notification",
            data: new FormData(resendData),
            contentType:false,
            processData:false,
            dataType: "json",
            success: function (response) {
                localStorage.setItem('resend',response.id);
                location.reload();
                window.scrollTo(0, 0);
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    if (localStorage.getItem('resend')) {
        let notificationRow = $('#notification-row'+localStorage.getItem('resend'));
        setTimeout(function(){
            $('html, body').animate({
                scrollTop: notificationRow.offset().top
            }, 1000);

            notificationRow.css("background-color", "#559fed");
            localStorage.removeItem('resend');

            setTimeout(function(){
                notificationRow.css("background-color", "");
            },2000);
        }, 1000);
    } else {
        window.scrollTo(0, 0);
    }

    // END OF RESEND MODAL

    // CANCEL NOTIFICATION

    let notificationToCancelId;

    function controlCancelLoadingElements(loadingText, loadingBar, title, confirmIcon, popupBlock) {
        loadingText.fadeOut('fast');
        loadingBar.fadeOut('fast');

        setTimeout(function() {
            title.fadeIn('fast');
            confirmIcon.fadeIn('fast');
            setTimeout(function() {
                popupBlock.fadeOut();
            },1600);
        },500);
    }

    function cancelNotificationAjaxAction() {
        let title = $('.cancel-confirm-title'),
            popupBlock = $('#cancel-notification-block'),
            loadingText = $('.cancel-loading-text'),
            loadingBar = $('.cancel-loading-bar'),
            confirmIcon = $('#cancel-confirm-check'),
            failedIcon = $('#cancel-failed-check'),
            choiceButtons = $('.cancel-confirm-buttons');

        title.slideUp('fast');
        choiceButtons.slideUp('fast');
        loadingText.slideDown('fast');
        loadingBar.fadeIn('fast');

        $.ajax({
            type: "POST",
            url: "/cancel-notification",
            data: {'id' : notificationToCancelId},
            dataType: 'json',
            success: function (response) {
                if (response.result) {
                    $('#scheduled-text'+notificationToCancelId).html('NOT SENT YET');
                    $('.cancel-notification-button'+notificationToCancelId).hide();
                    $('.dynamic-resend-button'+notificationToCancelId).show();
                    title.html('Notification successfully cancelled!');
                    controlCancelLoadingElements(loadingText, loadingBar, title, confirmIcon, popupBlock);
                } else {
                    title.html('Cancelling failed (Might be too late)!');
                    controlCancelLoadingElements(loadingText, loadingBar, title, failedIcon, popupBlock);
                }
                setTimeout(function() {
                    title.html('Are you sure you want to cancel this notification?');
                    confirmIcon.hide();
                    failedIcon.hide();
                    choiceButtons.show();
                }, 2400);
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    }

    $('.cancel-notification').on("click", function () {
        notificationToCancelId = this.id;
        $('#cancel-notification-block').fadeIn('fast');
    });

    $('.cancel-notification-confirm').on("click", function() {
        let buttonId = this.id;

        if (buttonId === "confirm") {
            cancelNotificationAjaxAction();
        } else if (buttonId === "cancel") {
            $('#cancel-notification-block').fadeOut('fast');
        }
    });

    // END OF CANCEL NOTIFICATION

    // VIEW CAMPAIGN NOTIFICATIONS REMEMBER LAST CLICKED COUNTRY

    $('.back-to-campaigns').on("click", function() {
        let countryId = this.id;
        localStorage.setItem('lastCountryClicked', countryId);
    });

    // END OF VIEW CAMPAIGN

    // CAMPAIGN DELETE CONFIRM

    $('.delete-campaign').on("click", function () {
        let campaignId = this.id;

        let confirmBlock = $('#confirm-delete-block'+campaignId);
        confirmBlock.slideDown();
        $('.campaign-actions'+campaignId).hide();

        $('html, body').animate({
            scrollTop: confirmBlock.offset().top
        }, 1000);
    });

    $('.confirm-delete').on("click", function() {
        let campaignId = this.id;
        let campaignCountryId = $('#campaign-country-id'+campaignId).html();
        let confirmDelete = $('#confirm-delete-block'+campaignId);
        let loadingDelete = $('#loading-delete'+campaignId);
        let loadingCog = $('#loading-cog'+campaignId);
        let loadingCheck = $('#loading-check'+campaignId);

        confirmDelete.slideUp();
        loadingDelete.slideDown();

        $.ajax({
            type: "POST",
            url: "/campaign/delete",
            data: {'campaign' : campaignId, 'country' : campaignCountryId},
            dataType: 'json',
            success: function (response) {
                if (response) {
                    loadingCog.hide();
                    loadingCheck.show();

                    setTimeout(function() {
                        $('#campaign-number'+campaignId).fadeOut('slow');
                        if (response.remaining === "0") {
                            $('.campaign-country'+campaignCountryId).slideUp('fast');
                        }
                    }, 1000);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $('.cancel-delete').on("click", function () {
        let campaignId = this.id;
        $('#confirm-delete-block'+campaignId).slideUp('fast');
        $('.campaign-actions'+campaignId).slideDown('fast');
    });

    // END OF CAMPAIGN DELETE CONFIRM

    // CAMPAIGN ACTION (PAUSE/RESUME)

    function campaignActionWorker(campaignAction, currentActionItem, campaignStatusBlock) {
        if (campaignAction === "pause") {
            currentActionItem.removeClass('fa-pause campaign-pause').addClass('fa-play campaign-resume').attr('title','Resume');
            campaignStatusBlock.html('Paused').css("color","red");
        } else {
            currentActionItem.removeClass('fa-play campaign-resume').addClass('fa-pause campaign-pause').attr('title',"Pause");
            campaignStatusBlock.html('Active').css("color","green");
        }
    }

    $(".campaign-activity-action").on("click", function () {
        let currentActionItem = $(this);
        let campaignId = this.id;
        let campaignAction = $(this).hasClass('campaign-resume') ? 'resume' : 'pause';
        let campaignStatusBlock = $('#campaign-status-'+campaignId);

        let loadingActionBlock = $('#campaign-action-loading-'+campaignId);
        currentActionItem.hide();
        loadingActionBlock.slideDown('fast');

        $.ajax({
            type: "POST",
            url: "/campaign/control",
            data: {'campaign' : campaignId, 'action' : campaignAction},
            dataType: 'json',
            success: function (response) {
                if (response.result) {
                    loadingActionBlock.hide();
                    campaignActionWorker(campaignAction, currentActionItem, campaignStatusBlock);
                    currentActionItem.slideDown('fast');
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    // END CAMPAIGN ACTION (PAUSE/RESUME)

    // CAMPAIGN CHECKBOXES

    let mainChoiceButton = $('.main-multiple-choice-button');
    let secondaryChoiceButton = $('.sidebar-multiple-choice-button');

    // Find if main button is visible on screen, if not show other button
    if (document.location.pathname === '/campaigns') {
        $(window).on("scroll",function() {
            let hT = mainChoiceButton.offset().top,
                hH = mainChoiceButton.outerHeight(),
                wH = $(window).height(),
                wS = $(this).scrollTop();
            if (wS > (hT + hH - wH) && (hT > wS) && (wS + wH > hT + hH)) {
                secondaryChoiceButton.hide();
            } else {
                secondaryChoiceButton.show();
            }
        });
    }

    if (document.location.pathname === '/campaigns') {
        let openedCountryBlockArray = JSON.parse(localStorage.getItem("openedCountries")),
            lastClickedCountry = localStorage.getItem("lastCountryClicked"),
            campaignCountryBlock = $('.campaign-country'+lastClickedCountry);

        if (lastClickedCountry) {
            setTimeout(function(){
                $('html, body').animate({
                    scrollTop: campaignCountryBlock.offset().top
                }, 1000);

                localStorage.removeItem('lastCountryClicked');
            }, 1000);
        }

        $.each(openedCountryBlockArray, function (key, value) {
            let countryBlock = $(".campaign-country" + value);
            let countryIdBlock = $('.country-id-'+value);
            countryBlock.collapse('show');
            countryIdBlock.attr('id', 'unfolded');
            countryIdBlock.css("background-color","#28a745").css("color","white");
        });
    }

    let campaignCheckboxes = $('.camp-checkbox');
    let campaignCheckArray = [];

    campaignCheckboxes.on("change",function(){
        let countCheckedCheckboxes = campaignCheckboxes.filter(':checked').length;
        let currentElementId = this.id;

        if ($(this).is(":checked")) {
            campaignCheckArray.push(this.id);
        } else {
            for (let i=campaignCheckArray.length-1; i>=0; i--) {
                if (campaignCheckArray[i] === currentElementId) {
                    campaignCheckArray.splice(i, 1);
                }
            }
        }

        $('#selected-campaign-number').html(countCheckedCheckboxes);
        if (jQuery.isEmptyObject(campaignCheckArray)) {
            $('.action-selection-block').slideUp('fast')
        } else {
            $('.action-selection-block').slideDown('fast')
        }
    });

    let remainingCampaignData;
    function findRemainingCampaignsByCountryAjax(countryId) {
        $.ajax({
            async: false,
            type: "POST",
            url: "/campaign/remaining-campaigns-by-country",
            data: {'country' : countryId},
            dataType: "json",
            success: function (response) {
                remainingCampaignData = response;
            }
        });
        return remainingCampaignData;
    }

    let checkboxVisibilityCheck = $('.checkbox-visibility-check');

    $('.show-checkboxes').on("click", function() {
        let checkboxBlocks = $(".campaign-checkbox, .campaign-checkbox-select");
        if (checkboxVisibilityCheck.attr('id') === "visible") {
            $('.show-checkboxes').removeClass('btn-secondary').addClass('btn-warning');
            checkboxVisibilityCheck.removeAttr('id');
            checkboxBlocks.hide();
            campaignCheckArray = []; //Reset the array since we un-select everything

            $('.camp-checkbox').prop("checked", false);
            $('#selected-campaign-number').html("");
            $('.action-selection-block').slideUp('fast');
        } else {
            $('.show-checkboxes').removeClass('btn-warning').addClass('btn-secondary');
            checkboxVisibilityCheck.attr("id","visible");
            checkboxBlocks.show();
        }
    });

    function multipleCampaignActionAjax(campaignArray, action, campaignLoadingBar) {
        let loadingText = $('.campaign-loading-text');
        let confirmCheck = $('#campaign-confirm-check');

        $.ajax({
            type: "POST",
            url: "/campaign/multiple-action",
            data: {'campaigns' : campaignArray, 'action' : action},
            dataType: 'json',
            success: function (response) {
                if (response.result === true) {
                    loadingText.html("Done!");
                    campaignLoadingBar.slideUp('fast');
                    confirmCheck.slideDown('fast');

                    if (action === "resume" || action === "pause") {
                        $(campaignArray).each(function(i, campaignId) {
                            campaignActionWorker(action, $(".campaign-action"+campaignId), $('#campaign-status-'+campaignId));
                        });
                    } else if (action === "delete") {
                        $(campaignArray).each(function(i, campaignId){
                            let campaignCountryId = $('#campaign-country-id'+campaignId).html();
                            let result = findRemainingCampaignsByCountryAjax(campaignCountryId); // Check how many campaigns of this country remain

                            $('#campaign-number'+campaignId).fadeOut('slow');
                            if (result.remaining === "0") {
                                $('.campaign-country'+result.country).slideUp('fast'); // If no more campaigns remove the choice
                            }
                        });

                        $('#selected-campaign-number').html("");
                        $('.action-selection-block').slideUp('fast');
                        campaignCheckArray = [];
                    }
                } else {
                    loadingText.html("Some problem occurred, please try again");
                }

                setTimeout(function() {
                    campaignLoadingBar.slideUp('fast');
                    confirmCheck.slideUp('fast');
                    $('#confirm-campaign-block').fadeOut('fast');

                    setTimeout(function() {
                        loadingText.html("Working..").hide();
                        $('.campaign-action-confirm-button, .campaign-confirm-title').show();
                    },250);
                }, 2000);
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    }

    $('.campaign-multiple-action').on('click', function() {
        let selectedAction = this.id;
        let selectedCampaignCount = campaignCheckArray.length;
        let demonstrativeWord;
        let campaignWord;

        if (selectedCampaignCount > 1) {
            demonstrativeWord = "these";
            campaignWord = "campaigns";
        } else {
            demonstrativeWord = "this";
            campaignWord = "campaign";
        }

        $('#campaign-confirm-action').html(selectedAction);
        $('.campaign-confirm-title').html("Are you sure you want to "+selectedAction.bold()+" "+demonstrativeWord+" "+selectedCampaignCount+" "+campaignWord+"?");
        $('#confirm-campaign-block').fadeIn('fast');
    });

    $('.campaign-action-confirm-button').on("click", function() {
        let userClick = this.id;
        let selectedAction = $('#campaign-confirm-action').html();

        if (userClick === "campaign-confirm") {
            let campaignLoadingBar = $(".campaign-loading-bar");
            campaignLoadingBar.slideDown('fast');
            $('.campaign-action-confirm-button').fadeOut('fast');
            $('.campaign-confirm-title').slideUp('fast');
            $('.campaign-loading-text').slideDown('fast');

            multipleCampaignActionAjax(campaignCheckArray, selectedAction, campaignLoadingBar);
        } else {
            $('#confirm-campaign-block').fadeOut('fast');
        }
    });

    // END OF CAMPAIGN CHECKBOXES

    // CAMPAIGN ASSIGN MODAL

    let notificationCheckbox = $(".form-check-input");

    notificationCheckbox.on('click', function () {
        if (notificationCheckbox.is(":checked")) {
            $('#assign-notifications-submit').prop('disabled', false);
        } else {
            $('#assign-notifications-submit').prop('disabled', true);
        }
    });

    // END OF CAMPAIGN ASSIGN MODAL

    // EDIT CAMPAIGN NAME

    function editCampaignName(currentInput, campaignId) {
        let originalNameBlock = $('#original-name-'+campaignId),
            nameDisplayBlock = $('#campaign-name-'+campaignId),
            loadingCog = $('#campaign-name-edit-cog-'+campaignId);

        currentInput.hide();
        $('#save-changes-'+campaignId).hide();

        if (currentInput.val().length > 2 && originalNameBlock.html() !== currentInput.val()) {
            loadingCog.slideDown('fast');

            $.ajax({
                type: "POST",
                url: "/campaign/edit-campaign-name",
                data: {
                    "name": currentInput.val(),
                    "campaignId": campaignId
                },
                dataType: "json",
                success: function(response) {
                    if (response) {
                        originalNameBlock.html(response.name);
                        loadingCog.fadeOut('fast');
                        setTimeout(function() {
                            nameDisplayBlock.html(response.name).fadeIn('slow');
                        }, 200);
                    }
                },
                error: function (jqXHR, exception) {
                    location.reload();
                }
            });
        } else {
            nameDisplayBlock.fadeIn('slow');
        }
    }

    $('.editable-campaign-name').on('click',function(){
        let currentBlock = $(this),
            campaignId = this.id.replace('campaign-name-',''),
            campaignName = $(this).html(),
            editInput = $('#edit-'+campaignId),
            saveButton = $('#save-changes-'+campaignId);

        currentBlock.hide();
        saveButton.show();
        editInput.show().focus().val(campaignName);
    });

    $(".campaign-name-input").on("focusout",function(){
        let currentInput = $(this),
            campaignId = this.id.replace('edit-','');

        editCampaignName(currentInput, campaignId);
    });

    $(".save-campaign-name-edit").on("click",function(){
        let currentInput = $(this),
            campaignId = this.id.replace('save-changes-','');

        editCampaignName(currentInput, campaignId);
    });

    // END OF EDIT CAMPAIGN NAME

    // CAMPAIGN NOTIFICATION CONTROL

    function controlCountryLocalStorage(openedCountries) {
        localStorage.removeItem("openedCountries");
        localStorage.setItem("openedCountries", JSON.stringify(openedCountries));
    }

    let currentClickedCountry;

    $(".campaign-country-block").on("click", function() {
        let classes = $(this).attr("class").split(/\s+/);
        $.each(classes, function(key, value) {
            if (value.indexOf("campaign-country") >= 0) {
                if (value.indexOf("campaign-country-")) {
                    currentClickedCountry = value.replace('campaign-country','');
                }
            }
        });

        let openedCountryBlockArray = JSON.parse(localStorage.getItem("openedCountries"));

        if (this.id) {
            if (openedCountryBlockArray) {
                for (let i=openedCountryBlockArray.length-1; i>=0; i--) {
                    if (openedCountryBlockArray[i] === currentClickedCountry) {
                        openedCountryBlockArray.splice(i, 1);
                    }
                }
            }
            controlCountryLocalStorage(openedCountryBlockArray);
            $(this).removeAttr('id');
            $(this).css("background-color","#f7f7f7").css("color","black");
        } else {
            if (!openedCountryBlockArray) {
                let newlyOpenedBlockArray = [];
                newlyOpenedBlockArray.push(currentClickedCountry);
                controlCountryLocalStorage(newlyOpenedBlockArray);
            } else {
                openedCountryBlockArray.push(currentClickedCountry);
                controlCountryLocalStorage(openedCountryBlockArray);
            }

            $(this).attr('id', 'unfolded');
            $(this).css("background-color","#28a745").css("color","white");
        }
    });

    $('.edit-campaign-notification, .preview-campaign-notification, .main-edit-notification').on("click", function () {
        $('#utilityModalTitle').html("Loading..");
        $('#loading-utility-block').slideDown('fast').css("display","block");

       let buttonId = this.id, currentAction = $(this).val(),
           notificationId = buttonId.replace(currentAction,''),
           notificationIconPreview = $('#utility-notification-icon-preview'),
           notificationImagePreview = $('#utility-notification-image-preview'),
           notificationIconInput = $('#utility-notification-icon'),
           notificationImageInput = $('#utility-notification-image'),
           pageLocation = $('#page-location');

        if ($(this).hasClass("main-edit-notification")) {
            pageLocation.html("notifications");
        } else {
            pageLocation.html("campaigns");
        }

        $.ajax({
            type: "POST",
            url: "/get-notification",
            data: {'id': notificationId},
            dataType: 'json',
            success: function (response) {
                $('#current-main-domain').html(response.domain);
                $('.edit_form_id').val(response.notification[0].id);
                $('#utility-notification-name').val(response.notification[0].name);
                $('#utility-notification-title').val(response.notification[0].title);
                $('#utility-notification-message').val(response.notification[0].message);
                notificationIconInput.val(response.notification[0].icon);
                notificationImageInput.val(response.notification[0].image);
                $('#utility-notification-url').val(response.notification[0].url);

                $('.notification-preview-title').html(response.notification[0].title);
                $('.notification-preview-message').html(response.notification[0].message);

                if (response.notification[0].icon) {
                    notificationIconInput.prop("readonly",false);
                    $('#utility-clear-icon-input').show();
                    notificationIconPreview.attr('src',response.domain+"/icons/"+response.notification[0].icon).show();
                    $('.notification-icon').attr('src',response.domain+"/icons/"+response.notification[0].icon);
                } else {
                    $('.notification-icon').attr('src','/icon_image.jpg');
                    notificationIconPreview.hide();
                }

                if (response.notification[0].image) {
                    notificationImageInput.prop("readonly",false);
                    $('#utility-clear-image-input').show();
                    notificationImagePreview.attr('src',response.domain+"/images/"+response.notification[0].image).show();
                    $('.notification-image').attr('src',response.domain+"/images/"+response.notification[0].image);
                } else {
                    $('.notification-image').attr('src','/big_image.jpg');
                    notificationImagePreview.hide();
                }

                $('#loading-utility-block').slideUp('fast');

                let editBlock = $('.edit-block');
                let previewBlock =  $('.preview-block');

                if (currentAction === "edit") {
                    $('#utilityModalTitle').html("Editing - " +response.notification[0].name);
                    previewBlock.slideUp('fast');
                    editBlock.slideDown('slow');
                } else {
                    $('#utilityModalTitle').html("Preview of - " +response.notification[0].name);
                    editBlock.slideUp('fast');
                    previewBlock.slideDown('slow');
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $('.utility-clear-input').on("click", function () {
        let id = this.id;

        if (id === "utility-clear-image-input") {
            $('#utility-notification-image').val("");
            $("#utility-clear-image-input").fadeOut('fast');
            $('#utility-notification-image-preview').fadeOut('fast');
        } else {
            $('#utility-notification-icon').val("");
            $("#utility-clear-icon-input").fadeOut('fast');
            $('#utility-notification-icon-preview').fadeOut('fast');
        }
    });

    function closeUtilityModal() {
        $('.edit-block, .preview-block').slideUp('slow');

        setTimeout(function() {
            $('#utilityModal').modal('hide');
        }, 1000);
    }

    $("#utility-edit-submit").on("click",function () {
        $(this).prop('disabled', true);
        $('#utility-notification-name, #utility-notification-title, #utility-notification-message, #utility-notification-icon, #utility-notification-image, #utility-notification-url').prop('readonly',true);
        $('#utility-clear-icon-input, #utility-clear-image-input').fadeOut('fast');

        let pageLocation = $('#page-location').html();

        $('#utilityModalTitle').html("Saving..");
        $('#loading-utility-block').slideDown('fast');
        let editData = $("#edit-form")[0];

        $.ajax({
            type: "POST",
            url: "/save-notification-edit",
            data: new FormData(editData),
            contentType:false,
            processData:false,
            dataType: "json",
            success: function (response) {
                if (pageLocation === "notifications") {
                    $('#main-notification-name-'+response.id).html(response.name);
                    $('#main-notification-title-'+response.id).html(response.title);
                    $('#main-notification-message-'+response.id).html(response.message);
                    $('#main-notification-url-'+response.id).html(response.url);

                    if (response.icon) {
                        let iconColumn = $('#main-icon-column-'+response.id);
                        if (iconColumn.is(':visible')) {
                            iconColumn.attr('src',response.domain+"/icons/"+response.icon);
                        } else {
                            let mainNotificationIcon = $('#main-notification-icon-'+response.id);
                            mainNotificationIcon.html("");
                            mainNotificationIcon.append("<img src='"+response.domain+"/icons/"+response.icon+"' style='width:30px;height:30px;'/></a>");
                        }
                    } else {
                        $('#main-notification-icon-'+response.id).html("NO ICON");
                    }

                    if (response.image) {
                        let imageColumn = $('#main-image-column-'+response.id);
                        if (imageColumn.is(':visible')) {
                            imageColumn.attr('src',response.domain+"/images/"+response.image);
                        } else {
                            let mainNotificationImage =  $('#main-notification-image-'+response.id);
                            mainNotificationImage.html("");
                            mainNotificationImage.append("<img src='"+response.domain+"/images/"+response.image+"' style='width:60px;height:30px;'/></a>");
                        }
                    } else {
                        $('#main-notification-icon-'+response.id).html("NO IMAGE");
                    }
                } else {
                    $('#campaign-view-notification-title' + response.id).html(response.name);
                }

                $('#utilityModalTitle').html("Notification edited!");
                $('#loading-utility-block').slideUp('fast');
                $('#utility-notification-name, #utility-notification-title, #utility-notification-message, #utility-notification-icon, #utility-notification-image, #utility-notification-url').prop('readonly',false);
                $('#utility-edit-submit').prop('disabled', false);
                closeUtilityModal();
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $('.utility-modal-close').on("click", function() {
        closeUtilityModal();
    });

        // UNPAUSE NOTIFICATION FROM VIEW

    $('.view-resume-campaign').on("click", function() {
        let campaignId = this.id;

        $.ajax({
            type: "POST",
            url: "/campaign/control",
            data: {'campaign' : campaignId, 'action' : 'resume'},
            dataType: 'json',
            success: function (response) {
                location.reload();
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

        // DELETE NOTIFICATION FROM VIEW

    function controlViewNotificationVisibility(notificationId, type) {
        let confirmBlock = $('#confirm-delete-block-'+notificationId),
            elementsToHide = $('#times-sent-'+notificationId+', #total-receivers-'+notificationId+', #options-for-notification-'+notificationId);

        if (type === "show") {
            elementsToHide.slideUp('fast');
            confirmBlock.slideDown('fast');
        } else {
            confirmBlock.slideUp('fast');
            elementsToHide.slideDown('fast');
        }
    }

    $('.delete-campaign-notification').on("click", function () {
        controlViewNotificationVisibility(this.id, "show");
    });

    $('.confirm-view-notification-delete').on("click", function () {
        let notificationId = this.id,
            loadingBlock = $('#view-loading-delete'+notificationId),
            loadingCog = $('#view-loading-cog'+notificationId),
            loadingCheck = $('#view-loading-check'+notificationId);

        $('#view-notification-delete-title'+notificationId+', #view-campaign-delete-buttons'+notificationId).slideUp('fast');
        loadingBlock.slideDown('fast');

        $.ajax({
            type: "POST",
            url: "/campaign/delete-notification",
            data: {'notification' : notificationId},
            dataType: "json",
            success: function (response) {
                if (response) {
                    loadingCog.hide();
                    loadingCheck.show();

                    setTimeout(function() {
                        $('#view-container-'+notificationId).slideUp('fast');
                    }, 1000);
                }
            }
        });
    });

    $('.cancel-view-notification-delete').on("click", function () {
        controlViewNotificationVisibility(this.id, "hide");
    });

        // PAUSE/UNPAUSE NOTIFICATION FROM VIEW

    $('.pause-campaign-notification').on("click", function () {
        let notificationId = this.id;
        let currentButton = $(this);
        let action = $(this).val();
        let notificationMainPauseButton = $('.notification-pause-button'+notificationId);
        notificationMainPauseButton.prop('disabled', true);

        let notificationScheduleId = $('#view-notification-schedule-id-'+notificationId).html();
        let staticPausedTimeBlock = $('.notification-time-paused'+notificationId);
        let dynamicPausedTimeBlock = $('.dynamic-notification-paused'+notificationId);
        let weekdayPauseBlock = $('.paused-block'+notificationId);
        let dynamicWeekdayPauseBlock = $('.dynamic-paused-block'+notificationId);
        let timeLoadingBlock = $('.time-loading-block'+notificationScheduleId);
        let dynamicTimeEditButton = $('.dynamic-time-edit-button'+notificationScheduleId);
        let dynamicTimeSetButton = $('.dynamic-time-set-button'+notificationScheduleId);
        let dynamicCampaignTimeBlock = $('.dynamic-campaign-time-block'+notificationScheduleId);
        let dynamicWeekdayErrorBlock = $('#dynamic-campaign-time-error'+notificationScheduleId);

        let allHideBlocks = $('.campaign-time-block'+notificationScheduleId+', #time-setting-form'+notificationScheduleId+', .next-send-block'+notificationScheduleId+', #dynamic-next-send-block'+notificationScheduleId+', #campaign-time-error'+notificationScheduleId+', .hidden-weekdays'+notificationScheduleId+', .dynamic-campaign-time-block'+notificationScheduleId);

        allHideBlocks.slideUp('fast');
        dynamicPausedTimeBlock.slideUp('fast');
        staticPausedTimeBlock.slideUp('fast');
        dynamicWeekdayPauseBlock.slideUp('fast');
        weekdayPauseBlock.slideUp('fast');
        timeLoadingBlock.slideDown('fast');

        $.ajax({
            type: "POST",
            url: "/campaign/control-single",
            data: {'notification' : notificationId, 'action' : action},
            dataType: "json",
            success: function (response) {
                if (response) {
                    timeLoadingBlock.slideUp('fast');
                    if (action === "pause") {
                        currentButton.html("Unpause").val("unpause");
                        allHideBlocks.slideUp('fast');
                        dynamicWeekdayErrorBlock.slideUp('fast');
                        dynamicPausedTimeBlock.slideDown('fast');
                        dynamicWeekdayPauseBlock.slideDown('fast');
                        weekdayPauseBlock.slideDown('fast');
                    } else {
                        notificationMainPauseButton.html("Pause").val("pause");
                        if (response.time) {
                            $('.dynamic-campaign-scheduled-time'+notificationScheduleId).html('Scheduled time: '+response.time);
                            dynamicTimeSetButton.hide();
                            dynamicTimeEditButton.show();
                            $('.hidden-weekdays'+notificationScheduleId).slideDown('fast');
                        } else {
                            $('.dynamic-campaign-scheduled-time'+notificationScheduleId).html('No time set yet');
                            dynamicTimeEditButton.hide();
                            dynamicTimeSetButton.show();
                            dynamicWeekdayErrorBlock.slideDown('fast');
                        }
                        dynamicPausedTimeBlock.slideUp('fast');
                        staticPausedTimeBlock.slideUp('fast');
                        dynamicWeekdayPauseBlock.slideUp('fast');
                        weekdayPauseBlock.slideUp('fast');
                        dynamicCampaignTimeBlock.slideDown('fast');
                        $('.next-send-block'+notificationScheduleId).slideDown('fast');
                    }

                    notificationMainPauseButton.prop('disabled', false);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    // END OF CAMPAIGN NOTIFICATION CONTROL

    // SET CAMPAIGN TIME

    $('.activate-time-form').on("click",function () {
        let scheduleId = this.id;
        let isDynamicChecker = $('#is-dynamic'+scheduleId);

        if ($(this).val() === "dynamic") {
            $('.dynamic-campaign-time-block' + scheduleId).slideUp('fast');
            isDynamicChecker.html("true");
        } else {
            $('.campaign-time-block' + scheduleId).slideUp('fast');
            isDynamicChecker.html("false");
        }
        $('#time-setting-form' + scheduleId).slideDown('fast');
    });
    
    $('.cancel-campaign-time').on("click",function () {
        let scheduleId = this.id;
        let isDynamicChecker = $('#is-dynamic'+scheduleId).html();

        if (isDynamicChecker === "true") {
            $('.dynamic-campaign-time-block' + scheduleId).slideDown('fast');
        } else {
            $('.campaign-time-block' + scheduleId).slideDown('fast');
        }
        $('#time-setting-form' + scheduleId).slideUp('fast');
    });

    function updateNextSendDate(response, scheduleId, firstResponse) {
        let nextSend = $('#next-send' + scheduleId);
        let nextSendBlock = $('#next-send-block' + scheduleId);
        let dynamicNextSend = $('#dynamic-next-send' + scheduleId);
        let dynamicNextSendBlock = $('#dynamic-next-send-block' + scheduleId);
        let time = (firstResponse) ? firstResponse.time : response.time;

        if (response.date !== false) {
            if (nextSendBlock.is(':visible')) {
                nextSend.html(response.date+ " " +time);
            } else {
                dynamicNextSend.html(response.date+ " " +time);
                dynamicNextSendBlock.slideDown('fast');
            }
        } else {
            $('#next-send-block' + scheduleId +", #dynamic-next-send-block" + scheduleId).slideUp('fast');
        }
    }

    $('.save-campaign-time').on("click",function () {
        let scheduleId = this.id;
        let isDynamicCheck = $('#is-dynamic'+scheduleId).html();
        let timeFormData = $("#campaign-time-form" + scheduleId)[0];

        $.ajax({
            type: "POST",
            url: "/campaign/save-time",
            data: new FormData(timeFormData),
            contentType:false,
            processData:false,
            dataType: "json",
            success: function(response) {
                if (response.result === true) {
                    updateNextSendDate(response, scheduleId, false);

                    $('.time-form-button' + scheduleId).html('Edit');
                    $('.campaign-scheduled-time' + scheduleId + ', .dynamic-campaign-scheduled-time' + scheduleId).html('Scheduled time: ' + response.time);
                    $('#campaign-time-form' + scheduleId).find('input[name="time"]').val(response.time);

                    $('#campaign-time-error' + scheduleId).slideUp('fast');
                    $('#dynamic-campaign-time-error'+scheduleId).slideUp('fast');
                    $('.hidden-weekdays' + scheduleId).slideDown('fast');

                    $('#time-setting-form' + scheduleId).slideUp('fast');
                    if (isDynamicCheck === "true") {
                        $('.dynamic-campaign-time-block' + scheduleId).slideDown('fast');
                    } else {
                        $('.campaign-time-block' + scheduleId).slideDown('fast');
                    }
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    // END OF SET CAMPAIGN TIME

    // SELECT WEEKDAY

    $(document).on('click','.select-day',function () {
        let weekdayParentId = $(this).parent().parent().attr('id');
        let notificationScheduleId = weekdayParentId.replace('weekdays','');
        let notificationId = $('#notification-id-reference-block-'+notificationScheduleId).html();

        $('.notification-pause-button'+notificationId).prop("disabled", true);

        let dayBlockId = this.id;
        if (dayBlockId !== "none") {
            changeWeekdayAttributes($(this), 'remove', 'none', notificationScheduleId, 'red');
        } else {
            let currentDay = $(this).parent().attr('id');
            changeWeekdayAttributes($(this), 'add', currentDay, notificationScheduleId, 'limegreen');
        }

        if (!$(this).hasClass("changed")) {
            $(this).addClass( "changed");
        } else {
            $(this).removeClass( "changed");
        }

        $('#submit-weekdays-block' + notificationScheduleId).slideDown('fast');
    });

    function findClosestDate(notificationScheduleId, firstResponse) {
        $.ajax({
            type: "POST",
            url: "/campaign/find-closest-date",
            data: {
                "schedule": notificationScheduleId
            },
            dataType: "json",
            success: function(response) {
                updateNextSendDate(response, notificationScheduleId, firstResponse);
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    }

    let weekDayArray = [];

    $('.save-weekdays').on('click', function () {
        let notificationScheduleId = this.id;
        let notificationId = $('#notification-id-reference-block-'+notificationScheduleId).html();

        $(".save-weekdays").prop("disabled", true);
        $('.weekday-block' + notificationScheduleId).slideDown('fast');
        $('.hidden-weekdays' + notificationScheduleId).slideUp('fast');
        $('#submit-weekdays-block' + notificationScheduleId).slideUp('fast');

        $('.selected-weekday' + notificationScheduleId).each(function () {
            weekDayArray.push($(this).attr('id'));
        });

        $.ajax({
            type: "POST",
            url: "/campaign/save-weekdays",
            data: {
                "weekdays": weekDayArray,
                "schedule": notificationScheduleId
            },
            dataType: "json",
            success: function(response) {
                findClosestDate(notificationScheduleId, response);
                $('.weekday-block' + notificationScheduleId).slideUp('fast');
                $('.hidden-weekdays' + notificationScheduleId).slideDown('fast');
                $('.notification-pause-button'+notificationId).prop("disabled", false);
                $(".save-weekdays").prop("disabled", false);
                weekDayArray = [];
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $('.cancel-weekdays').on('click', function () {
        let notificationScheduleId = this.id;
        let notificationId = $('#notification-id-reference-block-'+notificationScheduleId).html();
        $('.notification-pause-button'+notificationId).prop("disabled", false);

        $('.weekday-selector' + notificationScheduleId).each(function() {
            let currentDay = $(this).parent().attr('id');
            let day = this.id;

            if ($(this).hasClass("changed")) {
                if (day !== "none") {
                    changeWeekdayAttributes($(this), 'remove', 'none', notificationScheduleId, 'red');
                } else {
                    changeWeekdayAttributes($(this), 'add', currentDay, notificationScheduleId, 'limegreen');
                }
            }
            $(this).removeClass("changed");
        });

        $('#submit-weekdays-block' + notificationScheduleId).slideUp('fast');
    });

    function changeWeekdayAttributes(parentClass, type, currentDay, notificationScheduleId, color) {
        parentClass.css('background-color', color);
        parentClass.attr('id', currentDay);
        if (type === "remove") {
            parentClass.parent().removeClass( "selected-weekday" + notificationScheduleId);
        } else {
            parentClass.parent().addClass( "selected-weekday" + notificationScheduleId);
        }
    }

    // END OF SELECT WEEKDAY

    // CHANGE NOTIFICATION URLS

    $('.url-validation').on("change input paste keyup", function () {
        let currentUrlValue = $(this).val();
        let currentBlockId = this.id;
        let submitLinkChange = $('#link-changer-submit');
        let fromConfirmationBlock = $('#url-status-from').html();
        let toConfirmationBlock = $('#url-status-to').html();

        if (validURL(currentUrlValue) === false) {
            $('.link-changer-url-error-'+currentBlockId).slideDown('fast');
            $('#url-status-'+currentBlockId).html('false');
        } else {
            $('.link-changer-url-error-'+currentBlockId).slideUp('fast');
            $('#url-status-'+currentBlockId).html('true');
        }

        if (fromConfirmationBlock === "true" && toConfirmationBlock === "true") {
            submitLinkChange.prop('disabled',false);
        } else {
            submitLinkChange.prop('disabled',true);
        }
    });

    function controlLinkChangerLoader(response, changerTitleBlock, changerLoadingBar, changerFinishedIcon, iconColor) {
        let finalTitleMessage = response.total ? response.total+' notification domains successfully changed to '+response.to+'!' : 'No notifications with such domain found ('+response.from+')';
        changerTitleBlock.slideUp('fast');

        setTimeout(function() {
            changerTitleBlock.html(finalTitleMessage);
            changerLoadingBar.fadeOut('fast');
        },200);

        setTimeout(function () {
            changerFinishedIcon.fadeIn('fast').css({'transform':'scale(1.1)','text-shadow':'0 0 4px '+iconColor});
        },500);

        setTimeout(function() {
            changerFinishedIcon.css({'transform':'scale(1)','text-shadow':'0 0 0px'});
            changerTitleBlock.fadeIn('slow');
        },800);
    }

    $('#link-changer-submit').on("click", function() {
        let linkChangeData = $("#link-changer-form")[0],
            changerLoadingDiv = $('.changer-loading-div'),
            changerTitleBlock = $('.changer-title-text'),
            changerLoadingBar = $('.changer-loading-bar'),
            changerFrom = $("[name='linkFrom']").val(),
            changerTo = $("[name='linkTo']").val();

        changerTitleBlock.html('Changing links from '+changerFrom+' to '+changerTo);
        changerLoadingDiv.fadeIn('fast');

        $.ajax({
            type: "POST",
            url: "/change-links",
            data: new FormData(linkChangeData),
            contentType:false,
            processData:false,
            dataType: "json",
            success: function (response) {
                if (response.result) {
                    controlLinkChangerLoader(response, changerTitleBlock, changerLoadingBar, $('#changer-finished-check'), 'green');

                    setTimeout(function () {
                        location.reload();
                    },2500);
                } else {
                    controlLinkChangerLoader(response, changerTitleBlock, changerLoadingBar, $('#changer-failed-check'), 'red');

                    setTimeout(function() {
                        changerLoadingDiv.fadeOut('fast');

                        setTimeout(function() {
                            $('#changer-failed-check').fadeOut('fast');
                            changerLoadingBar.fadeIn('fast');
                        },200);
                    },3000);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    // END OF CHANGE NOTIFICATION URLS

    // USER SETTING CONTROL

    $('.user-settings-toggle').on("click", function() {
        $('.settings-menu').slideToggle();
    });

    function userControlLoadingActions(statusIcon, currentButton) {
        setTimeout(function() {
            statusIcon.fadeIn('fast');
            setTimeout(function() {
                statusIcon.fadeOut('fast');
                setTimeout(function() {
                    currentButton.prop("readonly", false);
                    currentButton.fadeIn('fast');
                }, 300);
            }, 500);
        }, 700);
    }

    $('#user-page-limit').on("change input paste keyup", function() {
        let currentInput = $(this),
            settingsSubmitButton = $('#settings-submit'),
            pageLimitHelp = $('#user-settings-page-limit-help');

        if (currentInput.val() < 10 || currentInput.val() > 100) {
            pageLimitHelp.slideDown('fast');
            settingsSubmitButton.prop("disabled", true);
            currentInput.css("border","3px solid red");
        } else {
            pageLimitHelp.slideUp('fast');
            settingsSubmitButton.prop("disabled", false);
            currentInput.css("border","1px solid #ced4da");
        }
    });

    $('#settings-submit').on("click", function () {
        let currentButton = $(this),
            pageLimitBlock = $('#user-page-limit'),
            colorBlock = $('#user-color'),
            loadingCog = $('#user-settings-cog'),
            confirmIcon = $('#user-settings-confirm'),
            failedIcon = $('#user-settings-failed');

            currentButton.prop("readonly", true);
            pageLimitBlock.prop("readonly", true);
            colorBlock.prop("readonly", true);

            currentButton.fadeOut('fast');
            setTimeout(function() {
                loadingCog.fadeIn('fast');
            }, 200);

            $.ajax({
                type: "POST",
                url: "/save-user-settings",
                data: {
                    "pageLimit": pageLimitBlock.val(),
                    "color": colorBlock.val()
                },
                dataType: "json",
                success: function(response) {
                    if (response.result) {
                        loadingCog.fadeOut('fast');
                        pageLimitBlock.val(response.pageLimit);
                        colorBlock.val(response.color);
                        userControlLoadingActions(confirmIcon, currentButton);
                    } else {
                        loadingCog.fadeOut('fast');
                        userControlLoadingActions(failedIcon, currentButton);
                    }
                    pageLimitBlock.prop("readonly", false);
                    colorBlock.prop("readonly", false);
                },
                error: function (jqXHR, exception) {
                    location.reload();
                }
            });
    });

    $('#user-settings-generate-invite-code').on("click", function () {
        let currentButton = $(this),
            regularButtonText = $('#settings-generate-text'),
            loadingCog = $('#generate-settings-cog'),
            copyButton = $('#copy-invite-code');

        copyButton.prop("disabled", true);
        currentButton.removeClass("btn-primary").addClass("btn-warning").prop("disabled", true);

        regularButtonText.fadeOut('fast');
        setTimeout(function() {
            loadingCog.fadeIn('slow');
        }, 300);

        $.ajax({
            type: "POST",
            url: "/generate-invite-code",
            dataType: "json",
            success: function(response) {
                if (response.result) {

                    setTimeout(function() {
                        $('#user-invite-code').val(response.generatedCode);
                        currentButton.removeClass("btn-warning").addClass("btn-primary").prop("disabled", false);
                        loadingCog.fadeOut('fast');
                        setTimeout(function() {
                            regularButtonText.fadeIn('slow');
                            copyButton.prop("disabled", false);
                        },300);
                    }, 1000);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $("#copy-invite-code").on("click",function(){
        let currentButton = $(this);

        $("#user-invite-code").select();
        document.execCommand("copy");
        currentButton.removeClass("btn-secondary").addClass("btn-success").html('Copied!');

        setTimeout(function () {
            currentButton.removeClass("btn-success").addClass("btn-secondary").html('Copy');
        }, 1000);
    });

    // END OF USER SETTING CONTROL

    // SERVER SETTINGS CONTROLLING

    $('.manage-server-settings').on("click", function() {
        let settingsButton = $(this),
            serverSettingsBlock = $('.server-setting-block'),
            serverManageCog = $('#server-settings-cog'),
            settingsManageText = $('#server-settings-manage-text');

        settingsManageText.fadeOut('fast');
        settingsButton.prop("disabled", true);
        serverManageCog.fadeIn('slow');

        $.ajax({
            type: "POST",
            url: "/get-server-settings",
            dataType: "json",
            success: function(response) {
                if (response) {
                    settingsButton.slideUp('fast');
                    $('.server-settings-domain-input').val(response.domain);
                    $('#current-domain-value').html(response.domain);
                    serverSettingsBlock.slideDown('fast');
                } else {
                    settingsButton.removeClass("btn-outline-primary").addClass("btn-danger");
                    settingsManageText.html('Failed!');
                    serverManageCog.fadeOut('fast');
                    settingsManageText.fadeIn('slow');

                    setTimeout(function() {
                        settingsManageText.html('Manage server settings');
                        settingsButton.removeClass("btn-danger").addClass("btn-outline-primary");
                        settingsButton.prop("disabled", false);
                    }, 1200);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $('.settings-domain-validation').on("change input paste keyup", function () {
        let currentUrlValue = $(this).val(),
            submitButton = $('#update-server-settings'),
            currentDomainValue = $('#current-domain-value'),
            informationBlock = $('#server-setting-information');

        if (validURL(currentUrlValue) === false) {
            informationBlock.html('Invalid url!').slideDown('fast');
            submitButton.prop("disabled", true);
        } else {
            if (currentUrlValue !== currentDomainValue.html()) {
                informationBlock.slideUp('fast');
                submitButton.prop("disabled", false);
            } else {
                informationBlock.html('Can\'t change to the same domain').slideDown('fast');
                submitButton.prop("disabled", true);
            }
        }
    });

    $('#update-server-settings').on("click", function() {
        let currentButton = $(this),
            serverSettingsDomain = $("#settings-server-domain"),
            submitButtonText = $('#server-settings-submit-text'),
            loadingCog = $('#server-settings-submit-cog'),
            currentDomainValue = $('#current-domain-value');

        currentButton.prop("readonly", true);
        serverSettingsDomain.prop("readonly", true);

        submitButtonText.fadeOut('fast');
        loadingCog.fadeIn('slow');

        $.ajax({
            type: "POST",
            url: "/save-server-settings",
            data: {
                "domain": serverSettingsDomain.val()
            },
            dataType: "json",
            success: function(response) {
                if (response) {
                    serverSettingsDomain.val(response.domain);
                    currentDomainValue.html(response.domain);
                    submitButtonText.html('Updated!');
                    loadingCog.slideUp('fast');

                    setTimeout(function() {
                        submitButtonText.slideDown('slow');
                        setTimeout(function() {
                            currentButton.prop("readonly", false).prop("disabled", true);
                            serverSettingsDomain.prop("readonly", false);
                            submitButtonText.html('Update');
                        },1500);
                    },500);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    // END OF SERVER SETTINGS

    // STATISTICS PANEL

    function controlNotificationElementsByPage(pageLocation, campaignNotificationBlock, notificationIdBlock, currentButton, action) {
        let currentUserColor = $('#current-user-color');

        if (pageLocation.html() === "campaigns") {
            if (action === "show") {
                campaignNotificationBlock.css("border","solid 4px blue");
            } else if (action === "hide") {
                currentButton.prop("disabled", false);
                campaignNotificationBlock.css("border","solid 1px black");
            }
        } else {
            if (action === "show") {
                notificationIdBlock.css("color","blue").css("font-size","20px");
            } else if (action === "hide") {
                currentButton.prop("disabled", false);
                notificationIdBlock.css("color",currentUserColor.html()).css("font-size","16px");
            }
        }
    }

    function fetchNotificationStatistics(notificationId, loadingCog, statPanelInside, currentButton) {
        $.ajax({
            type: "POST",
            url: "/get-notification-statistics",
            data: {
                "id": notificationId
            },
            dataType: "json",
            success: function(response) {
                if (response) {
                    let firstCheckReceivers = response.statistics[0].firstCheckReceivers,
                        firstCheckConversions = response.statistics[0].firstCheckConversions,
                        lastCheckReceivers = response.statistics[0].lastCheckReceivers,
                        lastCheckConversions = response.statistics[0].lastCheckConversions,
                        totalReceivers = response.statistics[0].totalReceivers,
                        totalConversions = response.statistics[0].totalConversions,
                        lastCheckDate = response.statistics[0].lastCheckDate.date;

                    let lastSentDate = (response.lastSent) ? response.lastSent.date : false,
                        firstCheckCtr = firstCheckConversions/firstCheckReceivers,
                        lastCheckCtr = lastCheckConversions/lastCheckReceivers,
                        totalCtr = totalConversions/totalReceivers;

                    $('#first-check-receivers').html(firstCheckReceivers);
                    $('#first-check-conversions').html(firstCheckConversions);
                    $('#first-check-ctr').html(firstCheckCtr.toFixed(4));

                    $('#last-check-receivers').html(lastCheckReceivers);
                    $('#last-check-conversions').html(lastCheckConversions);
                    $('#last-check-ctr').html(lastCheckCtr.toFixed(4));

                    $('#total-receivers').html(totalReceivers).css("font-weight","500");
                    $('#total-conversions').html(totalConversions).css("font-weight","500");
                    $('#total-ctr').html(totalCtr.toFixed(4)).css("font-weight","500");

                    let checkDateLabel = $('#statistics-check-date-label'),
                        lastSentDiv = $('#notification-statistics-last-sent-div');

                    if (response.statistics[0].checkCount < 5) {
                        checkDateLabel.html('Last check date:').css("color","black");
                    } else {
                        checkDateLabel.html('Final check date:').css("color","red");
                    }
                    $('#last-check-date').html(lastCheckDate.split('.')[0]);

                    if (lastSentDate) {
                        $('#notification-last-sent').html(lastSentDate.split('.')[0]);
                        lastSentDiv.show();
                    } else {
                        lastSentDiv.hide();
                    }

                    loadingCog.fadeOut('fast');
                    setTimeout(function() {
                        statPanelInside.fadeIn('fast');
                        currentButton.prop("disabled",false);
                    }, 500);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    }

    $('.show-notification-stats').on("click", function () {
        let notificationId = this.id,
            currentButton = $('.show-notification-stats'),
            statPanel = $(".stat-panel"),
            currentNotificationId = $('#current-notification-id'),
            loadingCog = $('#notification-statistics-cog'),
            statPanelInside = $('.stat-panel-inside'),
            pageLocation = $('#current-statistics-page-location'),
            campaignNotificationBlock = $("#campaign-notification-block-"+notificationId),
            notificationIdBlock = $('#main-notification-id-'+notificationId);

        currentButton.prop("disabled", true);
        if ($(this).hasClass("campaign-stat-button")) {
            pageLocation.html("campaigns");
        } else {
            pageLocation.html("notifications");
        }

        if (statPanel.hasClass('show')) {
            if (currentNotificationId.html() === notificationId) {
                statPanel.animate({ left:'100%'}).removeClass('show');
                controlNotificationElementsByPage(pageLocation, campaignNotificationBlock, notificationIdBlock, currentButton, "hide");

                currentNotificationId.html("");
                statPanelInside.fadeOut('fast');
            } else {
                currentNotificationId.html();

                if (pageLocation.html() === "campaigns") {
                    $("#campaign-notification-block-"+currentNotificationId.html()).css("border","solid 1px black");
                } else {
                    $("#main-notification-id-"+currentNotificationId.html()).css("border","solid 1px black");
                }

                currentNotificationId.html(notificationId);
                statPanelInside.fadeOut('fast');

                setTimeout(function() {
                    loadingCog.fadeIn('fast');
                    controlNotificationElementsByPage(pageLocation, campaignNotificationBlock, notificationIdBlock, currentButton,"show");
                    fetchNotificationStatistics(notificationId, loadingCog, statPanelInside, currentButton);
                },500);
            }
        } else {
            if (currentNotificationId.html() === notificationId) {
                statPanel.animate({ left:'100%'}).removeClass('show');
                controlNotificationElementsByPage(pageLocation, campaignNotificationBlock, notificationIdBlock, currentButton,"hide");

                currentNotificationId.html("");
                statPanelInside.fadeOut('fast');
            } else {
                let windowWidth = $(window).width(),
                    percent = "";

                if (windowWidth > 1800) {
                    percent = "82";
                } else if (windowWidth > 1600 && windowWidth < 1800) {
                    percent = "82";
                } else if (windowWidth > 1500 && windowWidth < 1600) {
                    percent = "80";
                } else if (windowWidth > 1400 && windowWidth < 1500) {
                    percent = "78";
                } else {
                    percent = "75";
                }

                currentNotificationId.html(notificationId);
                controlNotificationElementsByPage(pageLocation, campaignNotificationBlock, notificationIdBlock, currentButton,"show");

                statPanel.animate({ left: percent+'%'}).addClass('show');
                loadingCog.fadeIn('fast');
                fetchNotificationStatistics(notificationId, loadingCog, statPanelInside, currentButton);
            }
        }
    });

    $(".close-statistics-panel").on("click", function() {
        let notificationIdBlock = $('#current-notification-id');

        $(".stat-panel").animate({ left:'100%'}).removeClass('show');
        controlNotificationElementsByPage($('#current-statistics-page-location'), $("#campaign-notification-block-"+notificationIdBlock.html()), $('#main-notification-id-'+notificationIdBlock.html()), $('.show-notification-stats'), "hide");
        notificationIdBlock.html("");
        $('.stat-panel-inside').fadeOut('fast');
    });

    // END OF STATISTICS PANEL

    // CONTROL PANEL

    function controlPanelApiKeyErrors(submitButton, errorMessageText, errorBlock, errorMessage) {
        submitButton.prop("disabled",true);
        errorMessageText.html(errorMessage);
        errorBlock.slideDown('fast');
    }

    $("#control-panel-account-key").on("change paste keyup", function() {
        let currentApiKey = $('#current-account-api-key').html(),
            currentInput = $(this).val(),
            errorBlock = $('#control-panel-api-key-error'),
            errorMessageText = $('#control-panel-error-message'),
            submitButton = $('#control-panel-submit-os-api-key');

        if (currentInput.length === 48) {
            if (currentInput !== currentApiKey) {
                errorBlock.slideUp('fast');
                submitButton.prop("disabled",false);
            } else {
                controlPanelApiKeyErrors(submitButton, errorMessageText, errorBlock, "Inserted Api Key is the same as the current one");
            }
        } else if (currentInput.length > 48) {
            controlPanelApiKeyErrors(submitButton, errorMessageText, errorBlock, "Api Key is too long (standard is 48 symbols)");
        } else {
            controlPanelApiKeyErrors(submitButton, errorMessageText, errorBlock, "Api Key is too short (standard is 48 symbols)");
        }
    });

    function saveApiKeyChanges(newApiKey, action, loadingText, loadingBar, successIcon) {
        $.ajax({
            type: "POST",
            url: "/save-api-key",
            data: {
                "apiKey": newApiKey,
                "action": action
            },
            dataType: "json",
            success: function (response) {
                if (response) {
                    let loadingTextInside = (response.appCount) ? 'Set up '+response.appCount+' apps!' : 'Finished!';

                    setTimeout(function() {
                        loadingText.html(loadingTextInside);
                        loadingBar.slideUp('fast');
                        successIcon.slideDown('fast');

                        setTimeout(function() {
                            location.reload();
                        }, 500);
                    }, 500);
                }
            }
        });
    }

    $('#control-panel-submit-os-api-key').on("click", function() {
        let insertedApiKey = $("#control-panel-account-key").val(),
            loadingModal = $('#control-panel-loading-modal'),
            loadingText = $('#control-panel-loading-text'),
            loadingBar = $('#control-panel-loading-bar'),
            successIcon = $('#control-panel-success'),
            failedIcon = $('#control-panel-failed');

        loadingModal.fadeIn('fast');

        $.ajax({
            type: "POST",
            url: "/test-api-key",
            data : {
                "apiKey": insertedApiKey
            },
            dataType: "json",
            success: function(response) {
                if (response.result) {
                    loadingText.html('Api key verified!');
                    loadingBar.slideUp('fast');
                    successIcon.slideDown('fast');

                    if (response.status === "setup") {
                        setTimeout(function() {
                            loadingText.html('Setting up apps..');
                            successIcon.slideUp('fast');
                            loadingBar.slideDown('fast');

                            saveApiKeyChanges(insertedApiKey, response.status, loadingText, loadingBar, successIcon);
                        }, 2200);
                    } else {
                        setTimeout(function() {
                            loadingText.html('0 apps found, skipping setup..');
                            successIcon.slideUp('fast');
                            loadingBar.slideDown('fast');

                            saveApiKeyChanges(insertedApiKey, response.status, loadingText, loadingBar, successIcon);
                        }, 2200);
                    }
                } else {
                    loadingBar.slideUp('fast');
                    loadingText.html('Api key is invalid!');
                    failedIcon.slideDown('fast');

                    setTimeout(function() {
                        loadingModal.fadeOut('fast');
                        setTimeout(function() {
                            loadingText.html('Verifying api key..');
                            failedIcon.hide();
                            loadingBar.show();
                        }, 500);
                    }, 1000);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    });

    $('#cp-switch-type').on("click", function() {
        let imageWrapper = $('#cp-image-wrapper'),
            iconWrapper = $('#cp-icon-wrapper'),
            pictureManagingTitle = $('#picture-managing-title'),
            uploadPictureText = $('#cp-upload-picture-text'),
            uploadPictureButton = $('.uploadNotificationImage');

        if (imageWrapper.is(":visible")) {
            uploadPictureText.html('Upload icons: ');
            uploadPictureButton.attr("id","icon");
            pictureManagingTitle.html('Viewing icons');
            imageWrapper.slideUp('fast');
            iconWrapper.slideDown('fast');
        } else {
            uploadPictureText.html('Upload images: ');
            uploadPictureButton.attr("id","image");
            pictureManagingTitle.html('Viewing images');
            iconWrapper.slideUp('fast');
            imageWrapper.slideDown('fast');
        }
    });

    let imageDeleteArray = [];
    let iconDeleteArray = [];

    function removeIdsFromSelectedArray(deleteArray, pictureId) {
        for (let i=deleteArray.length-1; i>=0; i--) {
            if (deleteArray[i] === pictureId) {
                deleteArray.splice(i, 1);
            }
        }
    }

    let pictureId,
        pictureStatus,
        pictureBlock,
        pictureType;

    function setPictureValues(type, currentId, picBlock, picStatus) {
        pictureType = type;
        pictureId = currentId.replace(type+'-','');
        pictureBlock = $(picBlock+pictureId);
        pictureStatus = $(picStatus+pictureId);
    }

    $('.cp-delete-picture').on("click", function() {
        let currentDeleteButton = $(this),
            currentId = this.id,
            selectedImageText = $('#cp-selected-image-text'),
            selectedIconText = $('#cp-selected-icon-text');

        if (~currentId.indexOf("image-")) {
            setPictureValues("image", currentId, "#cp-image-block-","#cp-image-status-");
        } else {
            setPictureValues("icon", currentId, "#cp-icon-block-","#cp-icon-status-");
        }

        if (pictureStatus.html() === "false") {
            if (pictureType === "image") {
                imageDeleteArray.push(pictureId);
            } else {
                iconDeleteArray.push(pictureId);
            }

            pictureStatus.html("true");
            currentDeleteButton.css("color","#00FF00");
            pictureBlock.css("border","solid 3px red").css("border-radius","15px");
        } else {
            pictureStatus.html("false");
            currentDeleteButton.css("color","red");
            pictureBlock.css("border","none");

            if (pictureType === "image") {
                removeIdsFromSelectedArray(imageDeleteArray, pictureId);
            } else {
                removeIdsFromSelectedArray(iconDeleteArray, pictureId);
            }
        }

        if (imageDeleteArray.length) {
            selectedImageText.html("Selected images - "+imageDeleteArray.length);
            selectedImageText.slideDown('fast');
        } else {
            selectedImageText.slideUp('fast');
        }

        if (iconDeleteArray.length) {
            selectedIconText.html("Selected icons - "+iconDeleteArray.length);
            selectedIconText.slideDown('fast');
        } else {
            selectedIconText.slideUp('fast');
        }

        let deleteButton = $("#cp-delete-pictures");
        if (imageDeleteArray.length || iconDeleteArray.length) {
            deleteButton.slideDown('fast');
        } else {
            deleteButton.slideUp('up');
        }
    });

    function deletePicturesAjax () {
        let loadingText = $('#control-panel-loading-text'),
            loadingBar = $('#control-panel-loading-bar'),
            successIcon = $('#control-panel-success'),
            failedIcon = $('#control-panel-failed'),
            finalText;

        $.ajax({
            type: "POST",
            url: "/delete-pictures",
            data: {
                "images": imageDeleteArray,
                "icons" : iconDeleteArray
            },
            dataType: "json",
            success: function (response) {
                if (response) {
                    if (!response.removedImages && !response.removedIcons) {
                        loadingBar.slideUp('fast');
                        loadingText.html("Deletion failed!");
                        failedIcon.slideDown('fast');
                    } else {
                        if (response.removedImages && response.removedIcons) {
                            finalText = "Removed " + response.removedImages + " images and " + response.removedIcons + " icons!";
                        }

                        if (response.removedImages && !response.removedIcons) {
                            finalText = "Removed " + response.removedImages + " images!";
                        }

                        if (!response.removedImages && response.removedIcons) {
                            finalText = "Removed " + response.removedIcons + " icons!";
                        }

                        loadingBar.slideUp('fast');
                        loadingText.html(finalText);
                        successIcon.slideDown('fast');
                    }

                    setTimeout(function() {
                        location.reload();
                    },1500);
                }
            },
            error: function (jqXHR, exception) {
                location.reload();
            }
        });
    }

    $('#cp-delete-pictures').on("click", function() {
        $('#control-panel-loading-bar').hide();
        $('#control-panel-loading-text').html("This action will remove these pictures from existing notifications. Are you sure?");
        $('.cp-confirm-buttons').show();
        $('#control-panel-loading-modal').fadeIn('fast');
    });

    $('.cp-confirm-button').on("click", function() {
        let userClick = this.id,
            confirmButtons = $('.cp-confirm-buttons'),
            loadingText = $('#control-panel-loading-text'),
            loadingBar = $('#control-panel-loading-bar');

        if (userClick === "cp-confirm") {
            confirmButtons.slideUp('fast');
            loadingText.html("Deleting pictures..");
            loadingBar.slideDown('fast');
            deletePicturesAjax();
        } else {
            $('#control-panel-loading-modal').fadeOut('fast');
            setTimeout(function() {
                loadingText.html("Verifying api key..");
                confirmButtons.hide();
            },500);
        }
    });

    // END OF CONTROL PANEL

});