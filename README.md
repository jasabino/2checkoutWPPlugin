# WordPress Plugin for 2checkout paymnets in woocomerce

This is a wordPress Plugin for woocommerce that allow to do payments with credit card through 2checkout API without take the user to additional pages

For more information please check the documentation on:

 1. [WordPress](https://wordpress.com)
 2. [Woocommerce](https://woocommerce.com/)
 3. [2Checkout](https://www.2checkout.com/)

## Getting Started

These instructions will get you a copy of the project up and run on your local machine for development and testing purposes.

1. Download a GIT client as [TortoiseGIT](https://tortoisegit.org/)
2. Clone the repository from [SSH](git@gitlab.com:jasabino/2checkoutWPPlugin.git) or [HTTPS](https://gitlab.com/jasabino/2checkoutWPPlugin.git)

### Prerequisites

You need these programs in order to run this project:

1. XAMPP (or any PHP environment) [(Download)](https://www.apachefriends.org/es/index.html)
2. Install WordPress [(Download)](https://wordpress.org/download/)
3. Install Woocommerce [(Download)](https://es.wordpress.org/plugins/woocommerce/)

## Installing the Plugin

1. Copy the root folder of the project inside of the folder `wp-content/plugins` of your installation of wordpress
2. Go to administration panel of WordPress and click on Plugins
3. Looking for the plugin `Paymment module for 2CheckOut` and click on install and activate

**Note:** by the default this project is using an sample credientials of 2checkout, please create an account and change the credentials 

For change the credentials of 2checkout, please follow the next instructions:

1. Create an acount in [(Sandbox of 2checkout)](https://sandbox.2checkout.com/sandbox)
2. Inside of Plugins, click on `Settings` link of this plugin
3. Copy values of `Publishable Key`,`Private Key` and `Account Number` (Seller Id) from the Sandbox of 2CheckOut
4. Save

## Project Structure

It contains two files and one lib folder:

1. 2checkout.php
> Is the plugin, it contains a class that extends of WC_Payment_Gateway and override all methods necessaries for call the API of 2checkout and doing the payment

2. jquery.payment.php
> this is the jquery library for changing the validations in the form of paymments

## Built With

* [PHP](http://php.net/) - The Programming Language
* [WordPress](https://wordpress.com) - The framework
* [2checkout](https://www.2checkout.com/) - The API for payments