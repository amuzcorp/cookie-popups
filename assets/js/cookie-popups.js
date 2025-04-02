(function($) {
    'use strict';

    class CookiePopup {
        constructor() {
            this.popup = null;
            this.init();
        }

        init() {
            if (cookiePopupsData.debug) {
                console.log('CookiePopup initialized');
                console.log('Nonce:', cookiePopupsData.nonce);
                console.log('Ajax URL:', cookiePopupsData.ajaxurl);
            }
            this.checkAndShowPopup();
        }

        checkAndShowPopup() {
            if (cookiePopupsData.debug) {
                console.log('Checking for active popup...');
            }

            const ajaxData = {
                action: 'cookie_popups_get_active_popup',
                nonce: cookiePopupsData.nonce
            };

            if (cookiePopupsData.debug) {
                console.log('Sending AJAX request with data:', ajaxData);
            }

            $.ajax({
                url: cookiePopupsData.ajaxurl,
                type: 'POST',
                data: ajaxData,
                beforeSend: function() {
                    if (cookiePopupsData.debug) {
                        console.log('AJAX request starting...');
                    }
                },
                success: (response) => {
                    if (cookiePopupsData.debug) {
                        console.log('AJAX Response:', response);
                    }
                    if (response.success && response.data && response.data.popup) {
                        this.showPopup(response.data.popup);
                    } else {
                        if (cookiePopupsData.debug) {
                            console.log('No active popup to show');
                        }
                    }
                },
                error: (xhr, status, error) => {
                    if (cookiePopupsData.debug) {
                        console.error('AJAX Error:', {xhr, status, error});
                        console.error('Response Text:', xhr.responseText);
                    }
                }
            });
        }

        showPopup(popupData) {
            if (cookiePopupsData.debug) {
                console.log('Showing popup:', popupData);
            }

            // 이미 팝업이 있다면 제거
            if (this.popup) {
                this.popup.remove();
            }

            const popupHtml = `
                <div class="cookie-popup" data-popup-id="${popupData.id}">
                    <div class="cookie-popup-content">
                        <h2>${popupData.title}</h2>
                        <div class="cookie-popup-body">${popupData.content}</div>
                        <div class="cookie-popup-buttons">
                            <button class="cookie-popup-close">닫기</button>
                            <button class="cookie-popup-dismiss" data-days="${popupData.reappear_days}">
                                ${popupData.reappear_days}일 동안 보지 않기
                            </button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(popupHtml);
            this.popup = $('.cookie-popup');
            this.bindEvents();
        }

        bindEvents() {
            this.popup.find('.cookie-popup-close').on('click', () => {
                this.closePopup();
            });

            this.popup.find('.cookie-popup-dismiss').on('click', (e) => {
                const days = $(e.currentTarget).data('days');
                this.dismissPopup(days);
            });
        }

        closePopup() {
            if (cookiePopupsData.debug) {
                console.log('Closing popup');
            }
            this.popup.fadeOut(300, () => {
                this.popup.remove();
                this.popup = null;
            });
        }

        dismissPopup(days) {
            const popupId = this.popup.data('popup-id');
            
            if (cookiePopupsData.debug) {
                console.log('Dismissing popup:', {popupId, days});
            }

            $.ajax({
                url: cookiePopupsData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cookie_popups_dismiss',
                    nonce: cookiePopupsData.nonce,
                    post_id: popupId,
                    days: days
                },
                success: () => {
                    this.closePopup();
                },
                error: (xhr, status, error) => {
                    if (cookiePopupsData.debug) {
                        console.error('Dismiss AJAX Error:', {xhr, status, error});
                    }
                }
            });
        }
    }

    $(document).ready(() => {
        if (cookiePopupsData.debug) {
            console.log('Document ready, initializing CookiePopup');
            console.log('jQuery version:', $.fn.jquery);
        }
        new CookiePopup();
    });
})(jQuery); 