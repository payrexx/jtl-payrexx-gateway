<script>
    const createPopup = (platformUrl) => {
        const popupWidth = 900;
        const popupHeight = 900;

        // Get the parent window's size and position
        const dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
        const dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;

        const width = window.innerWidth
            ? window.innerWidth
            : document.documentElement.clientWidth
                ? document.documentElement.clientWidth
                : screen.width;

        const height = window.innerHeight
            ? window.innerHeight
            : document.documentElement.clientHeight
                ? document.documentElement.clientHeight
                : screen.height;

        const left = dualScreenLeft + (width - popupWidth) / 2;
        const top = dualScreenTop + (height - popupHeight) / 2;

        const params = "width=" + popupWidth + ",height=" + popupHeight + ",top=" + top + ",left=" + left + ",resizable=no,scrollbars=yes"

        const url = 'https://login.' + platformUrl + '?action=connect&plugin_id=32';
        return window.open(
            url,
            'Connect Payrexx',
            params
        );
    }

    const handleValidateSignature = () => {
        const data = {
            test: 'test'
        }
        request('{$postValidateCredentialsUrl}', data, (result) => {
            $('#successValidation').removeClass('d-none');
        }, (error) => {
            $('#errorValidation').removeClass('d-none');
        })
    }

    let popup;
    const handleConnectPayrexx = (event) => {
        const platformUrl = $('[name="payrexx_platform"]').val();
        if (!popup) {
            popup = createPopup(platformUrl);

            // handle closing of popup without message from window
            let popupCheck;
            popupCheck = setInterval(() => {
                if (popup.closed) {
                    popup = null;
                    clearInterval(popupCheck);
                }
            }, 750)
        }
    }

    const request = (endpoint, data, successCallback, errorCallback) => {
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    successCallback(result);
                } else {
                    errorCallback(result);
                }
            })
            .catch(error => {
                errorCallback(error);
            });
    }

    $(document).ready(() => {
        $('#validateButton').on('click', handleValidateSignature);
        $('#connectPayrexxButton').on('click', handleConnectPayrexx);

        window.addEventListener('message', function (event) {
            popup = null;
            if (!event.data || !event.data.instance) {
                return;
            }

            const data = {
                payrexx_instance: event.data.instance.name,
                payrexx_api_key: event.data.instance.apikey,
            };

            request('{$postConnectUrl}', data, (result) => {
                // set values in overview
                $('#resultApiKey').text(event.data.instance.apikey);
                $('#resultInstanceName').text(event.data.instance.name);
                // set values in configurations tab
                $('#payrexx_api_key').val(event.data.instance.apikey);
                $('#payrexx_instance').val(event.data.instance.name);
            }, (error) => {
                console.error(error)
                $('#errorValidation').removeClass('d-none');
            });
        })
    })

    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
</script>
<div>
    <div class="navbar-form">
        <div class="settings-content mb-4 text-center">
            <div id="successValidation" class="alert alert-success d-none" role="alert">
                {$languageTexts.jtl_payrexx_signature_check_success}
            </div>
            <div id="errorValidation" class="alert alert-danger d-none" role="alert">
                {$languageTexts.jtl_payrexx_signature_check_fail}
            </div>
            <hr>
        </div>

        <div class="table-responsive mb-4" style="max-width: 600px; margin: 0 auto;">
            <table class="table table-bordered">
                <tbody>
                <tr>
                    <th scope="row">API Key</th>
                    <td id="resultApiKey" class="text-break" style="word-wrap: break-word;">
                        {$payrexx_api_key|default:$languageTexts.jtl_payrexx_no_instance|escape}
                    </td>
                </tr>
                <tr>
                    <th scope="row">Instance</th>
                    <td id="resultInstanceName" class="text-break" style="word-wrap: break-word;">
                        {$payrexx_instance|default:$languageTexts.jtl_payrexx_no_instance|escape}
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <hr>
    <div class="form-group d-flex justify-content-center">
        <button name="validate" type="button" id="validateButton" class="btn btn-primary mr-3 px-4"
                data-toggle="tooltip" data-placement="top"
                title="{$languageTexts.jtl_signature_check_submit_tooltip}">
            {$languageTexts.jtl_signature_check_submit}
        </button>
        <button name="speichern" type="button" id="connectPayrexxButton" class="btn btn-secondary px-4"
                data-toggle="tooltip" data-placement="top"
                title="{$languageTexts.jtl_payrexx_connect_new_integration_tooltip}">
            {$languageTexts.jtl_payrexx_connect_new_integration}
        </button>
    </div>
</div>
