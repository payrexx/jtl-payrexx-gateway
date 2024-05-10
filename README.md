<h1 align="center">Payrexx Payment for JTL Shop5</h1>

A Payrexx plugin to accept payments in the JTL Shop.

## Support
This module supports JTL Shop versions 5.0 - 5.3.*
Note: It may work on future JTL Shop releases, but performance cannot be guaranteed.

## Installation
**Please install it manually**
### Manual Installation
1. Download the latest version of the module (the '.zip' file)
2. Uncompress the zip file you download and rename it to jtl_payrexx.
3. Include it in your JTL shop /plugins folder.
4. Login to JTL 5 shop backend > Plug-in manager, select the plugin and click Install
5. After installation click on the Settings "gear" icon
6. Enter the correct data from Payrexx and click Save.

### Installation via Shop Backend
1. Download the latest version of the module (the '.zip' file)
2. Go to the administration panel of your JTL Shop
3. In your administration panel, select the 'Plugins' menu and then choose 'Plug-in Manager'
4. Click the 'Upload' tab, select 'Choose', and then upload the '.zip' file that you downloaded earlier
5. After the module has been uploaded, go to the 'Available' tag, activate the checkbox for 'jtl_payrexx', and press 'Install'
6. Switching back to the 'Active' tab 
7. After installation click on the Settings "gear" icon
8. Enter the correct data from Payrexx and click Save.

## Payrexx Configuration
 1. To Configure the webhook URL in Payrexx, Log in to your Payrexx account.
 2. Go to settings -> API --> Find Webhook URL
 3. Insert the URL to your shop and add /payrexx-webhook (e.g. If your shop URL is http://www.example.com, the Webhook URL will be http://www.example.com/payrexx-webhook)
