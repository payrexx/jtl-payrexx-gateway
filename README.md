<h1 align="center">Payrexx Payment for JTL Shop5</h1>

A Payrexx plugin to accept payments in the JTL Shop.

## Support
- Module versions **1.1.0 and newer** support **JTL-Shop 5.2.3 – 5.6**
- Module versions **up to 1.0.20** support **JTL-Shop 5.0 – 5.4**

Note: It may work on future JTL Shop releases, but performance cannot be guaranteed.

## Installation
**Please install it manually**
### Manual Installation
1. Download the latest version of the module (the '.zip' file)
2. Uncompress the zip file.
3. Include **jtl_payrexx** folder in your JTL shop /plugins folder.
4. Login to JTL 5 shop backend > Plug-in manager, select the plugin and click Install
5. After installation click on the Settings "gear" icon
6. Enter the correct data from Payrexx and click Save.

### Installation via Shop Backend
1. Download the latest version of the module (the '.zip' file)
2. Uncompress the zip file and compress the zip for **jtl_payrexx**
3. Go to the administration panel of your JTL Shop
4. In your administration panel, select the 'Plugins' menu and then choose 'Plug-in Manager'
5. Click the 'Upload' tab, select 'Choose', and then upload the 'jtl_payrexx.zip' file.
6. After the module has been uploaded, go to the 'Available' tag, activate the checkbox for 'jtl_payrexx', and press 'Install'
7. Switching back to the 'Active' tab 
8. After installation click on the Settings "gear" icon
9. Enter the correct data from Payrexx and click Save.

## Payrexx Configuration
 1. To Configure the webhook URL in Payrexx, Log in to your Payrexx account.
 2. Go to Webhooks -> Add webhook
 3. Insert the URL to your shop and add /payrexx-webhook (e.g. If your shop URL is http://www.example.com, the Webhook URL will be http://www.example.com/payrexx-webhook)
