# PayDollar/SaimPay/PesoPay Payment plugin for Magento 2.X
Use PayDollar/SaimPay/PesoPays plugin for Magento 2.X to offer ALL payments option.

## Download
Please download the latest plugin version. [Download](https://github.com/asiapay-lib/asiapay-Magento-2.x/releases/latest)

## Integration
The plugin integrates Magento 2.X with PayDollar/SaimPay/PesoPay payment gateway with All payment method.

## Requirements
This plugin supports Magento version 2.0 and higher.

## Installation
1.	Upload the paydollar plugin to the Magento correct folder structure using an FTP client.
2.	After  installation,  go  to  the  admin  side  of  the  store  and  log-in,  then  go  to  ***Store -> Configurations -> Advanced -> Advanced***
3.	Ensure that the module **'Asiapay_Pdcptb'** is enabled.
4.	Then go to ***Store -> Configurations -> Sales -> "Payment Methods"*** then expand the **“AsiaPay's PayDollar - Client Post Through Browser Module”** then configure the values accordingly then save changes. 
5.	Set the module configurations

## Setup the Datafeed URL on PayDollar/PesoPay/SiamPay
 1. Login to your PayDollar/PesoPay/SiamPay account.
 2. After login, Go to left sidebar on Profile > Profile Settings > Payment Options.
 3. Click the “Enable” radio and set the datafeed URL on “Return Value Link” and click the “Update” button. The datafeed URL should be like this: http://www.yourmagentosite.com/pdcptb/datafeed/datafeed
 4. On the confirmation page, review your changes then click the “Confirm button”.

 ## Documentation
[Magento documentation](https://github.com/asiapay-lib/asiapay-Magento-2.x/raw/master/Magento2.0%20Payment%20Module%20Setup%20Guide.pdf)

## Support
If you have a feature request, or spotted a bug or a technical problem, create a GitHub issue. For other questions, contact our [Customer Service](https://www.paydollar.com/en/contactus.html).

## License
MIT license. For more information, see the LICENSE file.
